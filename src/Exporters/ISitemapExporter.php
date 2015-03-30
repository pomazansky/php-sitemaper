<?php

namespace PhpSitemaper\Exporters;

/**
 * Интерфейс экспорта src в XML формат
 *
 * Interface ISitemapExporter
 * @package Sitemap\Exporters
 */
interface ISitemapExporter
{
    /**
     * Метод устанавливает путь к файлу для экспорта
     *
     * @param string $filename
     */
    public function setFilename($filename);

    /**
     * Метод устанавливает базовый адрес для последующего добавления страниц
     *
     * @param $baseUrl
     */
    public function setBaseUrl($baseUrl);

    /**
     * Метод инициирует новый документ заданного типа
     *
     * @param string $mode
     */
    public function startDocument($mode = 'sitemap');

    /**
     * Метод добавляет в XML-документ адрес страницы и опциональные параметры
     *
     * @param string $loc
     * @param bool|string $lastMod
     * @param bool|string $changeFreq
     * @param bool|float $priority
     */
    public function attachUrl($loc, $lastMod = false, $changeFreq = false, $priority = false);

    /**
     * Метод добавляет в src Index адрес src
     *
     * @param $loc
     * @param string $lastMod
     */
    public function attachSitemap($loc, $lastMod);

    /**
     * Метод сохраняет XML-документ в указанный файл
     *
     */
    public function save();

}