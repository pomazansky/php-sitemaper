<?php

namespace PhpSitemaper\Parsers;

use nokogiri;

/**
 * Adapter class for Nokogiri HTML parsing library
 *
 * Class ParserNokogiri
 * @package Sitemap\Parsers
 */
class NokogiriAdapter implements IParser
{

    /**
     * HTML for parsing
     *
     * @var string
     */
    private $html;

    /**
     * Sets HTML for parsing
     *
     * @param string $html
     */
    public function setHtml($html = '')
    {
        $this->html = $html;
    }

    /**
     * Returns parsed from HTML URLs
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
