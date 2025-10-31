<?php

namespace PromotionEngineBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use PromotionEngineBundle\Entity\Campaign;
use PromotionEngineBundle\Entity\Constraint;
use PromotionEngineBundle\Enum\CompareType;
use PromotionEngineBundle\Enum\LimitType;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
#[When(env: 'dev')]
class ConstraintFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public const CONSTRAINT_MIN_AMOUNT = 'constraint-min-amount';
    public const CONSTRAINT_USER_LEVEL = 'constraint-user-level';
    public const CONSTRAINT_PRODUCT_CATEGORY = 'constraint-product-category';

    public static function getGroups(): array
    {
        return ['promotion-engine', 'test'];
    }

    public function load(ObjectManager $manager): void
    {
        $summerSale = $this->getReference(CampaignFixtures::CAMPAIGN_SUMMER_SALE, Campaign::class);
        $winterDiscount = $this->getReference(CampaignFixtures::CAMPAIGN_WINTER_DISCOUNT, Campaign::class);
        $newUser = $this->getReference(CampaignFixtures::CAMPAIGN_NEW_USER, Campaign::class);

        $minAmountConstraint = new Constraint();
        $minAmountConstraint->setCampaign($summerSale);
        $minAmountConstraint->setCompareType(CompareType::GTE);
        $minAmountConstraint->setLimitType(LimitType::ORDER_PRICE);
        $minAmountConstraint->setRangeValue('100.00');
        $minAmountConstraint->setValid(true);
        $manager->persist($minAmountConstraint);

        $userLevelConstraint = new Constraint();
        $userLevelConstraint->setCampaign($winterDiscount);
        $userLevelConstraint->setCompareType(CompareType::EQUAL);
        $userLevelConstraint->setLimitType(LimitType::FIRST_PURCHASE_USER);
        $userLevelConstraint->setRangeValue('VIP');
        $userLevelConstraint->setValid(true);
        $manager->persist($userLevelConstraint);

        $productCategoryConstraint = new Constraint();
        $productCategoryConstraint->setCampaign($newUser);
        $productCategoryConstraint->setCompareType(CompareType::IN);
        $productCategoryConstraint->setLimitType(LimitType::SPU_ID);
        $productCategoryConstraint->setRangeValue('electronics,clothing,books');
        $productCategoryConstraint->setValid(true);
        $manager->persist($productCategoryConstraint);

        $manager->flush();

        $this->addReference(self::CONSTRAINT_MIN_AMOUNT, $minAmountConstraint);
        $this->addReference(self::CONSTRAINT_USER_LEVEL, $userLevelConstraint);
        $this->addReference(self::CONSTRAINT_PRODUCT_CATEGORY, $productCategoryConstraint);
    }

    public function getDependencies(): array
    {
        return [
            CampaignFixtures::class,
        ];
    }
}
