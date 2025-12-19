<?php

declare(strict_types=1);

namespace PromotionEngineBundle\Tests\Procedure;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PromotionEngineBundle\Entity\TimeLimitActivity;
use PromotionEngineBundle\Enum\ActivityType;
use PromotionEngineBundle\Param\CreateTimeLimitActivityParam;
use PromotionEngineBundle\Procedure\CreateTimeLimitActivity;
use PromotionEngineBundle\Repository\TimeLimitActivityRepository;
use Tourze\PHPUnitJsonRPC\AbstractProcedureTestCase;

/**
 * @internal
 */
#[CoversClass(CreateTimeLimitActivity::class)]
#[RunTestsInSeparateProcesses]
final class CreateTimeLimitActivityTest extends AbstractProcedureTestCase
{
    private CreateTimeLimitActivity $procedure;

    private TimeLimitActivityRepository $activityRepository;

    protected function onSetUp(): void
    {
        $this->activityRepository = self::getService(TimeLimitActivityRepository::class);
        $this->procedure = self::getService(CreateTimeLimitActivity::class);
    }

    public function testSuccessfulCreateActivity(): void
    {
        $param = new CreateTimeLimitActivityParam(
            name: '测试限时活动',
            description: '测试描述',
            startTime: (new \DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s'),
            endTime: (new \DateTimeImmutable('+2 hours'))->format('Y-m-d H:i:s'),
            activityType: ActivityType::LIMITED_TIME_DISCOUNT->value,
            productIds: ['product_1', 'product_2'],
            priority: 1,
            exclusive: false,
            preheatEnabled: false,
        );

        $result = $this->procedure->execute($param);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('activityId', $result);
        $this->assertNotEmpty($result['activityId']);
    }

    public function testCreateActivityWithInvalidTimeRange(): void
    {
        $param = new CreateTimeLimitActivityParam(
            name: '测试限时活动',
            description: '测试描述',
            startTime: (new \DateTimeImmutable('+2 hours'))->format('Y-m-d H:i:s'),
            endTime: (new \DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s'), // 结束时间早于开始时间
            activityType: ActivityType::LIMITED_TIME_DISCOUNT->value,
            productIds: ['product_1'],
            priority: 1,
            exclusive: false,
            preheatEnabled: false,
        );

        $result = $this->procedure->execute($param);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
    }

    public function testCreateActivityWithPreheat(): void
    {
        $param = new CreateTimeLimitActivityParam(
            name: '测试预热活动',
            description: '测试预热描述',
            startTime: (new \DateTimeImmutable('+2 hours'))->format('Y-m-d H:i:s'),
            endTime: (new \DateTimeImmutable('+3 hours'))->format('Y-m-d H:i:s'),
            activityType: ActivityType::LIMITED_TIME_SECKILL->value,
            productIds: ['product_1'],
            priority: 1,
            exclusive: true,
            totalLimit: 100,
            preheatEnabled: true,
            preheatStartTime: (new \DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s'),
        );

        $result = $this->procedure->execute($param);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        if (!isset($result['success']) || true !== $result['success']) {
            $message = $result['message'] ?? 'Unknown error';
            $messageStr = is_scalar($message) ? (string) $message : (is_object($message) ? method_exists($message, '__toString') ? (string) $message : 'Unknown error' : 'Unknown error');
            self::fail('Activity creation failed: ' . $messageStr);
        }
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('activityId', $result);

        // 验证活动确实被创建
        $activityId = $result['activityId'];
        $activity = $this->activityRepository->find($activityId);
        $this->assertInstanceOf(TimeLimitActivity::class, $activity);
        $this->assertEquals('测试预热活动', $activity->getName());
        $this->assertTrue($activity->isPreheatEnabled());
        $this->assertEquals(100, $activity->getTotalLimit());
    }

    public function testCreateActivityWithEmptyName(): void
    {
        $param = new CreateTimeLimitActivityParam(
            name: '',
            description: '测试描述',
            startTime: (new \DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s'),
            endTime: (new \DateTimeImmutable('+2 hours'))->format('Y-m-d H:i:s'),
            activityType: ActivityType::LIMITED_TIME_DISCOUNT->value,
            productIds: ['product_1'],
            priority: 1,
            exclusive: false,
            preheatEnabled: false,
        );

        $result = $this->procedure->execute($param);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
    }

    public function testExecuteMethodDirectly(): void
    {
        $param = new CreateTimeLimitActivityParam(
            name: '直接测试活动',
            description: '直接调用execute方法测试',
            startTime: (new \DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s'),
            endTime: (new \DateTimeImmutable('+3 hours'))->format('Y-m-d H:i:s'),
            activityType: ActivityType::LIMITED_TIME_DISCOUNT->value,
            productIds: ['direct_test_product_1', 'direct_test_product_2'],
            priority: 5,
            exclusive: true,
            preheatEnabled: false,
        );

        // 直接调用execute()方法
        $result = $this->procedure->execute($param);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);

        if (true === $result['success']) {
            $this->assertArrayHasKey('activityId', $result);
            $this->assertNotEmpty($result['activityId']);

            // 验证活动确实被创建了
            $activityId = $result['activityId'];
            $activity = $this->activityRepository->find($activityId);
            $this->assertInstanceOf(TimeLimitActivity::class, $activity);
            $this->assertEquals('直接测试活动', $activity->getName());
            $this->assertEquals('直接调用execute方法测试', $activity->getDescription());
            $this->assertTrue($activity->isExclusive());
            $this->assertEquals(5, $activity->getPriority());
        } else {
            $this->assertArrayHasKey('message', $result);
        }
    }

    public function testExecuteWithInvalidActivityType(): void
    {
        $param = new CreateTimeLimitActivityParam(
            name: '无效类型测试活动',
            description: '测试无效活动类型',
            startTime: (new \DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s'),
            endTime: (new \DateTimeImmutable('+2 hours'))->format('Y-m-d H:i:s'),
            activityType: 'invalid_activity_type',
            productIds: ['product_1'],
            priority: 1,
            exclusive: false,
            preheatEnabled: false,
        );

        // 直接调用execute()方法
        $result = $this->procedure->execute($param);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
        $this->assertIsString($result['message']);
        $this->assertStringContainsString('活动类型无效', $result['message']);
    }
}
