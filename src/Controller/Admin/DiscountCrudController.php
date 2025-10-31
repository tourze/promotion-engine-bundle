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
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use PromotionEngineBundle\Entity\Discount;
use PromotionEngineBundle\Enum\DiscountType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;

/**
 * @extends AbstractCrudController<Discount>
 */
#[AdminCrud(routePath: '/promotion-engine/discount', routeName: 'promotion_engine_discount')]
final class DiscountCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Discount::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('优惠设置')
            ->setEntityLabelInPlural('优惠设置')
            ->setPageTitle('index', '优惠设置列表')
            ->setPageTitle('detail', '优惠设置详情')
            ->setPageTitle('new', '新建优惠设置')
            ->setPageTitle('edit', '编辑优惠设置')
            ->setHelp('index', '管理促销活动的具体优惠内容，包括减价、打折、赠品等优惠方式')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['id', 'remark'])
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

        yield ChoiceField::new('type', '优惠类型')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions(['class' => DiscountType::class])
            ->formatValue(function ($value) {
                return $value instanceof DiscountType ? $value->getLabel() : '';
            })
            ->setRequired(true)
            ->setColumns(6)
        ;

        yield MoneyField::new('value', '优惠数值')
            ->setCurrency('CNY')
            ->setStoredAsCents(false)
            ->setColumns(6)
            ->setHelp('减价活动填写减免金额，打折活动填写折扣率(0.1-1.0)')
        ;

        yield BooleanField::new('isLimited', '是否限量')
            ->setColumns(4)
            ->setHelp('限量优惠到达配额后自动停止')
        ;

        yield IntegerField::new('quota', '配额数量')
            ->setColumns(4)
            ->setHelp('限量优惠的总配额，0表示不限制')
        ;

        yield IntegerField::new('number', '已参与数量')
            ->setColumns(4)
            ->hideOnForm()
            ->setHelp('当前已参与的用户数量')
        ;

        yield TextareaField::new('remark', '备注')
            ->setMaxLength(200)
            ->setColumns(12)
            ->hideOnIndex()
        ;

        yield BooleanField::new('valid', '是否有效')
            ->setColumns(6)
        ;

        yield CollectionField::new('productRelations', '关联商品')
            ->onlyOnDetail()
            ->formatValue(function ($value) {
                return $value ? sprintf('共 %d 个关联商品', $value->count()) : '无关联商品';
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
        $discountTypeChoices = [];
        foreach (DiscountType::cases() as $case) {
            $discountTypeChoices[$case->getLabel()] = $case->value;
        }

        return $filters
            ->add(EntityFilter::new('campaign', '所属活动'))
            ->add(ChoiceFilter::new('type', '优惠类型')->setChoices($discountTypeChoices))
            ->add(BooleanFilter::new('isLimited', '是否限量'))
            ->add(BooleanFilter::new('valid', '是否有效'))
            ->add(NumericFilter::new('value', '优惠数值'))
            ->add(NumericFilter::new('quota', '配额数量'))
            ->add(NumericFilter::new('number', '已参与数量'))
            ->add(TextFilter::new('remark', '备注'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('updateTime', '更新时间'))
        ;
    }
}
