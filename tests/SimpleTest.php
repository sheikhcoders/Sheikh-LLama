<?php

use PHPUnit\Framework\TestCase;

class SimpleTest extends TestCase
{
    public function testApiFileExists()
    {
        $this->assertFileExists(__DIR__ . '/../api.php');
    }

    public function testIndexFileExists()
    {
        $this->assertFileExists(__DIR__ . '/../index.php');
    }

    public function testComposerFileExists()
    {
        $this->assertFileExists(__DIR__ . '/../composer.json');
    }
}
