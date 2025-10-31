<?php

namespace PromotionEngineBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use PromotionEngineBundle\Enum\CompareType;
use PromotionEngineBundle\Enum\LimitType;
use PromotionEngineBundle\Repository\ConstraintRepository;
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
#[ORM\Entity(repositoryClass: ConstraintRepository::class)]
#[ORM\Table(name: 'ims_promotion_limit', options: ['comment' => '促销约束条件'])]
class Constraint implements \Stringable, AdminArrayInterface
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
    #[ORM\ManyToOne(inversedBy: 'constraints')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Campaign $campaign = null;

    #[Assert\Choice(callback: [CompareType::class, 'cases'])]
    #[ORM\Column(length: 30, enumType: CompareType::class, options: ['comment' => '对比类型'])]
    private CompareType $compareType;

    #[Assert\Choice(callback: [LimitType::class, 'cases'])]
    #[ORM\Column(length: 100, enumType: LimitType::class, options: ['comment' => '限制类型'])]
    private LimitType $limitType;

    #[Assert\Length(max: 65535)]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '范围值'])]
    private ?string $rangeValue = null;

    public function __toString(): string
    {
        if (null === $this->getId()) {
            return '';
        }

        return "{$this->getLimitType()->getLabel()} {$this->getCompareType()->getLabel()} {$this->getRangeValue()}";
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

    public function getCompareType(): CompareType
    {
        return $this->compareType;
    }

    public function setCompareType(CompareType $compareType): void
    {
        $this->compareType = $compareType;
    }

    public function getLimitType(): LimitType
    {
        return $this->limitType;
    }

    public function setLimitType(LimitType $limitType): void
    {
        $this->limitType = $limitType;
    }

    public function getRangeValue(): ?string
    {
        return $this->rangeValue;
    }

    public function setRangeValue(?string $rangeValue): void
    {
        $this->rangeValue = $rangeValue;
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
            'compareType' => $this->getCompareType()->value,
            'limitType' => $this->getLimitType()->value,
            'rangeValue' => $this->getRangeValue(),
        ];
    }
}
