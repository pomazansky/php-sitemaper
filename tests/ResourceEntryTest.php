<?php

namespace PhpSitemaper\Tests;

use PhpSitemaper\ResourceEntry;
use PhpSitemaper\SitemapConfig;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Class ResourceEntryTest
 * @package PhpSitemaper\Tests
 */
class ResourceEntryTest extends \PHPUnit_Framework_TestCase
{
    public function testConstrucParams()
    {
        $url = '/test/path';
        $config = new SitemapConfig([]);
        $level = 1;
        $resource = new ResourceEntry($url, $config, $level);
        $this->assertEquals($url, $resource->getUrl());
    }
}
