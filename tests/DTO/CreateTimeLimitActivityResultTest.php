<?php

declare(strict_types=1);

namespace PromotionEngineBundle\Tests\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PromotionEngineBundle\DTO\CreateTimeLimitActivityResult;

/**
 * @internal
 */
#[CoversClass(CreateTimeLimitActivityResult::class)]
final class CreateTimeLimitActivityResultTest extends TestCase
{
    public function testConstructor(): void
    {
        $result = new CreateTimeLimitActivityResult(
            activityId: 'activity-123',
            success: true,
            message: 'Custom message'
        );

        $this->assertEquals('activity-123', $result->activityId);
        $this->assertTrue($result->success);
        $this->assertEquals('Custom message', $result->message);
    }

    public function testConstructorWithDefaults(): void
    {
        $result = new CreateTimeLimitActivityResult(
            activityId: 'activity-456'
        );

        $this->assertEquals('activity-456', $result->activityId);
        $this->assertTrue($result->success);
        $this->assertNull($result->message);
    }

    public function testSuccess(): void
    {
        $result = CreateTimeLimitActivityResult::success('activity-789');

        $this->assertEquals('activity-789', $result->activityId);
        $this->assertTrue($result->success);
        $this->assertEquals('活动创建成功', $result->message);
    }

    public function testFailure(): void
    {
        $errorMessage = '活动创建失败：参数无效';
        $result = CreateTimeLimitActivityResult::failure($errorMessage);

        $this->assertEquals('', $result->activityId);
        $this->assertFalse($result->success);
        $this->assertEquals($errorMessage, $result->message);
    }

    public function testToArray(): void
    {
        $result = new CreateTimeLimitActivityResult(
            activityId: 'activity-123',
            success: true,
            message: 'Test message'
        );

        $expected = [
            'success' => true,
            'activityId' => 'activity-123',
            'message' => 'Test message',
        ];

        $this->assertEquals($expected, $result->toArray());
    }

    public function testToArrayWithNullMessage(): void
    {
        $result = new CreateTimeLimitActivityResult(
            activityId: 'activity-456',
            success: false
        );

        $expected = [
            'success' => false,
            'activityId' => 'activity-456',
            'message' => null,
        ];

        $this->assertEquals($expected, $result->toArray());
    }

    public function testJsonSerialize(): void
    {
        $result = new CreateTimeLimitActivityResult(
            activityId: 'activity-789',
            success: true,
            message: 'JSON test'
        );

        $expected = [
            'success' => true,
            'activityId' => 'activity-789',
            'message' => 'JSON test',
        ];

        $this->assertEquals($expected, $result->jsonSerialize());
    }

    public function testJsonSerializeMatchesToArray(): void
    {
        $result = new CreateTimeLimitActivityResult(
            activityId: 'activity-abc',
            success: false,
            message: 'Error occurred'
        );

        $this->assertEquals($result->toArray(), $result->jsonSerialize());
    }

    public function testIsSuccessWithSuccess(): void
    {
        $result = new CreateTimeLimitActivityResult(
            activityId: 'activity-1',
            success: true
        );

        $this->assertTrue($result->isSuccess());
    }

    public function testIsSuccessWithFailure(): void
    {
        $result = new CreateTimeLimitActivityResult(
            activityId: 'activity-2',
            success: false
        );

        $this->assertFalse($result->isSuccess());
    }

    public function testSuccessFactoryMethodIsSuccess(): void
    {
        $result = CreateTimeLimitActivityResult::success('activity-success');

        $this->assertTrue($result->isSuccess());
    }

    public function testFailureFactoryMethodIsSuccess(): void
    {
        $result = CreateTimeLimitActivityResult::failure('Failed to create');

        $this->assertFalse($result->isSuccess());
    }

    public function testJsonEncoding(): void
    {
        $result = new CreateTimeLimitActivityResult(
            activityId: 'activity-json',
            success: true,
            message: 'JSON encoding test'
        );

        $jsonString = json_encode($result);
        $this->assertIsString($jsonString);
        $decodedArray = json_decode($jsonString, true);

        $expected = [
            'success' => true,
            'activityId' => 'activity-json',
            'message' => 'JSON encoding test',
        ];

        $this->assertEquals($expected, $decodedArray);
    }

    public function testReadonlyProperties(): void
    {
        $result = new CreateTimeLimitActivityResult(
            activityId: 'readonly-test',
            success: true,
            message: 'Readonly test'
        );

        // Test that all properties are accessible (readonly)
        $this->assertEquals('readonly-test', $result->activityId);
        $this->assertTrue($result->success);
        $this->assertEquals('Readonly test', $result->message);
    }

    public function testSuccessWithEmptyActivityId(): void
    {
        // Edge case: success method should allow empty string if needed
        $result = CreateTimeLimitActivityResult::success('');

        $this->assertEquals('', $result->activityId);
        $this->assertTrue($result->success);
        $this->assertEquals('活动创建成功', $result->message);
    }

    public function testFailureWithEmptyMessage(): void
    {
        // Edge case: failure method with empty error message
        $result = CreateTimeLimitActivityResult::failure('');

        $this->assertEquals('', $result->activityId);
        $this->assertFalse($result->success);
        $this->assertEquals('', $result->message);
    }
}
