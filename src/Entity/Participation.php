<?php

namespace PromotionEngineBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use PromotionEngineBundle\Repository\ParticipationRepository;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\Arrayable\AdminArrayInterface;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;

/**
 * @implements AdminArrayInterface<string, mixed>
 */
#[ORM\Entity(repositoryClass: ParticipationRepository::class)]
#[ORM\Table(name: 'ims_promotion_participation', options: ['comment' => '促销参与记录'])]
class Participation implements \Stringable, AdminArrayInterface
{
    use SnowflakeKeyAware;
    use TimestampableAware;
    use BlameableAware;

    #[ORM\ManyToOne]
    private ?UserInterface $user = null;

    /**
     * @var Collection<int, Campaign>
     */
    #[ORM\ManyToMany(targetEntity: Campaign::class, inversedBy: 'participations')]
    private Collection $campaigns;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true, options: ['comment' => '总价'])]
    #[Assert\Length(max: 10)]
    #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/', message: '总价格式不正确')]
    #[Assert\PositiveOrZero]
    private ?string $totalPrice = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true, options: ['comment' => '优惠扣减'])]
    #[Assert\Length(max: 10)]
    #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/', message: '优惠扣减格式不正确')]
    #[Assert\PositiveOrZero]
    private ?string $discountPrice = null;

    public function __construct()
    {
        $this->campaigns = new ArrayCollection();
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function setUser(?UserInterface $user): void
    {
        $this->user = $user;
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

    public function getTotalPrice(): ?string
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(?string $totalPrice): void
    {
        $this->totalPrice = $totalPrice;
    }

    public function getDiscountPrice(): ?string
    {
        return $this->discountPrice;
    }

    public function setDiscountPrice(?string $discountPrice): void
    {
        $this->discountPrice = $discountPrice;
    }

    public function __toString(): string
    {
        return $this->getId() ?? '';
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
            'user' => $this->getUser()?->getUserIdentifier(),
            'totalPrice' => $this->getTotalPrice(),
            'discountPrice' => $this->getDiscountPrice(),
            'campaigns' => $this->getCampaigns()->map(fn ($campaign) => $campaign->getId())->toArray(),
        ];
    }
}
