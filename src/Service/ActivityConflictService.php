<?php

namespace PromotionEngineBundle\Service;

use PromotionEngineBundle\Entity\TimeLimitActivity;
use PromotionEngineBundle\Exception\ActivityException;
use PromotionEngineBundle\Repository\TimeLimitActivityRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(public: true)]
readonly class ActivityConflictService
{
    public function __construct(
        private TimeLimitActivityRepository $activityRepository,
    ) {
    }

    /**
     * @param string[] $productIds
     * @return array<string, array<string, mixed>>
     */
    public function checkConflicts(
        array $productIds,
        \DateTimeInterface $startTime,
        \DateTimeInterface $endTime,
        ?string $excludeActivityId = null,
    ): array {
        $conflicts = $this->activityRepository->findConflictingActivities(
            $productIds,
            $startTime,
            $endTime,
            $excludeActivityId
        );

        $result = [];
        foreach ($conflicts as $activity) {
            $conflictProducts = array_intersect($productIds, $activity->getProductIds());
            $result[$activity->getId()] = [
                'activityId' => $activity->getId(),
                'activityName' => $activity->getName(),
                'activityType' => $activity->getActivityType()->getLabel(),
                'startTime' => $activity->getStartTime()->format('Y-m-d H:i:s'),
                'endTime' => $activity->getEndTime()->format('Y-m-d H:i:s'),
                'priority' => $activity->getPriority(),
                'conflictProducts' => $conflictProducts,
            ];
        }

        return $result;
    }

    /**
     * 检查两个活动是否存在冲突
     *
     * @param TimeLimitActivity[] $activities
     */
    public function hasConflict(TimeLimitActivity $activity, array $activities): bool
    {
        foreach ($activities as $otherActivity) {
            if ($this->areActivitiesInConflict($activity, $otherActivity)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 检查两个具体活动是否冲突
     */
    private function areActivitiesInConflict(TimeLimitActivity $activity1, TimeLimitActivity $activity2): bool
    {
        // 检查时间重叠
        if (!$this->hasTimeOverlap($activity1, $activity2)) {
            return false;
        }

        // 检查商品重叠
        $productOverlap = array_intersect($activity1->getProductIds(), $activity2->getProductIds());
        if ([] === $productOverlap) {
            return false;
        }

        // 如果任一活动是独占的，则冲突
        if ($activity1->isExclusive() || $activity2->isExclusive()) {
            return true;
        }

        // 检查活动类型是否冲突
        return $this->areActivityTypesInConflict($activity1->getActivityType(), $activity2->getActivityType());
    }

    /**
     * 检查两个活动是否有时间重叠
     */
    private function hasTimeOverlap(TimeLimitActivity $activity1, TimeLimitActivity $activity2): bool
    {
        $start1 = $activity1->getStartTime();
        $end1 = $activity1->getEndTime();
        $start2 = $activity2->getStartTime();
        $end2 = $activity2->getEndTime();

        return $start1 < $end2 && $start2 < $end1;
    }

    /**
     * 检查活动类型是否冲突
     */
    private function areActivityTypesInConflict(\BackedEnum $type1, \BackedEnum $type2): bool
    {
        // 同类型活动通常可以叠加（除非有其他限制）
        if ($type1 === $type2) {
            return false;
        }

        // 目前没有定义冲突的活动类型组合
        // 如果后续需要添加冲突规则，可以在这里定义
        return false;
    }

    /**
     * @param string[] $productIds
     */
    public function validateNoConflicts(
        array $productIds,
        \DateTimeInterface $startTime,
        \DateTimeInterface $endTime,
        bool $isExclusive,
        ?string $excludeActivityId = null,
    ): void {
        if (!$isExclusive) {
            return;
        }

        $conflicts = $this->checkConflicts($productIds, $startTime, $endTime, $excludeActivityId);

        if ([] !== $conflicts) {
            $conflictDetails = [];
            foreach ($conflicts as $conflict) {
                $activityName = is_string($conflict['activityName'] ?? null) ? $conflict['activityName'] : '';
                $startTime = is_string($conflict['startTime'] ?? null) ? $conflict['startTime'] : '';
                $endTime = is_string($conflict['endTime'] ?? null) ? $conflict['endTime'] : '';
                $conflictDetails[] = "{$activityName}({$startTime} - {$endTime})";
            }
            $conflictList = implode('、', $conflictDetails);
            throw ActivityException::activityConflict("商品已参与其他独占活动: {$conflictList}");
        }
    }

    /**
     * @param string[] $productIds
     * @return TimeLimitActivity[]
     */
    public function getActiveActivitiesForProducts(array $productIds, ?\DateTimeInterface $now = null): array
    {
        $now ??= new \DateTimeImmutable();

        return $this->activityRepository->findActivitiesByProductIds($productIds, $now);
    }

    /**
     * @param string[] $productIds
     * @return array<string, TimeLimitActivity>
     */
    public function resolveActivityPriorities(array $productIds, ?\DateTimeInterface $now = null): array
    {
        $activities = $this->getActiveActivitiesForProducts($productIds, $now);
        $result = [];

        foreach ($productIds as $productId) {
            $productActivities = array_filter($activities, function (TimeLimitActivity $activity) use ($productId) {
                return in_array($productId, $activity->getProductIds(), true);
            });

            if ([] === $productActivities) {
                continue;
            }

            usort($productActivities, function (TimeLimitActivity $a, TimeLimitActivity $b) {
                if ($a->getPriority() === $b->getPriority()) {
                    return $a->getCreateTime() <=> $b->getCreateTime();
                }

                return $b->getPriority() <=> $a->getPriority();
            });

            $result[$productId] = $productActivities[0];
        }

        return $result;
    }

    public function getHighestPriorityActivity(string $productId, ?\DateTimeInterface $now = null): ?TimeLimitActivity
    {
        return $this->activityRepository->findHighestPriorityActivityForProduct($productId, $now);
    }

    /**
     * @param string[] $productIds
     * @return array<string, array<string, mixed>>
     */
    public function getActivitySummaryForProducts(array $productIds, ?\DateTimeInterface $now = null): array
    {
        $priorities = $this->resolveActivityPriorities($productIds, $now);
        $result = [];

        foreach ($priorities as $productId => $activity) {
            $result[$productId] = [
                'activityId' => $activity->getId(),
                'activityName' => $activity->getName(),
                'activityType' => $activity->getActivityType()->getLabel(),
                'activityTypeValue' => $activity->getActivityType()->value,
                'status' => $activity->getStatus()->value,
                'statusLabel' => $activity->getStatus()->getLabel(),
                'priority' => $activity->getPriority(),
                'startTime' => $activity->getStartTime()->format('Y-m-d H:i:s'),
                'endTime' => $activity->getEndTime()->format('Y-m-d H:i:s'),
                'isExclusive' => $activity->isExclusive(),
                'totalLimit' => $activity->getTotalLimit(),
                'soldQuantity' => $activity->getSoldQuantity(),
                'remainingQuantity' => $activity->getRemainingQuantity(),
                'isSoldOut' => $activity->isSoldOut(),
                'isInPreheatPeriod' => $activity->isInPreheatPeriod($now),
                'preheatStartTime' => $activity->getPreheatStartTime()?->format('Y-m-d H:i:s'),
            ];
        }

        return $result;
    }
}
