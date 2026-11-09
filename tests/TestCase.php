<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Pages render without a frontend build (npm run build is not needed for tests).
        $this->withoutVite();
    }
}
