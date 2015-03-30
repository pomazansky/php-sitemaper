<?php

namespace PhpSitemaper\Exporters;

/**
 * Класс экспорта src в XML формат с помощью XMLWriter
 *
 * Class ExporterXmlWriter
 * @package Sitemap\Exporters
 */
class ExporterXmlWriter implements ISitemapExporter
{
    /**
     * Базовый URL
     *
     * @var string
     */
    private $baseUrl;

    /**
     * Путь к файлу для экспорта
     *
     * @var string
     */
    private $filename;

    /**
     * Объект XMLWriter
     *
     * @var \XMLWriter
     */
    private $writer;

    /**
     * Инициализация XML-документа
     */
    public function __construct()
    {
        $this->writer = new \XMLWriter();
    }

    /**
     * Метод устанавливает путь к файлу для экспорта
     *
     * @param string $filename
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
        $this->writer->openURI($filename);
    }

    /**
     * Установка параметров и инициализация документа
     *
     * @param string $mode
     */
    public function startDocument($mode = 'sitemap')
    {
        $this->writer->setIndent(true);
        $this->writer->startDocument('1.0', 'UTF-8');

        switch ($mode) {
            case 'sitemapindex' :
                $this->writer->startElement('sitemapindex');
                break;
            case 'sitemap':
            default :
                $this->writer->startElement('urlset');
        }

        /**
         * Добавление ссылки на XSD-схему для валидации итогового файла на соответствие спецификации
         */
        $this->writer->writeAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');

        $this->writer->writeAttribute('xsi:schemaLocation',
            "http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/{$mode}.xsd");

        $this->writer->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
    }

    /**
     * Метод устанавливает базовый адрес для последующего добавления страниц
     *
     * @param string $baseUrl
     */
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * Метод добавляет в XML-документ адрес страницы и опциональные параметры
     *
     * @param string $loc
     * @param null|string $lastMod
     * @param null|string $changeFreq
     * @param null|float $priority
     */
    public function attachUrl($loc, $lastMod = null, $changeFreq = null, $priority = null)
    {
        $this->writer->startElement('url');

        $this->writer->writeElement('loc', $this->baseUrl . $loc);

        if ($lastMod !== null) {
            $this->writer->writeElement('lastmod', $lastMod);
        }

        if ($changeFreq !== null) {
            $this->writer->writeElement('changefreq', $changeFreq);
        }

        if ($priority !== null) {
            $this->writer->writeElement('priority', number_format($priority, 2));
        }

        $this->writer->endElement();
    }

    /**
     * Метод добавляет в src Index адрес src
     *
     * @param $loc
     * @param string $lastMod
     */
    public function attachSitemap($loc, $lastMod)
    {
        $this->writer->startElement('sitemap');

        $this->writer->writeElement('loc', $this->baseUrl . $loc);

        $this->writer->writeElement('lastmod', $lastMod);

        $this->writer->endElement();
    }

    /**
     * Метод сохраняет XML-документ в указанный файл
     */
    public function save()
    {
        $this->writer->endElement();
        $this->writer->endDocument();

        $this->writer->flush();

        return true;
    }
}