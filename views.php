<?php
namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Plugin;
use Grav\Plugin\Views\Views;

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
            ]
        ];
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
        $this->grav['views'] = function () {
            return new Views($this->config->get('plugins.views'));
        };
    }

    /**
     * Initialize the plugin
     */
    public function onPluginsInitialized()
    {
        if ($this->isAdmin()) {
            return;
        }

        $this->enable([
            'onTwigInitialized' => [
                ['onTwigInitialized', 0]
            ],
        ]);
    }


    public function onTwigInitialized()
    {
        $this->grav['twig']->twig()->addFunction(
            new \Twig_SimpleFunction('track_views', [$this, 'trackViewsFunc'])
        );
    }

    public function trackViewsFunc($id)
    {
        $this->grav['views']->track($id);
    }
}
