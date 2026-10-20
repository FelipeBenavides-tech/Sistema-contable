<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Los tests no necesitan los archivos compilados de CSS/JS (npm run build)
        $this->withoutVite();
    }
}
