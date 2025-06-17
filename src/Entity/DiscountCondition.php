<?php

namespace PromotionEngineBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use PromotionEngineBundle\Repository\DiscountConditionRepository;
use Symfony\Component\Serializer\Attribute\Ignore;
use Tourze\Arrayable\AdminArrayInterface;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\EasyAdmin\Attribute\Action\Creatable;
use Tourze\EasyAdmin\Attribute\Action\Deletable;
use Tourze\EasyAdmin\Attribute\Action\Editable;
use Tourze\EasyAdmin\Attribute\Action\Listable;
use Tourze\EasyAdmin\Attribute\Column\ExportColumn;
use Tourze\EasyAdmin\Attribute\Column\ListColumn;
use Tourze\EasyAdmin\Attribute\Permission\AsPermission;

#[AsPermission(title: '优惠条件表')]
#[Listable]
#[Creatable]
#[Editable]
#[Deletable]
#[ORM\Entity(repositoryClass: DiscountConditionRepository::class)]
#[ORM\Table(name: 'ims_promotion_discount_condition')]
class DiscountCondition implements AdminArrayInterface
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
    #[ORM\Column(type: Types::STRING, nullable: false, options: ['comment' => '条件1'])]
    private string $condition1;

    #[ORM\Column(type: Types::STRING, nullable: true, options: ['comment' => '条件2'])]
    private ?string $condition2 = null;

    #[ORM\Column(type: Types::STRING, nullable: true, options: ['comment' => '条件3'])]
    private ?string $condition3 = null;

    public function getDiscount(): ?Discount
    {
        return $this->discount;
    }

    public function setDiscount(?Discount $discount): void
    {
        $this->discount = $discount;
    }

    public function retrieveAdminArray(): array
    {
        return [
            'condition1' => $this->getCondition1(),
            'condition2' => $this->getCondition2(),
            'condition3' => $this->getCondition3(),
            'discountId' => $this->getDiscount()?->getId(),
            'createTime' => $this->getCreateTime()?->format('Y-m-d H:i:s'),
        ];
    }

    public function getCondition1(): string
    {
        return $this->condition1;
    }

    public function setCondition1(string $condition1): void
    {
        $this->condition1 = $condition1;
    }

    public function getCondition2(): string
    {
        return $this->condition2;
    }

    public function setCondition2(string $condition2): void
    {
        $this->condition2 = $condition2;
    }

    public function getCondition3(): ?string
    {
        return $this->condition3;
    }

    public function setCondition3(?string $condition3): void
    {
        $this->condition3 = $condition3;
    }
}
