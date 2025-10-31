<?php

namespace PromotionEngineBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use PromotionEngineBundle\Entity\ActivityProduct;
use PromotionEngineBundle\Entity\TimeLimitActivity;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
#[When(env: 'dev')]
class ActivityProductFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public const ACTIVITY_PRODUCT_FLASH_SALE_PHONE = 'activity-product-flash-sale-phone';
    public const ACTIVITY_PRODUCT_FLASH_SALE_LAPTOP = 'activity-product-flash-sale-laptop';
    public const ACTIVITY_PRODUCT_HOLIDAY_DISCOUNT_CAMERA = 'activity-product-holiday-discount-camera';
    public const ACTIVITY_PRODUCT_HOLIDAY_DISCOUNT_TABLET = 'activity-product-holiday-discount-tablet';

    public static function getGroups(): array
    {
        return ['promotion-engine', 'test'];
    }

    public function getDependencies(): array
    {
        return [
            TimeLimitActivityFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $flashSaleActivity = $this->getReference(TimeLimitActivityFixtures::ACTIVITY_FLASH_SALE, TimeLimitActivity::class);
        $holidayDiscountActivity = $this->getReference(TimeLimitActivityFixtures::ACTIVITY_HOLIDAY_DISCOUNT, TimeLimitActivity::class);

        // 闪电秒杀 - 手机产品
        $flashSalePhone = new ActivityProduct();
        $flashSalePhone->setActivity($flashSaleActivity);
        $flashSalePhone->setProductId('PHONE001');
        $flashSalePhone->setActivityPrice('999.00');
        $flashSalePhone->setLimitPerUser(1);
        $flashSalePhone->setActivityStock(100);
        $flashSalePhone->setSoldQuantity(25);
        $flashSalePhone->setValid(true);
        $manager->persist($flashSalePhone);

        // 闪电秒杀 - 笔记本产品
        $flashSaleLaptop = new ActivityProduct();
        $flashSaleLaptop->setActivity($flashSaleActivity);
        $flashSaleLaptop->setProductId('LAPTOP001');
        $flashSaleLaptop->setActivityPrice('3999.00');
        $flashSaleLaptop->setLimitPerUser(1);
        $flashSaleLaptop->setActivityStock(50);
        $flashSaleLaptop->setSoldQuantity(15);
        $flashSaleLaptop->setValid(true);
        $manager->persist($flashSaleLaptop);

        // 节日优惠 - 相机产品
        $holidayDiscountCamera = new ActivityProduct();
        $holidayDiscountCamera->setActivity($holidayDiscountActivity);
        $holidayDiscountCamera->setProductId('CAMERA001');
        $holidayDiscountCamera->setActivityPrice('1599.00');
        $holidayDiscountCamera->setLimitPerUser(2);
        $holidayDiscountCamera->setActivityStock(200);
        $holidayDiscountCamera->setSoldQuantity(45);
        $holidayDiscountCamera->setValid(true);
        $manager->persist($holidayDiscountCamera);

        // 节日优惠 - 平板产品
        $holidayDiscountTablet = new ActivityProduct();
        $holidayDiscountTablet->setActivity($holidayDiscountActivity);
        $holidayDiscountTablet->setProductId('TABLET001');
        $holidayDiscountTablet->setActivityPrice('599.00');
        $holidayDiscountTablet->setLimitPerUser(3);
        $holidayDiscountTablet->setActivityStock(150);
        $holidayDiscountTablet->setSoldQuantity(30);
        $holidayDiscountTablet->setValid(true);
        $manager->persist($holidayDiscountTablet);

        $manager->flush();

        $this->addReference(self::ACTIVITY_PRODUCT_FLASH_SALE_PHONE, $flashSalePhone);
        $this->addReference(self::ACTIVITY_PRODUCT_FLASH_SALE_LAPTOP, $flashSaleLaptop);
        $this->addReference(self::ACTIVITY_PRODUCT_HOLIDAY_DISCOUNT_CAMERA, $holidayDiscountCamera);
        $this->addReference(self::ACTIVITY_PRODUCT_HOLIDAY_DISCOUNT_TABLET, $holidayDiscountTablet);
    }
}
