<?php

namespace PromotionEngineBundle\Procedure\Admin;

use Doctrine\ORM\EntityManagerInterface;
use PromotionEngineBundle\Entity\ProductRelation;
use PromotionEngineBundle\Enum\DiscountType;
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
#[MethodDoc('更新促销活动信息')]
#[MethodExpose('AdminUpdatePromotionCampaignDiscount')]
class AdminUpdatePromotionCampaignDiscount extends BaseProcedure
{
    public array $form = [];

    public array $record = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DiscountRepository $discountRepository,
        private readonly ProductRelationRepository $productRelationRepository,
        private readonly DiscountFreeConditionRepository $discountFreeConditionRepository,
        private readonly DiscountConditionRepository $discountConditionRepository,
    ) {
    }

    public function execute(): array
    {
        //        if (empty($this->form['value'])){
        //            throw new ApiException('请输入数值');
        //        }
        if (empty($this->form['type'])) {
            throw new ApiException('请选择优惠类型');
        }
        $type = DiscountType::tryFrom($this->form['type']);
        if (!$type) {
            throw new ApiException('请选择优惠类型');
        }

        $discount = $this->discountRepository->findOneBy(['id' => $this->record['id']]);
        if (!$discount) {
            throw new ApiException('记录不存在，请刷新页面后重试');
        }

        if (!empty($this->form['isLimited']) && (empty($this->form['quota']) || intval($this->form['quota']) <= 0)) {
            throw new ApiException('配额数错误');
        }

        $discountFreeCondition = null;
        $discountCondition = null;
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
                $discountFreeCondition = $this->discountFreeConditionRepository->findOneBy(['discount' => $discount]);
                if (!$discountFreeCondition) {
                    throw new ApiException('数据异常');
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
                $discountCondition = $this->discountConditionRepository->findOneBy(['discount' => $discount]);
                if (!$discountCondition) {
                    throw new ApiException('数据异常');
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
                $discountCondition = $this->discountConditionRepository->findOneBy(['discount' => $discount]);
                if (!$discountCondition) {
                    throw new ApiException('数据异常');
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
                $discountCondition = $this->discountConditionRepository->findOneBy(['discount' => $discount]);
                if (!$discountCondition) {
                    throw new ApiException('数据异常');
                }
                break;
        }

        $remark = $this->form['remark'];
        $discount->setType($type);
        $discount->setRemark($remark);
        $discount->setValue($this->form['value'] ?? 0);
        $discount->setValid($this->form['valid'] ?? false);
        $discount->setIsLimited($this->form['isLimited'] ?? false);
        $discount->setQuota($this->form['quota'] ?? 0);
        $relations = $discount->getProductRelations();
        $existRelations = [];
        foreach ($relations as $relation) {
            $existRelations["{$relation->getId()}"] = $relation;
        }
        $this->entityManager->wrapInTransaction(function () use ($discount, $type, $existRelations, $discountFreeCondition, $discountCondition) {
            $this->entityManager->persist($discount);
            switch ($type) {
                case DiscountType::BUY_GIVE:
                    foreach ($this->form['products'] as $product) {
                        $condition = [
                            'discount' => $discount,
                            'spuId' => $product['id'],
                        ];
                        if (!empty($product['skuId'])) {
                            $condition['skuId'] = $product['skuId'];
                        }
                        $relation = $this->productRelationRepository->findOneBy($condition);
                        if (!$relation) {
                            $relation = new ProductRelation();
                            $relation->setDiscount($discount);
                        } else {
                            if (isset($existRelations["{$relation->getId()}"])) {
                                unset($existRelations["{$relation->getId()}"]);
                            }
                        }
                        $relation->setSpuId($product['id']);
                        $relation->setSkuId($product['skuId'] ?? 0);
                        $giftQuantity = max(intval($product['giftQuantity']), 1);
                        $relation->setGiftQuantity($giftQuantity);
                        $relation->setTotal(intval($product['total']));
                        $this->entityManager->persist($relation);
                    }
                    // 把不在此次编辑的商品删除
                    $existRelations = array_values($existRelations);
                    foreach ($existRelations as $existRelation) {
                        $this->entityManager->remove($existRelation);
                    }
                    $this->entityManager->flush();
                    break;
                case DiscountType::BUY_N_GET_M:
                    $discountFreeCondition->setFreeQuantity($this->form['purchaseQuantity']);
                    $discountFreeCondition->setFreeQuantity($this->form['freeQuantity']);
                    $this->entityManager->persist($discountFreeCondition);
                    $this->entityManager->flush();
                    break;
                case DiscountType::PROGRESSIVE_DISCOUNT_SCHEME:
                    $discountCondition->setCondition1($this->form['condition1']);
                    $discountCondition->setCondition2($this->form['condition2']);
                    $this->entityManager->persist($discountCondition);
                    $this->entityManager->flush();
                    break;
                case DiscountType::SPEND_THRESHOLD_WITH_ADD_ON:
                    $discountCondition->setCondition1($this->form['condition1']);
                    $discountCondition->setCondition2($this->form['condition2']);
                    $discountCondition->setCondition3($this->form['condition2'] ?? 1);
                    $this->entityManager->persist($discountCondition);
                    $this->entityManager->flush();
                    foreach ($this->form['products'] as $product) {
                        $condition = [
                            'discount' => $discount,
                            'spuId' => $product['id'],
                        ];
                        if (!empty($product['skuId'])) {
                            $condition['skuId'] = $product['skuId'];
                        }
                        $relation = $this->productRelationRepository->findOneBy($condition);
                        if (!$relation) {
                            $relation = new ProductRelation();
                            $relation->setDiscount($discount);
                        } else {
                            if (isset($existRelations["{$relation->getId()}"])) {
                                unset($existRelations["{$relation->getId()}"]);
                            }
                        }
                        $relation->setSpuId($product['id']);
                        $relation->setSkuId($product['skuId'] ?? 0);
                        $giftQuantity = max(intval($product['giftQuantity']), 1);
                        $relation->setGiftQuantity($giftQuantity);
                        $relation->setTotal(intval($product['quota']));
                        $this->entityManager->persist($relation);
                    }
                    // 把不在此次编辑的商品删除
                    $existRelations = array_values($existRelations);
                    foreach ($existRelations as $existRelation) {
                        $this->entityManager->remove($existRelation);
                    }
                    $this->entityManager->flush();
                    // no break
                case DiscountType::FREE_FREIGHT:
                    $discountCondition->setCondition1(Json::encode($this->form['condition1']));
                    $discountCondition->setCondition2($this->form['condition2']);
                    $this->entityManager->persist($discountCondition);
                    $this->entityManager->flush();
                    break;
            }
        });

        return [
            '__message' => '编辑成功',
        ];
    }
}
