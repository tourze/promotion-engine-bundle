<?php

declare(strict_types=1);

namespace PromotionEngineBundle\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use PromotionEngineBundle\Entity\TimeLimitActivity;
use PromotionEngineBundle\Enum\ActivityStatus;
use PromotionEngineBundle\Enum\ActivityType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @extends AbstractCrudController<TimeLimitActivity>
 */
#[AdminCrud(routePath: '/promotion-engine/time-limit-activity', routeName: 'promotion_engine_time_limit_activity')]
final class TimeLimitActivityCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return TimeLimitActivity::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('限时活动')
            ->setEntityLabelInPlural('限时活动')
            ->setPageTitle('index', '限时活动列表')
            ->setPageTitle('detail', '限时活动详情')
            ->setPageTitle('new', '新建限时活动')
            ->setPageTitle('edit', '编辑限时活动')
            ->setHelp('index', '管理限时活动，包括限时折扣、限时秒杀、限量抢购等活动')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['id', 'name', 'description'])
            ->setPaginatorPageSize(20)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->setMaxLength(9999)
            ->hideOnForm()
        ;

        yield TextField::new('name', '活动名称')
            ->setRequired(true)
            ->setMaxLength(120)
            ->setColumns(6)
        ;

        yield TextareaField::new('description', '活动描述')
            ->setMaxLength(65535)
            ->setColumns(12)
            ->hideOnIndex()
        ;

        yield ChoiceField::new('activityType', '活动类型')
            ->setRequired(true)
            ->setChoices(ActivityType::toSelect())
            ->setColumns(6)
            ->setFormTypeOption('choice_value', fn ($choice) => $choice instanceof ActivityType ? $choice->value : $choice)
            ->formatValue(function ($value) {
                return $value instanceof ActivityType ? $value->getLabel() : $value;
            })
        ;

        yield ChoiceField::new('status', '活动状态')
            ->setRequired(true)
            ->setChoices(ActivityStatus::toSelect())
            ->setColumns(6)
            ->formatValue(function ($value) {
                return $value instanceof ActivityStatus ? $value->getLabel() : $value;
            })
            ->hideOnForm()
        ;

        yield DateTimeField::new('startTime', '开始时间')
            ->setRequired(true)
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setColumns(6)
        ;

        yield DateTimeField::new('endTime', '结束时间')
            ->setRequired(true)
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setColumns(6)
        ;

        yield BooleanField::new('preheatEnabled', '启用预热')
            ->setColumns(6)
            ->setHelp('是否启用预热功能')
        ;

        yield DateTimeField::new('preheatStartTime', '预热开始时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setColumns(6)
            ->setHelp('只有启用预热时才需要设置')
            ->hideOnIndex()
        ;

        yield IntegerField::new('priority', '优先级')
            ->setColumns(6)
            ->setHelp('优先级越高，活动优先级越高，默认为0')
        ;

        yield BooleanField::new('exclusive', '独占活动')
            ->setColumns(6)
            ->setHelp('同商品不能参与其他活动')
        ;

        yield IntegerField::new('totalLimit', '活动限量')
            ->setColumns(6)
            ->setHelp('限量抢购专用，为空表示不限量')
            ->hideOnIndex()
        ;

        yield IntegerField::new('soldQuantity', '已售数量')
            ->setColumns(6)
            ->hideOnForm()
        ;

        yield IntegerField::new('remainingQuantity', '剩余数量')
            ->setColumns(6)
            ->hideOnForm()
            ->onlyOnDetail()
            ->formatValue(function ($value, TimeLimitActivity $entity) {
                $remaining = $entity->getRemainingQuantity();

                return null !== $remaining ? $remaining : '不限量';
            })
        ;

        yield ArrayField::new('productIds', '参与商品ID')
            ->setColumns(12)
            ->setHelp('参与活动的商品ID列表')
            ->hideOnIndex()
        ;

        yield BooleanField::new('valid', '是否有效')
            ->setColumns(6)
        ;

        yield CollectionField::new('campaigns', '关联促销活动')
            ->onlyOnDetail()
            ->formatValue(function ($value) {
                return $value ? sprintf('共 %d 个促销活动', $value->count()) : '无关联活动';
            })
        ;

        yield TextField::new('createdBy', '创建人')
            ->hideOnForm()
            ->onlyOnDetail()
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->hideOnForm()
            ->setColumns(6)
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->hideOnForm()
            ->setColumns(6)
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $activate = Action::new('activate', '启用活动')
            ->linkToCrudAction('activate')
            ->setCssClass('btn btn-success')
            ->setIcon('fa fa-play')
            ->displayIf(fn (TimeLimitActivity $entity) => !($entity->isValid() ?? false))
        ;

        $deactivate = Action::new('deactivate', '禁用活动')
            ->linkToCrudAction('deactivate')
            ->setCssClass('btn btn-warning')
            ->setIcon('fa fa-pause')
            ->displayIf(fn (TimeLimitActivity $entity) => $entity->isValid() ?? false)
        ;

        $duplicate = Action::new('duplicate', '复制活动')
            ->linkToCrudAction('duplicate')
            ->setCssClass('btn btn-info')
            ->setIcon('fa fa-copy')
        ;

        $updateStatus = Action::new('updateStatus', '更新状态')
            ->linkToCrudAction('updateStatus')
            ->setCssClass('btn btn-primary')
            ->setIcon('fa fa-refresh')
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $activate)
            ->add(Crud::PAGE_INDEX, $deactivate)
            ->add(Crud::PAGE_INDEX, $duplicate)
            ->add(Crud::PAGE_INDEX, $updateStatus)
            ->add(Crud::PAGE_DETAIL, $activate)
            ->add(Crud::PAGE_DETAIL, $deactivate)
            ->add(Crud::PAGE_DETAIL, $duplicate)
            ->add(Crud::PAGE_DETAIL, $updateStatus)
            ->reorder(Crud::PAGE_INDEX, [Action::DETAIL, 'activate', 'deactivate', 'updateStatus', 'duplicate'])
            ->setPermission(Action::NEW, 'ROLE_ADMIN')
            ->setPermission(Action::EDIT, 'ROLE_ADMIN')
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('name', '活动名称'))
            ->add(ChoiceFilter::new('activityType', '活动类型')
                ->setChoices(ActivityType::toSelect()))
            ->add(ChoiceFilter::new('status', '活动状态')
                ->setChoices(ActivityStatus::toSelect()))
            ->add(BooleanFilter::new('valid', '是否有效'))
            ->add(BooleanFilter::new('preheatEnabled', '启用预热'))
            ->add(BooleanFilter::new('exclusive', '独占活动'))
            ->add(NumericFilter::new('priority', '优先级'))
            ->add(NumericFilter::new('totalLimit', '活动限量'))
            ->add(NumericFilter::new('soldQuantity', '已售数量'))
            ->add(DateTimeFilter::new('startTime', '开始时间'))
            ->add(DateTimeFilter::new('endTime', '结束时间'))
            ->add(DateTimeFilter::new('preheatStartTime', '预热开始时间'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('updateTime', '更新时间'))
        ;
    }

    #[AdminAction(routePath: '{entityId}/activate', routeName: 'time_limit_activity_activate')]
    public function activate(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity()->getInstance();
        assert($entity instanceof TimeLimitActivity);

        if ($entity->isValid() ?? false) {
            $this->addFlash('warning', '活动已经是启用状态');

            return $this->redirect($context->getRequest()->headers->get('referer') ?? '/admin');
        }

        $entity->setValid(true);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('限时活动 "%s" 已启用', $entity->getName()));

        return $this->redirect($context->getRequest()->headers->get('referer') ?? '/admin');
    }

    #[AdminAction(routePath: '{entityId}/deactivate', routeName: 'time_limit_activity_deactivate')]
    public function deactivate(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity()->getInstance();
        assert($entity instanceof TimeLimitActivity);

        if (!($entity->isValid() ?? false)) {
            $this->addFlash('warning', '活动已经是禁用状态');

            return $this->redirect($context->getRequest()->headers->get('referer') ?? '/admin');
        }

        $entity->setValid(false);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('限时活动 "%s" 已禁用', $entity->getName()));

        return $this->redirect($context->getRequest()->headers->get('referer') ?? '/admin');
    }

    #[AdminAction(routePath: '{entityId}/duplicate', routeName: 'time_limit_activity_duplicate')]
    public function duplicate(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity()->getInstance();
        assert($entity instanceof TimeLimitActivity);

        $newActivity = new TimeLimitActivity();
        $newActivity->setName($entity->getName() . ' (副本)');
        $newActivity->setDescription($entity->getDescription());
        $newActivity->setStartTime($entity->getStartTime());
        $newActivity->setEndTime($entity->getEndTime());
        $newActivity->setActivityType($entity->getActivityType());
        $newActivity->setStatus(ActivityStatus::PENDING);
        $newActivity->setPreheatEnabled($entity->isPreheatEnabled());
        $newActivity->setPreheatStartTime($entity->getPreheatStartTime());
        $newActivity->setPriority($entity->getPriority());
        $newActivity->setExclusive($entity->isExclusive());
        $newActivity->setTotalLimit($entity->getTotalLimit());
        $newActivity->setSoldQuantity(0);
        $newActivity->setProductIds($entity->getProductIds());
        $newActivity->setValid(false);

        $this->entityManager->persist($newActivity);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('限时活动 "%s" 已复制', $entity->getName()));

        return $this->redirect($context->getRequest()->headers->get('referer') ?? '/admin');
    }

    #[AdminAction(routePath: '{entityId}/update-status', routeName: 'time_limit_activity_update_status')]
    public function updateStatus(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity()->getInstance();
        assert($entity instanceof TimeLimitActivity);

        $currentStatus = $entity->calculateCurrentStatus();
        $oldStatus = $entity->getStatus();

        if ($currentStatus === $oldStatus) {
            $this->addFlash('info', sprintf('限时活动 "%s" 状态无需更新，当前状态：%s', $entity->getName(), $currentStatus->getLabel()));
        } else {
            $entity->setStatus($currentStatus);
            $this->entityManager->flush();

            $this->addFlash('success', sprintf('限时活动 "%s" 状态已更新：%s → %s', $entity->getName(), $oldStatus->getLabel(), $currentStatus->getLabel()));
        }

        return $this->redirect($context->getRequest()->headers->get('referer') ?? '/admin');
    }
}
