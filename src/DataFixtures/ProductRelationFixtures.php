<?php

namespace PromotionEngineBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use PromotionEngineBundle\Entity\Discount;
use PromotionEngineBundle\Entity\ProductRelation;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
#[When(env: 'dev')]
class ProductRelationFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public const PRODUCT_RELATION_SUMMER_CLOTHES = 'product-relation-summer-clothes';
    public const PRODUCT_RELATION_WINTER_ELECTRONICS = 'product-relation-winter-electronics';
    public const PRODUCT_RELATION_NEW_USER_BOOKS = 'product-relation-new-user-books';

    public static function getGroups(): array
    {
        return ['promotion-engine', 'test'];
    }

    public function load(ObjectManager $manager): void
    {
        $summerDiscount = $this->getReference(DiscountFixtures::DISCOUNT_SUMMER_PERCENT, Discount::class);
        $winterDiscount = $this->getReference(DiscountFixtures::DISCOUNT_WINTER_AMOUNT, Discount::class);
        $newUserDiscount = $this->getReference(DiscountFixtures::DISCOUNT_NEW_USER_PERCENT, Discount::class);

        $summerClothesRelation = new ProductRelation();
        $summerClothesRelation->setDiscount($summerDiscount);
        $summerClothesRelation->setSpuId('100100001');
        $summerClothesRelation->setSkuId('200100001');
        $summerClothesRelation->setTotal(100);
        $summerClothesRelation->setGiftQuantity(0);
        $manager->persist($summerClothesRelation);

        $winterElectronicsRelation = new ProductRelation();
        $winterElectronicsRelation->setDiscount($winterDiscount);
        $winterElectronicsRelation->setSpuId('100100002');
        $winterElectronicsRelation->setSkuId('200100002');
        $winterElectronicsRelation->setTotal(50);
        $winterElectronicsRelation->setGiftQuantity(5);
        $manager->persist($winterElectronicsRelation);

        $newUserBooksRelation = new ProductRelation();
        $newUserBooksRelation->setDiscount($newUserDiscount);
        $newUserBooksRelation->setSpuId('100100003');
        $newUserBooksRelation->setSkuId('200100003');
        $newUserBooksRelation->setTotal(200);
        $newUserBooksRelation->setGiftQuantity(20);
        $manager->persist($newUserBooksRelation);

        $additionalSummerRelation = new ProductRelation();
        $additionalSummerRelation->setDiscount($summerDiscount);
        $additionalSummerRelation->setSpuId('100100004');
        $additionalSummerRelation->setSkuId('200100004');
        $additionalSummerRelation->setTotal(75);
        $additionalSummerRelation->setGiftQuantity(0);
        $manager->persist($additionalSummerRelation);

        $manager->flush();

        $this->addReference(self::PRODUCT_RELATION_SUMMER_CLOTHES, $summerClothesRelation);
        $this->addReference(self::PRODUCT_RELATION_WINTER_ELECTRONICS, $winterElectronicsRelation);
        $this->addReference(self::PRODUCT_RELATION_NEW_USER_BOOKS, $newUserBooksRelation);
    }

    public function getDependencies(): array
    {
        return [
            DiscountFixtures::class,
        ];
    }
}
