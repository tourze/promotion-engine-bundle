<?php

namespace PromotionEngineBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PromotionEngineBundle\Entity\ActivityProduct;
use PromotionEngineBundle\Entity\TimeLimitActivity;
use PromotionEngineBundle\Enum\ActivityType;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(ActivityProduct::class)]
class ActivityProductTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new ActivityProduct();
    }

    /**
     * @return iterable<array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        $activity = new TimeLimitActivity();
        $activity->setName('Test Activity');
        $activity->setStartTime(new \DateTimeImmutable('2024-01-01 00:00:00'));
        $activity->setEndTime(new \DateTimeImmutable('2024-01-31 23:59:59'));
        $activity->setActivityType(ActivityType::LIMITED_TIME_SECKILL);

        yield ['activity', $activity];
        yield ['productId', 'product123'];
        yield ['activityPrice', '99.99'];
        yield ['limitPerUser', 5];
        yield ['activityStock', 100];
        yield ['soldQuantity', 30];
        yield ['valid', true];
    }

    public function testBasicGettersAndSetters(): void
    {
        $activityProduct = new ActivityProduct();
        $activity = new TimeLimitActivity();
        $activity->setName('Test Activity');
        $activity->setStartTime(new \DateTimeImmutable('2024-01-01 00:00:00'));
        $activity->setEndTime(new \DateTimeImmutable('2024-01-31 23:59:59'));
        $activity->setActivityType(ActivityType::LIMITED_TIME_SECKILL);

        $activityProduct->setActivity($activity);
        $activityProduct->setProductId('product456');
        $activityProduct->setActivityPrice('99.99');
        $activityProduct->setLimitPerUser(5);
        $activityProduct->setActivityStock(100);
        $activityProduct->setSoldQuantity(30);
        $activityProduct->setValid(true);

        $this->assertSame($activity, $activityProduct->getActivity());
        $this->assertSame('product456', $activityProduct->getProductId());
        $this->assertSame('99.99', $activityProduct->getActivityPrice());
        $this->assertSame(5, $activityProduct->getLimitPerUser());
        $this->assertSame(100, $activityProduct->getActivityStock());
        $this->assertSame(30, $activityProduct->getSoldQuantity());
        $this->assertTrue($activityProduct->isValid());
    }

    public function testStockCalculations(): void
    {
        $activityProduct = new ActivityProduct();
        $activityProduct->setActivityStock(100);
        $activityProduct->setSoldQuantity(30);

        $this->assertSame(70, $activityProduct->getRemainingStock());
        $this->assertTrue($activityProduct->isStockAvailable(70));
        $this->assertTrue($activityProduct->isStockAvailable(50));
        $this->assertFalse($activityProduct->isStockAvailable(71));
        $this->assertFalse($activityProduct->isSoldOut());
    }

    public function testSoldOutScenario(): void
    {
        $activityProduct = new ActivityProduct();
        $activityProduct->setActivityStock(50);
        $activityProduct->setSoldQuantity(50);

        $this->assertSame(0, $activityProduct->getRemainingStock());
        $this->assertFalse($activityProduct->isStockAvailable(1));
        $this->assertTrue($activityProduct->isSoldOut());
    }

    public function testStockUtilization(): void
    {
        $activityProduct = new ActivityProduct();

        $activityProduct->setActivityStock(0);
        $activityProduct->setSoldQuantity(0);
        $this->assertSame(0.0, $activityProduct->getStockUtilization());

        $activityProduct->setActivityStock(100);
        $activityProduct->setSoldQuantity(0);
        $this->assertSame(0.0, $activityProduct->getStockUtilization());

        $activityProduct->setActivityStock(100);
        $activityProduct->setSoldQuantity(25);
        $this->assertSame(25.0, $activityProduct->getStockUtilization());

        $activityProduct->setActivityStock(100);
        $activityProduct->setSoldQuantity(100);
        $this->assertSame(100.0, $activityProduct->getStockUtilization());

        $activityProduct->setActivityStock(100);
        $activityProduct->setSoldQuantity(150);
        $this->assertSame(100.0, $activityProduct->getStockUtilization());
    }

    public function testIncreaseSoldQuantity(): void
    {
        $activityProduct = new ActivityProduct();
        $activityProduct->setSoldQuantity(10);

        $activityProduct->increaseSoldQuantity(5);
        $this->assertSame(15, $activityProduct->getSoldQuantity());

        $activityProduct->increaseSoldQuantity();
        $this->assertSame(16, $activityProduct->getSoldQuantity());
    }

    public function testDecreaseSoldQuantity(): void
    {
        $activityProduct = new ActivityProduct();
        $activityProduct->setSoldQuantity(10);

        $activityProduct->decreaseSoldQuantity(3);
        $this->assertSame(7, $activityProduct->getSoldQuantity());

        $activityProduct->decreaseSoldQuantity();
        $this->assertSame(6, $activityProduct->getSoldQuantity());

        $activityProduct->decreaseSoldQuantity(10);
        $this->assertSame(0, $activityProduct->getSoldQuantity());
    }

    public function testActivityRelation(): void
    {
        $activityProduct = new ActivityProduct();
        $activity = new TimeLimitActivity();
        $activity->setName('Test Activity');
        $activity->setStartTime(new \DateTimeImmutable('2024-01-01 00:00:00'));
        $activity->setEndTime(new \DateTimeImmutable('2024-01-31 23:59:59'));
        $activity->setActivityType(ActivityType::LIMITED_TIME_SECKILL);

        $activityProduct->setActivity($activity);
        $this->assertSame($activity, $activityProduct->getActivity());
        $this->assertSame($activity, $activityProduct->getActivity());
    }

    public function testToString(): void
    {
        $activityProduct = new ActivityProduct();
        $this->assertSame('', (string) $activityProduct);
    }

    public function testRetrieveAdminArray(): void
    {
        $activityProduct = new ActivityProduct();
        $activity = new TimeLimitActivity();
        $activity->setName('Test Activity');
        $activity->setStartTime(new \DateTimeImmutable('2024-01-01 00:00:00'));
        $activity->setEndTime(new \DateTimeImmutable('2024-01-31 23:59:59'));
        $activity->setActivityType(ActivityType::LIMITED_TIME_SECKILL);

        $activityProduct->setActivity($activity);
        $activityProduct->setProductId('product456');
        $activityProduct->setActivityPrice('99.99');
        $activityProduct->setLimitPerUser(5);
        $activityProduct->setActivityStock(100);
        $activityProduct->setSoldQuantity(30);
        $activityProduct->setValid(true);

        $array = $activityProduct->retrieveAdminArray();

        $this->assertIsArray($array);
        $this->assertSame($activity->getId(), $array['activityId']);
        $this->assertSame('product456', $array['productId']);
        $this->assertSame('99.99', $array['activityPrice']);
        $this->assertSame(5, $array['limitPerUser']);
        $this->assertSame(100, $array['activityStock']);
        $this->assertSame(30, $array['soldQuantity']);
        $this->assertSame(70, $array['remainingStock']);
        $this->assertSame(30.0, $array['stockUtilization']);
        $this->assertFalse($array['isSoldOut']);
        $this->assertTrue($array['valid']);
    }
}
