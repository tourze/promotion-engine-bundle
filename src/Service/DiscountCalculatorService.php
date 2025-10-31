<?php

namespace PromotionEngineBundle\Service;

use Monolog\Attribute\WithMonologChannel;
use PromotionEngineBundle\DTO\CalculateActivityDiscountItem;
use PromotionEngineBundle\Entity\ActivityProduct;
use PromotionEngineBundle\Entity\DiscountRule;
use PromotionEngineBundle\Entity\TimeLimitActivity;
use PromotionEngineBundle\Enum\DiscountType;
use PromotionEngineBundle\Repository\DiscountRuleRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(public: true)]
#[WithMonologChannel(channel: 'promotion_engine')]
class DiscountCalculatorService
{
    public function __construct(
        private readonly DiscountRuleRepository $discountRuleRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function calculateDiscount(
        TimeLimitActivity $activity,
        ActivityProduct $activityProduct,
        CalculateActivityDiscountItem $item,
        ?string $userId = null,
    ): float {
        $activityId = $activity->getId();
        if (null === $activityId) {
            return $this->calculateSimpleDiscount($activityProduct, $item);
        }

        $discountRules = $this->discountRuleRepository->findByActivityId($activityId);

        if ([] === $discountRules) {
            return $this->calculateSimpleDiscount($activityProduct, $item);
        }

        $totalDiscount = 0.0;

        foreach ($discountRules as $rule) {
            $discount = $this->calculateRuleDiscount($rule, $item, $activity, $activityProduct, $userId);
            $totalDiscount += $discount;

            $this->logger->debug('应用折扣规则', [
                'activityId' => $activity->getId(),
                'ruleId' => $rule->getId(),
                'discountType' => $rule->getDiscountType()->value,
                'productId' => $item->productId,
                'discount' => $discount,
            ]);
        }

        return $totalDiscount;
    }

    private function calculateRuleDiscount(
        DiscountRule $rule,
        CalculateActivityDiscountItem $item,
        TimeLimitActivity $activity,
        ActivityProduct $activityProduct,
        ?string $userId,
    ): float {
        $originalAmount = $item->getTotalAmount();

        if (!$rule->isAmountQualified($originalAmount)) {
            return 0.0;
        }

        if (!$rule->isQuantityQualified($item->quantity)) {
            return 0.0;
        }

        $discount = match ($rule->getDiscountType()) {
            DiscountType::REDUCTION => $this->calculateReduction($rule, $item),
            DiscountType::DISCOUNT => $this->calculatePercentageDiscount($rule, $item),
            DiscountType::BUY_GIVE => $this->calculateBuyGive($rule, $item),
            DiscountType::BUY_N_GET_M => $this->calculateBuyNGetM($rule, $item),
            DiscountType::PROGRESSIVE_DISCOUNT_SCHEME => $this->calculateProgressiveDiscount($rule, $item),
            DiscountType::SPEND_THRESHOLD_WITH_ADD_ON => $this->calculateAddOnDiscount($rule, $item),
            default => 0.0,
        };

        if (null !== $rule->getMaxDiscountAmountAsFloat()) {
            $discount = min($discount, $rule->getMaxDiscountAmountAsFloat());
        }

        return max(0.0, $discount);
    }

    private function calculateSimpleDiscount(ActivityProduct $activityProduct, CalculateActivityDiscountItem $item): float
    {
        $activityPrice = (float) $activityProduct->getActivityPrice();
        $originalPrice = $item->price;

        if ($activityPrice >= $originalPrice) {
            return 0.0;
        }

        return ($originalPrice - $activityPrice) * $item->quantity;
    }

    private function calculateReduction(DiscountRule $rule, CalculateActivityDiscountItem $item): float
    {
        return $rule->getDiscount();
    }

    private function calculatePercentageDiscount(DiscountRule $rule, CalculateActivityDiscountItem $item): float
    {
        $discountRate = $rule->getDiscount();

        if ($discountRate >= 100.0) {
            return $item->getTotalAmount();
        }

        return $item->getTotalAmount() * ($discountRate / 100.0);
    }

    private function calculateBuyGive(DiscountRule $rule, CalculateActivityDiscountItem $item): float
    {
        $giftQuantity = $rule->getGiftQuantity() ?? 0;
        $giftProductIds = $rule->getGiftProductIds() ?? [];

        if ($giftQuantity <= 0 || [] === $giftProductIds) {
            return 0.0;
        }

        return $item->price * min($giftQuantity, $item->quantity);
    }

    private function calculateBuyNGetM(DiscountRule $rule, CalculateActivityDiscountItem $item): float
    {
        $requiredQuantity = $rule->getRequiredQuantity() ?? 1;
        $giftQuantity = $rule->getGiftQuantity() ?? 0;

        if ($requiredQuantity <= 0 || $giftQuantity <= 0) {
            return 0.0;
        }

        $eligibleSets = intval($item->quantity / $requiredQuantity);
        $freeItems = $eligibleSets * $giftQuantity;

        return $item->price * $freeItems;
    }

    private function calculateProgressiveDiscount(DiscountRule $rule, CalculateActivityDiscountItem $item): float
    {
        $config = $rule->getConfig() ?? [];
        $tiersValue = $config['tiers'] ?? [];

        if (!is_array($tiersValue) || [] === $tiersValue) {
            return 0.0;
        }

        /** @var array<int, array{minQuantity?: int, discountRate?: float}> $tiers */
        $tiers = array_filter($tiersValue, fn ($tier): bool => is_array($tier));

        usort($tiers, fn (array $a, array $b) => ($a['minQuantity'] ?? 0) <=> ($b['minQuantity'] ?? 0));

        /** @var array{minQuantity?: int, discountRate?: float}|null $applicableTier */
        $applicableTier = null;
        foreach ($tiers as $tier) {
            if ($item->quantity >= ($tier['minQuantity'] ?? 0)) {
                $applicableTier = $tier;
            } else {
                break;
            }
        }

        if (null === $applicableTier) {
            return 0.0;
        }

        $discountRate = (float) ($applicableTier['discountRate'] ?? 0.0);

        return $item->getTotalAmount() * ($discountRate / 100.0);
    }

    private function calculateAddOnDiscount(DiscountRule $rule, CalculateActivityDiscountItem $item): float
    {
        $config = $rule->getConfig() ?? [];
        $addOnPriceValue = $config['addOnPrice'] ?? 0.0;
        $addOnPrice = is_numeric($addOnPriceValue) ? (float) $addOnPriceValue : 0.0;
        $addOnProductIds = $config['addOnProductIds'] ?? [];

        if ($addOnPrice <= 0 || !is_array($addOnProductIds) || [] === $addOnProductIds) {
            return 0.0;
        }

        if (!in_array($item->productId, $addOnProductIds, true)) {
            return 0.0;
        }

        $originalPrice = $item->price;
        if ($addOnPrice >= $originalPrice) {
            return 0.0;
        }

        return ($originalPrice - $addOnPrice) * $item->quantity;
    }

    /**
     * @param DiscountRule[] $rules
     */
    public function validateRules(array $rules, CalculateActivityDiscountItem $item): bool
    {
        foreach ($rules as $rule) {
            if (!$rule->isAmountQualified($item->getTotalAmount())) {
                return false;
            }

            if (!$rule->isQuantityQualified($item->quantity)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param DiscountRule[] $rules
     * @return DiscountRule[]
     */
    public function getApplicableRules(array $rules, CalculateActivityDiscountItem $item): array
    {
        return array_filter($rules, function (DiscountRule $rule) use ($item) {
            return $rule->isAmountQualified($item->getTotalAmount())
                && $rule->isQuantityQualified($item->quantity);
        });
    }

    public function estimateMaxDiscount(
        DiscountRule $rule,
        float $maxAmount,
        int $maxQuantity,
    ): float {
        $mockItem = new CalculateActivityDiscountItem(
            productId: 'mock',
            skuId: 'mock',
            quantity: $maxQuantity,
            price: $maxAmount / $maxQuantity
        );

        return $this->calculateRuleDiscount($rule, $mockItem,
            new TimeLimitActivity(), new ActivityProduct(), null);
    }
}
