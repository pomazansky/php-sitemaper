<?php

namespace PhpSitemaper\Parsers;

/**
 * Интерфейс парсинга HTML-кода
 *
 * Interface IParser
 * @package Sitemap\Parsers
 */
interface IParser
{

    /**
     * Метод задает HTML-код для дальнейшего парсинга
     *
     * @param string $html
     */
    public function setHtml($html = '');

    /**
     * Метод осуществляет парсинг и возвращает массив URL-адресов
     *
     * @return array
     */
    public function parse();

}