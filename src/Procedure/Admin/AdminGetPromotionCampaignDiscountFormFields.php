<?php

namespace PromotionEngineBundle\Procedure\Admin;

use AntdCpBundle\Builder\Column\ActionColumn;
use AntdCpBundle\Builder\Column\ImageTitleColumn;
use AntdCpBundle\Builder\Column\TextColumn;
use AntdCpBundle\Builder\Field\InputNumberField;
use AntdCpBundle\Builder\Field\InputTextField;
use AntdCpBundle\Builder\Field\SelectField;
use AntdCpBundle\Builder\Field\SpuPickerField;
use PromotionEngineBundle\Enum\DiscountType;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;
use Tourze\JsonRPCLogBundle\Attribute\Log;
use Yiisoft\Arrays\ArrayHelper;

#[Log]
#[MethodTag('促销模块')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[MethodDoc('根据折扣类型获取折扣联动表单字段')]
#[MethodExpose('AdminGetPromotionCampaignDiscountFormFields')]
class AdminGetPromotionCampaignDiscountFormFields extends BaseProcedure
{
    public array $form = [];

    public array $record = [];

    public function execute(): array
    {
        $form = $this->form;
        $record = $this->record;
        if ($record && !$form && !empty($record['__discount'])) {
            // 有record的时候代表是编辑；商品类型是不可以编辑的
            $type = ArrayHelper::getValue($record, 'type');
        } else {
            $typeField = ArrayHelper::getValue($form, 'type');
            if (!isset($typeField['value'])) {
                return [
                    'visible' => false,
                    'fields' => [],
                ];
            }

            $type = ArrayHelper::getValue($typeField, 'value');
        }
        $type = DiscountType::tryFrom($type);
        if (!$type) {
            return [
                'visible' => false,
                'fields' => [],
            ];
        }
        $fields = [];
        $visible = false;
        switch ($type) {
            case DiscountType::BUY_GIVE:
                $visible = true;
                $fields = [
                    SpuPickerField::gen()
                        ->setId('products')
                        ->setSpan(24)
                        ->setLabel('选择赠品')
                        ->setRules([['required' => true, 'message' => '请选择商品']])
                        ->setTooltipTitle('商品的总配额不设置或者设置为0则不限制库存')
                        ->setInputProps([
                            'multiple' => true,
                            'importButton' => false,
                            'can_edit' => true,
                            'importApi' => 'AdminGetPromotionProducts',
                            'importButtonLabel' => '本地新建一个xlsx，第一列填写SkuId即可',
                            'api' => 'AdminGetPromotionProducts',
                            'selectField' => [
                                'id',
                                'thumb',
                                'title',
                                'info',
                                'goodsInfo',
                                'market_price',
                            ],
                            'columns' => [
                                TextColumn::gen()
                                    ->setWidth(120)
                                    ->setDataIndex('id')
                                    ->setTitle('SPUID'),
                                ImageTitleColumn::gen()
                                    ->setDataIndex('goodsInfo')
                                    ->setTitle('商品')
                                    ->setEditable(false),
                                TextColumn::gen()
                                    ->setDataIndex('quota')
                                    ->setTitle('总配额')
                                    ->setEditable(true),
                                TextColumn::gen()
                                    ->setDataIndex('giftQuantity')
                                    ->setTitle('赠送数')
                                    ->setEditable(true),
                                ActionColumn::gen()
                                    ->setWidth(70)
                                    ->setTitle('操作'),
                            ],
                        ]),
                ];
                break;
            case DiscountType::REDUCTION:
                $visible = true;
                $fields = [
                    InputNumberField::gen()
                        ->setId('value')
                        ->setLabel('优惠金额')
                        ->setInputProps([
                            'placeholder' => '优惠金额',
                            'min' => 0.01,
                            'precision' => 2,
                        ])
                        ->setRules([['required' => true, 'message' => '请输入优惠金额']]),
                ];
                break;
            case DiscountType::DISCOUNT:
                $visible = true;
                $fields = [
                    InputNumberField::gen()
                        ->setFieldProps(['help' => '10范围内的数值，最多两位小数'])
                        ->setId('value')
                        ->setLabel('折扣')
                        ->setInputProps([
                            'placeholder' => '请输入折扣',
                            'precision' => 2,
                            'min' => 1,
                            'max' => 10,
                        ])
                        ->setRules([['required' => true, 'message' => '请输入折扣']]),
                ];
                break;
            case DiscountType::BUY_N_GET_M:
                $visible = true;
                $fields = [
                    InputNumberField::gen()
                        ->setTooltipTitle('购买的单个sku件数')
                        ->setId('purchaseQuantity')
                        ->setLabel('购买数量')
                        ->setInputProps([
                            'placeholder' => '请输入购买数量',
                            'min' => 1,
                            'style' => [
                                'width' => '200px',
                            ],
                        ])
                        ->setRules([['required' => true, 'message' => '请输入购买数量']]),
                    InputNumberField::gen()
                        ->setTooltipTitle('免费获得的sku数量')
                        ->setId('freeQuantity')
                        ->setLabel('免费数量')
                        ->setInputProps([
                            'placeholder' => '请输入免费数量',
                            'min' => 1,
                            'style' => [
                                'width' => '200px',
                            ],
                        ])
                        ->setRules([['required' => true, 'message' => '请输入免费数量']]),
                ];
                break;
            case DiscountType::PROGRESSIVE_DISCOUNT_SCHEME:
                $visible = true;
                $fields = [
                    InputTextField::gen()
                        ->setId('condition1')
                        ->setSpan(12)
                        ->setLabel('')
                        ->setInputProps([
                            'addonBefore' => '第N件',
                            'addonAfter' => '',
                            'style' => [
                                'width' => '100%',
                            ],
                        ]),
                    InputTextField::gen()
                        ->setSpan(12)
                        ->setId('condition2')
                        ->setLabel('')
                        ->setInputProps([
                            'addonAfter' => '折',
                            'style' => [
                                'width' => '100%',
                            ],
                        ]),
                ];
                break;
            case DiscountType::SPEND_THRESHOLD_WITH_ADD_ON:
                $visible = true;
                $fields = [
                    InputTextField::gen()
                        ->setId('condition1')
                        ->setSpan(12)
                        ->setLabel('')
                        ->setInputProps([
                            'addonBefore' => '消费满',
                            'addonAfter' => '元',
                            'style' => [
                                'width' => '100%',
                            ],
                        ]),
                    InputTextField::gen()
                        ->setSpan(12)
                        ->setId('condition2')
                        ->setLabel('')
                        ->setInputProps([
                            'addonBefore' => '加价',
                            'addonAfter' => '元',
                            'style' => [
                                'width' => '100%',
                            ],
                        ]),
                    InputTextField::gen()
                        ->setSpan(12)
                        ->setId('condition3')
                        ->setLabel('可选件数')
                        ->setInputProps([
                            'style' => [
                                'width' => '100%',
                            ],
                        ]),
                    SpuPickerField::gen()
                        ->setId('products')
                        ->setSpan(24)
                        ->setLabel('选择赠品')
                        ->setTooltipTitle('商品的总配额不设置或者设置为0则不限制库存')
                        ->setRules([['required' => true, 'message' => '请选择商品']])
                        ->setInputProps([
                            'can_edit' => true,
                            'multiple' => true,
                            'importButton' => false,
                            'importApi' => 'AdminGetPromotionProducts',
                            'importButtonLabel' => '本地新建一个xlsx，第一列填写SkuId即可',
                            'api' => 'AdminGetPromotionProducts',
                            'selectField' => [
                                'id',
                                'thumb',
                                'title',
                                'info',
                                'goodsInfo',
                                'market_price',
                            ],
                            'columns' => [
                                TextColumn::gen()
                                    ->setWidth(120)
                                    ->setDataIndex('id')
                                    ->setTitle('SPUID'),
                                ImageTitleColumn::gen()
                                    ->setDataIndex('goodsInfo')
                                    ->setTitle('商品')
                                    ->setEditable(false),
                                TextColumn::gen()
                                    ->setDataIndex('quota')
                                    ->setTitle('总配额')
                                    ->setEditable(true),
                                TextColumn::gen()
                                    ->setDataIndex('giftQuantity')
                                    ->setTitle('赠送数')
                                    ->setEditable(true),
                                ActionColumn::gen()
                                    ->setWidth(70)
                                    ->setTitle('操作'),
                            ],
                        ]),
                ];
                break;
            case DiscountType::FREE_FREIGHT:
                $visible = true;
                $provinces = [
                    ['text' => '北京市', 'value' => '北京市'],
                    ['text' => '天津市', 'value' => '天津市'],
                    ['text' => '上海市', 'value' => '上海市'],
                    ['text' => '重庆市', 'value' => '重庆市'],
                    ['text' => '河北省', 'value' => '河北省'],
                    ['text' => '山西省', 'value' => '山西省'],
                    ['text' => '辽宁省', 'value' => '辽宁省'],
                    ['text' => '吉林省', 'value' => '吉林省'],
                    ['text' => '黑龙江省', 'value' => '黑龙江省'],
                    ['text' => '江苏省', 'value' => '江苏省'],
                    ['text' => '浙江省', 'value' => '浙江省'],
                    ['text' => '安徽省', 'value' => '安徽省'],
                    ['text' => '福建省', 'value' => '福建省'],
                    ['text' => '江西省', 'value' => '江西省'],
                    ['text' => '山东省', 'value' => '山东省'],
                    ['text' => '河南省', 'value' => '河南省'],
                    ['text' => '湖北省', 'value' => '湖北省'],
                    ['text' => '湖南省', 'value' => '湖南省'],
                    ['text' => '广东省', 'value' => '广东省'],
                    ['text' => '海南省', 'value' => '海南省'],
                    ['text' => '四川省', 'value' => '四川省'],
                    ['text' => '贵州省', 'value' => '贵州省'],
                    ['text' => '云南省', 'value' => '云南省'],
                    ['text' => '陕西省', 'value' => '陕西省'],
                    ['text' => '甘肃省', 'value' => '甘肃省'],
                    ['text' => '青海省', 'value' => '青海省'],
                    ['text' => '台湾省', 'value' => '台湾省'],
                    ['text' => '广西壮族自治区', 'value' => '广西壮族自治区'],
                    ['text' => '内蒙古自治区', 'value' => '内蒙古自治区'],
                    ['text' => '西藏自治区', 'value' => '西藏自治区'],
                    ['text' => '宁夏回族自治区', 'value' => '宁夏回族自治区'],
                    ['text' => '新疆维吾尔自治区', 'value' => '新疆维吾尔自治区'],
                    ['text' => '香港特别行政区', 'value' => '香港特别行政区'],
                    ['text' => '澳门特别行政区', 'value' => '澳门特别行政区'],
                ];
                $fields = [
                    SelectField::gen()
                        ->setTooltipTitle('作用的省份')
                        ->setId('condition1')
                        ->setLabel('省份')
                        ->setInputProps([
                            'placeholder' => '请选择省份',
                            'options' => $provinces,
                            'mode' => 'multiple',
                            'style' => [
                                'width' => '100%',
                            ],
                        ])
                        ->setRules([['required' => true, 'message' => '请选择省份']]),
                    InputNumberField::gen()
                        ->setTooltipTitle('消费达到具体金额免邮费')
                        ->setId('condition2')
                        ->setLabel('消费金额')
                        ->setInputProps([
                            'placeholder' => '请输入消费金额',
                            'precision' => 2,
                            'min' => 0.1,
                            'style' => [
                                'width' => '200px',
                            ],
                        ])
                        ->setRules([['required' => true, 'message' => '请输入消费金额']]),
                ];
                break;
        }

        return [
            'visible' => $visible,
            'fields' => $fields,
        ];
    }
}
