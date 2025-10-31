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
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use PromotionEngineBundle\Entity\DiscountRule;
use PromotionEngineBundle\Enum\DiscountType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @extends AbstractCrudController<DiscountRule>
 */
#[AdminCrud(routePath: '/promotion-engine/discount-rule', routeName: 'promotion_engine_discount_rule')]
final class DiscountRuleCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return DiscountRule::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('优惠规则')
            ->setEntityLabelInPlural('优惠规则')
            ->setPageTitle('index', '优惠规则列表')
            ->setPageTitle('detail', '优惠规则详情')
            ->setPageTitle('new', '新建优惠规则')
            ->setPageTitle('edit', '编辑优惠规则')
            ->setHelp('index', '管理促销活动的优惠规则，包括减价、打折、赠品等各种优惠类型')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['id', 'activityId'])
            ->setPaginatorPageSize(20)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->setMaxLength(9999)
            ->hideOnForm()
        ;

        yield TextField::new('activityId', '活动ID')
            ->setRequired(true)
            ->setMaxLength(19)
            ->setColumns(6)
            ->setHelp('关联的促销活动ID')
        ;

        yield ChoiceField::new('discountType', '优惠类型')
            ->setChoices(DiscountType::genOptions())
            ->setRequired(true)
            ->setColumns(6)
            ->renderExpanded(false)
            ->renderAsBadges([
                'reduction' => 'success',
                'discount' => 'primary',
                'free-freight' => 'info',
                'buy-give' => 'warning',
                'buy_n_get_m' => 'secondary',
                'progressive_discount_scheme' => 'dark',
                'spend_threshold_with_add_on' => 'light',
            ])
        ;

        yield MoneyField::new('discountValue', '优惠值')
            ->setCurrency('CNY')
            ->setRequired(true)
            ->setColumns(6)
            ->setHelp('具体的优惠金额或折扣比例')
        ;

        yield MoneyField::new('minAmount', '最低消费门槛')
            ->setCurrency('CNY')
            ->setColumns(6)
            ->setHelp('享受优惠所需的最低消费金额')
        ;

        yield MoneyField::new('maxDiscountAmount', '最大优惠金额')
            ->setCurrency('CNY')
            ->setColumns(6)
            ->setHelp('单笔订单可享受的最大优惠金额上限')
        ;

        yield IntegerField::new('requiredQuantity', '满足数量要求')
            ->setColumns(6)
            ->setHelp('触发优惠所需的最低商品数量')
        ;

        yield IntegerField::new('giftQuantity', '赠送数量')
            ->setColumns(6)
            ->setHelp('赠品或免费商品的数量')
        ;

        yield ArrayField::new('giftProductIds', '赠品商品ID列表')
            ->onlyOnDetail()
            ->hideOnIndex()
            ->setHelp('指定赠送的商品ID列表')
        ;

        yield ArrayField::new('config', '扩展配置')
            ->onlyOnDetail()
            ->hideOnIndex()
            ->setHelp('额外的配置参数，JSON格式')
        ;

        yield BooleanField::new('valid', '是否有效')
            ->setColumns(6)
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
        $activate = Action::new('activate', '启用规则')
            ->linkToCrudAction('activate')
            ->setCssClass('btn btn-success')
            ->setIcon('fa fa-play')
            ->displayIf(fn (DiscountRule $entity) => !($entity->isValid() ?? false))
        ;

        $deactivate = Action::new('deactivate', '禁用规则')
            ->linkToCrudAction('deactivate')
            ->setCssClass('btn btn-warning')
            ->setIcon('fa fa-pause')
            ->displayIf(fn (DiscountRule $entity) => $entity->isValid() ?? false)
        ;

        $duplicate = Action::new('duplicate', '复制规则')
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
            ->add(TextFilter::new('activityId', '活动ID'))
            ->add(ChoiceFilter::new('discountType', '优惠类型')
                ->setChoices(DiscountType::genOptions()))
            ->add(BooleanFilter::new('valid', '是否有效'))
            ->add(NumericFilter::new('discountValue', '优惠值'))
            ->add(NumericFilter::new('minAmount', '最低消费门槛'))
            ->add(NumericFilter::new('maxDiscountAmount', '最大优惠金额'))
            ->add(NumericFilter::new('requiredQuantity', '满足数量要求'))
            ->add(NumericFilter::new('giftQuantity', '赠送数量'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('updateTime', '更新时间'))
        ;
    }

    #[AdminAction(routePath: '{entityId}/activate', routeName: 'discount_rule_activate')]
    public function activate(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity()->getInstance();
        assert($entity instanceof DiscountRule);

        if ($entity->isValid() ?? false) {
            $this->addFlash('warning', '优惠规则已经是启用状态');

            return $this->redirect($context->getRequest()->headers->get('referer') ?? '/admin');
        }

        $entity->setValid(true);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('优惠规则 "%s" 已启用', $entity->getDiscountType()->getLabel()));

        return $this->redirect($context->getRequest()->headers->get('referer') ?? '/admin');
    }

    #[AdminAction(routePath: '{entityId}/deactivate', routeName: 'discount_rule_deactivate')]
    public function deactivate(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity()->getInstance();
        assert($entity instanceof DiscountRule);

        if (!($entity->isValid() ?? false)) {
            $this->addFlash('warning', '优惠规则已经是禁用状态');

            return $this->redirect($context->getRequest()->headers->get('referer') ?? '/admin');
        }

        $entity->setValid(false);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('优惠规则 "%s" 已禁用', $entity->getDiscountType()->getLabel()));

        return $this->redirect($context->getRequest()->headers->get('referer') ?? '/admin');
    }

    #[AdminAction(routePath: '{entityId}/duplicate', routeName: 'discount_rule_duplicate')]
    public function duplicate(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity()->getInstance();
        assert($entity instanceof DiscountRule);

        $newDiscountRule = new DiscountRule();
        $newDiscountRule->setActivityId($entity->getActivityId());
        $newDiscountRule->setDiscountType($entity->getDiscountType());
        $newDiscountRule->setDiscountValue($entity->getDiscountValue());
        $newDiscountRule->setMinAmount($entity->getMinAmount());
        $newDiscountRule->setMaxDiscountAmount($entity->getMaxDiscountAmount());
        $newDiscountRule->setRequiredQuantity($entity->getRequiredQuantity());
        $newDiscountRule->setGiftQuantity($entity->getGiftQuantity());
        $newDiscountRule->setGiftProductIds($entity->getGiftProductIds());
        $newDiscountRule->setConfig($entity->getConfig());
        $newDiscountRule->setValid(false);

        $this->entityManager->persist($newDiscountRule);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('优惠规则 "%s" 已复制', $entity->getDiscountType()->getLabel()));

        return $this->redirect($context->getRequest()->headers->get('referer') ?? '/admin');
    }
}
