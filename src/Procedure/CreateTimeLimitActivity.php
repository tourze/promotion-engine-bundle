<?php

namespace PromotionEngineBundle\Procedure;

use Doctrine\ORM\EntityManagerInterface;
use PromotionEngineBundle\DTO\CreateTimeLimitActivityInput;
use PromotionEngineBundle\DTO\CreateTimeLimitActivityResult;
use PromotionEngineBundle\Entity\TimeLimitActivity;
use PromotionEngineBundle\Enum\ActivityStatus;
use PromotionEngineBundle\Enum\ActivityType;
use PromotionEngineBundle\Exception\ActivityException;
use PromotionEngineBundle\Repository\TimeLimitActivityRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
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
    #[MethodParam(description: '活动名称')]
    public string $name;

    #[MethodParam(description: '活动描述')]
    public string $description;

    #[MethodParam(description: '开始时间（YYYY-MM-DD HH:mm:ss）')]
    public string $startTime;

    #[MethodParam(description: '结束时间（YYYY-MM-DD HH:mm:ss）')]
    public string $endTime;

    #[MethodParam(description: '活动类型')]
    public string $activityType;

    /** @var string[] */
    #[MethodParam(description: '参与商品ID列表')]
    public array $productIds;

    #[MethodParam(description: '活动优先级，默认为0')]
    public int $priority = 0;

    #[MethodParam(description: '是否独占（同商品不能参与其他活动），默认为false')]
    public bool $exclusive = false;

    #[MethodParam(description: '活动限量（限量抢购专用），默认为null')]
    public ?int $totalLimit = null;

    #[MethodParam(description: '是否启用预热，默认为false')]
    public bool $preheatEnabled = false;

    #[MethodParam(description: '预热开始时间（YYYY-MM-DD HH:mm:ss），默认为null')]
    public ?string $preheatStartTime = null;

    public function __construct(
        private readonly TimeLimitActivityRepository $activityRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function execute(): array
    {
        try {
            $input = $this->buildInput();
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

    private function buildInput(): CreateTimeLimitActivityInput
    {
        $activityType = ActivityType::tryFrom($this->activityType);
        if (null === $activityType) {
            throw ActivityException::invalidProductIds("活动类型无效: {$this->activityType}");
        }

        return new CreateTimeLimitActivityInput(
            name: $this->name,
            description: $this->description,
            startTime: $this->startTime,
            endTime: $this->endTime,
            activityType: $activityType,
            productIds: $this->productIds,
            priority: $this->priority,
            exclusive: $this->exclusive,
            totalLimit: $this->totalLimit,
            preheatEnabled: $this->preheatEnabled,
            preheatStartTime: $this->preheatStartTime,
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

        return $activity;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function getMockResult(): ?array
    {
        return CreateTimeLimitActivityResult::success('1234567890123456789')->toArray();
    }
}
