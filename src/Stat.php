<?php

namespace PhpSitemaper;

/**
 * Statistic gethering class
 *
 * Class Stat
 * @package Sitemap
 */
class Stat
{
    /**
     * Session id
     *
     * @var string
     */
    private $sessionId;

    /**
     * Nesting levels stat
     *
     * @var array
     */
    private $levels = [];

    /**
     * Parsing start time
     *
     * @var float
     */
    private $started;

    /**
     * Parsing finish time
     *
     * @var float
     */
    private $ended;

    /**
     * Sets process id on creation
     *
     * @param string $sessionId
     */
    public function __construct($sessionId)
    {
        $this->sessionId = $sessionId;
    }

    /**
     * Sets parsing start time
     */
    public function setStart()
    {
        $this->started = microtime(true);
    }

    /**
     * Sets new nesting level
     *
     * @param int $level
     */
    public function newLevel($level)
    {
        $this->levels[$level] = ['scanned' => 0, 'added' => 0];
    }

    /**
     * Pings scanned counter
     *
     * @param int $level
     */
    public function oneScanned($level)
    {
        $this->levels[$level]['scanned']++;
    }

    /**
     * Pings added counter
     *
     * @param int $level
     */
    public function oneAdded($level)
    {
        $this->levels[$level]['added']++;
        $this->saveToFile();
    }

    /**
     * Saves data to file
     */
    private function saveToFile()
    {
        file_put_contents(__DIR__ . '/../var/cache/stats/' . $this->sessionId, json_encode([
            'started' => $this->started,
            'ended' => $this->ended,
            'levels' => $this->levels
        ]));
    }

    /**
     * Sets quantity of links in queue on nesting level
     *
     * @param int $level
     * @param int $num
     */
    public function inQueue($level, $num)
    {
        $this->levels[$level]['inQueue'] = $num;
    }

    /**
     * Sets parse finish time
     */
    public function setEnd()
    {
        $this->ended = microtime(true);
        $this->saveToFile();
    }
}
