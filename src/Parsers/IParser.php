<?php

namespace PhpSitemaper\Parsers;

/**
 * HTML parse interface
 *
 * Interface IParser
 * @package Sitemap\Parsers
 */
interface IParser
{

    /**
     * Sets HTML for parsing
     *
     * @param string $html
     */
    public function setHtml($html = '');

    /**
     * Returns parsed from HTML URLs
     *
     * @return array
     */
    public function parse();

}