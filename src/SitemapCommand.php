<?php

namespace PhpSitemaper;

use PhpSitemaper\Exporters\ExporterXmlWriter;
use PhpSitemaper\Fetchers\GuzzleAdapter;
use PhpSitemaper\Parsers\ParserNokogiri;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Класс комманды для консольного приложения
 *
 * Class SitemapCommand
 * @package Sitemap
 */
class SitemapCommand extends Command
{
    /**
     * Метод устанавливате аргументы и флаги для консольной коммнады
     */
    protected function configure()
    {

        $defaltConfig = new SitemapConfig();

        $this->setName('generate')
            ->setDescription('Generates Sitemap for URL address')
            ->setDefinition([
                new InputArgument('url', InputArgument::REQUIRED, 'URL to start parse'),
                new InputOption('parseLevel', 'l', InputOption::VALUE_OPTIONAL, 'Parse Level',
                    $defaltConfig->parseLevel),
                new InputOption('changeFreq', 'f', InputOption::VALUE_OPTIONAL, 'How often do pages change',
                    $defaltConfig->changeFreq),
                new InputOption('lastMod', 'm', InputOption::VALUE_OPTIONAL, 'How to get Last Modified value',
                    $defaltConfig->lastMod),
                new InputOption('priority', 'p', InputOption::VALUE_OPTIONAL, 'Calculate page priority',
                    $defaltConfig->priority),
                new InputOption('gzip', 'z', InputOption::VALUE_OPTIONAL, 'GZip resulting files', $defaltConfig->gzip)
            ]);

    }

    /**
     * Метод выполнения консольной комманды
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $id = SitemapGenerator::genId();

        $sitemap = new SitemapGenerator();

        $sitemap->setBaseUrl($input->getArgument('url'));
        $sitemap->setConfig(new SitemapConfig($input->getOptions()));

        $sitemap->setFetcher(new GuzzleAdapter());

        /**
         * Пакет парсинга Nokogiri выбран на основании сравнительного тестирования подобных
         * решений для PHP на сайте Habrahabr.ru
         */
        $sitemap->setParser(new ParserNokogiri());

        /**
         * Экспорт в XML осуществляется с помощью XMLWriter, что показал лучшую производительность
         * по сравнению с DOM
         */
        $sitemap->setExporter(new ExporterXmlWriter());

        $sitemap->setStats(new Stat($id));

        /**
         * Запуск генерации Sitemap
         */
        $sitemap->execute();
    }

}