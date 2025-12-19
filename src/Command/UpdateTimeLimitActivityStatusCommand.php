<?php

namespace PromotionEngineBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use PromotionEngineBundle\Repository\TimeLimitActivityRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\Symfony\CronJob\Attribute\AsCronTask;

#[AsCommand(
    name: self::NAME,
    description: '自动更新限时活动状态'
)]
#[AsCronTask(expression: '*/5 * * * *')]
#[WithMonologChannel(channel: 'promotion_engine')]
final class UpdateTimeLimitActivityStatusCommand extends Command
{
    protected const NAME = 'promotion:update-activity-status';

    public function __construct(
        private readonly TimeLimitActivityRepository $activityRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $now = new \DateTimeImmutable();

        $this->logger->info('开始检查活动状态更新', [
            'time' => $now->format('Y-m-d H:i:s'),
        ]);

        try {
            $activities = $this->activityRepository->findActivitiesNeedingStatusUpdate($now);
            $updatedCount = 0;

            $this->entityManager->beginTransaction();

            foreach ($activities as $activity) {
                $oldStatus = $activity->getStatus();
                $newStatus = $activity->calculateCurrentStatus($now);

                if ($oldStatus !== $newStatus) {
                    $activity->setStatus($newStatus);
                    $this->activityRepository->save($activity, false);
                    ++$updatedCount;

                    $updateMessage = "更新活动状态: {$activity->getName()} ({$oldStatus->value} -> {$newStatus->value})";
                    if ($io->isVerbose()) {
                        $io->writeln($updateMessage);
                    }

                    $this->logger->info('活动状态已更新', [
                        'activityId' => $activity->getId(),
                        'activityName' => $activity->getName(),
                        'oldStatus' => $oldStatus->value,
                        'newStatus' => $newStatus->value,
                        'startTime' => $activity->getStartTime()->format('Y-m-d H:i:s'),
                        'endTime' => $activity->getEndTime()->format('Y-m-d H:i:s'),
                    ]);
                }
            }

            $this->entityManager->commit();

            $message = "活动状态更新完成，共处理 {$updatedCount} 个活动";
            $io->success($message);

            $this->logger->info($message, [
                'totalChecked' => count($activities),
                'updatedCount' => $updatedCount,
            ]);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->entityManager->rollback();

            $errorMessage = "活动状态更新失败: {$e->getMessage()}";
            $io->error($errorMessage);

            $this->logger->error($errorMessage, [
                'exception' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}
