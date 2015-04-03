<?php

namespace PhpSitemaper;

use PhpSitemaper\Exporters\XmlWriterAdapter;
use PhpSitemaper\Fetchers\GuzzleAdapter;
use PhpSitemaper\Parsers\NokogiriAdapter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Console App Command Class
 *
 * Class SitemapCommand
 * @package Sitemap
 */
class SitemapCommand extends Command
{
    /**
     * Sets input arguments and options
     */
    protected function configure()
    {

        $defaultConfig = new SitemapConfig();

        $this->setName('generate')
            ->setDescription('Generates Sitemap for URL address')
            ->setDefinition(
                [
                new InputArgument('url', InputArgument::REQUIRED, 'URL to start parse'),
                new InputOption(
                    'parseLevel',
                    'l',
                    InputOption::VALUE_OPTIONAL,
                    'Parse Level',
                    $defaultConfig->parseLevel
                ),
                new InputOption(
                    'changeFreq',
                    'f',
                    InputOption::VALUE_OPTIONAL,
                    'How often do pages change',
                    $defaultConfig->changeFreq
                ),
                new InputOption(
                    'lastMod',
                    'm',
                    InputOption::VALUE_OPTIONAL,
                    'How to get Last Modified value',
                    $defaultConfig->lastMod
                ),
                new InputOption(
                    'priority',
                    'p',
                    InputOption::VALUE_OPTIONAL,
                    'Calculate page priority',
                    $defaultConfig->priority
                ),
                new InputOption('gzip', 'z', InputOption::VALUE_OPTIONAL, 'GZip resulting files', $defaultConfig->gzip)
                ]
            );

    }

    /**
     * Console command execution
     *
     * @param  InputInterface  $input
     * @param OutputInterface $output
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sessionId = SitemapGenerator::genId();

        $sitemap = new SitemapGenerator();

        $sitemap->setBaseUrl($input->getArgument('url'));
        $sitemap->setConfig(new SitemapConfig($input->getOptions()));

        $sitemap->setFetcher(new GuzzleAdapter());

        /**
         * Sets HTML parser
         */
        $sitemap->setParser(new NokogiriAdapter());

        /**
         * Sets Sitemap XML exporter
         */
        $sitemap->setExporter(new XmlWriterAdapter());

        /**
         * Sets stats object
         */
        $sitemap->setStats(new Stat($sessionId));

        /**
         * Sitemap generation
         */
        $sitemap->execute();

        return 0;
    }
}
