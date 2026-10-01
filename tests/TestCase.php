<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Assert;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Assertions over records supplied to React; browser checks cover rendered controls.
        TestResponse::macro('assertWorkshopDataContains', function (string $text) {
            $props = $this->viewData('page')['props'];
            unset($props['auth'], $props['flash'], $props['workOrderStatuses'], $props['appointmentStatuses']);
            Assert::assertStringContainsString($text, json_encode($props, JSON_UNESCAPED_UNICODE));

            return $this;
        });
        TestResponse::macro('assertWorkshopDataMissing', function (string $text) {
            $props = $this->viewData('page')['props'];
            unset($props['auth'], $props['flash'], $props['workOrderStatuses'], $props['appointmentStatuses']);
            Assert::assertStringNotContainsString($text, json_encode($props, JSON_UNESCAPED_UNICODE));

            return $this;
        });
    }
}
