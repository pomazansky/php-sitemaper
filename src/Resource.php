<?php

namespace PhpSitemaper;

/**
 * Class Page
 * @package Sitemap
 */
class Resource
{
    /**
     * Relative page address
     *
     * @var string
     */
    protected $url;

    /**
     * Last modification time in W3C format
     *
     * @var string
     */
    protected $lastMod;

    /**
     * Page change frequency
     *
     * @var string
     */
    protected $changeFreq;

    /**
     * Relative page priority
     *
     * @var float
     */
    protected $priority;

    /**
     * Response headers
     *
     * @var array
     */
    protected $headers = [];

    /**
     * Sitemap configuration object
     *
     * @var SitemapConfig
     */
    protected $config;

    /**
     * Nesting level of a page
     *
     * @var integer
     */
    protected $level;

    /**
     * Sets base params
     *
     * @param $url
     * @param null|SitemapConfig $config
     * @param int $level
     */
    public function __construct($url, $config = null, $level = 0)
    {
        if ($config !== null) {
            $config = new SitemapConfig();
        }

        $this->config = $config;

        $this->url = $url;

        $this->setChangeFreq($config->changeFreq);

        if ($config->priority === 'auto') {
            $this->setPriority(pow(0.8, $level));
        }

        $this->setLastMod();
    }

    /**
     * Return relative URL
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Sets relative URL
     *
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * Return last modification time
     *
     * @return string
     */
    public function getLastMod()
    {
        return $this->lastMod;
    }

    /**
     * Sets last modification time
     */
    public function setLastMod()
    {
        switch ($this->config->lastMod) {
            case 'response':
                $this->parseHeadersForLastModified();
                break;
            case 'current':
                $this->lastMod = date(DATE_W3C, time());
                break;
            case '':
            default:
        }
    }

    /**
     * Determines if the link content is worth including in Sitemap using response headers
     * https://support.google.com/webmasters/answer/35287?hl=en
     */
    public function isValidContent()
    {
        $validTypes = [
            'application/pdf', //Adobe Portable Document Format
            'application/postscript', //Adobe PostScript
            'text/html', //HTML
            'application/vnd.oasis.opendocument.text', //OpenDocument text
            'application/vnd.oasis.opendocument.spreadsheet', //OpenDocument spreadsheet
            'application/vnd.oasis.opendocument.presentation', //OpenDocument presentation
            'application/msword', //Microsoft Word
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document', //Microsoft Word 2007
            'application/vnd.ms-excel', //Microsoft Excel
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', //Microsoft Excel 2007
            'application/vnd.ms-powerpoint', //Microsoft Powerpoint
            'application/vnd.openxmlformats-officedocument.presentationml.presentation', //Microsoft Powerpoint 2007,
            'application/rtf', //
            'application/x-rtf', // Rich Text Format
            'text/richtext', //
            'image/svg+xml', //Scalable Vector Graphics
            'application/x-latex', //TeX/LaTe
            'text/plain', //Text
            'text/vnd.wap.wml', //Wireless Markup Language
            'text/xml' // XML
        ];

        return in_array(trim(explode(';', $this->getHeader('Content-Type'))[0]), $validTypes, true);
    }

    /**
     * Returns response header
     *
     * @param string $hName
     * @return bool|string
     */
    protected function getHeader($hName)
    {
        foreach ($this->headers as $header) {
            $h = explode(': ', $header);
            if ($h[0] === $hName) {
                return $h[1];
            }
        }

        return false;
    }

    /**
     * Determines if response is HTML
     *
     * @return bool
     */
    public function isHtml()
    {
        return (strpos($this->getHeader('Content-Type'), 'text/html') === 0);
    }

    /**
     * Return change frequency
     *
     * @return string
     */
    public function getChangeFreq()
    {
        return $this->changeFreq;
    }

    /**
     * Sets change frequency
     *
     * @param string $changeFreq
     */
    public function setChangeFreq($changeFreq)
    {
        $this->changeFreq = $changeFreq;
    }

    /**
     * Return relative priority
     *
     * @return float
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Sets relative priority
     *
     * @param float $priority
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    /**
     * Returns response headers
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Sets response headers
     *
     * @param array $headers
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;

        $this->setLastMod();
    }

    /**
     * Analyses response headers for last modified time
     */
    protected function parseHeadersForLastModified()
    {
        $lastMod = $this->getHeader('Last-Modified');
        if ($lastMod !== false) {
            $this->lastMod = date(DATE_W3C, strtotime($lastMod));
        }
    }
}
