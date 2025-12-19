<?php

namespace PromotionEngineBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PromotionEngineBundle\Exception\ActivityException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(ActivityException::class)]
final class ActivityExceptionTest extends AbstractExceptionTestCase
{
    public function testConstructor(): void
    {
        $message = 'Test activity exception message';
        $code = 400;
        $previous = new \Exception('Previous exception');

        $exception = new ActivityException($message, $code, $previous);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testDefaultValues(): void
    {
        $exception = new ActivityException('Test message');

        $this->assertSame('Test message', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testInheritance(): void
    {
        $exception = new ActivityException('Test');

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
    }
}
