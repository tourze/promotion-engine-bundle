<?php

namespace PromotionEngineBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use PromotionEngineBundle\Entity\Campaign;
use PromotionEngineBundle\Entity\Participation;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
#[When(env: 'dev')]
class ParticipationFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public const PARTICIPATION_SUMMER_USER1 = 'participation-summer-user1';
    public const PARTICIPATION_WINTER_USER2 = 'participation-winter-user2';
    public const PARTICIPATION_NEW_USER3 = 'participation-new-user3';

    public static function getGroups(): array
    {
        return ['promotion-engine', 'test'];
    }

    public function load(ObjectManager $manager): void
    {
        $summerSale = $this->getReference(CampaignFixtures::CAMPAIGN_SUMMER_SALE, Campaign::class);
        $winterDiscount = $this->getReference(CampaignFixtures::CAMPAIGN_WINTER_DISCOUNT, Campaign::class);
        $newUser = $this->getReference(CampaignFixtures::CAMPAIGN_NEW_USER, Campaign::class);

        $summerParticipation = new Participation();
        $summerParticipation->addCampaign($summerSale);
        $summerParticipation->setTotalPrice('299.99');
        $summerParticipation->setDiscountPrice('45.00');
        $manager->persist($summerParticipation);

        $winterParticipation = new Participation();
        $winterParticipation->addCampaign($winterDiscount);
        $winterParticipation->setTotalPrice('599.99');
        $winterParticipation->setDiscountPrice('50.00');
        $manager->persist($winterParticipation);

        $newUserParticipation = new Participation();
        $newUserParticipation->addCampaign($newUser);
        $newUserParticipation->setTotalPrice('129.99');
        $newUserParticipation->setDiscountPrice('26.00');
        $manager->persist($newUserParticipation);

        $multiCampaignParticipation = new Participation();
        $multiCampaignParticipation->addCampaign($summerSale);
        $multiCampaignParticipation->addCampaign($newUser);
        $multiCampaignParticipation->setTotalPrice('399.99');
        $multiCampaignParticipation->setDiscountPrice('80.00');
        $manager->persist($multiCampaignParticipation);

        $manager->flush();

        $this->addReference(self::PARTICIPATION_SUMMER_USER1, $summerParticipation);
        $this->addReference(self::PARTICIPATION_WINTER_USER2, $winterParticipation);
        $this->addReference(self::PARTICIPATION_NEW_USER3, $newUserParticipation);
    }

    public function getDependencies(): array
    {
        return [
            CampaignFixtures::class,
        ];
    }
}
