<?php

namespace PromotionEngineBundle\Procedure\Admin;

use Doctrine\ORM\EntityManagerInterface;
use PromotionEngineBundle\Entity\Discount;
use PromotionEngineBundle\Entity\DiscountCondition;
use PromotionEngineBundle\Entity\DiscountFreeCondition;
use PromotionEngineBundle\Entity\ProductRelation;
use PromotionEngineBundle\Enum\DiscountType;
use PromotionEngineBundle\Repository\CampaignRepository;
use PromotionEngineBundle\Repository\DiscountConditionRepository;
use PromotionEngineBundle\Repository\DiscountFreeConditionRepository;
use PromotionEngineBundle\Repository\DiscountRepository;
use PromotionEngineBundle\Repository\ProductRelationRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;
use Tourze\JsonRPCLogBundle\Attribute\Log;
use Yiisoft\Json\Json;

#[Log]
#[MethodTag('促销模块')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[MethodDoc('新建促销活动折扣信息')]
#[MethodExpose('AdminCreatePromotionCampaignDiscount')]
class AdminCreatePromotionCampaignDiscount extends BaseProcedure
{
    public array $form = [];

    public array $record = [];

    public function __construct(
        private readonly DiscountRepository $discountRepository,
        private readonly CampaignRepository $campaignRepository,
        private readonly DiscountFreeConditionRepository $discountFreeConditionRepository,
        private readonly ProductRelationRepository $productRelationRepository,
        private readonly DiscountConditionRepository $discountConditionRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function execute(): array
    {
        if (empty($this->form['type'])) {
            throw new ApiException('请选择优惠类型');
        }
        $type = DiscountType::tryFrom($this->form['type']);
        if (!$type) {
            throw new ApiException('请选择优惠类型');
        }

        if (!empty($this->form['isLimited']) && (empty($this->form['quota']) || intval($this->form['quota']) <= 0)) {
            throw new ApiException('配额数错误');
        }

        switch ($type) {
            case DiscountType::BUY_GIVE:
                if (empty($this->form['products'])) {
                    throw new ApiException('请选择赠品');
                }
                break;
            case DiscountType::BUY_N_GET_M:
                if (empty($this->form['purchaseQuantity'])) {
                    throw new ApiException('请输入购买数量');
                }
                if (empty($this->form['freeQuantity'])) {
                    throw new ApiException('请输入免费数量');
                }
                break;
            case DiscountType::PROGRESSIVE_DISCOUNT_SCHEME:
                if (empty($this->form['condition1'])) {
                    throw new ApiException('请输入件数');
                }
                if (!is_numeric($this->form['condition1'])) {
                    throw new ApiException('件数输入异常');
                }
                if (empty($this->form['condition2'])) {
                    throw new ApiException('请输入折扣数');
                }
                if (!is_numeric($this->form['condition2'])) {
                    throw new ApiException('折扣数输入异常');
                }
                break;
            case DiscountType::SPEND_THRESHOLD_WITH_ADD_ON:
                if (empty($this->form['condition1'])) {
                    throw new ApiException('请输入满足金额');
                }
                if (!is_numeric($this->form['condition1'])) {
                    throw new ApiException('满足金额输入异常');
                }
                if (empty($this->form['condition2'])) {
                    throw new ApiException('请输入补充金额');
                }
                if (!is_numeric($this->form['condition1'])) {
                    throw new ApiException('补充金额输入异常');
                }
                if (empty($this->form['products'])) {
                    throw new ApiException('请选择赠品');
                }
                break;
            case DiscountType::FREE_FREIGHT:
                if (empty($this->form['condition1'])) {
                    throw new ApiException('请选择省份');
                }
                if (empty($this->form['condition2'])) {
                    throw new ApiException('请输入消费金额');
                }
                if ($this->form['condition2'] <= 0) {
                    throw new ApiException('消费金额不得小于等于0');
                }
                break;
        }

        $campaign = $this->campaignRepository->findOneBy(['id' => $this->record['id']]);
        if (!$campaign) {
            throw new ApiException('活动不存在，请刷新页面后重试~');
        }
        $remark = $this->form['remark'];

        $discount = new Discount();
        $discount->setType($type);
        $discount->setCampaign($campaign);
        $discount->setRemark($remark);
        $discount->setValue($this->form['value'] ?? 0);
        $discount->setValid($this->form['valid'] ?? false);
        $discount->setIsLimited($this->form['isLimited'] ?? false);
        $discount->setQuota($this->form['quota'] ?? 0);
        $this->entityManager->wrapInTransaction(function () use ($discount, $type) {
            $this->entityManager->persist($discount);
            $this->entityManager->flush();
            switch ($type) {
                case DiscountType::BUY_GIVE:
                    foreach ($this->form['products'] as $product) {
                        $relation = new ProductRelation();
                        $relation->setDiscount($discount);
                        $relation->setSpuId($product['id']);
                        $relation->setSkuId($product['skuId'] ?? 0);
                        $giftQuantity = max(intval($product['giftQuantity']), 1);
                        $relation->setGiftQuantity($giftQuantity);
                        $relation->setTotal(intval($product['total']));
                        $this->entityManager->persist($relation);
                    }
                    $this->entityManager->flush();
                    break;
                case DiscountType::BUY_N_GET_M:
                    $condition = new DiscountFreeCondition();
                    $condition->setDiscount($discount);
                    $condition->setPurchaseQuantity($this->form['purchaseQuantity']);
                    $condition->setFreeQuantity($this->form['freeQuantity']);
                    $this->entityManager->persist($condition);
                    $this->entityManager->flush();
                    break;
                case DiscountType::PROGRESSIVE_DISCOUNT_SCHEME:
                    $discountCondition = new DiscountCondition();
                    $discountCondition->setDiscount($discount);
                    $discountCondition->setCondition1($this->form['condition1']);
                    $discountCondition->setCondition2($this->form['condition2']);
                    $this->entityManager->persist($discountCondition);
                    $this->entityManager->flush();
                    break;
                case DiscountType::SPEND_THRESHOLD_WITH_ADD_ON:
                    $discountCondition = new DiscountCondition();
                    $discountCondition->setDiscount($discount);
                    $discountCondition->setCondition1($this->form['condition1']);
                    $discountCondition->setCondition2($this->form['condition2']);
                    $discountCondition->setCondition3($this->form['condition2'] ?? 1);
                    $this->entityManager->persist($discountCondition);
                    $this->entityManager->flush();
                    foreach ($this->form['products'] as $product) {
                        $relation = new ProductRelation();
                        $relation->setDiscount($discount);
                        $relation->setSpuId($product['id']);
                        $relation->setSkuId($product['skuId'] ?? 0);
                        $giftQuantity = max(intval($product['giftQuantity']), 1);
                        $relation->setGiftQuantity($giftQuantity);
                        $relation->setTotal(intval($product['quota']));
                        $this->entityManager->persist($relation);
                    }
                    $this->entityManager->flush();
                    break;
                case DiscountType::FREE_FREIGHT:
                    $discountCondition = new DiscountCondition();
                    $discountCondition->setDiscount($discount);
                    $discountCondition->setCondition1(Json::encode($this->form['condition1']));
                    $discountCondition->setCondition2($this->form['condition2']);
                    $this->entityManager->persist($discountCondition);
                    $this->entityManager->flush();
            }
        });

        return [
            '__message' => '创建成功',
        ];
    }
}
