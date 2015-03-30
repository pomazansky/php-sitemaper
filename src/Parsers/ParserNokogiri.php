<?php

namespace PhpSitemaper\Parsers;

use nokogiri;

/**
 * Класс парсинга HTML на основании библиотеки Nokogiri
 *
 * Class ParserNokogiri
 * @package Sitemap\Parsers
 */
class ParserNokogiri implements IParser
{

    /**
     * HTML-код для парсинга
     *
     * @var string
     */
    private $html;

    /**
     * Метод устанавливает HTML-код для парсинга
     *
     * @param string $html
     */
    public function setHtml($html = '')
    {
        $this->html = $html;
    }

    /**
     * Метод осуществляет парсинг и возвращает массив найденных ссылок
     *
     * @return array
     */
    public function parse()
    {
        $links = [];
        $saw = new nokogiri($this->html);

        foreach ($saw->get('a') as $a) {
            if (!empty($a['href'])) {
                $links[] = $a['href'];
            }
        }

        return $links;
    }
}
