<?php

declare(strict_types=1);

namespace PromotionEngineBundle\Tests\Param;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PromotionEngineBundle\Param\CreateTimeLimitActivityParam;
use Symfony\Component\Validator\Validation;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;

/**
 * CreateTimeLimitActivityParam 单元测试
 *
 * @internal
 */
#[CoversClass(CreateTimeLimitActivityParam::class)]
final class CreateTimeLimitActivityParamTest extends TestCase
{
    public function testImplementsRpcParamInterface(): void
    {
        $param = new CreateTimeLimitActivityParam(
            name: '春季促销活动',
            description: '全场商品8折优惠',
            startTime: '2024-03-01 00:00:00',
            endTime: '2024-03-31 23:59:59',
            activityType: 'limited_time_discount',
            productIds: ['PROD001', 'PROD002'],
        );

        $this->assertInstanceOf(RpcParamInterface::class, $param);
    }

    public function testConstructorWithRequiredValues(): void
    {
        $param = new CreateTimeLimitActivityParam(
            name: '春季促销活动',
            description: '全场商品8折优惠',
            startTime: '2024-03-01 00:00:00',
            endTime: '2024-03-31 23:59:59',
            activityType: 'limited_time_discount',
            productIds: ['PROD001', 'PROD002'],
        );

        $this->assertSame('春季促销活动', $param->name);
        $this->assertSame('全场商品8折优惠', $param->description);
        $this->assertSame('2024-03-01 00:00:00', $param->startTime);
        $this->assertSame('2024-03-31 23:59:59', $param->endTime);
        $this->assertSame('limited_time_discount', $param->activityType);
        $this->assertSame(['PROD001', 'PROD002'], $param->productIds);
        $this->assertSame(0, $param->priority);
        $this->assertFalse($param->exclusive);
        $this->assertNull($param->totalLimit);
        $this->assertFalse($param->preheatEnabled);
        $this->assertNull($param->preheatStartTime);
    }

    public function testConstructorWithAllValues(): void
    {
        $param = new CreateTimeLimitActivityParam(
            name: '春季促销活动',
            description: '全场商品8折优惠',
            startTime: '2024-03-01 00:00:00',
            endTime: '2024-03-31 23:59:59',
            activityType: 'limited_quantity_purchase',
            productIds: ['PROD001', 'PROD002'],
            priority: 10,
            exclusive: true,
            totalLimit: 1000,
            preheatEnabled: true,
            preheatStartTime: '2024-02-28 00:00:00',
        );

        $this->assertSame(10, $param->priority);
        $this->assertTrue($param->exclusive);
        $this->assertSame(1000, $param->totalLimit);
        $this->assertTrue($param->preheatEnabled);
        $this->assertSame('2024-02-28 00:00:00', $param->preheatStartTime);
    }

    public function testClassIsReadonly(): void
    {
        $reflection = new \ReflectionClass(CreateTimeLimitActivityParam::class);

        $this->assertTrue($reflection->isReadOnly());
    }

    public function testValidationPassesWithValidData(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $param = new CreateTimeLimitActivityParam(
            name: '春季促销活动',
            description: '全场商品8折优惠',
            startTime: '2024-03-01 00:00:00',
            endTime: '2024-03-31 23:59:59',
            activityType: 'limited_time_discount',
            productIds: ['PROD001', 'PROD002'],
        );
        $violations = $validator->validate($param);

        $this->assertCount(0, $violations);
    }

    public function testValidationFailsWithEmptyName(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $param = new CreateTimeLimitActivityParam(
            name: '',
            description: '全场商品8折优惠',
            startTime: '2024-03-01 00:00:00',
            endTime: '2024-03-31 23:59:59',
            activityType: 'limited_time_discount',
            productIds: ['PROD001'],
        );
        $violations = $validator->validate($param);

        $this->assertGreaterThan(0, count($violations));
    }

    public function testValidationFailsWithEmptyProductIds(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $param = new CreateTimeLimitActivityParam(
            name: '春季促销活动',
            description: '全场商品8折优惠',
            startTime: '2024-03-01 00:00:00',
            endTime: '2024-03-31 23:59:59',
            activityType: 'limited_time_discount',
            productIds: [],
        );
        $violations = $validator->validate($param);

        $this->assertGreaterThan(0, count($violations));
    }
}
