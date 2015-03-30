<?php

namespace PhpSitemaper;

/**
 * Класс страницы
 *
 * Class Page
 * @package Sitemap
 */
class Page
{
    /**
     * Относительны адрес страницы
     *
     * @var string
     */
    protected $url;

    /**
     * Параметр времени последней модификации страницы в формате W3C
     *
     * @var string
     */
    protected $lastMod;

    /**
     * Параметр регулярности изменения страницы
     *
     * @var string
     */
    protected $changeFreq;

    /**
     * Параметр относительно приоритета страницы
     *
     * @var float
     */
    protected $priority;

    /**
     * Заголовки ответа веб-сервера
     *
     * @var array
     */
    protected $headers = [];

    /**
     * Объект конфигурации
     *
     * @var SitemapConfig
     */
    protected $config;

    /**
     * Уровень ссылочной вложености страницы по отношению к главной.
     * Используется для автоматического расчета параметра priority
     *
     * @var integer
     */
    protected $level;

    /**
     * Установка базовых параметров
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
     * Метод возвращает относительный URL страницы
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Метод устанавливает относительный URL страницы
     *
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * Метод возвращает время последней модификации страницы
     *
     * @return string
     */
    public function getLastMod()
    {
        return $this->lastMod;
    }

    /**
     * Метод установки времени последней модификации страницы
     */
    public function setLastMod()
    {
        switch ($this->config->lastMod) {
            case 'response' :
                $this->parseHeadersForLastModified();
                break;
            case 'current' :
                $this->lastMod = date(DATE_W3C, time());
                break;
            case '' :
            default :
        }
    }

    /**
     * Метод определяет на основании заголовков является ли полученый
     * ответ подходящего типа для включения в src.
     * Список валидных типов сформирован на оновании списка типов файлов,
     * которые индексирует Google
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
     * Метод возвращает значение определенного заголовка ответа веб-сервера
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
     * Метод определяет на основании заголовков является ли полученый
     * ответ от веб-сервера страницей HTML
     *
     * @return bool
     */
    public function isHtml()
    {
        return (strpos($this->getHeader('Content-Type'), 'text/html') === 0);
    }

    /**
     * Метод возвращает частоту изменения страницы
     *
     * @return string
     */
    public function getChangeFreq()
    {
        return $this->changeFreq;
    }

    /**
     * Метод устанавливает частоту изменения страницы
     *
     * @param string $changeFreq
     */
    public function setChangeFreq($changeFreq)
    {
        $this->changeFreq = $changeFreq;
    }

    /**
     * Метод возвращает относительный приоритет страницы
     *
     * @return float
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Метод устанавливает относительный приоритет страницы
     *
     * @param float $priority
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    /**
     * Метод возвращает заголовки ответа веб-сервера при загрузке страницы
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Метод устанавливает заголовки ответа веб-сервера
     *
     * @param array $headers
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;

        $this->setLastMod();
    }

    /**
     * Метод определения последней модификации страницы на основании заголовков
     * ответа веб-сервера
     */
    protected function parseHeadersForLastModified()
    {
        $lastMod = $this->getHeader('Last-Modified');
        if ($lastMod !== false) {
            $this->lastMod = date(DATE_W3C, strtotime($lastMod));
        }
    }
}