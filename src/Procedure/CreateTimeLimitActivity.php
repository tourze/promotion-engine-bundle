<?php

namespace PromotionEngineBundle\Procedure;

use Doctrine\ORM\EntityManagerInterface;
use PromotionEngineBundle\DTO\CreateTimeLimitActivityInput;
use PromotionEngineBundle\DTO\CreateTimeLimitActivityResult;
use PromotionEngineBundle\Entity\TimeLimitActivity;
use PromotionEngineBundle\Enum\ActivityStatus;
use PromotionEngineBundle\Enum\ActivityType;
use PromotionEngineBundle\Exception\ActivityException;
use PromotionEngineBundle\Param\CreateTimeLimitActivityParam;
use PromotionEngineBundle\Repository\TimeLimitActivityRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\JsonRPC\Core\Result\ArrayResult;
use Tourze\JsonRPCLockBundle\Procedure\LockableProcedure;
use Tourze\JsonRPCLogBundle\Attribute\Log;

#[MethodExpose(method: 'CreateTimeLimitActivity')]
#[MethodTag(name: '限时活动模块')]
#[MethodDoc(summary: '创建限时活动')]
#[IsGranted(attribute: 'IS_AUTHENTICATED_FULLY')]
#[Autoconfigure(public: true)]
#[Log]
class CreateTimeLimitActivity extends LockableProcedure
{
    public function __construct(
        private readonly TimeLimitActivityRepository $activityRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @phpstan-param CreateTimeLimitActivityParam $param
     */
    public function execute(CreateTimeLimitActivityParam|RpcParamInterface $param): ArrayResult
    {
        try {
            $input = $this->buildInput($param);
            $this->validateInput($input);

            $this->entityManager->beginTransaction();

            $activity = $this->createActivity($input);
            $this->activityRepository->save($activity, false);

            $this->entityManager->commit();

            $activityId = $activity->getId();
            if (null === $activityId) {
                throw new ActivityException('活动ID生成失败');
            }

            return CreateTimeLimitActivityResult::success($activityId)->toArray();
        } catch (\Throwable $e) {
            if ($this->entityManager->getConnection()->isTransactionActive()) {
                $this->entityManager->rollback();
            }

            return CreateTimeLimitActivityResult::failure($e->getMessage())->toArray();
        }
    }

    private function buildInput(CreateTimeLimitActivityParam $param): CreateTimeLimitActivityInput
    {
        $activityType = ActivityType::tryFrom($param->activityType);
        if (null === $activityType) {
            throw ActivityException::invalidProductIds("活动类型无效: {$param->activityType}");
        }

        return new CreateTimeLimitActivityInput(
            name: $param->name,
            description: $param->description,
            startTime: $param->startTime,
            endTime: $param->endTime,
            activityType: $activityType,
            productIds: $param->productIds,
            priority: $param->priority,
            exclusive: $param->exclusive,
            totalLimit: $param->totalLimit,
            preheatEnabled: $param->preheatEnabled,
            preheatStartTime: $param->preheatStartTime,
        );
    }

    private function validateInput(CreateTimeLimitActivityInput $input): void
    {
        if ('' === trim($input->name)) {
            throw ActivityException::invalidProductIds('活动名称不能为空');
        }

        if (!$input->hasProductIds()) {
            throw ActivityException::invalidProductIds('参与商品ID列表不能为空');
        }

        if (!$input->isValidTimeRange()) {
            throw ActivityException::invalidTimeRange('活动结束时间必须晚于开始时间');
        }

        if (!$input->isValidPreheatTime()) {
            throw ActivityException::invalidTimeRange('预热开始时间必须早于活动开始时间');
        }

        if (ActivityType::LIMITED_QUANTITY_PURCHASE === $input->activityType && null === $input->totalLimit) {
            throw ActivityException::invalidProductIds('限量抢购活动必须设置活动限量');
        }

        $this->checkActivityConflicts($input);
    }

    private function checkActivityConflicts(CreateTimeLimitActivityInput $input): void
    {
        if (!$input->exclusive) {
            return;
        }

        $startTime = new \DateTimeImmutable($input->startTime);
        $endTime = new \DateTimeImmutable($input->endTime);

        $conflicts = $this->activityRepository->findConflictingActivities(
            $input->productIds,
            $startTime,
            $endTime
        );

        if ([] !== $conflicts) {
            $conflictNames = array_map(fn ($activity) => $activity->getName(), $conflicts);
            $conflictList = implode('、', $conflictNames);
            throw ActivityException::activityConflict("商品已参与其他独占活动: {$conflictList}");
        }
    }

    private function createActivity(CreateTimeLimitActivityInput $input): TimeLimitActivity
    {
        $activity = new TimeLimitActivity();
        $activity->setName($input->name);
        $activity->setDescription($input->description);
        $activity->setStartTime(new \DateTimeImmutable($input->startTime));
        $activity->setEndTime(new \DateTimeImmutable($input->endTime));
        $activity->setActivityType($input->activityType);
        $activity->setProductIds($input->productIds);
        $activity->setPriority($input->priority);
        $activity->setExclusive($input->exclusive);
        $activity->setPreheatEnabled($input->preheatEnabled);

        if (null !== $input->preheatStartTime && '' !== $input->preheatStartTime) {
            $activity->setPreheatStartTime(new \DateTimeImmutable($input->preheatStartTime));
        }

        if (null !== $input->totalLimit && $input->totalLimit > 0) {
            $activity->setTotalLimit($input->totalLimit);
        }

        $now = new \DateTimeImmutable();
        $status = $activity->calculateCurrentStatus($now);
        $activity->setStatus($status);
        $activity->setValid(true);

        return new ArrayResult($activity);
    }

    /**
     * @return array<string, mixed>|null
     */
}
