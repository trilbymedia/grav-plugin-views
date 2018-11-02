<?php
/**
 * @package    Grav\Plugin\Views
 *
 * @copyright  Copyright (C) 2014 - 2017 Trilby Media, LLC. All rights reserved.
 * @license    MIT License; see LICENSE file for details.
 */
namespace Grav\Plugin\Console;

use Grav\Console\ConsoleCommand;
use Grav\Common\Grav;
use Grav\Plugin\Database\Database;
use Grav\Plugin\Views\Views;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class CleanCommand
 *
 * @package Grav\Console\Cli
 */
class ListCommand extends ConsoleCommand
{
    /** @var array */
    protected $options = [];

    /** @var Views */
    protected $views;

    /**
     * Configure the command
     */
    protected function configure()
    {
        $this
            ->setName('list')
            ->addArgument(
                'slug',
                InputArgument::OPTIONAL,
                'The page slug',
                ''
            )
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_OPTIONAL,
                'Limit the list of page views',
                10
            )
            ->addOption(
                'sort',
                's',
                InputOption::VALUE_OPTIONAL,
                'Sort the list of page views (desc / asc)',
                'desc'
            )
            ->setDescription('List the page views count')
            ->setHelp('The <info>list</info> command displays the page views count')
        ;
    }

    /**
     * @return int|null|void
     */
    protected function serve()
    {
        include __DIR__ . '/../vendor/autoload.php';
        $grav = Grav::instance();
        /** @var use new SymfonyStyle helper $io */
        $io = new SymfonyStyle($this->input, $this->output);

        // Initialize Plugins
        $grav->fireEvent('onPluginsInitialized');

        $slug = $this->input->getArgument('slug');
        $limit = $this->input->getOption('limit');
        $sort = $this->input->getOption('sort');

        $views = $grav['views'];

        $table = new Table($this->output);
        $table->setStyle('box');
        $table->setHeaders(['Slug', 'Views Count']);
        $rows = [];

        if ($slug) {
            $rows[] = [$slug, $views->get($slug)];
        } else {
            $total = $views->getAll($limit, $sort);
            foreach ($total as $view) {
                $rows[] = [$view['id'], $view['count']];
            }
        }

        $io->title('Page Views List');
        $table->setRows($rows);
        $table->render();
        $io->newLine();
    }
}
