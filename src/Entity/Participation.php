<?php

namespace PromotionEngineBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use PromotionEngineBundle\Repository\ParticipationRepository;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\Arrayable\AdminArrayInterface;
use Tourze\DoctrineSnowflakeBundle\Service\SnowflakeIdGenerator;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;

#[ORM\Entity(repositoryClass: ParticipationRepository::class)]
#[ORM\Table(name: 'ims_promotion_participation', options: ['comment' => '促销参与记录'])]
class Participation implements \Stringable, AdminArrayInterface {
    use TimestampableAware;
    use BlameableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(SnowflakeIdGenerator::class)]
    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['comment' => 'ID'])]
    private ?string $id = null;

    #[ORM\ManyToOne]

    private ?UserInterface $user = null;

    /**
     * @var Collection<int, Campaign>
     */

    #[ORM\ManyToMany(targetEntity: Campaign::class, inversedBy: 'participations')]
    private Collection $campaigns;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true, options: ['comment' => '总价'])]
    private ?string $totalPrice = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true, options: ['comment' => '优惠扣减'])]
    private ?string $discountPrice = null;

    public function __construct()
    {
        $this->campaigns = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function setUser(?UserInterface $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, Campaign>
     */
    public function getCampaigns(): Collection
    {
        return $this->campaigns;
    }

    public function addCampaign(Campaign $campaign): static
    {
        if (!$this->campaigns->contains($campaign)) {
            $this->campaigns->add($campaign);
        }

        return $this;
    }

    public function removeCampaign(Campaign $campaign): static
    {
        $this->campaigns->removeElement($campaign);

        return $this;
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
        return (string) ($this->getId() ?? '');
    }

    public function retrieveAdminArray(): array
    {
        return [
            'id' => $this->getId(),
            'createTime' => $this->getCreateTime()?->format('Y-m-d H:i:s'),
            'updateTime' => $this->getUpdateTime()?->format('Y-m-d H:i:s'),
            'user' => $this->getUser()?->getUserIdentifier(),
            'totalPrice' => $this->getTotalPrice(),
            'discountPrice' => $this->getDiscountPrice(),
            'campaigns' => $this->getCampaigns()->map(fn($campaign) => $campaign->getId())->toArray(),
        ];
    }

}
