<?php

declare(strict_types=1);

namespace TextSummarizer\Tests\Exceptions;

use PHPUnit\Framework\TestCase;
use TextSummarizer\Exceptions\TextProcessingException;

/**
 * @covers \TextSummarizer\Exceptions\TextProcessingException
 */
final class TextProcessingExceptionTest extends TestCase
{
    public function testExceptionCanBeCreated(): void
    {
        $exception = new TextProcessingException('Test error message');
        
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals('Test error message', $exception->getMessage());
    }

    public function testExceptionWithCodeAndPrevious(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = new TextProcessingException('Test error', 123, $previous);
        
        $this->assertEquals('Test error', $exception->getMessage());
        $this->assertEquals(123, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}