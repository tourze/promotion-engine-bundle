<?php

namespace PromotionEngineBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use PromotionEngineBundle\Entity\Discount;
use PromotionEngineBundle\Entity\DiscountCondition;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
#[When(env: 'dev')]
class DiscountConditionFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public const CONDITION_SUMMER_MIN_QTY = 'condition-summer-min-qty';
    public const CONDITION_WINTER_MIN_AMOUNT = 'condition-winter-min-amount';
    public const CONDITION_NEW_USER_FIRST_ORDER = 'condition-new-user-first-order';

    public static function getGroups(): array
    {
        return ['promotion-engine', 'test'];
    }

    public function load(ObjectManager $manager): void
    {
        $summerDiscount = $this->getReference(DiscountFixtures::DISCOUNT_SUMMER_PERCENT, Discount::class);
        $winterDiscount = $this->getReference(DiscountFixtures::DISCOUNT_WINTER_AMOUNT, Discount::class);
        $newUserDiscount = $this->getReference(DiscountFixtures::DISCOUNT_NEW_USER_PERCENT, Discount::class);

        $summerMinQtyCondition = new DiscountCondition();
        $summerMinQtyCondition->setDiscount($summerDiscount);
        $summerMinQtyCondition->setCondition1('min_quantity:3');
        $summerMinQtyCondition->setCondition2('category:summer_items');
        $summerMinQtyCondition->setCondition3('valid_date:2024-06-01,2024-08-31');
        $manager->persist($summerMinQtyCondition);

        $winterMinAmountCondition = new DiscountCondition();
        $winterMinAmountCondition->setDiscount($winterDiscount);
        $winterMinAmountCondition->setCondition1('min_amount:200.00');
        $winterMinAmountCondition->setCondition2('user_level:VIP');
        $winterMinAmountCondition->setCondition3('store_type:online');
        $manager->persist($winterMinAmountCondition);

        $newUserFirstOrderCondition = new DiscountCondition();
        $newUserFirstOrderCondition->setDiscount($newUserDiscount);
        $newUserFirstOrderCondition->setCondition1('is_first_order:true');
        $newUserFirstOrderCondition->setCondition2('registration_days_less_than:7');
        $newUserFirstOrderCondition->setCondition3('min_amount:50.00');
        $manager->persist($newUserFirstOrderCondition);

        $manager->flush();

        $this->addReference(self::CONDITION_SUMMER_MIN_QTY, $summerMinQtyCondition);
        $this->addReference(self::CONDITION_WINTER_MIN_AMOUNT, $winterMinAmountCondition);
        $this->addReference(self::CONDITION_NEW_USER_FIRST_ORDER, $newUserFirstOrderCondition);
    }

    public function getDependencies(): array
    {
        return [
            DiscountFixtures::class,
        ];
    }
}
