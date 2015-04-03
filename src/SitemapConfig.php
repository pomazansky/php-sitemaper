<?php

namespace PhpSitemaper;

/**
 * Sitemap generation config
 *
 * Class SitemapConfig
 * @package Sitemap
 */
class SitemapConfig
{
    /**
     * Nesting level
     * @var integer
     */
    public $parseLevel = 3;
    /**
     * Change frequency mode
     * @var null|string
     */
    public $changeFreq;
    /**
     * Last modified mode
     * @var string
     */
    public $lastMod = 'response';
    /**
     * Priority mode
     * @var string
     */
    public $priority = 'auto';
    /**
     * GZip mode
     * @var bool
     */
    public $gzip = false;

    /**
     * Checks and sets params on creation
     *
     * @param array $params
     */
    public function __construct(array $params = [])
    {
        if (array_key_exists('parseLevel', $params)) {
            $this->setParseLevel($params['parseLevel']);
        }

        if (array_key_exists('changeFreq', $params)) {
            $this->setChangeFreq($params['changeFreq']);
        }

        if (array_key_exists('lastMod', $params)) {
            $this->setLastMod($params['lastMod']);
        }

        if (array_key_exists('priority', $params)) {
            $this->setPriority($params['priority']);
        }

        if (array_key_exists('gzip', $params)) {
            $this->setGzip($params['gzip']);
        }
    }

    /**
     * Sets nesting level
     *
     * @param $parseLevel
     */
    public function setParseLevel($parseLevel)
    {
        $this->parseLevel = $this->checkParam($parseLevel, range(1, 5)) ? $parseLevel : $this->parseLevel;
    }

    /**
     * Supports params checking
     *
     * @param $value
     * @param array $validValues
     * @return bool
     */
    private function checkParam($value, array $validValues)
    {
        return in_array($value, $validValues, false);
    }

    /**
     * Sets change frequency mode
     *
     * @param $changeFreq
     */
    public function setChangeFreq($changeFreq)
    {
        $this->changeFreq = $this->checkParam(
            $changeFreq,
            ['', 'always','hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never']
        ) ? $changeFreq : $this->changeFreq;
    }

    /**
     * Sets last modification mode
     *
     * @param $lastMod
     */
    public function setLastMod($lastMod)
    {
        $this->lastMod = $this->checkParam($lastMod, ['', 'response', 'current']) ? $lastMod : $this->lastMod;
    }

    /**
     * Sets priority mode
     *
     * @param $priority
     */
    public function setPriority($priority)
    {
        $this->priority = $this->checkParam($priority, ['', 'auto']) ? $priority : $this->priority;
    }

    /**
     * Sets GZip mode
     *
     * @param $gzip
     */
    public function setGzip($gzip)
    {
        $this->gzip = $this->checkParam($gzip, ['', 'on']) ? $gzip : $this->gzip;
    }
}
