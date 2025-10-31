<?php

declare(strict_types=1);

namespace PromotionEngineBundle\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use PromotionEngineBundle\Entity\Constraint;
use PromotionEngineBundle\Enum\CompareType;
use PromotionEngineBundle\Enum\LimitType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;

/**
 * @extends AbstractCrudController<Constraint>
 */
#[AdminCrud(routePath: '/promotion-engine/constraint', routeName: 'promotion_engine_constraint')]
final class ConstraintCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Constraint::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('约束条件')
            ->setEntityLabelInPlural('约束条件')
            ->setPageTitle('index', '约束条件列表')
            ->setPageTitle('detail', '约束条件详情')
            ->setPageTitle('new', '新建约束条件')
            ->setPageTitle('edit', '编辑约束条件')
            ->setHelp('index', '管理促销活动的触发条件和限制，如订单金额、用户类型、商品范围等')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['id', 'rangeValue'])
            ->setPaginatorPageSize(20)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->setMaxLength(9999)
            ->hideOnForm()
        ;

        yield AssociationField::new('campaign', '所属活动')
            ->setRequired(true)
            ->formatValue(function ($value) {
                return $value ? sprintf('%s (#%s)', $value->getTitle(), $value->getId()) : '';
            })
        ;

        yield ChoiceField::new('limitType', '限制类型')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions(['class' => LimitType::class])
            ->formatValue(function ($value) {
                return $value instanceof LimitType ? $value->getLabel() : '';
            })
            ->setRequired(true)
            ->setColumns(6)
        ;

        yield ChoiceField::new('compareType', '对比类型')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions(['class' => CompareType::class])
            ->formatValue(function ($value) {
                return $value instanceof CompareType ? $value->getLabel() : '';
            })
            ->setRequired(true)
            ->setColumns(6)
        ;

        yield TextareaField::new('rangeValue', '范围值')
            ->setMaxLength(65535)
            ->setColumns(12)
            ->setHelp('具体的条件值，如价格、商品ID、数量等。多个值用逗号分隔')
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
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->reorder(Crud::PAGE_INDEX, [Action::DETAIL])
            ->setPermission(Action::NEW, 'ROLE_ADMIN')
            ->setPermission(Action::EDIT, 'ROLE_ADMIN')
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        $limitTypeChoices = [];
        foreach (LimitType::cases() as $case) {
            $limitTypeChoices[$case->getLabel()] = $case->value;
        }

        $compareTypeChoices = [];
        foreach (CompareType::cases() as $case) {
            $compareTypeChoices[$case->getLabel()] = $case->value;
        }

        return $filters
            ->add(EntityFilter::new('campaign', '所属活动'))
            ->add(ChoiceFilter::new('limitType', '限制类型')->setChoices($limitTypeChoices))
            ->add(ChoiceFilter::new('compareType', '对比类型')->setChoices($compareTypeChoices))
            ->add(TextFilter::new('rangeValue', '范围值'))
            ->add(BooleanFilter::new('valid', '是否有效'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('updateTime', '更新时间'))
        ;
    }
}
