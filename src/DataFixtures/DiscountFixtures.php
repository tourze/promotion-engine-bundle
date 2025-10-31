<?php

namespace PromotionEngineBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use PromotionEngineBundle\Entity\Campaign;
use PromotionEngineBundle\Entity\Discount;
use PromotionEngineBundle\Enum\DiscountType;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
#[When(env: 'dev')]
class DiscountFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public const DISCOUNT_SUMMER_PERCENT = 'discount-summer-percent';
    public const DISCOUNT_WINTER_AMOUNT = 'discount-winter-amount';
    public const DISCOUNT_NEW_USER_PERCENT = 'discount-new-user-percent';

    public static function getGroups(): array
    {
        return ['promotion-engine', 'test'];
    }

    public function load(ObjectManager $manager): void
    {
        $summerSale = $this->getReference(CampaignFixtures::CAMPAIGN_SUMMER_SALE, Campaign::class);
        $winterDiscount = $this->getReference(CampaignFixtures::CAMPAIGN_WINTER_DISCOUNT, Campaign::class);
        $newUser = $this->getReference(CampaignFixtures::CAMPAIGN_NEW_USER, Campaign::class);

        $summerPercentDiscount = new Discount();
        $summerPercentDiscount->setCampaign($summerSale);
        $summerPercentDiscount->setType(DiscountType::DISCOUNT);
        $summerPercentDiscount->setValue('15.00');
        $summerPercentDiscount->setRemark('夏季大促15%折扣');
        $summerPercentDiscount->setIsLimited(true);
        $summerPercentDiscount->setQuota(1000);
        $summerPercentDiscount->setNumber(0);
        $summerPercentDiscount->setValid(true);
        $manager->persist($summerPercentDiscount);

        $winterAmountDiscount = new Discount();
        $winterAmountDiscount->setCampaign($winterDiscount);
        $winterAmountDiscount->setType(DiscountType::REDUCTION);
        $winterAmountDiscount->setValue('50.00');
        $winterAmountDiscount->setRemark('冬季减50元优惠');
        $winterAmountDiscount->setIsLimited(false);
        $winterAmountDiscount->setQuota(0);
        $winterAmountDiscount->setNumber(0);
        $winterAmountDiscount->setValid(true);
        $manager->persist($winterAmountDiscount);

        $newUserPercentDiscount = new Discount();
        $newUserPercentDiscount->setCampaign($newUser);
        $newUserPercentDiscount->setType(DiscountType::DISCOUNT);
        $newUserPercentDiscount->setValue('20.00');
        $newUserPercentDiscount->setRemark('新用户专享8折优惠');
        $newUserPercentDiscount->setIsLimited(true);
        $newUserPercentDiscount->setQuota(500);
        $newUserPercentDiscount->setNumber(0);
        $newUserPercentDiscount->setValid(true);
        $manager->persist($newUserPercentDiscount);

        $manager->flush();

        $this->addReference(self::DISCOUNT_SUMMER_PERCENT, $summerPercentDiscount);
        $this->addReference(self::DISCOUNT_WINTER_AMOUNT, $winterAmountDiscount);
        $this->addReference(self::DISCOUNT_NEW_USER_PERCENT, $newUserPercentDiscount);
    }

    public function getDependencies(): array
    {
        return [
            CampaignFixtures::class,
        ];
    }
}
