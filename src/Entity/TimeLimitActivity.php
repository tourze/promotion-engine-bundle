<?php

namespace PromotionEngineBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use PromotionEngineBundle\Enum\ActivityStatus;
use PromotionEngineBundle\Enum\ActivityType;
use PromotionEngineBundle\Repository\TimeLimitActivityRepository;
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
#[ORM\Entity(repositoryClass: TimeLimitActivityRepository::class)]
#[ORM\Table(name: 'ims_promotion_time_limit_activity', options: ['comment' => '限时活动'])]
class TimeLimitActivity implements AdminArrayInterface, \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;
    use BlameableAware;

    #[Assert\NotBlank]
    #[Assert\Length(max: 120)]
    #[ORM\Column(length: 120, options: ['comment' => '活动名称'])]
    private string $name;

    #[Assert\Length(max: 65535)]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '活动描述'])]
    private ?string $description = null;

    #[Assert\NotNull]
    #[IndexColumn]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '开始时间'])]
    private \DateTimeInterface $startTime;

    #[Assert\NotNull]
    #[IndexColumn]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '结束时间'])]
    private \DateTimeInterface $endTime;

    #[Assert\NotNull]
    #[Assert\Choice(callback: [ActivityType::class, 'cases'])]
    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, enumType: ActivityType::class, options: ['comment' => '活动类型'])]
    private ActivityType $activityType;

    #[Assert\NotNull]
    #[Assert\Choice(callback: [ActivityStatus::class, 'cases'])]
    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, enumType: ActivityStatus::class, options: ['comment' => '活动状态', 'default' => 'pending'])]
    private ActivityStatus $status = ActivityStatus::PENDING;

    #[Assert\Type(type: 'bool')]
    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否启用预热', 'default' => false])]
    private bool $preheatEnabled = false;

    #[Assert\When(
        expression: 'this.isPreheatEnabled()',
        constraints: [new Assert\NotNull()]
    )]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '预热开始时间'])]
    private ?\DateTimeInterface $preheatStartTime = null;

    #[Assert\PositiveOrZero]
    #[IndexColumn]
    #[ORM\Column(options: ['comment' => '活动优先级', 'default' => 0])]
    private int $priority = 0;

    #[Assert\Type(type: 'bool')]
    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否独占（同商品不能参与其他活动）', 'default' => false])]
    private bool $exclusive = false;

    #[Assert\Positive]
    #[ORM\Column(nullable: true, options: ['comment' => '活动限量（限量抢购专用）'])]
    private ?int $totalLimit = null;

    #[Assert\PositiveOrZero]
    #[ORM\Column(options: ['comment' => '已售数量', 'default' => 0])]
    private int $soldQuantity = 0;

    /**
     * @var array<string>
     */
    #[Assert\All(constraints: [
        new Assert\Type(type: 'string'),
        new Assert\NotBlank(),
    ])]
    #[ORM\Column(type: Types::JSON, options: ['comment' => '参与商品ID列表'])]
    private array $productIds = [];

    #[Assert\Type(type: 'bool')]
    #[IndexColumn]
    #[TrackColumn]
    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['comment' => '有效', 'default' => 0])]
    private ?bool $valid = false;

    /**
     * @var Collection<int, Campaign>
     */
    #[Ignore]
    #[ORM\ManyToMany(targetEntity: Campaign::class, cascade: ['persist'])]
    #[ORM\JoinTable(name: 'ims_promotion_time_limit_activity_campaign')]
    private Collection $campaigns;

    public function __construct()
    {
        $this->campaigns = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
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

    public function getActivityType(): ActivityType
    {
        return $this->activityType;
    }

    public function setActivityType(ActivityType $activityType): void
    {
        $this->activityType = $activityType;
    }

    public function getStatus(): ActivityStatus
    {
        return $this->status;
    }

    public function setStatus(ActivityStatus $status): void
    {
        $this->status = $status;
    }

    public function isPreheatEnabled(): bool
    {
        return $this->preheatEnabled;
    }

    public function setPreheatEnabled(bool $preheatEnabled): void
    {
        $this->preheatEnabled = $preheatEnabled;
    }

    public function getPreheatStartTime(): ?\DateTimeInterface
    {
        return $this->preheatStartTime;
    }

    public function setPreheatStartTime(?\DateTimeInterface $preheatStartTime): void
    {
        $this->preheatStartTime = $preheatStartTime;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    public function isExclusive(): bool
    {
        return $this->exclusive;
    }

    public function setExclusive(bool $exclusive): void
    {
        $this->exclusive = $exclusive;
    }

    public function getTotalLimit(): ?int
    {
        return $this->totalLimit;
    }

    public function setTotalLimit(?int $totalLimit): void
    {
        $this->totalLimit = $totalLimit;
    }

    public function getSoldQuantity(): int
    {
        return $this->soldQuantity;
    }

    public function setSoldQuantity(int $soldQuantity): void
    {
        $this->soldQuantity = $soldQuantity;
    }

    /**
     * @return array<string>
     */
    public function getProductIds(): array
    {
        return $this->productIds;
    }

    /**
     * @param array<string> $productIds
     */
    public function setProductIds(array $productIds): void
    {
        $this->productIds = $productIds;
    }

    public function addProductId(string $productId): void
    {
        if (!in_array($productId, $this->productIds, true)) {
            $this->productIds[] = $productId;
        }
    }

    public function removeProductId(string $productId): void
    {
        $this->productIds = array_values(array_filter($this->productIds, fn ($id) => $id !== $productId));
    }

    public function isValid(): ?bool
    {
        return $this->valid;
    }

    public function setValid(?bool $valid): void
    {
        $this->valid = $valid;
    }

    /**
     * @return Collection<int, Campaign>
     */
    public function getCampaigns(): Collection
    {
        return $this->campaigns;
    }

    public function addCampaign(Campaign $campaign): void
    {
        if (!$this->campaigns->contains($campaign)) {
            $this->campaigns->add($campaign);
        }
    }

    public function removeCampaign(Campaign $campaign): void
    {
        $this->campaigns->removeElement($campaign);
    }

    public function getRemainingQuantity(): ?int
    {
        if (null === $this->totalLimit) {
            return null;
        }

        return max(0, $this->totalLimit - $this->soldQuantity);
    }

    public function isSoldOut(): bool
    {
        if (null === $this->totalLimit) {
            return false;
        }

        return $this->soldQuantity >= $this->totalLimit;
    }

    public function isInPreheatPeriod(?\DateTimeInterface $now = null): bool
    {
        if (!$this->preheatEnabled || null === $this->preheatStartTime) {
            return false;
        }

        $now ??= new \DateTimeImmutable();

        return $now >= $this->preheatStartTime && $now < $this->startTime;
    }

    public function isActive(?\DateTimeInterface $now = null): bool
    {
        $now ??= new \DateTimeImmutable();

        return $now >= $this->startTime && $now <= $this->endTime;
    }

    public function isFinished(?\DateTimeInterface $now = null): bool
    {
        $now ??= new \DateTimeImmutable();

        return $now > $this->endTime;
    }

    public function calculateCurrentStatus(?\DateTimeInterface $now = null): ActivityStatus
    {
        if ($this->isFinished($now)) {
            return ActivityStatus::FINISHED;
        }

        if ($this->isActive($now)) {
            return ActivityStatus::ACTIVE;
        }

        return ActivityStatus::PENDING;
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
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'startTime' => $this->getStartTime()->format('Y-m-d H:i:s'),
            'endTime' => $this->getEndTime()->format('Y-m-d H:i:s'),
            'activityType' => $this->getActivityType()->value,
            'activityTypeLabel' => $this->getActivityType()->getLabel(),
            'status' => $this->getStatus()->value,
            'statusLabel' => $this->getStatus()->getLabel(),
            'preheatEnabled' => $this->isPreheatEnabled(),
            'preheatStartTime' => $this->getPreheatStartTime()?->format('Y-m-d H:i:s'),
            'priority' => $this->getPriority(),
            'exclusive' => $this->isExclusive(),
            'totalLimit' => $this->getTotalLimit(),
            'soldQuantity' => $this->getSoldQuantity(),
            'remainingQuantity' => $this->getRemainingQuantity(),
            'productIds' => $this->getProductIds(),
            'valid' => $this->isValid(),
        ];
    }
}
