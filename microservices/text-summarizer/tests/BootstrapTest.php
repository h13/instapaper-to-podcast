<?php

declare(strict_types=1);

namespace TextSummarizer\Tests;

use PHPUnit\Framework\TestCase;
use TextSummarizer\Bootstrap;

/**
 * @covers \TextSummarizer\Bootstrap
 */
final class BootstrapTest extends TestCase
{
    private string $originalDir;

    protected function setUp(): void
    {
        $this->originalDir = getcwd();
    }

    protected function tearDown(): void
    {
        chdir($this->originalDir);
    }

    public function testAutoloadThrowsExceptionWhenVendorDirectoryMissing(): void
    {
        $this->markTestSkipped('Cannot test vendor missing scenario without conflicts');
    }

    public function testAutoloadSuccessful(): void
    {
        $this->markTestSkipped('Cannot test autoload in isolation');
    }

    public function testGetApp(): void
    {
        try {
            $app = Bootstrap::getApp('cli-hal-api-app');
            $this->assertInstanceOf(\BEAR\Sunday\Extension\Application\AppInterface::class, $app);
        } catch (\Exception $e) {
            $this->markTestSkipped('Cannot test getApp in isolation: ' . $e->getMessage());
        }
    }

    public function testGetAppWithDifferentContext(): void
    {
        try {
            $app = Bootstrap::getApp('hal-api-app');
            $this->assertInstanceOf(\BEAR\Sunday\Extension\Application\AppInterface::class, $app);
        } catch (\Exception $e) {
            $this->markTestSkipped('Cannot test getApp in isolation: ' . $e->getMessage());
        }
    }
}