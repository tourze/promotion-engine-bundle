<?php

namespace PromotionEngineBundle\Procedure\Admin;

use AntdCpBundle\Builder\Action\ButtonAction;
use AntdCpBundle\Builder\Action\DiyDrawer;
use AntdCpBundle\Builder\Column\ActionColumn;
use AntdCpBundle\Builder\Column\FilterColumn;
use AntdCpBundle\Builder\Column\TextColumn;
use AntdCpBundle\Builder\Field\DateTimePickerField;
use AntdCpBundle\Builder\Field\InputNumberField;
use AntdCpBundle\Builder\Field\InputTextField;
use AntdCpBundle\Builder\Field\SelectField;
use AntdCpBundle\Builder\Information\DiyList;
use AntdCpBundle\Builder\Page\AdminListPage;
use Doctrine\ORM\QueryBuilder;
use PromotionEngineBundle\Entity\Campaign;
use PromotionEngineBundle\Entity\Constraint;
use PromotionEngineBundle\Repository\CampaignRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPCLogBundle\Attribute\Log;

#[Log]
#[MethodTag('促销模块')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[MethodDoc('获取促销活动列表页面')]
#[MethodExpose('AdminGetPromotionCampaignListPage')]
class AdminGetPromotionCampaignListPage extends AdminListPage
{
    public function __construct(
        private readonly CampaignRepository $campaignRepository,
    ) {
    }

    public function getPageTitle(): string
    {
        return '优惠活动';
    }

    public function renderFilterFormFields(): array
    {
        return [
            InputTextField::gen()
                ->setId('filter_keyword')
                ->setLabel('关键词')
                ->setInputProps([
                    'placeholder' => '可搜索标题',
                ]),
        ];
    }

    public function renderFilterFormButtons(): array
    {
        return [
            ButtonAction::gen()
                ->setLabel('查询')
                ->setType('submit'),
            ButtonAction::gen()
                ->setLabel('重置')
                ->setType('reset'),
        ];
    }

    public function renderHeaderButtons(): array
    {
        return [
            DiyDrawer::gen()
                ->setLabel('新建活动')
                ->setType('primary')
                ->setTitle('新建活动')
                ->setFormProps([
                    'layout' => 'vertical',
                ])
                ->setFormOkApi('AdminCreatePromotionCampaign')
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
        yield TextColumn::gen()
            ->setDataIndex('title')
            ->setTitle('标题');
        yield TextColumn::gen()
            ->setDataIndex('startTime')
            ->setTitle('开始时间');
        yield TextColumn::gen()
            ->setDataIndex('endTime')
            ->setTitle('结束时间');
        yield FilterColumn::gen()
            ->setTitle('状态')
            ->setDataIndex('valid')
            ->setFilters([['text' => '有效', 'value' => true, 'status' => 'success'], ['text' => '无效', 'value' => false, 'status' => 'error']]);
        yield TextColumn::gen()
            ->setDataIndex('createTime')
            ->setTitle('创建时间');
        yield ActionColumn::gen()
            ->setTitle('操作')
            ->setWidth(250)
            ->setButtons([
                DiyDrawer::gen()
                    ->setLabel('编辑活动')
                    ->setTitle('编辑活动')
                    ->setFormProps([
                        'layout' => 'vertical',
                    ])
                    ->setFormOkApi('AdminUpdatePromotionCampaign')
                    ->setContentConfig([
                        [
                            'type' => 'form',
                            'field' => $this->getFormFields(),
                        ],
                    ]),
                DiyDrawer::gen()
                    ->setLabel('参与限制')
                    ->setWidth(1024)
                    ->setType('primary')
                    ->setFormProps(['layout' => 'vertical'])
                    ->setTitle('参与限制')
                    ->setShowOnCloseButton(false)
                    ->setContentConfig([
                        [
                            'type' => 'information',
                            'information' => [
                                DiyList::gen()
                                    ->setApiName('AdminGetCurdListPage')
                                    ->setProps([
                                        '_model_class' => Constraint::class,
                                        '_primaryModel' => Campaign::class,
                                        '_model_links' => 'campaign',
                                        '_mappedBy' => 'campaign',
                                    ]),
                            ],
                        ],
                    ]),
                DiyDrawer::gen()
                    ->setLabel('优惠')
                    ->setWidth(1024)
                    ->setType('primary')
                    ->setFormProps(['layout' => 'vertical'])
                    ->setTitle('优惠列表')
                    ->setShowOnCloseButton(false)
                    ->setContentConfig([
                        [
                            'type' => 'information',
                            'information' => [
                                DiyList::gen()
                                    ->setApiName('AdminGetPromotionCampaignDiscountListPage')
                                    ->setProps([
                                    ]),
                            ],
                        ],
                    ]),
            ]);
    }

    public function getQuery(): ?QueryBuilder
    {
        $query = $this->campaignRepository->createQueryBuilder('a');
        if (!empty($this->filters['filter_keyword']) && trim($this->filters['filter_keyword'])) {
            $keyword = trim($this->filters['filter_keyword']);
            $query->andWhere('a.title like :keyword')->setParameter('keyword', "%{$keyword}%");
        }

        $query->orderBy('a.id', 'DESC');

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

        /** @var Campaign[] $models */
        foreach ($models as $model) {
            $list[] = [
                'id' => $model->getId(),
                'title' => $model->getTitle(),
                'valid' => $model->isValid(),
                'startTime' => $model->getStartTime()?->format('Y-m-d H:i:s'),
                'endTime' => $model->getEndTime()?->format('Y-m-d H:i:s'),
                'createTime' => $model->getCreateTime()?->format('Y-m-d H:i:s'),
            ];
        }

        return $list;
    }

    public function getFormFields(): array
    {
        $formFields = [
            InputTextField::gen()
                ->setId('title')
                ->setLabel('标题')
                ->setRules([['required' => true, 'message' => '请填写标题']]),
            SelectField::gen()
                ->setId('valid')
                ->setSpan(12)
                ->setLabel('状态')
                ->setInputProps([
                    'style' => ['weight' => '100%'],
                    'options' => [['text' => '有效', 'value' => true, 'status' => 'success'], ['text' => '无效', 'value' => false, 'status' => 'error']],
                ]),
            InputNumberField::gen()
                ->setSpan(12)
                ->setId('weight')
                ->setLabel('权重')
                ->setInputProps(['defaultValue' => 0,  'style' => ['weight' => '100%']]),
            DateTimePickerField::gen()
                ->setSpan(12)
                ->setId('startTime')
                ->setLabel('开始时间'),
            DateTimePickerField::gen()
                ->setSpan(12)
                ->setId('endTime')
                ->setLabel('结束时间'),
        ];

        return $formFields;
    }
}
