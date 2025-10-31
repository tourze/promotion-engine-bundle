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
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\PercentField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use PromotionEngineBundle\Entity\ActivityProduct;
use PromotionEngineBundle\Entity\TimeLimitActivity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @extends AbstractCrudController<ActivityProduct>
 */
#[AdminCrud(routePath: '/promotion-engine/activity-product', routeName: 'promotion_engine_activity_product')]
final class ActivityProductCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return ActivityProduct::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('活动商品')
            ->setEntityLabelInPlural('活动商品')
            ->setPageTitle('index', '活动商品列表')
            ->setPageTitle('detail', '活动商品详情')
            ->setPageTitle('new', '新建活动商品')
            ->setPageTitle('edit', '编辑活动商品')
            ->setHelp('index', '管理限时活动中的商品，包括活动价格、库存、限购等设置')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['id', 'productId'])
            ->setPaginatorPageSize(20)
        ;
    }

    /**
     * @return \Generator<int, FieldInterface, mixed, void>
     */
    public function configureFields(string $pageName): iterable
    {
        yield from $this->getBasicFields();
        yield from $this->getStockRelatedFields();
        yield from $this->getSystemFields();
    }

    /**
     * @return \Generator<int, FieldInterface, mixed, void>
     */
    private function getBasicFields(): iterable
    {
        yield IdField::new('id', 'ID')
            ->setMaxLength(9999)
            ->hideOnForm()
        ;

        yield AssociationField::new('activity', '所属活动')
            ->setRequired(true)
            ->setColumns(6)
            ->autocomplete()
            ->formatValue(function ($value) {
                return $value ? $value->getName() : '';
            })
        ;

        yield TextField::new('productId', '商品ID')
            ->setRequired(true)
            ->setMaxLength(255)
            ->setColumns(6)
            ->setHelp('商品的唯一标识符')
        ;

        yield MoneyField::new('activityPrice', '活动价格')
            ->setRequired(true)
            ->setCurrency('CNY')
            ->setColumns(6)
            ->setHelp('商品在活动期间的特价')
        ;

        yield IntegerField::new('limitPerUser', '限购数量')
            ->setRequired(true)
            ->setColumns(6)
            ->setHelp('每个用户最多可购买的数量')
        ;

        yield BooleanField::new('valid', '是否有效')
            ->setColumns(6)
            ->setHelp('是否启用该活动商品')
        ;
    }

    /**
     * @return \Generator<int, FieldInterface, mixed, void>
     */
    private function getStockRelatedFields(): iterable
    {
        yield IntegerField::new('activityStock', '活动库存')
            ->setRequired(true)
            ->setColumns(6)
            ->setHelp('参与活动的商品总库存')
        ;

        yield IntegerField::new('soldQuantity', '已售数量')
            ->setColumns(6)
            ->hideOnForm()
            ->setHelp('已经售出的数量')
        ;

        yield Field::new('remainingStock', '剩余库存')
            ->setColumns(6)
            ->hideOnForm()
            ->formatValue(fn ($value, ActivityProduct $entity) => $this->formatRemainingStock($entity))
            ->setHelp('剩余库存 / 总库存 (百分比)')
        ;

        yield PercentField::new('stockUtilization', '库存使用率')
            ->setColumns(6)
            ->hideOnForm()
            ->setNumDecimals(1)
            ->formatValue(fn ($value, ActivityProduct $entity) => $this->formatStockUtilization($entity))
            ->setHelp('已售数量占总库存的百分比')
        ;

        yield BooleanField::new('isSoldOut', '是否售罄')
            ->onlyOnIndex()
            ->onlyOnDetail()
            ->formatValue(fn ($value, ActivityProduct $entity) => $this->formatSoldOutStatus($entity))
        ;
    }

    /**
     * @return \Generator<int, FieldInterface, mixed, void>
     */
    private function getSystemFields(): iterable
    {
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

    private function formatRemainingStock(ActivityProduct $entity): string
    {
        $remaining = $entity->getRemainingStock();
        $total = $entity->getActivityStock();
        $percentage = $total > 0 ? round(($remaining / $total) * 100) : 0;

        $class = $percentage > 50 ? 'success' : ($percentage > 20 ? 'warning' : 'danger');

        return sprintf(
            '<span class="badge bg-%s">%d / %d (%d%%)</span>',
            $class,
            $remaining,
            $total,
            $percentage
        );
    }

    private function formatStockUtilization(ActivityProduct $entity): string
    {
        $utilization = $entity->getStockUtilization();
        $class = $utilization > 80 ? 'danger' : ($utilization > 50 ? 'warning' : 'success');

        return sprintf(
            '<span class="badge bg-%s">%.1f%%</span>',
            $class,
            $utilization
        );
    }

    private function formatSoldOutStatus(ActivityProduct $entity): string
    {
        return $entity->isSoldOut()
            ? '<span class="badge bg-danger">售罄</span>'
            : '<span class="badge bg-success">有库存</span>';
    }

    public function configureActions(Actions $actions): Actions
    {
        $activate = Action::new('activate', '启用商品')
            ->linkToCrudAction('activate')
            ->setCssClass('btn btn-success')
            ->setIcon('fa fa-play')
            ->displayIf(fn (ActivityProduct $entity) => !($entity->isValid() ?? false))
        ;

        $deactivate = Action::new('deactivate', '禁用商品')
            ->linkToCrudAction('deactivate')
            ->setCssClass('btn btn-warning')
            ->setIcon('fa fa-pause')
            ->displayIf(fn (ActivityProduct $entity) => $entity->isValid() ?? false)
        ;

        $resetStock = Action::new('resetStock', '重置库存')
            ->linkToCrudAction('resetStock')
            ->setCssClass('btn btn-info')
            ->setIcon('fa fa-refresh')
            ->displayIf(fn (ActivityProduct $entity) => $entity->getSoldQuantity() > 0)
        ;

        $addStock = Action::new('addStock', '增加库存')
            ->linkToCrudAction('addStock')
            ->setCssClass('btn btn-primary')
            ->setIcon('fa fa-plus')
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $activate)
            ->add(Crud::PAGE_INDEX, $deactivate)
            ->add(Crud::PAGE_INDEX, $addStock)
            ->add(Crud::PAGE_DETAIL, $activate)
            ->add(Crud::PAGE_DETAIL, $deactivate)
            ->add(Crud::PAGE_DETAIL, $resetStock)
            ->add(Crud::PAGE_DETAIL, $addStock)
            ->reorder(Crud::PAGE_INDEX, [Action::DETAIL, 'activate', 'deactivate', 'addStock'])
            ->setPermission(Action::NEW, 'ROLE_ADMIN')
            ->setPermission(Action::EDIT, 'ROLE_ADMIN')
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
            ->setPermission('resetStock', 'ROLE_SUPER_ADMIN')
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('productId', '商品ID'))
            ->add(EntityFilter::new('activity', '所属活动'))
            ->add(BooleanFilter::new('valid', '是否有效'))
            ->add(NumericFilter::new('activityPrice', '活动价格'))
            ->add(NumericFilter::new('limitPerUser', '限购数量'))
            ->add(NumericFilter::new('activityStock', '活动库存'))
            ->add(NumericFilter::new('soldQuantity', '已售数量'))
        ;
    }

    #[AdminAction(routePath: '{entityId}/activate', routeName: 'activity_product_activate')]
    public function activate(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity()->getInstance();
        assert($entity instanceof ActivityProduct);

        if ($entity->isValid() ?? false) {
            $this->addFlash('warning', '活动商品已经是启用状态');

            return $this->redirect($context->getRequest()->headers->get('referer') ?? '/admin');
        }

        $entity->setValid(true);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('活动商品 "%s" 已启用', $entity->getProductId()));

        return $this->redirect($context->getRequest()->headers->get('referer') ?? '/admin');
    }

    #[AdminAction(routePath: '{entityId}/deactivate', routeName: 'activity_product_deactivate')]
    public function deactivate(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity()->getInstance();
        assert($entity instanceof ActivityProduct);

        if (!($entity->isValid() ?? false)) {
            $this->addFlash('warning', '活动商品已经是禁用状态');

            return $this->redirect($context->getRequest()->headers->get('referer') ?? '/admin');
        }

        $entity->setValid(false);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('活动商品 "%s" 已禁用', $entity->getProductId()));

        return $this->redirect($context->getRequest()->headers->get('referer') ?? '/admin');
    }

    #[AdminAction(routePath: '{entityId}/reset-stock', routeName: 'activity_product_reset_stock')]
    public function resetStock(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity()->getInstance();
        assert($entity instanceof ActivityProduct);

        if (0 === $entity->getSoldQuantity()) {
            $this->addFlash('warning', '该商品的已售数量已经为0，无需重置');

            return $this->redirect($context->getRequest()->headers->get('referer') ?? '/admin');
        }

        $entity->setSoldQuantity(0);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('活动商品 "%s" 的库存已重置', $entity->getProductId()));

        return $this->redirect($context->getRequest()->headers->get('referer') ?? '/admin');
    }

    #[AdminAction(routePath: '{entityId}/add-stock', routeName: 'activity_product_add_stock')]
    public function addStock(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity()->getInstance();
        assert($entity instanceof ActivityProduct);

        $addQuantity = (int) $request->query->get('quantity', 10);

        if ($addQuantity <= 0) {
            $this->addFlash('danger', '增加的库存数量必须大于0');

            return $this->redirect($context->getRequest()->headers->get('referer') ?? '/admin');
        }

        $originalStock = $entity->getActivityStock();
        $entity->setActivityStock($originalStock + $addQuantity);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf(
            '活动商品 "%s" 的库存已从 %d 增加到 %d',
            $entity->getProductId(),
            $originalStock,
            $entity->getActivityStock()
        ));

        return $this->redirect($context->getRequest()->headers->get('referer') ?? '/admin');
    }
}
