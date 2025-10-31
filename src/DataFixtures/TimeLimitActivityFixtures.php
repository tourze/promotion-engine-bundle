<?php

namespace PromotionEngineBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use PromotionEngineBundle\Entity\TimeLimitActivity;
use PromotionEngineBundle\Enum\ActivityStatus;
use PromotionEngineBundle\Enum\ActivityType;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
#[When(env: 'dev')]
class TimeLimitActivityFixtures extends Fixture implements FixtureGroupInterface
{
    public const ACTIVITY_FLASH_SALE = 'activity-flash-sale';
    public const ACTIVITY_HOLIDAY_DISCOUNT = 'activity-holiday-discount';

    public static function getGroups(): array
    {
        return ['promotion-engine', 'test'];
    }

    public function load(ObjectManager $manager): void
    {
        // 限时秒杀活动
        $flashSale = new TimeLimitActivity();
        $flashSale->setName('闪电秒杀');
        $flashSale->setActivityType(ActivityType::LIMITED_TIME_SECKILL);
        $flashSale->setStartTime(new \DateTimeImmutable('2023-11-01 10:00:00'));
        $flashSale->setEndTime(new \DateTimeImmutable('2023-11-30 23:59:59'));
        $flashSale->setStatus(ActivityStatus::ACTIVE);
        $flashSale->setPriority(100);
        $flashSale->setExclusive(false);
        $flashSale->setValid(true);

        $manager->persist($flashSale);
        $this->addReference(self::ACTIVITY_FLASH_SALE, $flashSale);

        // 节日优惠活动
        $holidayDiscount = new TimeLimitActivity();
        $holidayDiscount->setName('节日特惠');
        $holidayDiscount->setActivityType(ActivityType::LIMITED_TIME_DISCOUNT);
        $holidayDiscount->setStartTime(new \DateTimeImmutable('2023-12-01 00:00:00'));
        $holidayDiscount->setEndTime(new \DateTimeImmutable('2023-12-31 23:59:59'));
        $holidayDiscount->setStatus(ActivityStatus::PENDING);
        $holidayDiscount->setPriority(80);
        $holidayDiscount->setExclusive(true);
        $holidayDiscount->setValid(true);

        $manager->persist($holidayDiscount);
        $this->addReference(self::ACTIVITY_HOLIDAY_DISCOUNT, $holidayDiscount);

        $manager->flush();
    }
}
