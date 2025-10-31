<?php

namespace PromotionEngineBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use PromotionEngineBundle\Repository\ProductRelationRepository;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\Arrayable\AdminArrayInterface;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;

/**
 * @implements AdminArrayInterface<string, mixed>
 */
#[ORM\Entity(repositoryClass: ProductRelationRepository::class)]
#[ORM\Table(name: 'ims_promotion_discount_product_relation', options: ['comment' => '促销优惠产品关系'])]
class ProductRelation implements AdminArrayInterface
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    private int $id = 0;

    public function getId(): int
    {
        return $this->id;
    }

    #[Ignore]
    #[ORM\ManyToOne(inversedBy: 'productRelations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Discount $discount = null;

    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['comment' => 'spuId'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    #[Assert\Regex(pattern: '/^\d+$/', message: 'SPU ID必须是数字')]
    private string $spuId;

    #[ORM\Column(type: Types::BIGINT, nullable: true, options: ['comment' => 'skuId'])]
    #[Assert\Length(max: 20)]
    #[Assert\Regex(pattern: '/^\d+$/', message: 'SKU ID必须是数字')]
    private ?string $skuId = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '总库存', 'default' => 0])]
    #[Assert\PositiveOrZero]
    private int $total = 0;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '赠送数量', 'default' => 1])]
    #[Assert\Positive]
    private int $giftQuantity = 1;

    /**
     * @return array<string, mixed>
     */
    public function retrieveAdminArray(): array
    {
        return [
            'spuId' => $this->getSpuId(),
            'skuId' => $this->getSkuId(),
            'discountId' => $this->getDiscount()?->getId(),
            'createTime' => $this->getCreateTime()?->format('Y-m-d H:i:s'),
        ];
    }

    public function getDiscount(): ?Discount
    {
        return $this->discount;
    }

    public function setDiscount(?Discount $discount): void
    {
        $this->discount = $discount;
    }

    public function getSpuId(): string
    {
        return $this->spuId;
    }

    public function setSpuId(string $spuId): void
    {
        $this->spuId = $spuId;
    }

    public function getSkuId(): ?string
    {
        return $this->skuId;
    }

    public function setSkuId(?string $skuId): void
    {
        $this->skuId = $skuId;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function setTotal(int $total): void
    {
        $this->total = $total;
    }

    public function getGiftQuantity(): int
    {
        return $this->giftQuantity;
    }

    public function setGiftQuantity(int $giftQuantity): void
    {
        $this->giftQuantity = $giftQuantity;
    }

    public function __toString(): string
    {
        return (string) $this->getId();
    }
}
