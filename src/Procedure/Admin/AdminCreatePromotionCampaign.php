<?php

namespace PromotionEngineBundle\Procedure\Admin;

use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use PromotionEngineBundle\Entity\Campaign;
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
#[MethodDoc('新建促销活动')]
#[MethodExpose('AdminCreatePromotionCampaign')]
class AdminCreatePromotionCampaign extends BaseProcedure
{
    public array $form = [];

    public array $record = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function execute(): array
    {
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

        $title = $this->form['title'];
        $valid = $this->form['valid'] ?? false;
        $weight = intval($this->form['weight']);

        $campaign = new Campaign();
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
