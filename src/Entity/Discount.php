<?php

namespace PromotionEngineBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use PromotionEngineBundle\Enum\DiscountType;
use PromotionEngineBundle\Repository\DiscountRepository;
use Symfony\Component\Serializer\Attribute\Ignore;
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
#[ORM\Entity(repositoryClass: DiscountRepository::class)]
#[ORM\Table(name: 'ims_promotion_discount', options: ['comment' => '促销优惠'])]
class Discount implements AdminArrayInterface, \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;
    use BlameableAware;

    #[Assert\Type(type: 'bool')]
    #[IndexColumn]
    #[TrackColumn]
    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['comment' => '有效', 'default' => 0])]
    private ?bool $valid = false;

    #[Ignore]
    #[ORM\ManyToOne(inversedBy: 'discounts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Campaign $campaign = null;

    #[Assert\Type(type: 'bool')]
    #[ORM\Column(type: Types::TEXT, nullable: false, options: ['default' => 0, 'comment' => '是否限量'])]
    private bool $isLimited = false;

    #[Assert\PositiveOrZero]
    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['default' => 0, 'comment' => '配额数'])]
    private int $quota = 0;

    #[Assert\PositiveOrZero]
    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['default' => 0, 'comment' => '参与数量'])]
    private ?int $number = 0;

    #[Assert\Choice(callback: [DiscountType::class, 'cases'])]
    #[ORM\Column(length: 50, enumType: DiscountType::class, options: ['comment' => '活动优惠'])]
    private DiscountType $type;

    #[Assert\Length(max: 13)]
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true, options: ['comment' => '数值'])]
    private ?string $value = null;

    #[Assert\Length(max: 200)]
    #[ORM\Column(length: 200, nullable: true, options: ['comment' => '备注'])]
    private ?string $remark = null;

    /**
     * @var Collection<int, ProductRelation>
     */
    #[ORM\OneToMany(mappedBy: 'discount', targetEntity: ProductRelation::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $productRelations;

    public function __construct()
    {
        $this->productRelations = new ArrayCollection();
    }

    public function __toString(): string
    {
        if (null === $this->getId()) {
            return '';
        }

        return "{$this->getType()->getLabel()} {$this->getValue()}";
    }

    public function isValid(): ?bool
    {
        return $this->valid;
    }

    public function setValid(?bool $valid): void
    {
        $this->valid = $valid;
    }

    public function getCampaign(): ?Campaign
    {
        return $this->campaign;
    }

    public function setCampaign(?Campaign $campaign): void
    {
        $this->campaign = $campaign;
    }

    public function getType(): DiscountType
    {
        return $this->type;
    }

    public function setType(DiscountType $type): void
    {
        $this->type = $type;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): void
    {
        $this->value = $value;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function setRemark(?string $remark): void
    {
        $this->remark = $remark;
    }

    /**
     * @return Collection<int, ProductRelation>
     */
    public function getProductRelations(): Collection
    {
        return $this->productRelations;
    }

    public function addProductRelation(ProductRelation $productRelation): void
    {
        if (!$this->productRelations->contains($productRelation)) {
            $this->productRelations->add($productRelation);
            $productRelation->setDiscount($this);
        }
    }

    public function removeProductRelation(ProductRelation $productRelation): void
    {
        if ($this->productRelations->removeElement($productRelation)) {
            // set the owning side to null (unless already changed)
            if ($productRelation->getDiscount() === $this) {
                $productRelation->setDiscount(null);
            }
        }
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
            'type' => $this->getType()->value,
            'value' => $this->getValue(),
            'remark' => $this->getRemark(),
            'number' => $this->getNumber(),
            'quota' => $this->getQuota(),
            'isLimited' => $this->isLimited(),
        ];
    }

    public function isLimited(): bool
    {
        return $this->isLimited;
    }

    public function setIsLimited(bool $isLimited): void
    {
        $this->isLimited = $isLimited;
    }

    public function getQuota(): int
    {
        return $this->quota;
    }

    public function setQuota(int $quota): void
    {
        $this->quota = $quota;
    }

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function setNumber(?int $number): void
    {
        $this->number = $number;
    }
}
