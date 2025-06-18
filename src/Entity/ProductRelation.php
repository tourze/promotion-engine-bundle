<?php

namespace PromotionEngineBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use PromotionEngineBundle\Repository\ProductRelationRepository;
use Symfony\Component\Serializer\Attribute\Ignore;
use Tourze\Arrayable\AdminArrayInterface;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\EasyAdmin\Attribute\Action\Listable;

#[Listable]
#[ORM\Entity(repositoryClass: ProductRelationRepository::class)]
#[ORM\Table(name: 'ims_promotion_discount_product_relation')]
class ProductRelation implements AdminArrayInterface
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    private ?int $id = 0;

    public function getId(): ?int
    {
        return $this->id;
    }
    use TimestampableAware;

    #[Ignore]
    #[ORM\ManyToOne(inversedBy: 'productRelations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Discount $discount = null;

    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['comment' => 'spuId'])]
    private string $spuId;

    #[ORM\Column(type: Types::BIGINT, nullable: true, options: ['comment' => 'skuId'])]
    private ?string $skuId = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '总库存', 'default' => 0])]
    private int $total = 0;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '赠送数量', 'default' => 1])]
    private int $giftQuantity = 1;

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
        return (string) ($this->getId() ?? '');
    }

}
