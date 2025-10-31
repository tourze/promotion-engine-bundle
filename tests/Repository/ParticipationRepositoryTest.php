<?php

namespace PromotionEngineBundle\Tests\Repository;

use BizUserBundle\Entity\BizUser;
use BizUserBundle\Repository\BizUserRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PromotionEngineBundle\Entity\Campaign;
use PromotionEngineBundle\Entity\Participation;
use PromotionEngineBundle\Repository\CampaignRepository;
use PromotionEngineBundle\Repository\ParticipationRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(ParticipationRepository::class)]
#[RunTestsInSeparateProcesses]
final class ParticipationRepositoryTest extends AbstractRepositoryTestCase
{
    protected function onSetUp(): void
    {
        // 基础设置，由父类处理
    }

    public function testRepositoryIsServiceEntityRepository(): void
    {
        $repository = self::getService(ParticipationRepository::class);
        $this->assertInstanceOf(ServiceEntityRepository::class, $repository);
    }

    public function testRepositoryConstruction(): void
    {
        $repository = self::getService(ParticipationRepository::class);
        $this->assertInstanceOf(ParticipationRepository::class, $repository);
    }

    public function testSave(): void
    {
        $repository = self::getService(ParticipationRepository::class);

        $participation = $this->createTestParticipation();
        $repository->save($participation);

        $foundParticipation = $repository->find($participation->getId());
        $this->assertNotNull($foundParticipation);
        $this->assertInstanceOf(Participation::class, $foundParticipation);
        $this->assertSame('100.00', $foundParticipation->getTotalPrice());
        $this->assertSame('10.00', $foundParticipation->getDiscountPrice());
    }

    public function testRemove(): void
    {
        $repository = self::getService(ParticipationRepository::class);

        $participation = $this->createTestParticipation();
        $repository->save($participation);
        $id = $participation->getId();

        $repository->remove($participation);

        $foundParticipation = $repository->find($id);
        $this->assertNull($foundParticipation);
    }

    public function testFindByWithNullCriteria(): void
    {
        $repository = self::getService(ParticipationRepository::class);

        $this->clearExistingData($repository);

        $participation1 = $this->createTestParticipation('100.00', null);
        $repository->save($participation1);

        $participationsWithNullDiscountPrice = $repository->createQueryBuilder('p')
            ->where('p.discountPrice IS NULL')
            ->getQuery()
            ->getResult()
        ;

        $this->assertIsArray($participationsWithNullDiscountPrice);
        $this->assertGreaterThanOrEqual(1, count($participationsWithNullDiscountPrice));

        $found = false;
        foreach ($participationsWithNullDiscountPrice as $participation) {
            if ('100.00' === $participation->getTotalPrice()) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function testFindOneByAssociationUserShouldReturnMatchingEntity(): void
    {
        $repository = self::getService(ParticipationRepository::class);

        $this->clearExistingData($repository);

        $user1 = $this->createTestBizUser('user1');
        $user2 = $this->createTestBizUser('user2');
        $campaign = $this->createTestCampaign();

        $participation1 = new Participation();
        $participation1->setUser($user1);
        $participation1->addCampaign($campaign);
        $participation1->setTotalPrice('100.00');
        $participation1->setDiscountPrice('10.00');
        $repository->save($participation1);

        $participation2 = new Participation();
        $participation2->setUser($user2);
        $participation2->addCampaign($campaign);
        $participation2->setTotalPrice('200.00');
        $participation2->setDiscountPrice('20.00');
        $repository->save($participation2);

        $foundByUser = $repository->findOneBy(['user' => $user1]);
        $this->assertNotNull($foundByUser);
        $this->assertInstanceOf(Participation::class, $foundByUser);
        $this->assertSame('100.00', $foundByUser->getTotalPrice());
        $this->assertSame($participation1->getId(), $foundByUser->getId());
    }

    public function testFindOneByAssociationCampaignsShouldReturnMatchingEntity(): void
    {
        $repository = self::getService(ParticipationRepository::class);

        $this->clearExistingData($repository);

        $user = $this->createTestBizUser('testuser');
        $campaign1 = $this->createTestCampaign();
        $campaign2 = $this->createTestCampaign();

        $participation1 = new Participation();
        $participation1->setUser($user);
        $participation1->addCampaign($campaign1);
        $participation1->setTotalPrice('100.00');
        $participation1->setDiscountPrice('10.00');
        $repository->save($participation1);

        $participation2 = new Participation();
        $participation2->setUser($user);
        $participation2->addCampaign($campaign2);
        $participation2->setTotalPrice('200.00');
        $participation2->setDiscountPrice('20.00');
        $repository->save($participation2);

        $foundByCampaign = $repository->createQueryBuilder('p')
            ->join('p.campaigns', 'c')
            ->where('c.id = :campaignId')
            ->setParameter('campaignId', $campaign1->getId())
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        $this->assertNotNull($foundByCampaign);
        $this->assertSame('100.00', $foundByCampaign->getTotalPrice());
    }

    public function testCountByAssociationUserShouldReturnCorrectNumber(): void
    {
        $repository = self::getService(ParticipationRepository::class);

        $this->clearExistingData($repository);

        $user1 = $this->createTestBizUser('user1');
        $user2 = $this->createTestBizUser('user2');
        $campaign = $this->createTestCampaign();

        for ($i = 1; $i <= 4; ++$i) {
            $participation = new Participation();
            $participation->setUser($user1);
            $participation->addCampaign($campaign);
            $participation->setTotalPrice((string) (100 * $i));
            $participation->setDiscountPrice((string) (10 * $i));
            $repository->save($participation);
        }

        for ($i = 1; $i <= 2; ++$i) {
            $participation = new Participation();
            $participation->setUser($user2);
            $participation->addCampaign($campaign);
            $participation->setTotalPrice((string) (200 * $i));
            $participation->setDiscountPrice((string) (20 * $i));
            $repository->save($participation);
        }

        $count = $repository->count(['user' => $user1]);
        $this->assertSame(4, $count);
    }

    public function testCountWithNullCriteria(): void
    {
        $repository = self::getService(ParticipationRepository::class);

        $this->clearExistingData($repository);

        $participation1 = $this->createTestParticipation('100.00', null);
        $repository->save($participation1);

        $nonNullTotalPriceCount = (int) $repository->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.totalPrice IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $this->assertGreaterThanOrEqual(1, $nonNullTotalPriceCount);

        $totalCount = $repository->count([]);
        $this->assertSame(1, $totalCount);
        $this->assertSame($totalCount, $nonNullTotalPriceCount);
    }

    protected function createNewEntity(): object
    {
        return $this->createTestParticipation();
    }

    protected function getRepository(): ParticipationRepository
    {
        return self::getService(ParticipationRepository::class);
    }

    private function createTestCampaign(): Campaign
    {
        $campaign = new Campaign();
        $campaign->setTitle('测试活动');
        $campaign->setStartTime(new \DateTimeImmutable('2024-01-01'));
        $campaign->setEndTime(new \DateTimeImmutable('2024-12-31'));

        $campaignRepository = self::getService(CampaignRepository::class);
        $campaignRepository->save($campaign);

        return $campaign;
    }

    private function createTestParticipation(
        string $totalPrice = '100.00',
        ?string $discountPrice = '10.00',
    ): Participation {
        $user = $this->createTestBizUser('testuser');
        $campaign = $this->createTestCampaign();

        $participation = new Participation();
        $participation->setUser($user);
        $participation->addCampaign($campaign);
        $participation->setTotalPrice($totalPrice);
        $participation->setDiscountPrice($discountPrice);

        return $participation;
    }

    private function clearExistingData(ParticipationRepository $repository): void
    {
        $allParticipations = $repository->findAll();
        foreach ($allParticipations as $participation) {
            if ($participation instanceof Participation) {
                $repository->remove($participation);
            }
        }
    }

    private function createTestBizUser(string $username): BizUser
    {
        $user = new BizUser();
        $uniqueUsername = $username . '_' . uniqid();
        $user->setUsername($uniqueUsername);
        $user->setEmail($uniqueUsername . '@test.com');
        $user->setPasswordHash('hashed_password');

        $userRepository = self::getService(BizUserRepository::class);
        $userRepository->save($user);

        return $user;
    }
}
