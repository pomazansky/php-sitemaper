<?php

namespace PhpSitemaper;

use PhpSitemaper\Fetchers\MultiFetcherInterface;
use PhpSitemaper\Parsers\ParserInterface;

/**
 * Class Crawler
 * @package PhpSitemaper
 */
class Crawler
{

    /**
     * Owner object
     *
     * @var SitemapGenerator
     */
    private $owner;

    /**
     * Base URL
     *
     * @var array
     */
    private $baseUrl;

    /**
     * Sitemap generation config object
     *
     * @var SitemapConfig
     */
    private $config;

    /**
     * Array of enlisted pages
     *
     * @var \PhpSitemaper\ResourceEntry[]
     */
    private $resources = [];

    /**
     * Array of pages to fetch
     *
     * @var array
     */
    private $fetchQueue;

    /**
     * Object of HTTP client
     *
     * @var MultiFetcherInterface
     */
    private $fetcher;

    /**
     * HTML parser object
     *
     * @var ParserInterface
     */
    private $parser;

    /**
     * Nesting level counter
     *
     * @var int
     */
    private $currentLevel;

    /**
     * Statistic gethering object
     *
     * @var Stat
     */
    private $stats;

    /**
     * @param SitemapGenerator $owner
     */
    public function __construct(SitemapGenerator $owner)
    {
        $this->owner = $owner;
    }

    /**
     * Callback for first touching (retrieving only server response headers)
     *
     * @param string $url
     * @param array $headers
     */
    public function touchResource($url, $headers)
    {
        $resource = new ResourceEntry($url, $this->config, $this->currentLevel);
        $resource->setHeaders($headers);

        $this->stats->oneScanned($this->currentLevel);

        if (!$resource->isValidContent()) {
            return;
        }

        $this->resources[$resource->getUrl()] = $resource;

        $this->stats->oneAdded($this->currentLevel);

        if ($this->currentLevel < $this->config->parseLevel && $resource->isHtml()) {
            $this->addToGetQueue($url);
        }
    }

    /**
     * Adds URL to GET queue
     *
     * @param string $url
     */
    public function addToGetQueue($url)
    {
        if (!in_array($url, $this->fetchQueue['get'][$this->currentLevel], true)
            && !in_array($url, $this->fetchQueue['get'][$this->currentLevel + 1], true)
        ) {
            $this->fetchQueue['get'][$this->currentLevel][] = $url;
        }
    }

    /**
     * Page parsing and adding retrieved URLs to queue
     *
     * @param $resourceUrl
     * @param string $html
     */
    public function parseResource($resourceUrl, $html)
    {
        if (empty($html)) {
            unset($this->resources[$resourceUrl]);
            return;
        }
        $this->parser->setHtml($html);

        $parsedLinks = $this->parser->parse();

        foreach ($parsedLinks as $link) {
            $filteredUrl = $this->filterLink($link, $resourceUrl);
            if ($filteredUrl) {
                $this->addToHeadQueue($filteredUrl);
            }
        }
    }

    /**
     * Filters founded link due to Sitemap specs
     *
     * @param $url
     * @param $currentUrl
     * @return bool|string
     */
    private function filterLink($url, $currentUrl)
    {
        $url = parse_url($url);

        foreach (['scheme', 'host', 'port'] as $elementName) {
            if (isset($url[$elementName]) && $url[$elementName] !== $this->baseUrl[$elementName]) {
                return false;
            }
        }

        if (isset($url['path']) && strpos($url['path'], './') !== false) {
            $url['path'] = $this->reformatPath($url['path'], $currentUrl);
        }

        $path = $url['path'] ?: '/';
        $query = $url['query'] ?: '';

        return "$path$query";
    }

    /**
     * Transforms relative URL to absolute
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
     * Adds URL to HEAD queue
     *
     * @param string $url
     */
    public function addToHeadQueue($url)
    {
        if (!array_key_exists($url, $this->resources)
            && !in_array($url, $this->fetchQueue['head'][$this->currentLevel + 1], true)
        ) {
            $this->fetchQueue['head'][$this->currentLevel + 1][] = $url;
        }
    }


    /**
     * Sitemap generation execution
     */
    public function execute()
    {
        $this->fetchQueue['head'][0][] = '/';
        $this->fetchQueue['get'][0] = [];

        $this->stats->setStart(microtime(true));
        $this->stats->newLevel(0);
        $this->stats->inQueue(0, 1);

        $this->fetcher->setBaseUrl($this->owner->getBaseUrl());

        for ($this->currentLevel = 0; $this->currentLevel < $this->config->parseLevel; $this->currentLevel++) {
            $this->fetchQueue['head'][$this->currentLevel + 1] = [];
            $this->fetchQueue['get'][$this->currentLevel + 1] = [];

            $this->stats->newLevel($this->currentLevel);
            $this->stats->inQueue($this->currentLevel, count($this->fetchQueue['head'][$this->currentLevel]));

            $this->fetcher->headPool($this->fetchQueue['head'][$this->currentLevel], [$this, 'touchResource']);
            $this->fetcher->getPool($this->fetchQueue['get'][$this->currentLevel], [$this, 'parseResource']);

            if (count($this->fetchQueue['head'][$this->currentLevel + 1]) === 0) {
                break;
            }
        }
        $this->stats->setEnd();
    }

    /**
     * @param array $baseUrl
     */
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * @param SitemapConfig $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * @param MultiFetcherInterface $fetcher
     */
    public function setFetcher($fetcher)
    {
        $this->fetcher = $fetcher;
    }

    /**
     * @param ParserInterface $parser
     */
    public function setParser($parser)
    {
        $this->parser = $parser;
    }

    /**
     * @param Stat $stats
     */
    public function setStats($stats)
    {
        $this->stats = $stats;
    }

    /**
     * @return ResourceEntry[]
     */
    public function getResources()
    {
        return $this->resources;
    }
}
