<?php

namespace PromotionEngineBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use PromotionEngineBundle\Repository\ActivityProductRepository;
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
#[ORM\Entity(repositoryClass: ActivityProductRepository::class)]
#[ORM\Table(name: 'ims_promotion_activity_product', options: ['comment' => '活动商品关联表'])]
#[ORM\UniqueConstraint(name: 'uniq_activity_product', fields: ['activity_id', 'productId'])]
class ActivityProduct implements AdminArrayInterface, \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;
    use BlameableAware;

    #[Assert\NotBlank]
    #[ORM\Column(length: 255, options: ['comment' => '商品ID'])]
    private string $productId;

    #[Assert\PositiveOrZero]
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['comment' => '活动价格'])]
    private string $activityPrice = '0.00';

    #[Assert\PositiveOrZero]
    #[ORM\Column(options: ['comment' => '限购数量（每用户）', 'default' => 1])]
    private int $limitPerUser = 1;

    #[Assert\PositiveOrZero]
    #[ORM\Column(options: ['comment' => '活动库存', 'default' => 0])]
    private int $activityStock = 0;

    #[Assert\PositiveOrZero]
    #[ORM\Column(options: ['comment' => '已售数量', 'default' => 0])]
    private int $soldQuantity = 0;

    #[Assert\Type(type: 'bool')]
    #[IndexColumn]
    #[TrackColumn]
    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['comment' => '有效', 'default' => 0])]
    private ?bool $valid = false;

    #[ORM\ManyToOne(targetEntity: TimeLimitActivity::class)]
    #[ORM\JoinColumn(name: 'activity_id', referencedColumnName: 'id', nullable: false)]
    private ?TimeLimitActivity $activity = null;

    public function __toString(): string
    {
        if (null === $this->getId()) {
            return '';
        }

        return "{$this->activity?->getId()}:{$this->getProductId()}";
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function setProductId(string $productId): void
    {
        $this->productId = $productId;
    }

    public function getActivityPrice(): string
    {
        return $this->activityPrice;
    }

    public function setActivityPrice(string $activityPrice): void
    {
        $this->activityPrice = $activityPrice;
    }

    public function getLimitPerUser(): int
    {
        return $this->limitPerUser;
    }

    public function setLimitPerUser(int $limitPerUser): void
    {
        $this->limitPerUser = $limitPerUser;
    }

    public function getActivityStock(): int
    {
        return $this->activityStock;
    }

    public function setActivityStock(int $activityStock): void
    {
        $this->activityStock = $activityStock;
    }

    public function getSoldQuantity(): int
    {
        return $this->soldQuantity;
    }

    public function setSoldQuantity(int $soldQuantity): void
    {
        $this->soldQuantity = $soldQuantity;
    }

    public function increaseSoldQuantity(int $quantity = 1): void
    {
        $this->soldQuantity += $quantity;
    }

    public function decreaseSoldQuantity(int $quantity = 1): void
    {
        $this->soldQuantity = max(0, $this->soldQuantity - $quantity);
    }

    public function isValid(): ?bool
    {
        return $this->valid;
    }

    public function setValid(?bool $valid): void
    {
        $this->valid = $valid;
    }

    public function getActivity(): ?TimeLimitActivity
    {
        return $this->activity;
    }

    public function setActivity(?TimeLimitActivity $activity): void
    {
        $this->activity = $activity;
    }

    public function getRemainingStock(): int
    {
        return max(0, $this->activityStock - $this->soldQuantity);
    }

    public function isStockAvailable(int $quantity = 1): bool
    {
        return $this->getRemainingStock() >= $quantity;
    }

    public function isSoldOut(): bool
    {
        return $this->soldQuantity >= $this->activityStock;
    }

    public function getStockUtilization(): float
    {
        if ($this->activityStock <= 0) {
            return 0.0;
        }

        return min(100.0, ($this->soldQuantity / $this->activityStock) * 100);
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieveAdminArray(): array
    {
        return [
            'id' => $this->getId(),
            'createTime' => $this->getCreateTime()?->format('Y-m-d H:i:s'),
            'updateTime' => $this->getUpdateTime()?->format('Y-m-d H:i:s'),
            'activityId' => $this->activity?->getId(),
            'productId' => $this->getProductId(),
            'activityPrice' => $this->getActivityPrice(),
            'limitPerUser' => $this->getLimitPerUser(),
            'activityStock' => $this->getActivityStock(),
            'soldQuantity' => $this->getSoldQuantity(),
            'remainingStock' => $this->getRemainingStock(),
            'stockUtilization' => $this->getStockUtilization(),
            'isSoldOut' => $this->isSoldOut(),
            'valid' => $this->isValid(),
        ];
    }
}
