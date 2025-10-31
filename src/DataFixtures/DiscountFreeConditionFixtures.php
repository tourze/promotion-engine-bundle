<?php

namespace PromotionEngineBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use PromotionEngineBundle\Entity\Discount;
use PromotionEngineBundle\Entity\DiscountFreeCondition;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
#[When(env: 'dev')]
class DiscountFreeConditionFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public const FREE_CONDITION_BUY_2_GET_1 = 'free-condition-buy-2-get-1';
    public const FREE_CONDITION_BUY_3_GET_1 = 'free-condition-buy-3-get-1';
    public const FREE_CONDITION_BUY_5_GET_2 = 'free-condition-buy-5-get-2';

    public static function getGroups(): array
    {
        return ['promotion-engine', 'test'];
    }

    public function load(ObjectManager $manager): void
    {
        $summerDiscount = $this->getReference(DiscountFixtures::DISCOUNT_SUMMER_PERCENT, Discount::class);
        $winterDiscount = $this->getReference(DiscountFixtures::DISCOUNT_WINTER_AMOUNT, Discount::class);
        $newUserDiscount = $this->getReference(DiscountFixtures::DISCOUNT_NEW_USER_PERCENT, Discount::class);

        $buy2Get1Condition = new DiscountFreeCondition();
        $buy2Get1Condition->setDiscount($summerDiscount);
        $buy2Get1Condition->setPurchaseQuantity('2');
        $buy2Get1Condition->setFreeQuantity('1');
        $manager->persist($buy2Get1Condition);

        $buy3Get1Condition = new DiscountFreeCondition();
        $buy3Get1Condition->setDiscount($winterDiscount);
        $buy3Get1Condition->setPurchaseQuantity('3');
        $buy3Get1Condition->setFreeQuantity('1');
        $manager->persist($buy3Get1Condition);

        $buy5Get2Condition = new DiscountFreeCondition();
        $buy5Get2Condition->setDiscount($newUserDiscount);
        $buy5Get2Condition->setPurchaseQuantity('5');
        $buy5Get2Condition->setFreeQuantity('2');
        $manager->persist($buy5Get2Condition);

        $manager->flush();

        $this->addReference(self::FREE_CONDITION_BUY_2_GET_1, $buy2Get1Condition);
        $this->addReference(self::FREE_CONDITION_BUY_3_GET_1, $buy3Get1Condition);
        $this->addReference(self::FREE_CONDITION_BUY_5_GET_2, $buy5Get2Condition);
    }

    public function getDependencies(): array
    {
        return [
            DiscountFixtures::class,
        ];
    }
}
