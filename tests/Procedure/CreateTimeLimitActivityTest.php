<?php

namespace PromotionEngineBundle\Tests\Procedure;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PromotionEngineBundle\Entity\TimeLimitActivity;
use PromotionEngineBundle\Enum\ActivityType;
use PromotionEngineBundle\Procedure\CreateTimeLimitActivity;
use PromotionEngineBundle\Repository\TimeLimitActivityRepository;
use Tourze\JsonRPC\Core\Tests\AbstractProcedureTestCase;

/**
 * @internal
 */
#[CoversClass(CreateTimeLimitActivity::class)]
#[RunTestsInSeparateProcesses]
class CreateTimeLimitActivityTest extends AbstractProcedureTestCase
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
        $this->procedure->name = '测试限时活动';
        $this->procedure->description = '测试描述';
        $this->procedure->startTime = (new \DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s');
        $this->procedure->endTime = (new \DateTimeImmutable('+2 hours'))->format('Y-m-d H:i:s');
        $this->procedure->activityType = ActivityType::LIMITED_TIME_DISCOUNT->value;
        $this->procedure->productIds = ['product_1', 'product_2'];
        $this->procedure->priority = 1;
        $this->procedure->exclusive = false;
        $this->procedure->preheatEnabled = false;
        $this->procedure->preheatStartTime = null;
        $this->procedure->totalLimit = null;

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('activityId', $result);
        $this->assertNotEmpty($result['activityId']);
    }

    public function testCreateActivityWithInvalidTimeRange(): void
    {
        $this->procedure->name = '测试限时活动';
        $this->procedure->description = '测试描述';
        $this->procedure->startTime = (new \DateTimeImmutable('+2 hours'))->format('Y-m-d H:i:s');
        $this->procedure->endTime = (new \DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s'); // 结束时间早于开始时间
        $this->procedure->activityType = ActivityType::LIMITED_TIME_DISCOUNT->value;
        $this->procedure->productIds = ['product_1'];
        $this->procedure->priority = 1;
        $this->procedure->exclusive = false;
        $this->procedure->preheatEnabled = false;
        $this->procedure->preheatStartTime = null;
        $this->procedure->totalLimit = null;

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
    }

    public function testCreateActivityWithPreheat(): void
    {
        $this->procedure->name = '测试预热活动';
        $this->procedure->description = '测试预热描述';
        $this->procedure->startTime = (new \DateTimeImmutable('+2 hours'))->format('Y-m-d H:i:s');
        $this->procedure->endTime = (new \DateTimeImmutable('+3 hours'))->format('Y-m-d H:i:s');
        $this->procedure->activityType = ActivityType::LIMITED_TIME_SECKILL->value;
        $this->procedure->productIds = ['product_1'];
        $this->procedure->priority = 1;
        $this->procedure->exclusive = true;
        $this->procedure->preheatEnabled = true;
        $this->procedure->preheatStartTime = (new \DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s');
        $this->procedure->totalLimit = 100;

        $result = $this->procedure->execute();

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
        $this->procedure->name = '';
        $this->procedure->description = '测试描述';
        $this->procedure->startTime = (new \DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s');
        $this->procedure->endTime = (new \DateTimeImmutable('+2 hours'))->format('Y-m-d H:i:s');
        $this->procedure->activityType = ActivityType::LIMITED_TIME_DISCOUNT->value;
        $this->procedure->productIds = ['product_1'];
        $this->procedure->priority = 1;
        $this->procedure->exclusive = false;
        $this->procedure->preheatEnabled = false;
        $this->procedure->preheatStartTime = null;
        $this->procedure->totalLimit = null;

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
    }

    public function testExecuteMethodDirectly(): void
    {
        $this->procedure->name = '直接测试活动';
        $this->procedure->description = '直接调用execute方法测试';
        $this->procedure->startTime = (new \DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s');
        $this->procedure->endTime = (new \DateTimeImmutable('+3 hours'))->format('Y-m-d H:i:s');
        $this->procedure->activityType = ActivityType::LIMITED_TIME_DISCOUNT->value;
        $this->procedure->productIds = ['direct_test_product_1', 'direct_test_product_2'];
        $this->procedure->priority = 5;
        $this->procedure->exclusive = true;
        $this->procedure->preheatEnabled = false;
        $this->procedure->preheatStartTime = null;
        $this->procedure->totalLimit = null;

        // 直接调用execute()方法
        $result = $this->procedure->execute();

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
        $this->procedure->name = '无效类型测试活动';
        $this->procedure->description = '测试无效活动类型';
        $this->procedure->startTime = (new \DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s');
        $this->procedure->endTime = (new \DateTimeImmutable('+2 hours'))->format('Y-m-d H:i:s');
        $this->procedure->activityType = 'invalid_activity_type';
        $this->procedure->productIds = ['product_1'];
        $this->procedure->priority = 1;
        $this->procedure->exclusive = false;
        $this->procedure->preheatEnabled = false;
        $this->procedure->preheatStartTime = null;
        $this->procedure->totalLimit = null;

        // 直接调用execute()方法
        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
        $this->assertIsString($result['message']);
        $this->assertStringContainsString('活动类型无效', $result['message']);
    }
}
