<?php

namespace PhpSitemaper\Fetchers;

use GuzzleHttp\Client;
use GuzzleHttp\Event\CompleteEvent;
use GuzzleHttp\Pool;

/**
 * Class GuzzleAdapter
 * @package PhpSitemaper\Fetchers
 */
class GuzzleAdapter implements MultiFetcherInterface
{

    /**
     * Base URL
     *
     * @var string
     */
    private $baseUrl;

    /**
     * Guzzle Client
     *
     * @var Client
     */
    private $client;

    /**
     * Head Pool complete callback
     *
     * @var callable
     */
    private $onHeadComplete;

    /**
     * Get Pool complete callback
     *
     * @var callable
     */
    private $onGetComplete;

    /**
     * Initialises Guzzle Client
     */
    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * Sets the base URL
     *
     * @param string $baseUrl
     */
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * Does multiple HTTP HEAD requests
     *
     * @param array    $urls
     * @param callable $complete
     */
    public function headPool(array $urls, callable $complete)
    {
        $this->onHeadComplete = $complete;

        $requests = [];

        foreach ($urls as $url) {
            $requests[] = $this->client->createRequest('HEAD', $this->baseUrl . $url);
        }

        Pool::send($this->client, $requests, [
            'complete' => [$this, 'onHeadComplete']
        ]);

    }

    /**
     * Callback method for HTTP HEAD request success
     *
     * @param CompleteEvent $event
     */
    public function onHeadComplete(CompleteEvent $event)
    {
        $url = parse_url($event->getRequest()->getUrl());
        $query = !empty($url['query']) ? '?' . $url['query'] : '';

        $headers = [];
        foreach ($event->getResponse()->getHeaders() as $name => $values) {
            $headers[] = $name . ': ' . implode(', ', $values);
        }

        call_user_func_array($this->onHeadComplete, [$url['path'] . $query, $headers]);
    }

    /**
     * Does multiple HTTP GET requests
     *
     * @param array $urls
     * @param callable $complete
     */
    public function getPool(array $urls, callable $complete)
    {
        $this->onGetComplete = $complete;

        $requests = [];

        foreach ($urls as $url) {
            $requests[] = $this->client->createRequest('GET', $this->baseUrl . $url);
        }

        Pool::send($this->client, $requests, [
            'complete' => [$this, 'onGetComplete']
        ]);
    }

    /**
     * Callback for HTTP GET request success
     *
     * @param CompleteEvent $event
     */
    public function onGetComplete(CompleteEvent $event)
    {
        $url = parse_url($event->getRequest()->getUrl());
        $query = !empty($url['query']) ? '?' . $url['query'] : '';

        $html = $event->getResponse()->getBody();

        call_user_func_array($this->onGetComplete, [$url['path'] . $query, $html]);
    }
}
