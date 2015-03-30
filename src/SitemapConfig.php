<?php

namespace PhpSitemaper;

/**
 * Класс параметров генерации Sitemap
 *
 * Class SitemapConfig
 * @package Sitemap
 */
class SitemapConfig
{
    /**
     * Глубина парсинга страниц
     * @var integer
     */
    public $parseLevel = 3;
    /**
     * Частота изменения страницы
     * @var null|string
     */
    public $changeFreq;
    /**
     * Метод установки параметра lastmod страницы
     * @var string
     */
    public $lastMod = 'response';
    /**
     * Метод установки приоритета страницы
     * @var string
     */
    public $priority = 'auto';
    /**
     * Сжимать ли результирующий файл
     * @var bool
     */
    public $gzip = false;

    /**
     * Метод проверет и устанавливает парамеры при создании
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
     * Установка уровня парсинга
     *
     * @param $parseLevel
     */
    public function setParseLevel($parseLevel)
    {
        $this->parseLevel = $this->checkParam($parseLevel, range(1, 5)) ? $parseLevel : $this->parseLevel;
    }

    /**
     * Вспомогательный метод проверки параметров
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
     * Установка режима частоты изменения
     *
     * @param $changeFreq
     */
    public function setChangeFreq($changeFreq)
    {
        $this->changeFreq = $this->checkParam($changeFreq,
            ['', 'always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never']) ?
            $changeFreq : $this->changeFreq;
    }

    /**
     * Установка режима определения времени поледнего изменения
     *
     * @param $lastMod
     */
    public function setLastMod($lastMod)
    {
        $this->lastMod = $this->checkParam($lastMod, ['', 'response', 'current']) ? $lastMod : $this->lastMod;
    }

    /**
     * Установка режима определения относительного приоритета страницы
     *
     * @param $priority
     */
    public function setPriority($priority)
    {
        $this->priority = $this->checkParam($priority, ['', 'auto']) ? $priority : $this->priority;
    }

    /**
     * Установка режима архивирование результирующих файлов
     *
     * @param $gzip
     */
    public function setGzip($gzip)
    {
        $this->gzip = $this->checkParam($gzip, ['', 'on']) ? $gzip : $this->gzip;
    }
}