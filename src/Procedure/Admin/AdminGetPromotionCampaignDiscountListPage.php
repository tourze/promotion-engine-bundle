<?php

namespace PromotionEngineBundle\Procedure\Admin;

use AntdCpBundle\Builder\Action\DiyDrawer;
use AntdCpBundle\Builder\Column\ActionColumn;
use AntdCpBundle\Builder\Column\FilterColumn;
use AntdCpBundle\Builder\Column\TextColumn;
use AntdCpBundle\Builder\Field\InputNumberField;
use AntdCpBundle\Builder\Field\InputTextField;
use AntdCpBundle\Builder\Field\LinkageFormField;
use AntdCpBundle\Builder\Field\SelectField;
use AntdCpBundle\Builder\Page\AdminListPage;
use Doctrine\ORM\QueryBuilder;
use ProductBundle\Repository\SpuRepository;
use PromotionEngineBundle\Entity\Campaign;
use PromotionEngineBundle\Entity\Discount;
use PromotionEngineBundle\Entity\ProductRelation;
use PromotionEngineBundle\Enum\DiscountType;
use PromotionEngineBundle\Repository\CampaignRepository;
use PromotionEngineBundle\Repository\DiscountConditionRepository;
use PromotionEngineBundle\Repository\DiscountFreeConditionRepository;
use PromotionEngineBundle\Repository\DiscountRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPCLogBundle\Attribute\Log;
use Yiisoft\Json\Json;

#[Log]
#[MethodTag('促销模块')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[MethodDoc('获取促销折扣列表页面')]
#[MethodExpose('AdminGetPromotionCampaignDiscountListPage')]
class AdminGetPromotionCampaignDiscountListPage extends AdminListPage
{
    public array $record = [];

    private ?Campaign $campaign = null;

    public function __construct(
        private readonly CampaignRepository $campaignRepository,
        private readonly DiscountRepository $discountRepository,
        private readonly SpuRepository $spuRepository,
        private readonly DiscountFreeConditionRepository $discountFreeConditionRepository,
        private readonly DiscountConditionRepository $discountConditionRepository,
    ) {
    }

    public function getPageTitle(): string
    {
        return $this->getCampaign()?->getTitle() . '-折扣';
    }

    public function renderFilterFormFields(): array
    {
        return [
            //            InputTextField::gen()
            //                ->setId('filter_keyword')
            //                ->setLabel('关键词')
            //                ->setInputProps([
            //                    'placeholder' => '可搜索标题',
            //                ]),
        ];
    }

    public function renderFilterFormButtons(): array
    {
        return [
            //            ButtonAction::gen()
            //                ->setLabel('查询')
            //                ->setType('submit'),
            //            ButtonAction::gen()
            //                ->setLabel('重置')
            //                ->setType('reset'),
        ];
    }

    public function renderHeaderButtons(): array
    {
        return [
            DiyDrawer::gen()
                ->setLabel('新建')
                ->setType('primary')
                ->setTitle('新建')
                ->setFormProps([
                    'layout' => 'vertical',
                ])
                ->setFormOkApi('AdminCreatePromotionCampaignDiscount')
                ->setDynamicFields(['type'])
                ->setContentConfig([
                    [
                        'type' => 'form',
                        'field' => $this->getFormFields(),
                    ],
                ]),
        ];
    }

    public function renderColumns(): iterable
    {
        yield TextColumn::gen()
            ->setDataIndex('id')
            ->setTitle('编号');
        yield FilterColumn::gen()
            ->setTitle('类型')
            ->setDataIndex('type')
            ->setFilters(DiscountType::genOptions());
        yield FilterColumn::gen()
            ->setTitle('是否限量')
            ->setDataIndex('isLimited')
            ->setFilters([['text' => '是', 'value' => true, 'status' => 'success'], ['text' => '否', 'value' => false, 'status' => 'error']]);
        yield TextColumn::gen()
            ->setDataIndex('quota')
            ->setTitle('配额数');
        yield TextColumn::gen()
            ->setDataIndex('remark')
            ->setTitle('备注');
        yield TextColumn::gen()
            ->setDataIndex('value')
            ->setTitle('数值');
        yield FilterColumn::gen()
            ->setTitle('状态')
            ->setDataIndex('valid')
            ->setFilters([['text' => '有效', 'value' => true, 'status' => 'success'], ['text' => '无效', 'value' => false, 'status' => 'error']]);
        yield TextColumn::gen()
            ->setDataIndex('createTime')
            ->setTitle('创建时间');
        yield ActionColumn::gen()
            ->setTitle('操作')
            ->setWidth(120)
            ->setButtons([
                DiyDrawer::gen()
                    ->setLabel('编辑活动')
                    ->setTitle('编辑活动')
                    ->setFormProps([
                        'layout' => 'vertical',
                    ])
                    ->setFormOkApi('AdminUpdatePromotionCampaignDiscount')
                    ->setDynamicFields(['type'])
                    ->setContentConfig([
                        [
                            'type' => 'form',
                            'field' => $this->getFormFields(),
                        ],
                    ]),
            ]);
    }

    public function getQuery(): ?QueryBuilder
    {
        $query = $this->discountRepository->createQueryBuilder('a')->orderBy('a.id', 'DESC');

        return $query;
    }

    public function getList(): array
    {
        $queryBuilder = clone $this->getQuery();
        $min = ($this->currentPage - 1) * $this->pageSize;
        $max = $min + $this->pageSize;
        $queryBuilder->setFirstResult($min);
        $queryBuilder->setMaxResults($this->pageSize);
        $query = $queryBuilder->getQuery();
        $models = $query->getResult();

        $list = [];

        /** @var Discount[] $models */
        foreach ($models as $model) {
            $products = [];
            if (in_array($model->getType(), [DiscountType::BUY_GIVE, DiscountType::PROGRESSIVE_DISCOUNT_SCHEME, DiscountType::SPEND_THRESHOLD_WITH_ADD_ON])) {
                $spus = $this->spuRepository->createQueryBuilder('s')
                    ->select(['pr.id as relationId', 's.id as id', 's.mainPic as mainThumb', 's.title as title', 'pr.giftQuantity', 'pr.total'])
                    ->innerJoin(ProductRelation::class, 'pr', 'WITH', 's.id=pr.spuId')
                    ->where('pr.discount = :discount')
                    ->setParameter('discount', $model)
                    ->getQuery()
                    ->getArrayResult();
                foreach ($spus as $spu) {
                    $products[] = [
                        'relationId' => $spu['relationId'],
                        'id' => $spu['id'],
                        'giftQuantity' => $spu['giftQuantity'],
                        'quota' => $spu['total'],
                        'spuId' => $spu['id'],
                        'goodsInfo' => [
                            'thumb' => $spu['mainThumb'],
                            'title' => $spu['title'],
                        ],
                    ];
                }
            }
            $tmp = [
                'id' => $model->getId(),
                'isLimited' => $model->isLimited(),
                'quota' => $model->getQuota(),
                'remark' => $model->getRemark(),
                'valid' => $model->isValid(),
                'value' => $model->getValue(),
                'type' => $model->getType()->value,
                'products' => $products,
                '__discount' => 1,
                'createTime' => $model->getCreateTime()?->format('Y-m-d H:i:s'),
            ];
            if (DiscountType::BUY_N_GET_M === $model->getType()) {
                $condition = $this->discountFreeConditionRepository->findOneBy(['discount' => $model]);
                if ($condition) {
                    $tmp['purchaseQuantity'] = $condition->getPurchaseQuantity();
                    $tmp['freeQuantity'] = $condition->getFreeQuantity();
                }
            }
            if (in_array($model->getType(), [DiscountType::PROGRESSIVE_DISCOUNT_SCHEME, DiscountType::SPEND_THRESHOLD_WITH_ADD_ON, DiscountType::FREE_FREIGHT])) {
                $condition = $this->discountConditionRepository->findOneBy(['discount' => $model]);
                if ($condition) {
                    $tmp['condition1'] = $condition->getCondition1();
                    $tmp['condition2'] = $condition->getCondition2();
                    $tmp['condition3'] = $condition->getCondition3();
                    if (DiscountType::FREE_FREIGHT == $model->getType()) {
                        $tmp['condition1'] = Json::decode($condition->getCondition1());
                    }
                }
            }
            $list[] = $tmp;
        }

        return $list;
    }

    public function getFormFields(): array
    {
        $formFields = [
            SelectField::gen()
                ->setId('type')
                ->setSpan(24)
                ->setLabel('优惠类型')
                ->setInputProps([
                    'style' => ['weight' => '100%'],
                    'options' => DiscountType::genOptions(),
                ]),
            SelectField::gen()
                ->setId('isLimited')
                ->setSpan(12)
                ->setLabel('是否限量')
                ->setTooltipTitle('设置了限量则限制参与的总次数')
                ->setInputProps([
                    'style' => ['weight' => '100%'],
                    'options' => [['text' => '是', 'value' => true, 'status' => 'success'], ['text' => '否', 'value' => false, 'status' => 'error']],
                ]),
            InputNumberField::gen()
                ->setId('quota')
                ->setSpan(12)
                ->setTooltipTitle('总参与次数的配额')
                ->setInputProps([
                    'style' => ['weight' => '150px'],
                ])
                ->setLabel('配额'),
            InputTextField::gen()
                ->setId('remark')
                ->setLabel('备注'),
            SelectField::gen()
                ->setId('valid')
                ->setSpan(24)
                ->setLabel('状态')
                ->setInputProps([
                    'style' => ['weight' => '100%'],
                    'options' => [['text' => '有效', 'value' => true, 'status' => 'success'], ['text' => '无效', 'value' => false, 'status' => 'error']],
                ]),
            LinkageFormField::gen()->setRefreshApiName('AdminGetPromotionCampaignDiscountFormFields'),
        ];

        return $formFields;
    }

    private function getCampaign(): ?Campaign
    {
        if (!$this->campaign && isset($this->record['id']) && $this->record['id']) {
            $campaign = $this->campaignRepository->findOneBy([
                'id' => $this->record['id'],
            ]);
            if ($campaign) {
                $this->campaign = $campaign;
            }
        }

        return $this->campaign;
    }
}
