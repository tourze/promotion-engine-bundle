<?php

namespace PromotionEngineBundle\Entity;

use AntdCpBundle\Builder\Field\DynamicFieldSet;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use PromotionEngineBundle\Repository\CampaignRepository;
use Symfony\Component\Serializer\Attribute\Ignore;
use Tourze\Arrayable\AdminArrayInterface;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Service\SnowflakeIdGenerator;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineTrackBundle\Attribute\TrackColumn;
use Tourze\DoctrineUserBundle\Attribute\CreatedByColumn;
use Tourze\DoctrineUserBundle\Attribute\UpdatedByColumn;
use Tourze\EasyAdmin\Attribute\Action\Creatable;
use Tourze\EasyAdmin\Attribute\Action\Deletable;
use Tourze\EasyAdmin\Attribute\Action\Editable;
use Tourze\EasyAdmin\Attribute\Action\Listable;
use Tourze\EasyAdmin\Attribute\Column\BoolColumn;
use Tourze\EasyAdmin\Attribute\Column\ExportColumn;
use Tourze\EasyAdmin\Attribute\Column\ListColumn;
use Tourze\EasyAdmin\Attribute\Field\FormField;
use Tourze\EasyAdmin\Attribute\Filter\Filterable;
use Tourze\EasyAdmin\Attribute\Filter\Keyword;
use Tourze\EasyAdmin\Attribute\Permission\AsPermission;

#[AsPermission(title: '促销活动')]
#[Listable]
#[Creatable]
#[Editable]
#[Deletable]
#[ORM\Entity(repositoryClass: CampaignRepository::class)]
#[ORM\Table(name: 'ims_promotion_campaign')]
class Campaign implements AdminArrayInterface, \Stringable
{
    use TimestampableAware;

    #[CreatedByColumn]
    #[ORM\Column(nullable: true, options: ['comment' => '创建人'])]
    private ?string $createdBy = null;

    #[UpdatedByColumn]
    #[ORM\Column(nullable: true, options: ['comment' => '更新人'])]
    private ?string $updatedBy = null;

    #[ExportColumn]
    #[ListColumn(order: -1, sorter: true)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(SnowflakeIdGenerator::class)]
    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['comment' => 'ID'])]
    private ?string $id = null;

    #[Keyword]
    #[ListColumn]
    #[FormField]
    #[ORM\Column(length: 120, options: ['comment' => '名称'])]
    private string $title;

    #[Keyword]
    #[ListColumn]
    #[FormField]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '描述'])]
    private ?string $description = null;

    #[Filterable]
    #[ListColumn(sorter: true)]
    #[FormField(span: 8)]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, options: ['comment' => '开始时间'])]
    private \DateTimeInterface $startTime;

    #[Filterable]
    #[ListColumn(sorter: true)]
    #[FormField(span: 8)]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, options: ['comment' => '结束时间'])]
    private \DateTimeInterface $endTime;

    #[ListColumn]
    #[BoolColumn]
    #[ORM\Column(nullable: true, options: ['comment' => '排他'])]
    private ?bool $exclusive = null;

    #[ListColumn]
    #[FormField(span: 8)]
    #[ORM\Column(options: ['comment' => '权重'])]
    private int $weight = 0;

    /**
     * @DynamicFieldSet()
     *
     * @var Collection<int, Constraint>
     */
    #[ListColumn(title: '参与限制')]
    #[FormField(title: '参与限制')]
    #[ORM\OneToMany(mappedBy: 'campaign', targetEntity: Constraint::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $constraints;

    /**
     * @DynamicFieldSet()
     *
     * @var Collection<int, Discount>
     */
    #[ListColumn(title: '享受折扣')]
    #[FormField(title: '享受折扣')]
    #[ORM\OneToMany(mappedBy: 'campaign', targetEntity: Discount::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $discounts;

    /**
     * @var Collection<int, Participation>
     */
    #[Ignore]
    #[ORM\ManyToMany(targetEntity: Participation::class, mappedBy: 'campaigns')]
    private Collection $participations;

    #[BoolColumn]
    #[IndexColumn]
    #[TrackColumn]
    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['comment' => '有效', 'default' => 0])]
    #[ListColumn(order: 97)]
    #[FormField(order: 97)]
    private ?bool $valid = false;

    public function __construct()
    {
        $this->constraints = new ArrayCollection();
        $this->discounts = new ArrayCollection();
        $this->participations = new ArrayCollection();
    }

    public function __toString()
    {
        if (!$this->getId()) {
            return '';
        }

        return "{$this->getTitle()}";
    }

    public function setCreatedBy(?string $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    public function setUpdatedBy(?string $updatedBy): self
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    public function getUpdatedBy(): ?string
    {
        return $this->updatedBy;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function isValid(): ?bool
    {
        return $this->valid;
    }

    public function setValid(?bool $valid): self
    {
        $this->valid = $valid;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getStartTime(): \DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeInterface $startTime): static
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): \DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTimeInterface $endTime): static
    {
        $this->endTime = $endTime;

        return $this;
    }

    public function isExclusive(): ?bool
    {
        return $this->exclusive;
    }

    public function setExclusive(?bool $exclusive): static
    {
        $this->exclusive = $exclusive;

        return $this;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function setWeight(int $weight): static
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * @return Collection<int, Constraint>
     */
    public function getConstraints(): Collection
    {
        return $this->constraints;
    }

    public function addConstraint(Constraint $constraint): static
    {
        if (!$this->constraints->contains($constraint)) {
            $this->constraints->add($constraint);
            $constraint->setCampaign($this);
        }

        return $this;
    }

    public function removeConstraint(Constraint $constraint): static
    {
        if ($this->constraints->removeElement($constraint)) {
            // set the owning side to null (unless already changed)
            if ($constraint->getCampaign() === $this) {
                $constraint->setCampaign(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Discount>
     */
    public function getDiscounts(): Collection
    {
        return $this->discounts;
    }

    public function addDiscount(Discount $discount): static
    {
        if (!$this->discounts->contains($discount)) {
            $this->discounts->add($discount);
            $discount->setCampaign($this);
        }

        return $this;
    }

    public function removeDiscount(Discount $discount): static
    {
        if ($this->discounts->removeElement($discount)) {
            // set the owning side to null (unless already changed)
            if ($discount->getCampaign() === $this) {
                $discount->setCampaign(null);
            }
        }

        return $this;
    }

    public function retrieveAdminArray(): array
    {
        return [
            'id' => $this->getId(),
            'createTime' => $this->getCreateTime()?->format('Y-m-d H:i:s'),
            'updateTime' => $this->getUpdateTime()?->format('Y-m-d H:i:s'),
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
            'startTime' => $this->getStartTime()->format('Y-m-d H:i:s'),
            'endTime' => $this->getEndTime()->format('Y-m-d H:i:s'),
            'exclusive' => $this->isExclusive(),
            'weight' => $this->getWeight(),
        ];
    }

    /**
     * @return Collection<int, Participation>
     */
    public function getParticipations(): Collection
    {
        return $this->participations;
    }

    public function addParticipation(Participation $participation): static
    {
        if (!$this->participations->contains($participation)) {
            $this->participations->add($participation);
            $participation->addCampaign($this);
        }

        return $this;
    }

    public function removeParticipation(Participation $participation): static
    {
        if ($this->participations->removeElement($participation)) {
            $participation->removeCampaign($this);
        }

        return $this;
    }
}
