<?php

namespace PromotionEngineBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use PromotionEngineBundle\Repository\DiscountConditionRepository;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\Arrayable\AdminArrayInterface;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;

/**
 * @implements AdminArrayInterface<string, mixed>
 */
#[ORM\Entity(repositoryClass: DiscountConditionRepository::class)]
#[ORM\Table(name: 'ims_promotion_discount_condition', options: ['comment' => '优惠条件'])]
class DiscountCondition implements AdminArrayInterface
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

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[ORM\Column(type: Types::STRING, nullable: false, options: ['comment' => '条件1'])]
    private string $condition1;

    #[Assert\Length(max: 255)]
    #[ORM\Column(type: Types::STRING, nullable: true, options: ['comment' => '条件2'])]
    private ?string $condition2 = null;

    #[Assert\Length(max: 255)]
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

    /**
     * @return array<string, mixed>
     */
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

    public function getCondition2(): ?string
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

    public function __toString(): string
    {
        return (string) $this->getId();
    }
}
