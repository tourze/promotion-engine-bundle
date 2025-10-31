<?php

namespace PromotionEngineBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use PromotionEngineBundle\Entity\DiscountRule;
use PromotionEngineBundle\Entity\TimeLimitActivity;
use PromotionEngineBundle\Enum\DiscountType;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
#[When(env: 'dev')]
class DiscountRuleFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public const DISCOUNT_RULE_REDUCTION = 'discount-rule-reduction';
    public const DISCOUNT_RULE_PERCENTAGE = 'discount-rule-percentage';
    public const DISCOUNT_RULE_FREE_FREIGHT = 'discount-rule-free-freight';
    public const DISCOUNT_RULE_BUY_GIVE = 'discount-rule-buy-give';

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
        // 满减规则 - 使用闪电秒杀活动
        $reductionRule = new DiscountRule();
        $reductionRule->setActivityId($this->getReference(TimeLimitActivityFixtures::ACTIVITY_FLASH_SALE, TimeLimitActivity::class)->getId() ?? '');
        $reductionRule->setDiscountType(DiscountType::REDUCTION);
        $reductionRule->setDiscountValue('20.00');
        $reductionRule->setMinAmount('100.00');
        $reductionRule->setMaxDiscountAmount('50.00');
        $reductionRule->setValid(true);
        $manager->persist($reductionRule);

        // 折扣规则 - 使用节日优惠活动
        $discountRule = new DiscountRule();
        $discountRule->setActivityId($this->getReference(TimeLimitActivityFixtures::ACTIVITY_HOLIDAY_DISCOUNT, TimeLimitActivity::class)->getId() ?? '');
        $discountRule->setDiscountType(DiscountType::DISCOUNT);
        $discountRule->setDiscountValue('0.85'); // 8.5折
        $discountRule->setMinAmount('50.00');
        $discountRule->setValid(true);
        $manager->persist($discountRule);

        // 免邮规则 - 使用闪电秒杀活动的另一个规则
        $freeFreightRule = new DiscountRule();
        $freeFreightRule->setActivityId($this->getReference(TimeLimitActivityFixtures::ACTIVITY_FLASH_SALE, TimeLimitActivity::class)->getId() ?? '');
        $freeFreightRule->setDiscountType(DiscountType::FREE_FREIGHT);
        $freeFreightRule->setDiscountValue('0.00');
        $freeFreightRule->setMinAmount('99.00');
        $freeFreightRule->setValid(true);
        $manager->persist($freeFreightRule);

        // 买赠规则 - 使用节日优惠活动的另一个规则
        $buyGiveRule = new DiscountRule();
        $buyGiveRule->setActivityId($this->getReference(TimeLimitActivityFixtures::ACTIVITY_HOLIDAY_DISCOUNT, TimeLimitActivity::class)->getId() ?? '');
        $buyGiveRule->setDiscountType(DiscountType::BUY_GIVE);
        $buyGiveRule->setDiscountValue('0.00');
        $buyGiveRule->setRequiredQuantity(2);
        $buyGiveRule->setGiftQuantity(1);
        $buyGiveRule->setGiftProductIds(['GIFT001', 'GIFT002']);
        $buyGiveRule->setConfig([
            'allow_multiple' => true,
            'gift_selection' => 'auto',
        ]);
        $buyGiveRule->setValid(true);
        $manager->persist($buyGiveRule);

        $manager->flush();

        $this->addReference(self::DISCOUNT_RULE_REDUCTION, $reductionRule);
        $this->addReference(self::DISCOUNT_RULE_PERCENTAGE, $discountRule);
        $this->addReference(self::DISCOUNT_RULE_FREE_FREIGHT, $freeFreightRule);
        $this->addReference(self::DISCOUNT_RULE_BUY_GIVE, $buyGiveRule);
    }
}
