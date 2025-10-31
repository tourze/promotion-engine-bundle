<?php

declare(strict_types=1);

namespace PromotionEngineBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use PromotionEngineBundle\Entity\DiscountCondition;

/**
 * @extends AbstractCrudController<DiscountCondition>
 */
#[AdminCrud(routePath: '/promotion-engine/discount-condition', routeName: 'promotion_engine_discount_condition')]
final class DiscountConditionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return DiscountCondition::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('优惠条件')
            ->setEntityLabelInPlural('优惠条件')
            ->setPageTitle('index', '优惠条件列表')
            ->setPageTitle('detail', '优惠条件详情')
            ->setPageTitle('new', '新建优惠条件')
            ->setPageTitle('edit', '编辑优惠条件')
            ->setHelp('index', '管理优惠活动的触发条件，支持多重条件组合')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['id', 'condition1', 'condition2', 'condition3'])
            ->setPaginatorPageSize(20)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->setMaxLength(9999)
            ->hideOnForm()
        ;

        yield AssociationField::new('discount', '关联优惠')
            ->setRequired(true)
            ->setColumns(6)
            ->setHelp('选择此条件关联的优惠活动')
            ->formatValue(function ($value) {
                return $value ? sprintf('%s (ID: %s)', $value->__toString(), $value->getId()) : '未关联';
            })
        ;

        yield TextField::new('condition1', '条件1')
            ->setRequired(true)
            ->setMaxLength(255)
            ->setColumns(12)
            ->setHelp('主要条件，必须填写')
        ;

        yield TextField::new('condition2', '条件2')
            ->setMaxLength(255)
            ->setColumns(6)
            ->setHelp('可选的附加条件')
        ;

        yield TextField::new('condition3', '条件3')
            ->setMaxLength(255)
            ->setColumns(6)
            ->setHelp('可选的第三个条件')
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
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->setPermission(Action::NEW, 'ROLE_ADMIN')
            ->setPermission(Action::EDIT, 'ROLE_ADMIN')
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('condition1', '条件1'))
            ->add(TextFilter::new('condition2', '条件2'))
            ->add(TextFilter::new('condition3', '条件3'))
            ->add(EntityFilter::new('discount', '关联优惠'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('updateTime', '更新时间'))
        ;
    }
}
