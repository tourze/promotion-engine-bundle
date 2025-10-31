<?php

namespace PromotionEngineBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use PromotionEngineBundle\Repository\DiscountFreeConditionRepository;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\Arrayable\AdminArrayInterface;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;

/**
 * @implements AdminArrayInterface<string, mixed>
 */
#[ORM\Entity(repositoryClass: DiscountFreeConditionRepository::class)]
#[ORM\Table(name: 'ims_promotion_discount_free_condition', options: ['comment' => '赠品条件'])]
class DiscountFreeCondition implements AdminArrayInterface
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    private int $id = 0;

    #[Ignore]
    #[ORM\OneToOne(targetEntity: Discount::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(unique: true, nullable: false, onDelete: 'CASCADE')]
    private ?Discount $discount = null;

    #[ORM\Column(type: Types::INTEGER, nullable: false, options: ['comment' => '购买数量'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 10)]
    #[Assert\Regex(pattern: '/^\d+$/', message: '购买数量必须是数字')]
    private string $purchaseQuantity;

    #[ORM\Column(type: Types::INTEGER, nullable: false, options: ['comment' => '免费数量'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 10)]
    #[Assert\Regex(pattern: '/^\d+$/', message: '免费数量必须是数字')]
    private string $freeQuantity;

    public function getId(): int
    {
        return $this->id;
    }

    public function getDiscount(): ?Discount
    {
        return $this->discount;
    }

    public function setDiscount(?Discount $discount): void
    {
        $this->discount = $discount;
    }

    public function getPurchaseQuantity(): string
    {
        return $this->purchaseQuantity;
    }

    public function setPurchaseQuantity(string $purchaseQuantity): void
    {
        $this->purchaseQuantity = $purchaseQuantity;
    }

    public function getFreeQuantity(): string
    {
        return $this->freeQuantity;
    }

    public function setFreeQuantity(string $freeQuantity): void
    {
        $this->freeQuantity = $freeQuantity;
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieveAdminArray(): array
    {
        return [
            'purchaseQuantity' => $this->getPurchaseQuantity(),
            'freeQuantity' => $this->getFreeQuantity(),
            'discountId' => $this->getDiscount()?->getId(),
            'createTime' => $this->getCreateTime()?->format('Y-m-d H:i:s'),
        ];
    }

    public function __toString(): string
    {
        return (string) $this->getId();
    }
}
