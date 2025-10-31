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
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use PromotionEngineBundle\Entity\Campaign;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @extends AbstractCrudController<Campaign>
 */
#[AdminCrud(routePath: '/promotion-engine/campaign', routeName: 'promotion_engine_campaign')]
final class CampaignCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Campaign::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('促销活动')
            ->setEntityLabelInPlural('促销活动')
            ->setPageTitle('index', '促销活动列表')
            ->setPageTitle('detail', '促销活动详情')
            ->setPageTitle('new', '新建促销活动')
            ->setPageTitle('edit', '编辑促销活动')
            ->setHelp('index', '管理促销活动，包括满减、打折、赠品等各种营销活动')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['id', 'title', 'description'])
            ->setPaginatorPageSize(20)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->setMaxLength(9999)
            ->hideOnForm()
        ;

        yield TextField::new('title', '活动名称')
            ->setRequired(true)
            ->setMaxLength(120)
            ->setColumns(6)
        ;

        yield TextareaField::new('description', '活动描述')
            ->setMaxLength(65535)
            ->setColumns(12)
            ->hideOnIndex()
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

        yield BooleanField::new('exclusive', '排他活动')
            ->setColumns(6)
            ->setHelp('排他活动不可与其他活动同时使用')
        ;

        yield IntegerField::new('weight', '权重')
            ->setColumns(6)
            ->setHelp('权重越高优先级越高，默认为0')
        ;

        yield BooleanField::new('valid', '是否有效')
            ->setColumns(6)
        ;

        yield CollectionField::new('constraints', '约束条件')
            ->onlyOnDetail()
            ->formatValue(function ($value) {
                return $value ? sprintf('共 %d 个约束条件', $value->count()) : '无约束条件';
            })
        ;

        yield CollectionField::new('discounts', '优惠设置')
            ->onlyOnDetail()
            ->formatValue(function ($value) {
                return $value ? sprintf('共 %d 个优惠设置', $value->count()) : '无优惠设置';
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
            ->displayIf(fn (Campaign $entity) => !($entity->isValid() ?? false))
        ;

        $deactivate = Action::new('deactivate', '禁用活动')
            ->linkToCrudAction('deactivate')
            ->setCssClass('btn btn-warning')
            ->setIcon('fa fa-pause')
            ->displayIf(fn (Campaign $entity) => $entity->isValid() ?? false)
        ;

        $duplicate = Action::new('duplicate', '复制活动')
            ->linkToCrudAction('duplicate')
            ->setCssClass('btn btn-info')
            ->setIcon('fa fa-copy')
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $activate)
            ->add(Crud::PAGE_INDEX, $deactivate)
            ->add(Crud::PAGE_INDEX, $duplicate)
            ->add(Crud::PAGE_DETAIL, $activate)
            ->add(Crud::PAGE_DETAIL, $deactivate)
            ->add(Crud::PAGE_DETAIL, $duplicate)
            ->reorder(Crud::PAGE_INDEX, [Action::DETAIL, 'activate', 'deactivate', 'duplicate'])
            ->setPermission(Action::NEW, 'ROLE_ADMIN')
            ->setPermission(Action::EDIT, 'ROLE_ADMIN')
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('title', '活动名称'))
            ->add(BooleanFilter::new('valid', '是否有效'))
            ->add(BooleanFilter::new('exclusive', '排他活动'))
            ->add(NumericFilter::new('weight', '权重'))
            ->add(DateTimeFilter::new('startTime', '开始时间'))
            ->add(DateTimeFilter::new('endTime', '结束时间'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('updateTime', '更新时间'))
        ;
    }

    #[AdminAction(routePath: '{entityId}/activate', routeName: 'campaign_activate')]
    public function activate(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity()->getInstance();
        assert($entity instanceof Campaign);

        if ($entity->isValid() ?? false) {
            $this->addFlash('warning', '活动已经是启用状态');

            return $this->redirect($context->getRequest()->headers->get('referer') ?? '/admin');
        }

        $entity->setValid(true);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('促销活动 "%s" 已启用', $entity->getTitle()));

        return $this->redirect($context->getRequest()->headers->get('referer') ?? '/admin');
    }

    #[AdminAction(routePath: '{entityId}/deactivate', routeName: 'campaign_deactivate')]
    public function deactivate(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity()->getInstance();
        assert($entity instanceof Campaign);

        if (!($entity->isValid() ?? false)) {
            $this->addFlash('warning', '活动已经是禁用状态');

            return $this->redirect($context->getRequest()->headers->get('referer') ?? '/admin');
        }

        $entity->setValid(false);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('促销活动 "%s" 已禁用', $entity->getTitle()));

        return $this->redirect($context->getRequest()->headers->get('referer') ?? '/admin');
    }

    #[AdminAction(routePath: '{entityId}/duplicate', routeName: 'campaign_duplicate')]
    public function duplicate(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity()->getInstance();
        assert($entity instanceof Campaign);

        $newCampaign = new Campaign();
        $newCampaign->setTitle($entity->getTitle() . ' (副本)');
        $newCampaign->setDescription($entity->getDescription());
        $newCampaign->setStartTime($entity->getStartTime());
        $newCampaign->setEndTime($entity->getEndTime());
        $newCampaign->setExclusive($entity->isExclusive());
        $newCampaign->setWeight($entity->getWeight());
        $newCampaign->setValid(false);

        $this->entityManager->persist($newCampaign);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('促销活动 "%s" 已复制', $entity->getTitle()));

        return $this->redirect($context->getRequest()->headers->get('referer') ?? '/admin');
    }
}
