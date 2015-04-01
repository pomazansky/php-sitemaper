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
     * Relative URL
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
     * Set the base URL
     *
     * @param string $baseUrl
     */
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * Sends a GET request
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
     * Returns web-server response code
     *
     * @return int
     */
    public function getResponseCode()
    {
        return $this->response->getStatusCode();
    }

    /**
     * Sends a HEAD request
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
     * Returns request body
     *
     * @return string
     */
    public function getContent()
    {
        return $this->response->getBody();
    }

    /**
     * Return request headers
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
}