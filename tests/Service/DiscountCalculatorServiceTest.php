<?php

namespace PromotionEngineBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PromotionEngineBundle\DTO\CalculateActivityDiscountItem;
use PromotionEngineBundle\Entity\ActivityProduct;
use PromotionEngineBundle\Entity\DiscountRule;
use PromotionEngineBundle\Entity\TimeLimitActivity;
use PromotionEngineBundle\Enum\ActivityType;
use PromotionEngineBundle\Enum\DiscountType;
use PromotionEngineBundle\Service\DiscountCalculatorService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[RunTestsInSeparateProcesses]
#[CoversClass(DiscountCalculatorService::class)]
class DiscountCalculatorServiceTest extends AbstractIntegrationTestCase
{
    private DiscountCalculatorService $service;

    protected function onSetUp(): void
    {
        $this->service = self::getService(DiscountCalculatorService::class);
    }

    public function testCalculateDiscount(): void
    {
        $activity = $this->createTestActivity();
        $activityProduct = $this->createTestActivityProduct($activity);
        $item = new CalculateActivityDiscountItem(
            productId: 'product_1',
            skuId: 'sku_1',
            quantity: 2,
            price: 100.0
        );

        $result = $this->service->calculateDiscount($activity, $activityProduct, $item, 'user_123');

        $this->assertIsFloat($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    public function testCalculateDiscountWithNullUser(): void
    {
        $activity = $this->createTestActivity();
        $activityProduct = $this->createTestActivityProduct($activity);
        $item = new CalculateActivityDiscountItem(
            productId: 'product_1',
            skuId: 'sku_1',
            quantity: 1,
            price: 50.0
        );

        $result = $this->service->calculateDiscount($activity, $activityProduct, $item, null);

        $this->assertIsFloat($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    public function testCalculateDiscountForDifferentActivityTypes(): void
    {
        $activityTypes = [
            ActivityType::LIMITED_TIME_DISCOUNT,
            ActivityType::LIMITED_TIME_SECKILL,
            ActivityType::LIMITED_QUANTITY_PURCHASE,
        ];

        $item = new CalculateActivityDiscountItem(
            productId: 'product_1',
            skuId: 'sku_1',
            quantity: 1,
            price: 100.0
        );

        foreach ($activityTypes as $activityType) {
            $activity = $this->createTestActivity($activityType);
            $activityProduct = $this->createTestActivityProduct($activity);

            $result = $this->service->calculateDiscount($activity, $activityProduct, $item, 'user_123');

            $this->assertIsFloat($result);
            $this->assertGreaterThanOrEqual(0, $result);
        }
    }

    public function testCalculateDiscountWithZeroPrice(): void
    {
        $activity = $this->createTestActivity();
        $activityProduct = $this->createTestActivityProduct($activity);
        $item = new CalculateActivityDiscountItem(
            productId: 'product_1',
            skuId: 'sku_1',
            quantity: 1,
            price: 0.0
        );

        $result = $this->service->calculateDiscount($activity, $activityProduct, $item, 'user_123');

        $this->assertIsFloat($result);
        $this->assertEquals(0.0, $result);
    }

    public function testEstimateMaxDiscount(): void
    {
        $activity = $this->createTestActivity();

        // 创建一个模拟的折扣规则
        $discountRule = $this->createMockDiscountRule();

        $maxDiscount = $this->service->estimateMaxDiscount($discountRule, 1000.0, 10);

        $this->assertIsFloat($maxDiscount);
        $this->assertGreaterThanOrEqual(0.0, $maxDiscount);
    }

    public function testEstimateMaxDiscountWithZeroAmount(): void
    {
        $discountRule = $this->createMockDiscountRule();

        $maxDiscount = $this->service->estimateMaxDiscount($discountRule, 0.0, 1);

        $this->assertSame(0.0, $maxDiscount);
    }

    public function testValidateRules(): void
    {
        $rules = [$this->createMockDiscountRule()];
        $item = new CalculateActivityDiscountItem(
            productId: 'product_validate',
            skuId: 'sku_validate',
            quantity: 2,
            price: 100.0
        );

        $isValid = $this->service->validateRules($rules, $item);

        $this->assertIsBool($isValid);
    }

    public function testValidateRulesWithEmptyRules(): void
    {
        $item = new CalculateActivityDiscountItem(
            productId: 'product_empty',
            skuId: 'sku_empty',
            quantity: 1,
            price: 50.0
        );

        $isValid = $this->service->validateRules([], $item);

        $this->assertTrue($isValid); // 空规则列表应该通过验证
    }

    public function testValidateRulesWithMultipleRules(): void
    {
        $rules = [
            $this->createMockDiscountRule(),
            $this->createMockDiscountRule(),
        ];

        $item = new CalculateActivityDiscountItem(
            productId: 'product_multi',
            skuId: 'sku_multi',
            quantity: 3,
            price: 75.0
        );

        $isValid = $this->service->validateRules($rules, $item);

        $this->assertIsBool($isValid);
    }

    public function testGetApplicableRules(): void
    {
        $rules = [
            $this->createMockDiscountRule(),
            $this->createMockDiscountRule(),
        ];

        $item = new CalculateActivityDiscountItem(
            productId: 'product_applicable',
            skuId: 'sku_applicable',
            quantity: 2,
            price: 150.0
        );

        $applicableRules = $this->service->getApplicableRules($rules, $item);

        $this->assertIsArray($applicableRules);
        foreach ($applicableRules as $rule) {
            $this->assertInstanceOf(DiscountRule::class, $rule);
        }
    }

    private function createTestActivity(ActivityType $activityType = ActivityType::LIMITED_TIME_DISCOUNT): TimeLimitActivity
    {
        $activity = new TimeLimitActivity();
        $activity->setName('测试活动');
        $activity->setDescription('测试描述');
        $activity->setStartTime(new \DateTimeImmutable('-1 hour'));
        $activity->setEndTime(new \DateTimeImmutable('+1 hour'));
        $activity->setActivityType($activityType);
        $activity->setProductIds(['product_1']);
        $activity->setPriority(1);
        $activity->setExclusive(false);
        $activity->setPreheatEnabled(false);

        // 使用反射设置ID，模拟持久化后的实体
        $reflection = new \ReflectionClass($activity);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($activity, 'test-activity-' . uniqid());

        return $activity;
    }

    private function createTestActivityProduct(TimeLimitActivity $activity): ActivityProduct
    {
        $activityProduct = new ActivityProduct();
        $activityProduct->setActivity($activity);
        $activityProduct->setProductId('product_1');
        $activityProduct->setActivityPrice('80.00');
        $activityProduct->setActivityStock(100);
        $activityProduct->setSoldQuantity(0);
        $activityProduct->setLimitPerUser(5);

        return $activityProduct;
    }

    private function createMockDiscountRule(): DiscountRule
    {
        $discountRule = $this->getMockBuilder(DiscountRule::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $discountRule->method('isAmountQualified')
            ->willReturn(true)
        ;

        $discountRule->method('isQuantityQualified')
            ->willReturn(true)
        ;

        $discountRule->method('getDiscount')
            ->willReturn(10.0)
        ;

        $discountRule->method('getMaxDiscountAmountAsFloat')
            ->willReturn(100.0)
        ;

        $discountRule->method('getDiscountType')
            ->willReturn(DiscountType::DISCOUNT)
        ;

        return $discountRule;
    }
}
