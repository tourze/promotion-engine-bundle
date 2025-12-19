<?php

namespace PromotionEngineBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use PromotionEngineBundle\DTO\CalculateActivityDiscountInput;
use PromotionEngineBundle\DTO\CalculateActivityDiscountItem;
use PromotionEngineBundle\Entity\ActivityProduct;
use PromotionEngineBundle\Entity\TimeLimitActivity;
use PromotionEngineBundle\Enum\ActivityStatus;
use PromotionEngineBundle\Enum\ActivityType;
use PromotionEngineBundle\Repository\ActivityProductRepository;
use PromotionEngineBundle\Service\ActivityDiscountService;
use PromotionEngineBundle\Service\DiscountCalculatorService;
use PromotionEngineBundle\Service\DiscountLimitService;
use PromotionEngineBundle\Service\DiscountStackingService;
use Psr\Log\LoggerInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(ActivityDiscountService::class)]
#[RunTestsInSeparateProcesses]
final class ActivityDiscountServiceTest extends AbstractIntegrationTestCase
{
    private ActivityDiscountService $service;

    private MockObject $activityProductRepository;

    private MockObject $discountCalculatorService;

    private MockObject $discountStackingService;

    private MockObject $discountLimitService;

    private MockObject $logger;

    protected function onSetUp(): void
    {
        $this->activityProductRepository = $this->createMock(ActivityProductRepository::class);
        $this->discountCalculatorService = $this->createMock(DiscountCalculatorService::class);
        $this->discountStackingService = $this->createMock(DiscountStackingService::class);
        $this->discountLimitService = $this->createMock(DiscountLimitService::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        // 将Mock服务注入到容器中
        self::getContainer()->set(ActivityProductRepository::class, $this->activityProductRepository);
        self::getContainer()->set(DiscountCalculatorService::class, $this->discountCalculatorService);
        self::getContainer()->set(DiscountStackingService::class, $this->discountStackingService);
        self::getContainer()->set(DiscountLimitService::class, $this->discountLimitService);
        self::getContainer()->set('monolog.logger.promotion_engine', $this->logger);

        // 从容器中获取服务实例
        $this->service = self::getService(ActivityDiscountService::class);
    }

    public function testCalculateDiscountWithEmptyItems(): void
    {
        $input = new CalculateActivityDiscountInput([]);

        $result = $this->service->calculateDiscount($input);

        $this->assertFalse($result->success);
        $this->assertSame('商品列表为空', $result->message);
    }

    public function testCalculateDiscountWithNoActiveActivities(): void
    {
        $items = [
            new CalculateActivityDiscountItem('product1', 'sku1', 1, 100.0),
        ];
        $input = new CalculateActivityDiscountInput($items);

        $this->activityProductRepository
            ->expects($this->once())
            ->method('findActiveByProductIds')
            ->with(['product1'])
            ->willReturn([])
        ;

        $result = $this->service->calculateDiscount($input);

        $this->assertTrue($result->success);
        $this->assertSame(100.0, $result->originalTotalAmount);
        $this->assertSame(0.0, $result->discountTotalAmount);
        $this->assertSame(100.0, $result->finalTotalAmount);
    }

    public function testCalculateDiscountWithValidActivity(): void
    {
        $items = [
            new CalculateActivityDiscountItem('product1', 'sku1', 1, 100.0),
        ];
        $input = new CalculateActivityDiscountInput($items, 'user123');

        $activity = $this->createMockActivity();
        $activityProduct = $this->createMockActivityProduct('product1', $activity);

        $this->activityProductRepository
            ->expects($this->once())
            ->method('findActiveByProductIds')
            ->with(['product1'])
            ->willReturn([$activityProduct])
        ;

        $this->discountStackingService
            ->expects($this->once())
            ->method('filterStackableActivities')
            ->with([$activity], $input)
            ->willReturn([$activity])
        ;

        $this->activityProductRepository
            ->expects($this->once())
            ->method('findActiveByProductId')
            ->with('product1')
            ->willReturn($activityProduct)
        ;

        $this->discountCalculatorService
            ->expects($this->once())
            ->method('calculateDiscount')
            ->with($activity, $activityProduct, $items[0], 'user123')
            ->willReturn(20.0)
        ;

        $this->discountLimitService
            ->expects($this->once())
            ->method('validateDiscountLimits')
            ->willReturn([
                'valid' => true,
                'adjustedDiscount' => 20.0,
                'limitReasons' => [],
                'originalDiscount' => 20.0,
            ])
        ;

        $this->discountLimitService
            ->expects($this->once())
            ->method('validateOrderLimits')
            ->willReturn([
                'valid' => true,
                'adjustedTotalDiscount' => 20.0,
                'limitReasons' => [],
            ])
        ;

        $result = $this->service->calculateDiscount($input);

        $this->assertTrue($result->success);
        $this->assertSame(100.0, $result->originalTotalAmount);
        $this->assertSame(20.0, $result->discountTotalAmount);
        $this->assertSame(80.0, $result->finalTotalAmount);
        $this->assertCount(1, $result->items);
        $this->assertCount(0, $result->appliedActivities);
    }

    public function testCalculateDiscountWithLimitAdjustment(): void
    {
        $items = [
            new CalculateActivityDiscountItem('product1', 'sku1', 5, 100.0),
        ];
        $input = new CalculateActivityDiscountInput($items, 'user123');

        $activity = $this->createMockActivity();
        $activityProduct = $this->createMockActivityProduct('product1', $activity);

        $this->activityProductRepository
            ->expects($this->once())
            ->method('findActiveByProductIds')
            ->willReturn([$activityProduct])
        ;

        $this->discountStackingService
            ->expects($this->once())
            ->method('filterStackableActivities')
            ->willReturn([$activity])
        ;

        $this->activityProductRepository
            ->expects($this->once())
            ->method('findActiveByProductId')
            ->willReturn($activityProduct)
        ;

        $this->discountCalculatorService
            ->expects($this->once())
            ->method('calculateDiscount')
            ->willReturn(100.0)
        ;

        $this->discountLimitService
            ->expects($this->once())
            ->method('validateDiscountLimits')
            ->willReturn([
                'valid' => true,
                'adjustedDiscount' => 50.0,
                'limitReasons' => [
                    ['type' => 'per_user_quantity_limit', 'limit' => 3, 'requested' => 5, 'allowed' => 3],
                ],
                'originalDiscount' => 100.0,
            ])
        ;

        $this->discountLimitService
            ->expects($this->once())
            ->method('validateOrderLimits')
            ->willReturn([
                'valid' => true,
                'adjustedTotalDiscount' => 50.0,
                'limitReasons' => [],
            ])
        ;

        $result = $this->service->calculateDiscount($input);

        $this->assertTrue($result->success);
        $this->assertSame(50.0, $result->discountTotalAmount);
        $this->assertSame(450.0, $result->finalTotalAmount);

        $item = $result->items[0];
        $this->assertCount(0, $item->appliedActivities);
    }

    public function testCalculateDiscountWithOrderLimitAdjustment(): void
    {
        $items = [
            new CalculateActivityDiscountItem('product1', 'sku1', 1, 1000.0),
        ];
        $input = new CalculateActivityDiscountInput($items, 'user123');

        $activity = $this->createMockActivity();
        $activityProduct = $this->createMockActivityProduct('product1', $activity);

        $this->activityProductRepository
            ->expects($this->once())
            ->method('findActiveByProductIds')
            ->willReturn([$activityProduct])
        ;

        $this->discountStackingService
            ->expects($this->once())
            ->method('filterStackableActivities')
            ->willReturn([$activity])
        ;

        $this->activityProductRepository
            ->expects($this->once())
            ->method('findActiveByProductId')
            ->willReturn($activityProduct)
        ;

        $this->discountCalculatorService
            ->expects($this->once())
            ->method('calculateDiscount')
            ->willReturn(900.0)
        ;

        $this->discountLimitService
            ->expects($this->once())
            ->method('validateDiscountLimits')
            ->willReturn([
                'valid' => true,
                'adjustedDiscount' => 900.0,
                'limitReasons' => [],
                'originalDiscount' => 900.0,
            ])
        ;

        $this->discountLimitService
            ->expects($this->once())
            ->method('validateOrderLimits')
            ->willReturn([
                'valid' => true,
                'adjustedTotalDiscount' => 800.0,
                'limitReasons' => [
                    ['type' => 'order_discount_rate_limit', 'maxRate' => 80.0],
                ],
            ])
        ;

        $result = $this->service->calculateDiscount($input);

        $this->assertTrue($result->success);
        $this->assertSame(800.0, $result->discountTotalAmount);
        $this->assertSame(200.0, $result->finalTotalAmount);
        $this->assertCount(2, $result->discountDetails);

        // 验证第一个折扣详情是活动折扣
        $this->assertSame('activity123', $result->discountDetails[0]->activityId);
        $this->assertSame(900.0, $result->discountDetails[0]->discountAmount);

        // 验证第二个折扣详情是系统限制调整
        $this->assertSame('system', $result->discountDetails[1]->activityId);
        $this->assertSame('系统优惠上限控制', $result->discountDetails[1]->activityName);
        $this->assertSame(-100.0, $result->discountDetails[1]->discountAmount);
    }

    public function testCalculateDiscountWithException(): void
    {
        $items = [
            new CalculateActivityDiscountItem('product1', 'sku1', 1, 100.0),
        ];
        $input = new CalculateActivityDiscountInput($items);

        $this->activityProductRepository
            ->expects($this->once())
            ->method('findActiveByProductIds')
            ->willThrowException(new \RuntimeException('Database error'))
        ;

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with('活动优惠计算失败')
        ;

        $result = $this->service->calculateDiscount($input);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('计算失败', $result->message);
    }

    private function createMockActivity(): TimeLimitActivity
    {
        $activity = $this->createMock(TimeLimitActivity::class);
        $activity->method('getId')->willReturn('activity123');
        $activity->method('getName')->willReturn('测试活动');
        $activity->method('getActivityType')->willReturn(ActivityType::LIMITED_TIME_DISCOUNT);
        $activity->method('getStatus')->willReturn(ActivityStatus::ACTIVE);
        $activity->method('isValid')->willReturn(true);
        $activity->method('isActive')->willReturn(true);
        $activity->method('getProductIds')->willReturn(['product1']);
        $activity->method('getPriority')->willReturn(10);
        $activity->method('isExclusive')->willReturn(false);
        $activity->method('getStartTime')->willReturn(new \DateTimeImmutable('2024-01-01'));
        $activity->method('getEndTime')->willReturn(new \DateTimeImmutable('2024-12-31'));

        return $activity;
    }

    private function createMockActivityProduct(string $productId, TimeLimitActivity $activity): ActivityProduct
    {
        $activityProduct = $this->createMock(ActivityProduct::class);
        $activityProduct->method('getProductId')->willReturn($productId);
        $activityProduct->method('getActivity')->willReturn($activity);
        $activityProduct->method('getActivityPrice')->willReturn('80.00');
        $activityProduct->method('getLimitPerUser')->willReturn(10);
        $activityProduct->method('getRemainingStock')->willReturn(100);

        return $activityProduct;
    }
}
