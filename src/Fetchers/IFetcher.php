<?php

namespace PhpSitemaper\Fetchers;

/**
 * Интерфей для описания компонента для работы с HTTP(S)
 *
 * Interface IFetcher
 * @package Sitemap\Fetchers
 */
interface IFetcher
{
    /**
     * Метод устанавливает базовый URL
     *
     * @param string $baseUrl
     */
    public function setBaseUrl($baseUrl);

    /**
     * Метод устанавливает URL относительно базового
     *
     * @param string $url
     */
    public function setUrl($url);

    /**
     * Метод делает запрос GET по указанному URL
     *
     * @param callable $callback
     * @return bool
     */
    public function get(callable $callback = null);

    /**
     * Метод делает запрос HEAD по указанному URL
     *
     * @param callable $callback
     * @return bool
     */
    public function head(callable $callback = null);

    /**
     * Метод возвращает тело ответа веб-сервера
     *
     * @return string
     */
    public function getContent();

    /**
     * Метод возвращает заголовки ответа веб-сервера
     *
     * @return array
     */
    public function getHeaders();

    /**
     * Метод возвращает относительный URL
     *
     * @return string
     */
    public function getUrl();

    /**
     * Метод возвращает последний код ответа веб-сервера
     *
     * @return int
     */
    public function getResponseCode();
}
