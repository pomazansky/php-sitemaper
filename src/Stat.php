<?php

namespace PhpSitemaper;

/**
 * Класс сбора статистики
 *
 * Class Stat
 * @package Sitemap
 */
class Stat
{
    /**
     * Идентификатор процесса
     *
     * @var string
     */
    private $id;

    /**
     * Статичтика по уровням вложености
     *
     * @var array
     */
    private $levels = [];

    /**
     * Время начала паринга
     *
     * @var float
     */
    private $started;

    /**
     * Время окончания парсинга
     *
     * @var float
     */
    private $ended;

    /**
     * Установка идентификатора процесса при создании
     *
     * @param string $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * Установка времени начала парсинга
     */
    public function setStart()
    {
        $this->started = microtime(true);
    }

    /**
     * Создание нового уровня парсинга
     *
     * @param int $level
     */
    public function newLevel($level)
    {
        $this->levels[$level] = ['scanned' => 0, 'added' => 0];
    }

    /**
     * Увеличение счетчика просканированых страниц
     *
     * @param int $level
     */
    public function oneScanned($level)
    {
        $this->levels[$level]['scanned']++;
    }

    /**
     * Увеличение счетчика додавленных страниц
     *
     * @param int $level
     */
    public function oneAdded($level)
    {
        $this->levels[$level]['added']++;
        $this->saveToFile();
    }

    /**
     * Сохраниние данных в файл
     */
    private function saveToFile()
    {
        file_put_contents('cache/stats/' . $this->id, json_encode([
            'started' => $this->started,
            'ended' => $this->ended,
            'levels' => $this->levels
        ]));
    }

    /**
     * Установка количества ссылок в очереди для конкретного уровня парсинга
     *
     * @param int $level
     * @param int $num
     */
    public function inQueue($level, $num)
    {
        $this->levels[$level]['inQueue'] = $num;
    }

    /**
     * Установка времени окончания парсинга
     */
    public function setEnd()
    {
        $this->ended = microtime(true);
        $this->saveToFile();
    }

}