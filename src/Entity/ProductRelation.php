<?php

namespace PromotionEngineBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use PromotionEngineBundle\Repository\ProductRelationRepository;
use Symfony\Component\Serializer\Attribute\Ignore;
use Tourze\Arrayable\AdminArrayInterface;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\EasyAdmin\Attribute\Action\Creatable;
use Tourze\EasyAdmin\Attribute\Action\Deletable;
use Tourze\EasyAdmin\Attribute\Action\Editable;
use Tourze\EasyAdmin\Attribute\Action\Listable;
use Tourze\EasyAdmin\Attribute\Column\ExportColumn;
use Tourze\EasyAdmin\Attribute\Column\ListColumn;
use Tourze\EasyAdmin\Attribute\Field\FormField;
use Tourze\EasyAdmin\Attribute\Permission\AsPermission;

#[AsPermission(title: '享受折扣')]
#[Listable]
#[Creatable]
#[Editable]
#[Deletable]
#[ORM\Entity(repositoryClass: ProductRelationRepository::class)]
#[ORM\Table(name: 'ims_promotion_discount_product_relation')]
class ProductRelation implements AdminArrayInterface
{
    #[ListColumn(order: -1)]
    #[ExportColumn]
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

    #[ListColumn]
    #[FormField(span: 6)]
    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['comment' => 'spuId'])]
    private string $spuId;

    #[ListColumn]
    #[FormField(span: 6)]
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
}
