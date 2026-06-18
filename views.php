<?php

/**
 * @package    Grav\Plugin\Views
 *
 * @copyright  Copyright (C) 2014 - 2019 Trilby Media, LLC. All rights reserved.
 * @license    MIT License; see LICENSE file for details.
 */

namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Config\Config;
use Grav\Common\Plugin;
use Grav\Common\Uri;
use Grav\Plugin\Views\Api\ViewsReportController;
use Grav\Plugin\Views\Views;
use RocketTheme\Toolbox\Event\Event;
use Twig\TwigFunction;

/**
 * Class ViewsPlugin
 * @package Grav\Plugin
 */
class ViewsPlugin extends Plugin
{
    /**
     * @return array
     *
     * The getSubscribedEvents() gives the core a list of events
     *     that the plugin wants to listen to. The key of each
     *     array section is the event that the plugin listens to
     *     and the value (in the form of an array) contains the
     *     callable (or function) as well as the priority. The
     *     higher the number the higher the priority.
     */
    public static function getSubscribedEvents()
    {
        return [
            'onCliInitialize' => [
                ['autoload', 100000],
                ['register', 1000]
            ],
            'onPluginsInitialized' => [
                ['autoload', 100000],
                ['register', 1000],
                ['onPluginsInitialized', 1000]
            ],
            'onApiRegisterRoutes' => [
                ['onApiRegisterRoutes', 0]
            ]
        ];
    }

    /**
     * [onApiRegisterRoutes] Register the lightweight endpoint that backs the
     * Admin2 dashboard widget, so it doesn't have to hit the heavyweight
     * /reports endpoint. Only fires when the API plugin is present.
     */
    public function onApiRegisterRoutes(Event $event)
    {
        $event['routes']->get('/views/top-pages', [ViewsReportController::class, 'topPages']);
    }

    /**
     * [onPluginsInitialized:100000] Composer autoload.
     *
     * @return ClassLoader
     */
    public function autoload()
    {
        return require __DIR__ . '/vendor/autoload.php';
    }

    /**
     * Register the service
     */
    public function register()
    {
        $this->grav['views'] = function ($c) {
            /** @var Config $config */
            $config = $c['config'];

            return new Views($config->get('plugins.views'));
        };
    }

    /**
     * Initialize the plugin
     */
    public function onPluginsInitialized()
    {
        $events = [];
        if ($this->grav['config']->get('plugins.views.admin2.reports', true)) {
            $events['onApiGenerateReports'] = [
                ['onApiGenerateReports', 10]
            ];
        }
        if ($this->grav['config']->get('plugins.views.admin2.dashboard', true)) {
            $events['onApiDashboardWidgets'] = [
                ['onApiDashboardWidgets', 10]
            ];
        }
        if ($events) {
            $this->enable($events);
        }

        if ($this->isAdmin()) {
            $this->enable([
                'onAdminGenerateReports' => [
                    ['onAdminGenerateReports', 10]
                ],
                'onTwigTemplatePaths' => [
                    ['onTwigTemplatePaths', 0]
                ],
            ]);
            return;
        }

        $this->enable([
            'onTwigInitialized' => [
                ['onTwigInitialized', 0]
            ],
        ]);

        if ($this->grav['config']->get('plugins.views.autotrack')) {
            $this->enable([
                'onPageInitialized' => [
                    ['onPageInitialized', 0]
                ],
            ]);
        }
    }

    public function onAdminGenerateReports(Event $e)
    {
        $reports = $e['reports'];

        /** @var Uri $uri */
        $uri = $this->grav['uri'];

        $data = [
            'views' => $this->grav['views']->getAll(null, 20, 'desc'),
            'base_url' => $baseUrlRelative = $uri->rootUrl(false),
        ];

        $reports['Grav Views'] = $this->grav['twig']->processTemplate('reports/views-report.html.twig', $data);
    }

    public function onApiGenerateReports(Event $e)
    {
        $items = $this->grav['views']->getAll(null, 20, 'desc');
        $total = 0;
        foreach ($items as $item) {
            $total += (int) ($item['count'] ?? 0);
        }

        $reports = $e['reports'];
        $reports[] = [
            'id' => 'views',
            'title' => 'Grav Views',
            'provider' => 'views',
            'component' => 'views-report',
            'status' => 'success',
            'message' => $items ? $total . ' tracked views across top pages.' : 'No views tracked yet.',
            'items' => $items,
        ];
        $e['reports'] = $reports;
    }

    public function onApiDashboardWidgets(Event $e)
    {
        $widgets = $e['widgets'];
        $widgets[] = [
            'id' => 'views.top-pages',
            'label' => 'Grav Views',
            'icon' => 'Eye',
            'sizes' => ['sm', 'md'],
            'defaultSize' => 'sm',
            'authorize' => 'api.reports.read',
            'priority' => 55,
            'scriptUrl' => '/gpm/plugins/views/widget-script',
        ];
        $e['widgets'] = $widgets;
    }

    /**
     * Add current directory to twig lookup paths.
     */
    public function onTwigTemplatePaths()
    {
        $this->grav['twig']->twig_paths[] = __DIR__ . '/templates';
    }


    public function onTwigInitialized()
    {
        $this->grav['twig']->twig()->addFunction(
            new TwigFunction('track_views', [$this, 'trackViewsFunc'], ['is_safe' => ['html']])
        );
    }

    public function onPageInitialized(Event $event)
    {
        if ($this->grav['config']->get('plugins.views.tracking.humans_only', false)) {
            $browser = $this->grav['browser'] ?? null;
            if ($browser && method_exists($browser, 'isHuman') && !$browser->isHuman()) {
                return;
            }
            if ($browser && method_exists($browser, 'isTrackable') && !$browser->isTrackable()) {
                return;
            }
        }

        $page = $event['page'];
        $route = is_object($page) && method_exists($page, 'route') ? (string) $page->route() : '';

        if ($route === '') {
            return;
        }

        $this->grav['views']->track($route, 'pages');
    }

    /**
     * @param mixed|null $id
     */
    public function trackViewsFunc($id = null, $type = 'pages')
    {
        if (null === $id) {
            return;
        }

        // Convert objects to string
        $id = (string)$id;

        $this->grav['views']->track($id, $type);
    }
}
