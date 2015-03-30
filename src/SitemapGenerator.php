<?php

namespace PhpSitemaper;

use PhpSitemaper\Exporters\ISitemapExporter;
use PhpSitemaper\Fetchers\IFetcher;
use PhpSitemaper\Parsers\IParser;

/**
 * Класс генератора src
 *
 * Class SitemapGenerator
 * @package src
 */
class SitemapGenerator
{

    /**
     * Название файла src Index
     *
     * @var string|null
     */
    private $sitemapIndexFile;

    /**
     * Список названий сгенерированных файлов src
     *
     * @var array
     */
    private $sitemapFiles = [];
    /**
     * Параметр конфигурации
     *
     * @var SitemapConfig
     */
    private $config;

    /**
     * Базовый адрес
     *
     * @var array
     */
    private $baseUrl;

    /**
     * Массив страниц
     *
     * @var Page[]
     */
    private $pages = [];

    /**
     * Массив очереди загрузки.
     * Уровни вложенности реализуются за счет подмасивов
     *
     * @var array
     */
    private $fetchQueue = [];

    /**
     * Объект загрузчика страниц
     *
     * @var IFetcher
     */
    private $fetcher;

    /**
     * Объект парсера страниц
     *
     * @var IParser
     */
    private $parser;

    /**
     * Объект экспортера src в XML
     *
     * @var ISitemapExporter
     */
    private $exporter;

    /**
     * Счетчик уровня вложенности
     *
     * @var int
     */
    private $i;

    /**
     * Стастистика
     *
     * @var Stat
     */
    private $stats;

    /**
     * Инициализация конфигурации "по умолчанию"
     */
    public function __construct()
    {
        $this->config = new SitemapConfig();
    }

    /**
     * Установка конфигурации
     *
     * @param SitemapConfig $config
     */
    public function setConfig(SitemapConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Установка загрузчика страниц
     *
     * @param IFetcher $fetcher
     */
    public function setFetcher(IFetcher $fetcher)
    {
        $this->fetcher = $fetcher;
    }

    /**
     * Установка парсера страниц
     *
     * @param IParser $parser
     */
    public function setParser(IParser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Установка экспортера XML
     *
     * @param ISitemapExporter $exporter
     */
    public function setExporter(ISitemapExporter $exporter)
    {
        $this->exporter = $exporter;
    }

    /**
     * Проверка доступности страницы, принятие решения о добавлении в общий список ссылок
     * на основании проверки MIME-типа и, если полученный контенкт HTML - отправка на парсинг ссылок
     */
    public function touchPage()
    {
        $page = new Page($this->fetcher->getUrl(), $this->config, $this->i);
        $page->setHeaders($this->fetcher->getHeaders());

        $this->stats->oneScanned($this->i);

        if (!$page->isValidContent()) {
            return;
        }

        $this->pages[$page->getUrl()] = $page;

        $this->stats->oneAdded($this->i);

        if ($this->i < $this->config->parseLevel && $page->isHtml()) {
            $this->fetcher->get([$this, 'parsePage']);
        }
    }

    /**
     * Парсинг страницы и добавление найденых ссылок
     */
    public function parsePage()
    {

        $this->parser->setHtml($this->fetcher->getContent());

        $parsedLinks = $this->parser->parse();
        $filteredLinks = $this->filterLinks($parsedLinks, $this->fetcher->getUrl());

        foreach ($filteredLinks as $link) {
            $this->addToQueue($link, $this->i);
        }
    }

    /**
     * Фильтрация найденных ссылок в соответствии со спецификацией src
     *
     * @param array $parsedLinks
     * @param string $currentUrl
     * @return array
     */
    private function filterLinks(array $parsedLinks = [], $currentUrl)
    {
        $filteredLinks = [];

        foreach ($parsedLinks as $link) {

            $url = parse_url($link);

            if (!empty($url['path']) && strpos($url['path'], './') !== false) {
                $url['path'] = $this->reformatPath($url['path'], $currentUrl);
            }

            $baseUrl = $this->getBaseUrlParts();

            if (!empty($url['scheme']) && $url['scheme'] !== $baseUrl['scheme']) {
                continue;
            }

            if (!empty($url['host']) && $url['host'] !== $baseUrl['host']) {
                continue;
            }

            if (!empty($url['port']) && $url['port'] !== $baseUrl['port']) {
                continue;
            }

            $path = !empty($url['path']) ? $url['path'] : '/';
            $query = !empty($url['query']) ? "?{$url['query']}" : '';
            $fragment = /*isset($url['fragment']) ? "#{$url['fragment']}" :*/
                '';

            $filteredLinks[] = "$path$query$fragment";
        }

        return array_unique($filteredLinks);
    }

    /**
     * Метод приводит относительный путь ссылки к абсолютному
     *
     * @param string $pathStr
     * @param string $basePathStr
     * @return string
     */
    public function reformatPath($pathStr, $basePathStr)
    {

        $basePathStr = trim($basePathStr, '/');

        $path = explode('/', $pathStr);
        $newPath = [];

        $basePath = explode('/', $basePathStr);

        foreach ($path as $dir) {
            if ($dir === '.') {
                continue;
            }
            if ($dir === '..') {
                array_pop($basePath);
                continue;
            }
            $newPath[] = $dir;
        }

        $newPath = array_merge($basePath, $newPath);
        $newPath = '/' . implode('/', $newPath);

        return $newPath;
    }

    /**
     * Метод возвращает базовый адрес в виде массива элементов
     *
     * @return array
     */
    public function getBaseUrlParts()
    {
        return $this->baseUrl;
    }

    /**
     * Добавление новой найденной ссылки в список страниц и очередь на загрузку
     *
     * @param string $url
     * @param int $level
     */
    public function addToQueue($url, $level = 0)
    {
        if (!array_key_exists($url, $this->pages) && !in_array($url, $this->fetchQueue[$level],
                true) && !in_array($url, $this->fetchQueue[$level + 1], true)
        ) {
            $this->fetchQueue[$level + 1][] = $url;
        }
    }

    /**
     * Реализации алгоритма обхода страниц сайта
     */
    public function execute()
    {
        $this->fetchQueue[0][] = '/';
        $this->stats->setStart(microtime(true));
        $this->stats->newLevel(0);
        $this->stats->inQueue(0, 1);

        $this->fetcher->setBaseUrl($this->getBaseUrl());

        for ($this->i = 0; $this->i < $this->config->parseLevel; $this->i++) {
            $this->fetchQueue[$this->i + 1] = [];

            $this->stats->newLevel($this->i);
            $this->stats->inQueue($this->i, count($this->fetchQueue[$this->i]));

            foreach ($this->fetchQueue[$this->i] as $url) {
                $this->fetcher->setUrl($url);

                $this->fetcher->head([$this, 'touchPage']);
            }

            if (count($this->fetchQueue[$this->i + 1]) === 0) {
                break;
            }
        }
        $this->stats->setEnd();

        $this->export();
    }

    /**
     * Метод возвращает базовый адрес в виде строки
     *
     * @return string
     */
    public function getBaseUrl()
    {
        $scheme = $this->baseUrl['scheme'] . '://';
        $host = $this->baseUrl['host'];
        $port = !empty($this->baseUrl['port']) ? ':' . $this->baseUrl['port'] : '';
        $path = $this->baseUrl['path'];

        return "$scheme$host$port$path";
    }

    /**
     * Установка базового адреса
     *
     * @param string $url
     */
    public function setBaseUrl($url)
    {
        $url = parse_url($url);

        if (empty($url['host']) && empty($url['scheme']) && !empty($url['path'])) {
            $url['host'] = $url['path'];
            $url['scheme'] = 'http';
            unset($url['path']);
        };

        $scheme = $url['scheme'] === 'https' ? 'https' : 'http';
        $host = $url['host'];
        $port = !empty($url['port']) ? ':' . $url['port'] : '';
        $path = '';

        $this->baseUrl = ['scheme' => $scheme, 'host' => $host, 'port' => $port, 'path' => $path];
    }

    /**
     * Метод реализует экспорт полученного списка ссылок в формат src.xml, при необходимости
     * формирует несколько файлов и src Index
     */
    private function export()
    {
        $writtenUrls = 0;
        $currentFileIndex = 0;

        $this->sitemapFiles[] = $this->genSitemapFilename();
        $this->exporter->setBaseUrl($this->getBaseUrl());
        $this->exporter->setFilename('download/' . $this->sitemapFiles[0]);
        $this->exporter->startDocument();

        foreach ($this->pages as $page) {

            $this->exporter->attachUrl($page->getUrl(), $page->getLastMod(), $page->getChangeFreq(),
                $page->getPriority());

            $writtenUrls++;
            if ($writtenUrls === 50000 || filesize('download/' . $this->sitemapFiles[$currentFileIndex]) > 10484000) {

                $this->exporter->save();
                $currentFileIndex++;
                $writtenUrls = 0;
                $filename = $this->genSitemapFilename();
                $this->sitemapFiles[] = $filename;
                $this->exporter->setFilename('download/' . $filename);
                $this->exporter->startDocument();
            }

        }

        $this->exporter->save();

        if ($this->config->gzip) {
            foreach ($this->sitemapFiles as &$filenameGZ) {
                $this->gzFile('download/' . $filenameGZ);
                $filenameGZ .= '.gz';
            }
            unset($filenameGZ);
        }

        if (count($this->sitemapFiles) > 1) {
            $this->sitemapIndexFile = $this->genSitemapFilename('sitemapindex');
            $this->exporter->setFilename($this->sitemapIndexFile);
            $this->exporter->startDocument('sitemapindex');

            foreach ($this->sitemapFiles as $filename) {

                $this->exporter->attachSitemap($this->getBaseUrl() . '/' . $filename, date(DATE_W3C, time()));
            }

            $this->exporter->save();

            if ($this->config->gzip) {
                $this->gzFile('download/' . $this->sitemapIndexFile);
                $this->sitemapIndexFile .= '.gz';
            }
        }

    }

    /**
     * Генерация имени файла Sitemap
     *
     * @param string $mode
     * @return string
     */
    private function genSitemapFilename($mode = 'sitemap')
    {
        return $this->getBaseUrlParts()['host'] . '-' . time() . "-{$mode}.xml";
    }

    /**
     * Метод сжимает указанный файл алгоритмом gzip и удаляет исходный файл
     *
     * @param string $sourceFile
     */
    private function gzFile($sourceFile)
    {
        file_put_contents("compress.zlib://$sourceFile" . '.gz', file_get_contents($sourceFile));
        unlink($sourceFile);
    }

    /**
     * Метод возвращает количество найденых ссылок
     *
     * @return int
     */
    public function getPagesCount()
    {
        return count($this->pages);
    }

    /**
     * Метод возвращает список имен файлов Sitemap
     *
     * @return array
     */
    public function getSitemapFiles()
    {
        return $this->sitemapFiles;
    }

    /**
     * Метод возвращает имя файла Sitemap Index
     *
     * @return null|string
     */
    public function getSitemapIndexFile()
    {
        return $this->sitemapIndexFile;
    }

    /**
     * Установка объекта сбора статистики
     *
     * @param Stat $stats
     */
    public function setStats(Stat $stats)
    {
        $this->stats = $stats;
    }

    /**
     * Генерация идентификатора процесса генерации Sitemap
     *
     * @return string
     */
    public static function genId()
    {
        return time() . '-' . uniqid();
    }
}