<?php

namespace PromotionEngineBundle\Procedure\Admin;

use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use PromotionEngineBundle\Repository\CampaignRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;
use Tourze\JsonRPCLogBundle\Attribute\Log;

#[Log]
#[MethodTag('促销模块')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[MethodDoc('更新促销活动信息')]
#[MethodExpose('AdminUpdatePromotionCampaign')]
class AdminUpdatePromotionCampaign extends BaseProcedure
{
    public array $form = [];

    public array $record = [];

    public function __construct(
        private readonly CampaignRepository $campaignRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function execute(): array
    {
        if (empty($this->record['id']) || !intval($this->record['id'])) {
            throw new ApiException('记录不存在，请刷新页面后重试~');
        }
        if (empty($this->form['title']) || !trim($this->form['title'])) {
            throw new ApiException('请输入标题');
        }
        if (empty($this->form['startTime'])) {
            throw new ApiException('请选择开始时间');
        }
        if (empty($this->form['endTime'])) {
            throw new ApiException('请选择结束时间');
        }
        $startTime = Carbon::parse($this->form['startTime']);
        $endTime = Carbon::parse($this->form['endTime']);
        if ($endTime->lessThan($startTime)) {
            throw new ApiException('结束时间不得小于开始时间');
        }

        $campaign = $this->campaignRepository->findOneBy(['id' => $this->record['id']]);
        if (!$campaign) {
            throw new ApiException('记录不存在，请刷新页面后重试');
        }
        $title = $this->form['title'];
        $valid = $this->form['valid'] ?? false;
        $weight = intval($this->form['weight']);

        $campaign->setTitle($title);
        $campaign->setValid($valid);
        $campaign->setWeight($weight);
        $campaign->setStartTime($startTime);
        $campaign->setEndTime($endTime);
        $this->entityManager->persist($campaign);
        $this->entityManager->flush();

        return [
            '__message' => '创建成功',
        ];
    }
}
