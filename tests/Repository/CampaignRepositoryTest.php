<?php

namespace PromotionEngineBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PromotionEngineBundle\Entity\Campaign;
use PromotionEngineBundle\Repository\CampaignRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(CampaignRepository::class)]
#[RunTestsInSeparateProcesses]
final class CampaignRepositoryTest extends AbstractRepositoryTestCase
{
    protected function onSetUp(): void
    {
        // 基础设置，由父类处理
    }

    public function testRepositoryIsServiceEntityRepository(): void
    {
        $repository = self::getService(CampaignRepository::class);
        $this->assertInstanceOf(ServiceEntityRepository::class, $repository);
    }

    public function testRepositoryConstruction(): void
    {
        $repository = self::getService(CampaignRepository::class);
        $this->assertInstanceOf(CampaignRepository::class, $repository);
    }

    // findBy() 方法测试

    // findOneBy() 方法测试

    // findAll() 方法测试

    // find() 方法测试

    // save() 和 remove() 方法测试
    public function testSaveShouldPersistEntity(): void
    {
        $repository = self::getService(CampaignRepository::class);
        $campaign = $this->createTestCampaign();

        $repository->save($campaign);

        $this->assertNotNull($campaign->getId());

        $foundCampaign = $repository->find($campaign->getId());
        $this->assertInstanceOf(Campaign::class, $foundCampaign);
        $this->assertSame($campaign->getTitle(), $foundCampaign->getTitle());
    }

    public function testRemoveShouldDeleteEntity(): void
    {
        $repository = self::getService(CampaignRepository::class);
        $campaign = $this->createTestCampaign();
        $repository->save($campaign);
        $campaignId = $campaign->getId();

        $repository->remove($campaign);

        $foundCampaign = $repository->find($campaignId);
        $this->assertNull($foundCampaign);
    }

    // findOneBy 排序逻辑测试

    // IS NULL 查询相关测试

    protected function createNewEntity(): object
    {
        $entity = new Campaign();
        $entity->setTitle('Test Campaign ' . uniqid());
        $entity->setDescription('Test Campaign Description');
        $entity->setStartTime(new \DateTimeImmutable('2024-01-01 00:00:00'));
        $entity->setEndTime(new \DateTimeImmutable('2024-12-31 23:59:59'));
        $entity->setExclusive(false);
        $entity->setWeight(0);
        $entity->setValid(true);

        return $entity;
    }

    protected function getRepository(): CampaignRepository
    {
        return self::getService(CampaignRepository::class);
    }

    private function createTestCampaign(): Campaign
    {
        $campaign = new Campaign();
        $campaign->setTitle('test_campaign_' . uniqid());
        $campaign->setDescription('Test Campaign Description');
        $campaign->setStartTime(new \DateTimeImmutable('2024-01-01 00:00:00'));
        $campaign->setEndTime(new \DateTimeImmutable('2024-12-31 23:59:59'));
        $campaign->setExclusive(false);
        $campaign->setWeight(0);
        $campaign->setValid(true);

        return $campaign;
    }
}
