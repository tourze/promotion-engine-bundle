<?php

namespace PromotionEngineBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use PromotionEngineBundle\Repository\DiscountFreeConditionRepository;
use Symfony\Component\Serializer\Attribute\Ignore;
use Tourze\Arrayable\AdminArrayInterface;
use Tourze\EasyAdmin\Attribute\Action\Creatable;
use Tourze\EasyAdmin\Attribute\Action\Deletable;
use Tourze\EasyAdmin\Attribute\Action\Editable;
use Tourze\EasyAdmin\Attribute\Action\Listable;
use Tourze\EasyAdmin\Attribute\Column\ListColumn;
use Tourze\EasyAdmin\Attribute\Permission\AsPermission;

#[AsPermission(title: '优惠买N得M关系表')]
#[Listable]
#[Creatable]
#[Editable]
#[Deletable]
#[ORM\Entity(repositoryClass: DiscountFreeConditionRepository::class)]
#[ORM\Table(name: 'ims_promotion_discount_free_condition')]
class DiscountFreeCondition implements AdminArrayInterface
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
    #[Filterable]
    #[IndexColumn]
    #[ListColumn(order: 98, sorter: true)]
    #[ExportColumn]
    #[CreateTimeColumn]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '创建时间'])]
    private ?\DateTimeInterface $createTime = null;

    #[UpdateTimeColumn]
    #[ListColumn(order: 99, sorter: true)]
    #[Filterable]
    #[ExportColumn]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '更新时间'])]
    private ?\DateTimeInterface $updateTime = null;

    public function setCreateTime(?\DateTimeInterface $createdAt): void
    {
        $this->createTime = $createdAt;
    }

    public function getCreateTime(): ?\DateTimeInterface
    {
        return $this->createTime;
    }

    public function setUpdateTime(?\DateTimeInterface $updateTime): void
    {
        $this->updateTime = $updateTime;
    }

    public function getUpdateTime(): ?\DateTimeInterface
    {
        return $this->updateTime;
    }

    #[Ignore]
    #[ORM\OneToOne(targetEntity: Discount::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(unique: true, nullable: false, onDelete: 'CASCADE')]
    private ?Discount $discount = null;

    #[ListColumn]
    #[ORM\Column(type: Types::INTEGER, nullable: false, options: ['comment' => '购买数量'])]
    private string $purchaseQuantity;

    #[ListColumn]
    #[ORM\Column(type: Types::INTEGER, nullable: false, options: ['comment' => '免费数量'])]
    private string $freeQuantity;

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

    public function retrieveAdminArray(): array
    {
        return [
            'purchaseQuantity' => $this->getPurchaseQuantity(),
            'freeQuantity' => $this->getFreeQuantity(),
            'discountId' => $this->getDiscount()?->getId(),
            'createTime' => $this->getCreateTime()?->format('Y-m-d H:i:s'),
        ];
    }
}
