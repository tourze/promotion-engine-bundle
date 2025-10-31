<?php

namespace PromotionEngineBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use PromotionEngineBundle\Entity\Campaign;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
#[When(env: 'dev')]
class CampaignFixtures extends Fixture implements FixtureGroupInterface
{
    public const CAMPAIGN_SUMMER_SALE = 'campaign-summer-sale';
    public const CAMPAIGN_WINTER_DISCOUNT = 'campaign-winter-discount';
    public const CAMPAIGN_NEW_USER = 'campaign-new-user';

    public static function getGroups(): array
    {
        return ['promotion-engine', 'test'];
    }

    public function load(ObjectManager $manager): void
    {
        $summerSale = new Campaign();
        $summerSale->setTitle('夏季大促销');
        $summerSale->setDescription('夏季全场商品大促销活动，多重优惠等你来');
        $summerSale->setStartTime(new \DateTimeImmutable('2024-06-01 00:00:00'));
        $summerSale->setEndTime(new \DateTimeImmutable('2024-08-31 23:59:59'));
        $summerSale->setExclusive(false);
        $summerSale->setWeight(100);
        $summerSale->setValid(true);
        $manager->persist($summerSale);

        $winterDiscount = new Campaign();
        $winterDiscount->setTitle('冬季折扣季');
        $winterDiscount->setDescription('冬季温暖优惠，服装数码全场折扣');
        $winterDiscount->setStartTime(new \DateTimeImmutable('2024-12-01 00:00:00'));
        $winterDiscount->setEndTime(new \DateTimeImmutable('2025-02-28 23:59:59'));
        $winterDiscount->setExclusive(true);
        $winterDiscount->setWeight(200);
        $winterDiscount->setValid(true);
        $manager->persist($winterDiscount);

        $newUser = new Campaign();
        $newUser->setTitle('新用户专享');
        $newUser->setDescription('新注册用户专享优惠活动');
        $newUser->setStartTime(new \DateTimeImmutable('2024-01-01 00:00:00'));
        $newUser->setEndTime(new \DateTimeImmutable('2024-12-31 23:59:59'));
        $newUser->setExclusive(false);
        $newUser->setWeight(50);
        $newUser->setValid(true);
        $manager->persist($newUser);

        $manager->flush();

        $this->addReference(self::CAMPAIGN_SUMMER_SALE, $summerSale);
        $this->addReference(self::CAMPAIGN_WINTER_DISCOUNT, $winterDiscount);
        $this->addReference(self::CAMPAIGN_NEW_USER, $newUser);
    }
}
