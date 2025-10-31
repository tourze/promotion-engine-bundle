<?php

namespace PromotionEngineBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use PromotionEngineBundle\Repository\CampaignRepository;
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
#[ORM\Entity(repositoryClass: CampaignRepository::class)]
#[ORM\Table(name: 'ims_promotion_campaign', options: ['comment' => '促销活动'])]
class Campaign implements AdminArrayInterface, \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;
    use BlameableAware;

    #[Assert\NotBlank]
    #[Assert\Length(max: 120)]
    #[ORM\Column(length: 120, options: ['comment' => '名称'])]
    private string $title;

    #[Assert\Length(max: 65535)]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '描述'])]
    private ?string $description = null;

    #[Assert\NotNull]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '开始时间'])]
    private \DateTimeInterface $startTime;

    #[Assert\NotNull]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '结束时间'])]
    private \DateTimeInterface $endTime;

    #[Assert\Type(type: 'bool')]
    #[ORM\Column(nullable: true, options: ['comment' => '排他'])]
    private ?bool $exclusive = null;

    #[Assert\PositiveOrZero]
    #[ORM\Column(options: ['comment' => '权重'])]
    private int $weight = 0;

    /**
     * @var Collection<int, Constraint>
     */
    #[ORM\OneToMany(mappedBy: 'campaign', targetEntity: Constraint::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $constraints;

    /**
     * @var Collection<int, Discount>
     */
    #[ORM\OneToMany(mappedBy: 'campaign', targetEntity: Discount::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $discounts;

    /**
     * @var Collection<int, Participation>
     */
    #[Ignore]
    #[ORM\ManyToMany(targetEntity: Participation::class, mappedBy: 'campaigns')]
    private Collection $participations;

    #[Assert\Type(type: 'bool')]
    #[IndexColumn]
    #[TrackColumn]
    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['comment' => '有效', 'default' => 0])]
    private ?bool $valid = false;

    public function __construct()
    {
        $this->constraints = new ArrayCollection();
        $this->discounts = new ArrayCollection();
        $this->participations = new ArrayCollection();
    }

    public function __toString(): string
    {
        if (null === $this->getId()) {
            return '';
        }

        return $this->getTitle();
    }

    public function isValid(): ?bool
    {
        return $this->valid;
    }

    public function setValid(?bool $valid): void
    {
        $this->valid = $valid;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getStartTime(): \DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeInterface $startTime): void
    {
        $this->startTime = $startTime;
    }

    public function getEndTime(): \DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTimeInterface $endTime): void
    {
        $this->endTime = $endTime;
    }

    public function isExclusive(): ?bool
    {
        return $this->exclusive;
    }

    public function setExclusive(?bool $exclusive): void
    {
        $this->exclusive = $exclusive;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function setWeight(int $weight): void
    {
        $this->weight = $weight;
    }

    /**
     * @return Collection<int, Constraint>
     */
    public function getConstraints(): Collection
    {
        return $this->constraints;
    }

    public function addConstraint(Constraint $constraint): void
    {
        if (!$this->constraints->contains($constraint)) {
            $this->constraints->add($constraint);
            $constraint->setCampaign($this);
        }
    }

    public function removeConstraint(Constraint $constraint): void
    {
        if ($this->constraints->removeElement($constraint)) {
            // set the owning side to null (unless already changed)
            if ($constraint->getCampaign() === $this) {
                $constraint->setCampaign(null);
            }
        }
    }

    /**
     * @return Collection<int, Discount>
     */
    public function getDiscounts(): Collection
    {
        return $this->discounts;
    }

    public function addDiscount(Discount $discount): void
    {
        if (!$this->discounts->contains($discount)) {
            $this->discounts->add($discount);
            $discount->setCampaign($this);
        }
    }

    public function removeDiscount(Discount $discount): void
    {
        if ($this->discounts->removeElement($discount)) {
            // set the owning side to null (unless already changed)
            if ($discount->getCampaign() === $this) {
                $discount->setCampaign(null);
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

    public function addParticipation(Participation $participation): void
    {
        if (!$this->participations->contains($participation)) {
            $this->participations->add($participation);
            $participation->addCampaign($this);
        }
    }

    public function removeParticipation(Participation $participation): void
    {
        if ($this->participations->removeElement($participation)) {
            $participation->removeCampaign($this);
        }
    }
}
