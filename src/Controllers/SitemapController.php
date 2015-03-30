<?php

namespace PhpSitemaper\Controllers;

use PhpSitemaper\Exporters\ExporterXmlWriter;
use PhpSitemaper\Fetchers\FetcherCurl;
use PhpSitemaper\Parsers\ParserNokogiri;
use PhpSitemaper\SitemapConfig;
use PhpSitemaper\SitemapGenerator;
use PhpSitemaper\Stat;
use PhpSitemaper\Views\SitemapView;

/**
 * Класс Контроллера
 *
 * Class SitemapController
 * @package Sitemap\Controllers
 */
class SitemapController
{
    /**
     * Действие "по умолчанию". Создает Вид для отображения главной формы
     */
    public function indexAction()
    {
        $view = new SitemapView();
        $view->renderIndex();
    }

    /**
     * Действие генерации Sitemap
     */
    public function generateAction()
    {
        /**
         * Генерируем id для процесса генерации и прописывамем в сессию
         */
        $id = SitemapGenerator::genId();
        $_SESSION['id'] = $id;

        /**
         * Создание объекта генерации src и установка базовых параметров
         */
        $sitemap = new SitemapGenerator();
        $sitemap->setConfig(new SitemapConfig($_POST));
        $sitemap->setBaseUrl($_POST['url']);

        /**
         * Устанавливаем fetcher - модуль загрузки файлов по HTTP(S)
         */
        $sitemap->setFetcher(new FetcherCurl());

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

        /**
         * Устанавливаем объек для сбора статистики
         */
        $sitemap->setStats(new Stat($id));

        /**
         * Запуск генерации Sitemap
         */
        $sitemap->execute();

        /**
         * Создание объекта Вида для отрисовки страницы результата
         */
        $view = new SitemapView();
        $view->renderResult($sitemap);
    }
}