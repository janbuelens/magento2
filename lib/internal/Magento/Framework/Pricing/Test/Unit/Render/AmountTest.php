<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\Pricing\Test\Unit\Render;

use Magento\Framework\Pricing\Render\RendererPool;
use Magento\Framework\Pricing\Render\Amount;
use Magento\Framework\Pricing\Object\SaleableInterface;
use Magento\Framework\Pricing\Price\PriceInterface;

/**
 * Test class for \Magento\Framework\Pricing\Render\Amount
 */
class AmountTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Amount
     */
    protected $model;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceCurrency;

    /**
     * @var RendererPool|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $rendererPool;

    /**
     * @var \Magento\Framework\View\LayoutInterface | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $layout;

    /**
     * @var SaleableInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $saleableItemMock;

    /**
     * @var \Magento\Framework\Pricing\Amount\AmountInterface | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $amount;

    /**
     * @var PriceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceMock;

    public function setUp()
    {
        $this->priceCurrency = $this->getMock('Magento\Framework\Pricing\PriceCurrencyInterface');
        $data = [
            'default' => [
                'adjustments' => [
                    'base_price_test' => [
                        'tax' => [
                            'adjustment_render_class' => 'Magento\Framework\View\Element\Template',
                            'adjustment_render_template' => 'template.phtml',
                        ],
                    ],
                ],
            ],
        ];

        $this->rendererPool = $this->getMock(
            'Magento\Framework\Pricing\Render\RendererPool',
            [],
            ['data' => $data],
            '',
            false,
            false
        );

        $this->layout = $this->getMock('Magento\Framework\View\Layout', [], [], '', false);
        $this->amount = $this->getMockForAbstractClass('Magento\Framework\Pricing\Amount\AmountInterface');
        $this->saleableItemMock = $this->getMockForAbstractClass('Magento\Framework\Pricing\Object\SaleableInterface');
        $this->priceMock = $this->getMockForAbstractClass('Magento\Framework\Pricing\Price\PriceInterface');

        $eventManager = $this->getMock('Magento\Framework\Event\Test\Unit\ManagerStub', [], [], '', false);
        $config = $this->getMock('Magento\Store\Model\Store\Config', [], [], '', false);
        $scopeConfigMock = $this->getMockForAbstractClass('Magento\Framework\App\Config\ScopeConfigInterface');
        $context = $this->getMock('Magento\Framework\View\Element\Template\Context', [], [], '', false);
        $context->expects($this->any())
            ->method('getEventManager')
            ->will($this->returnValue($eventManager));
        $context->expects($this->any())
            ->method('getStoreConfig')
            ->will($this->returnValue($config));
        $context->expects($this->any())
            ->method('getLayout')
            ->will($this->returnValue($this->layout));
        $context->expects($this->any())
            ->method('getScopeConfig')
            ->will($this->returnValue($scopeConfigMock));

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->model = $objectManager->getObject(
            'Magento\Framework\Pricing\Render\Amount',
            [
                'context' => $context,
                'priceCurrency' => $this->priceCurrency,
                'rendererPool' => $this->rendererPool,
                'amount' => $this->amount,
                'saleableItem' => $this->saleableItemMock,
                'price' => $this->priceMock
            ]
        );
    }

    public function testFormatCurrency()
    {
        $amount = '100';
        $includeContainer = true;
        $precision = \Magento\Framework\Pricing\PriceCurrencyInterface::DEFAULT_PRECISION;

        $result = '100.0 grn';

        $this->priceCurrency->expects($this->once())
            ->method('format')
            ->with($amount, $includeContainer, $precision)
            ->will($this->returnValue($result));

        $this->assertEquals($result, $this->model->formatCurrency($amount, $includeContainer, $precision));
    }

    public function testGetDisplayCurrencySymbol()
    {
        $currencySymbol = '$';
        $this->priceCurrency->expects($this->once())
            ->method('getCurrencySymbol')
            ->willReturn($currencySymbol);
        $this->assertEquals($currencySymbol, $this->model->getDisplayCurrencySymbol());
    }

    /**
     * Test case for getAdjustmentRenders method through toHtml()
     */
    public function testToHtmlSkipAdjustments()
    {
        $this->model->setData('skip_adjustments', true);
        $this->rendererPool->expects($this->never())
            ->method('getAdjustmentRenders');

        $this->model->toHtml();
    }

    /**
     * Test case for getAdjustmentRenders method through toHtml()
     */
    public function testToHtmlGetAdjustmentRenders()
    {
        $data = ['key1' => 'data1', 'css_classes' => 'class1 class2'];
        $expectedData = [
            'key1' => 'data1',
            'css_classes' => 'class1 class2',
            'module_name' => null,
            'adjustment_css_classes' => 'class1 class2 render1 render2',
        ];

        $this->model->setData($data);

        $adjustmentRender1 = $this->getAdjustmentRenderMock($expectedData);
        $adjustmentRender2 = $this->getAdjustmentRenderMock($expectedData);
        $adjustmentRenders = ['render1' => $adjustmentRender1, 'render2' => $adjustmentRender2];
        $this->rendererPool->expects($this->once())
            ->method('getAdjustmentRenders')
            ->will($this->returnValue($adjustmentRenders));
        $this->amount->expects($this->atLeastOnce())
            ->method('getAdjustmentAmount')
            ->willReturn(true);

        $this->model->toHtml();
    }

    public function testGetDisplayValueExiting()
    {
        $displayValue = 5.99;
        $this->model->setDisplayValue($displayValue);
        $this->assertEquals($displayValue, $this->model->getDisplayValue());
    }

    public function testGetDisplayValue()
    {
        $amountValue = 100.99;
        $this->amount->expects($this->once())
            ->method('getValue')
            ->will($this->returnValue($amountValue));
        $this->assertEquals($amountValue, $this->model->getDisplayValue());
    }

    public function testGetAmount()
    {
        $this->assertEquals($this->amount, $this->model->getAmount());
    }

    public function testGetSealableItem()
    {
        $this->assertEquals($this->saleableItemMock, $this->model->getSaleableItem());
    }

    public function testGetPrice()
    {
        $this->assertEquals($this->priceMock, $this->model->getPrice());
    }

    public function testAdjustmentsHtml()
    {
        $adjustmentHtml1 = 'adjustment_1_html';
        $adjustmentHtml2 = 'adjustment_2_html';
        $data = ['key1' => 'data1', 'css_classes' => 'class1 class2'];
        $expectedData = [
            'key1' => 'data1',
            'css_classes' => 'class1 class2',
            'module_name' => null,
            'adjustment_css_classes' => 'class1 class2 render1 render2',
        ];

        $this->model->setData($data);

        $this->assertFalse($this->model->hasAdjustmentsHtml());

        $adjustmentRender1 = $this->getAdjustmentRenderMock($expectedData, $adjustmentHtml1, 'adjustment_code1');
        $adjustmentRender2 = $this->getAdjustmentRenderMock($expectedData, $adjustmentHtml2, 'adjustment_code2');
        $adjustmentRenders = ['render1' => $adjustmentRender1, 'render2' => $adjustmentRender2];
        $this->rendererPool->expects($this->once())
            ->method('getAdjustmentRenders')
            ->will($this->returnValue($adjustmentRenders));
        $this->amount->expects($this->atLeastOnce())
            ->method('getAdjustmentAmount')
            ->willReturn(true);

        $this->model->toHtml();

        $this->assertTrue($this->model->hasAdjustmentsHtml());

        $this->assertEquals($adjustmentHtml1 . $adjustmentHtml2, $this->model->getAdjustmentsHtml());
    }

    protected function getAdjustmentRenderMock($data = [], $html = '', $code = 'adjustment_code')
    {
        $adjustmentRender = $this->getMockForAbstractClass(
            'Magento\Framework\Pricing\Render\AdjustmentRenderInterface'
        );
        $adjustmentRender->expects($this->once())
            ->method('render')
            ->with($this->model, $data)
            ->will($this->returnValue($html));
        $adjustmentRender->expects($this->any())
            ->method('getAdjustmentCode')
            ->will($this->returnValue($code));
        return $adjustmentRender;
    }
}
