<?php

namespace PhpSitemaper\Fetchers;

/**
 * Класс дл работы с HTTP(S) на основе libcurl
 *
 * Class FetcherCurl
 * @package Sitemap\Fetchers
 */
class FetcherCurl implements IFetcher
{
    /**
     * Базовый URL
     *
     * @var string
     */
    protected $baseUrl;
    /**
     * Относительный URL
     *
     * @var string
     */
    protected $url;
    /**
     * Тело ответа веб-сервера
     *
     * @var string
     */
    protected $content;
    /**
     * Заголовки ответа веб-сервера
     *
     * @var array
     */
    protected $headers;
    /**
     * Код ответа веб-сервера
     *
     * @var int
     */
    protected $responseCode;
    /**
     * Ресурс Curl
     *
     * @var
     */
    private $ch;
    /**
     * Ответ веб-сервера
     *
     * @var
     */
    private $response;
    /**
     * Размер заголовков в ответе
     *
     * @var
     */
    private $headerSize;

    /**
     * Проверяем наличие установленного расширения PHP Curl
     */
    public function __construct()
    {
        if (!function_exists('curl_init')) {
            throw new \BadFunctionCallException('Oops... We can\'t move on... Please, install a php curl extension');
        }
    }

    /**
     * Метод делает запрос HEAD и в случае успеха вызывает callback-функцию
     *
     * @param callable $callback
     * @return bool
     */
    public function head(callable $callback = null)
    {
        $this->init();

        curl_setopt($this->ch, CURLOPT_NOBODY, true);

        $this->exec();

        if ($this->response === false) {
            return false;
        }

        $this->headers = explode("\n", trim(substr($this->response, 0, $this->headerSize)));

        if ($this->responseCode < 400 && is_callable($callback)) {
            call_user_func($callback);
        }

        return true;

    }

    /**
     * Метод инициирует ресурс Curl и устанавливает базовые параметры
     */
    private function init()
    {
        $this->ch = curl_init($this->baseUrl . $this->url);

        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->ch, CURLOPT_HEADER, 1);

        curl_setopt($this->ch, CURLOPT_ENCODING, '');
        curl_setopt($this->ch, CURLOPT_USERAGENT,
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/41.0.2272.76 Chrome/41.0.2272.76 Safari/537.36');

        $cookieFile = 'cache/cookies/' . parse_url($this->baseUrl)['host'];

        curl_setopt($this->ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($this->ch, CURLOPT_COOKIEFILE, $cookieFile);

        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 1);

    }

    /**
     * Метод совершает запрос к серверу
     */
    private function exec()
    {
        $this->response = curl_exec($this->ch);

        $this->responseCode = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);

        $this->headerSize = curl_getinfo($this->ch, CURLINFO_HEADER_SIZE);

        curl_close($this->ch);
    }

    /**
     * Метод совершает GET запрос к серверу и вызывает callback-функцию в случае успеха
     *
     * @param callable $callback
     * @return bool
     */
    public function get(callable $callback = null)
    {
        $this->init();

        $this->exec();

        if ($this->response === false) {
            return false;
        }

        $this->headers = explode("\n", trim(substr($this->response, 0, $this->headerSize)));
        $this->content = substr($this->response, $this->headerSize);

        if ($this->responseCode < 400 && is_callable($callback)) {
            call_user_func($callback);
        }

        return true;
    }

    /**
     * Метод возвращает тело ответа веб-сервера
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Метод возвращает заголовки ответа веб-сервера
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Метод возвращает код овета веб-сервера
     *
     * @return int
     */
    public function getResponseCode()
    {
        return $this->responseCode;
    }

    /**
     * Метод возвращает относительный URL
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Метод устанавливает относительный URL
     *
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;

        $this->headers = null;
        $this->content = null;
    }

    /**
     * Метод возвращает базовый URL
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * Метод устанавливает базовый URL
     *
     * @param string $baseUrl
     */
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }
}