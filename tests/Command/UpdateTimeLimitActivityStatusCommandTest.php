<?php

namespace PromotionEngineBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PromotionEngineBundle\Command\UpdateTimeLimitActivityStatusCommand;
use PromotionEngineBundle\Entity\TimeLimitActivity;
use PromotionEngineBundle\Enum\ActivityStatus;
use PromotionEngineBundle\Enum\ActivityType;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;

/**
 * @internal
 */
#[CoversClass(UpdateTimeLimitActivityStatusCommand::class)]
#[RunTestsInSeparateProcesses]
final class UpdateTimeLimitActivityStatusCommandTest extends AbstractCommandTestCase
{
    private CommandTester $commandTester;

    protected function getCommandTester(): CommandTester
    {
        return $this->commandTester;
    }

    protected function onSetUp(): void
    {
        // Get command from service container
        $command = self::getContainer()->get(UpdateTimeLimitActivityStatusCommand::class);
        self::assertInstanceOf(Command::class, $command);

        $application = new Application();
        $application->addCommand($command);

        $command = $application->find('promotion:update-activity-status');
        $this->commandTester = new CommandTester($command);
    }

    public function testExecuteWithoutActivities(): void
    {
        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('活动状态更新完成', $output);
    }

    public function testExecuteWithExpiredActivity(): void
    {
        // 创建一个已过期的活动
        $activity = new TimeLimitActivity();
        $activity->setName('过期活动');
        $activity->setActivityType(ActivityType::LIMITED_TIME_SECKILL);
        $activity->setStartTime(new \DateTimeImmutable('-7 days'));
        $activity->setEndTime(new \DateTimeImmutable('-1 day')); // 昨天结束
        $activity->setStatus(ActivityStatus::ACTIVE); // 状态还是活跃的
        $activity->setValid(true);

        self::getEntityManager()->persist($activity);
        self::getEntityManager()->flush();

        // 确保数据库事务已提交
        self::getEntityManager()->clear();

        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('活动状态更新完成', $output);

        // 重新从数据库获取实体
        $updatedActivity = self::getEntityManager()->find(TimeLimitActivity::class, $activity->getId());
        $this->assertNotNull($updatedActivity);
        $this->assertEquals(ActivityStatus::FINISHED, $updatedActivity->getStatus());
    }

    public function testExecuteWithPendingActivity(): void
    {
        // 创建一个应该从PENDING变为ACTIVE的活动
        $activity = new TimeLimitActivity();
        $activity->setName('应该开始的活动');
        $activity->setActivityType(ActivityType::LIMITED_TIME_SECKILL);
        $activity->setStartTime(new \DateTimeImmutable('-1 hour')); // 1小时前开始
        $activity->setEndTime(new \DateTimeImmutable('+7 days'));
        $activity->setStatus(ActivityStatus::PENDING); // 状态还是待开始
        $activity->setValid(true);

        self::getEntityManager()->persist($activity);
        self::getEntityManager()->flush();

        // 确保数据库事务已提交
        self::getEntityManager()->clear();

        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $exitCode);

        // 重新从数据库获取实体
        $updatedActivity = self::getEntityManager()->find(TimeLimitActivity::class, $activity->getId());
        $this->assertNotNull($updatedActivity);
        $this->assertEquals(ActivityStatus::ACTIVE, $updatedActivity->getStatus());
    }

    public function testExecuteWithActiveActivity(): void
    {
        // 创建一个应该从PENDING变为ACTIVE的活动
        $activity = new TimeLimitActivity();
        $activity->setName('应该激活的活动');
        $activity->setActivityType(ActivityType::LIMITED_TIME_SECKILL);
        $activity->setStartTime(new \DateTimeImmutable('-1 day')); // 昨天开始
        $activity->setEndTime(new \DateTimeImmutable('+1 day')); // 明天结束
        $activity->setStatus(ActivityStatus::PENDING); // 状态还是待开始
        $activity->setValid(true);

        self::getEntityManager()->persist($activity);
        self::getEntityManager()->flush();

        // 确保数据库事务已提交
        self::getEntityManager()->clear();

        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $exitCode);

        // 重新从数据库获取实体
        $updatedActivity = self::getEntityManager()->find(TimeLimitActivity::class, $activity->getId());
        $this->assertNotNull($updatedActivity);
        $this->assertEquals(ActivityStatus::ACTIVE, $updatedActivity->getStatus());
    }

    public function testExecuteWithMultipleActivities(): void
    {
        // 创建多个不同状态的活动
        $expiredActivity = new TimeLimitActivity();
        $expiredActivity->setName('过期活动');
        $expiredActivity->setActivityType(ActivityType::LIMITED_TIME_SECKILL);
        $expiredActivity->setStartTime(new \DateTimeImmutable('-7 days'));
        $expiredActivity->setEndTime(new \DateTimeImmutable('-1 day'));
        $expiredActivity->setStatus(ActivityStatus::ACTIVE);
        $expiredActivity->setValid(true);

        $activeActivity = new TimeLimitActivity();
        $activeActivity->setName('活跃活动');
        $activeActivity->setActivityType(ActivityType::LIMITED_TIME_DISCOUNT);
        $activeActivity->setStartTime(new \DateTimeImmutable('-1 day'));
        $activeActivity->setEndTime(new \DateTimeImmutable('+1 day'));
        $activeActivity->setStatus(ActivityStatus::PENDING);
        $activeActivity->setValid(true);

        $pendingActivity = new TimeLimitActivity();
        $pendingActivity->setName('未开始活动');
        $pendingActivity->setActivityType(ActivityType::LIMITED_QUANTITY_PURCHASE);
        $pendingActivity->setStartTime(new \DateTimeImmutable('+1 day'));
        $pendingActivity->setEndTime(new \DateTimeImmutable('+7 days'));
        $pendingActivity->setStatus(ActivityStatus::ACTIVE);
        $pendingActivity->setValid(true);

        self::getEntityManager()->persist($expiredActivity);
        self::getEntityManager()->persist($activeActivity);
        self::getEntityManager()->persist($pendingActivity);
        self::getEntityManager()->flush();

        // 确保数据库事务已提交
        self::getEntityManager()->clear();

        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('活动状态更新完成', $output);

        // 重新从数据库获取实体
        $updatedExpiredActivity = self::getEntityManager()->find(TimeLimitActivity::class, $expiredActivity->getId());
        $updatedActiveActivity = self::getEntityManager()->find(TimeLimitActivity::class, $activeActivity->getId());
        $updatedPendingActivity = self::getEntityManager()->find(TimeLimitActivity::class, $pendingActivity->getId());

        $this->assertNotNull($updatedExpiredActivity);
        $this->assertNotNull($updatedActiveActivity);
        $this->assertNotNull($updatedPendingActivity);

        $this->assertEquals(ActivityStatus::FINISHED, $updatedExpiredActivity->getStatus());
        $this->assertEquals(ActivityStatus::ACTIVE, $updatedActiveActivity->getStatus());
        $this->assertEquals(ActivityStatus::PENDING, $updatedPendingActivity->getStatus());
    }

    public function testExecuteWithVerboseOutput(): void
    {
        $activity = new TimeLimitActivity();
        $activity->setName('测试活动');
        $activity->setActivityType(ActivityType::LIMITED_TIME_SECKILL);
        $activity->setStartTime(new \DateTimeImmutable('-1 day'));
        $activity->setEndTime(new \DateTimeImmutable('-1 hour')); // 1小时前结束
        $activity->setStatus(ActivityStatus::ACTIVE);
        $activity->setValid(true);

        self::getEntityManager()->persist($activity);
        self::getEntityManager()->flush();

        $exitCode = $this->commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('更新活动状态', $output);
        $this->assertStringContainsString('测试活动', $output);
    }

    public function testGetNameAndDescription(): void
    {
        $command = self::getContainer()->get(UpdateTimeLimitActivityStatusCommand::class);
        self::assertInstanceOf(UpdateTimeLimitActivityStatusCommand::class, $command);

        $this->assertEquals('promotion:update-activity-status', $command->getName());
        $this->assertNotEmpty($command->getDescription());
        $this->assertStringContainsString('更新限时活动状态', $command->getDescription());
    }
}
