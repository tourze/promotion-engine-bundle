<?php

namespace PromotionEngineBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use PromotionEngineBundle\Enum\DiscountType;
use PromotionEngineBundle\Repository\DiscountRuleRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\Arrayable\AdminArrayInterface;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineTrackBundle\Attribute\TrackColumn;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;

/**
 * @implements AdminArrayInterface<string, mixed>
 */
#[ORM\Entity(repositoryClass: DiscountRuleRepository::class)]
#[ORM\Table(name: 'ims_promotion_discount_rule', options: ['comment' => '优惠规则'])]
class DiscountRule implements AdminArrayInterface, \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;
    use BlameableAware;

    #[Assert\NotBlank]
    #[IndexColumn]
    #[ORM\Column(length: 19, options: ['comment' => '活动ID'])]
    private string $activityId;

    #[Assert\NotNull]
    #[Assert\Choice(callback: [DiscountType::class, 'cases'])]
    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, enumType: DiscountType::class, options: ['comment' => '折扣类型'])]
    private DiscountType $discountType;

    #[Assert\PositiveOrZero]
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['comment' => '折扣值'])]
    private string $discountValue = '0.00';

    #[Assert\PositiveOrZero]
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true, options: ['comment' => '最低消费门槛'])]
    private ?string $minAmount = null;

    #[Assert\PositiveOrZero]
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true, options: ['comment' => '最大优惠金额'])]
    private ?string $maxDiscountAmount = null;

    #[Assert\Positive]
    #[ORM\Column(nullable: true, options: ['comment' => '满足数量要求'])]
    private ?int $requiredQuantity = null;

    #[Assert\Positive]
    #[ORM\Column(nullable: true, options: ['comment' => '赠送数量'])]
    private ?int $giftQuantity = null;

    /**
     * @var array<string>
     */
    #[Assert\All(constraints: [
        new Assert\Type(type: 'string'),
        new Assert\NotBlank(),
    ])]
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '赠品商品ID列表'])]
    private ?array $giftProductIds = null;

    /**
     * @var array<string, mixed>
     */
    #[Assert\Type(type: 'array')]
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '扩展配置'])]
    private ?array $config = null;

    #[Assert\Type(type: 'bool')]
    #[IndexColumn]
    #[TrackColumn]
    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['comment' => '有效', 'default' => 0])]
    private ?bool $valid = false;

    public function __toString(): string
    {
        if (null === $this->getId()) {
            return '';
        }

        return $this->getDiscountType()->getLabel();
    }

    public function getActivityId(): string
    {
        return $this->activityId;
    }

    public function setActivityId(string $activityId): void
    {
        $this->activityId = $activityId;
    }

    public function getDiscountType(): DiscountType
    {
        return $this->discountType;
    }

    public function setDiscountType(DiscountType $discountType): void
    {
        $this->discountType = $discountType;
    }

    public function getDiscountValue(): string
    {
        return $this->discountValue;
    }

    public function setDiscountValue(string $discountValue): void
    {
        $this->discountValue = $discountValue;
    }

    public function getMinAmount(): ?string
    {
        return $this->minAmount;
    }

    public function setMinAmount(?string $minAmount): void
    {
        $this->minAmount = $minAmount;
    }

    public function getMaxDiscountAmount(): ?string
    {
        return $this->maxDiscountAmount;
    }

    public function setMaxDiscountAmount(?string $maxDiscountAmount): void
    {
        $this->maxDiscountAmount = $maxDiscountAmount;
    }

    public function getRequiredQuantity(): ?int
    {
        return $this->requiredQuantity;
    }

    public function setRequiredQuantity(?int $requiredQuantity): void
    {
        $this->requiredQuantity = $requiredQuantity;
    }

    public function getGiftQuantity(): ?int
    {
        return $this->giftQuantity;
    }

    public function setGiftQuantity(?int $giftQuantity): void
    {
        $this->giftQuantity = $giftQuantity;
    }

    /**
     * @return array<string>|null
     */
    public function getGiftProductIds(): ?array
    {
        return $this->giftProductIds;
    }

    /**
     * @param array<string>|null $giftProductIds
     */
    public function setGiftProductIds(?array $giftProductIds): void
    {
        $this->giftProductIds = $giftProductIds;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getConfig(): ?array
    {
        return $this->config;
    }

    /**
     * @param array<string, mixed>|null $config
     */
    public function setConfig(?array $config): void
    {
        $this->config = $config;
    }

    public function isValid(): ?bool
    {
        return $this->valid;
    }

    public function setValid(?bool $valid): void
    {
        $this->valid = $valid;
    }

    public function getDiscount(): float
    {
        return (float) $this->discountValue;
    }

    public function getMinAmountAsFloat(): float
    {
        return null !== $this->minAmount ? (float) $this->minAmount : 0.0;
    }

    public function getMaxDiscountAmountAsFloat(): ?float
    {
        return null !== $this->maxDiscountAmount ? (float) $this->maxDiscountAmount : null;
    }

    public function isAmountQualified(float $amount): bool
    {
        return $amount >= $this->getMinAmountAsFloat();
    }

    public function isQuantityQualified(int $quantity): bool
    {
        return null === $this->requiredQuantity || $quantity >= $this->requiredQuantity;
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        return [
            'id' => $this->getId(),
            'activityId' => $this->activityId,
            'discountType' => $this->discountType->value,
            'discountTypeLabel' => $this->discountType->getLabel(),
            'discountValue' => $this->discountValue,
            'minAmount' => $this->minAmount,
            'maxDiscountAmount' => $this->maxDiscountAmount,
            'requiredQuantity' => $this->requiredQuantity,
            'giftQuantity' => $this->giftQuantity,
            'giftProductIds' => $this->giftProductIds,
            'config' => $this->config,
            'valid' => $this->valid,
            'createdAt' => $this->getCreateTime()?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->getUpdateTime()?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieveAdminArray(): array
    {
        return $this->toAdminArray();
    }
}
