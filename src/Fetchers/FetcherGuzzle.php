<?php

namespace PhpSitemaper\Fetchers;


use GuzzleHttp\Client;
use GuzzleHttp\Message\ResponseInterface;

class FetcherGuzzle implements IFetcher
{

    /**
     * Base URL
     *
     * @var string
     */
    private $baseUrl;

    /**
     * URL
     *
     * @var string
     */
    private $url;

    /**
     * Guzzle Client
     *
     * @var Client
     */
    private $client;

    /**
     * @var ResponseInterface
     */
    private $response;

    public function __construct()
    {
        $this->client = new Client();
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

    /**
     * Метод делает запрос GET по указанному URL
     *
     * @param callable $callback
     * @return bool
     */
    public function get(callable $callback = null)
    {
        $this->response = $this->client->get($this->baseUrl . $this->url);

        if ($this->getResponseCode() < 400 && is_callable($callback)) {
            call_user_func($callback);
        }

        return true;
    }

    /**
     * Метод возвращает последний код ответа веб-сервера
     *
     * @return int
     */
    public function getResponseCode()
    {
        return $this->response->getStatusCode();
    }

    /**
     * Метод делает запрос HEAD по указанному URL
     *
     * @param callable $callback
     * @return bool
     */
    public function head(callable $callback = null)
    {
        $this->response = $this->client->head($this->baseUrl . $this->url);

        if ($this->getResponseCode() < 400 && is_callable($callback)) {
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
        return $this->response->getBody();
    }

    /**
     * Метод возвращает заголовки ответа веб-сервера
     *
     * @return array
     */
    public function getHeaders()
    {
        $headers = [];

        foreach ($this->response->getHeaders() as $name => $values) {
            $headers[] = $name . ': ' . implode(', ', $values);
        }

        return $headers;
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
     * Метод устанавливает URL относительно базового
     *
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }
}