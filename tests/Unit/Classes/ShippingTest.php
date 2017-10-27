<?php

/**
 * Copyright Shopgate Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @author    Shopgate Inc, 804 Congress Ave, Austin, Texas 78701 <interfaces@shopgate.com>
 * @copyright Shopgate Inc
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

/**
 * Class ShippingTest
 */
class ShippingTest extends \PHPUnit_Framework_TestCase
{
    /** @var ShopgateShipping */
    private $subjectUnderTest;

    public function setUp()
    {
        /** @var PHPUnit_Framework_MockObject_MockObject | ShopGate $shopGate */
        $shopGate               = new PaymentModule();
        $this->subjectUnderTest = new ShopgateShipping($shopGate);
    }

    /**
     * @param Order $expectedOrder
     * @param Order $preConditionedOrder
     * @param int   $shopgateCarrierId
     * @param float $shippingCosts
     * @param float $paymentCosts
     *
     * @dataProvider provideUpdateOrderTotalLegacyCases
     */
    public function testUpdateOrderTotalsLegacy(
        Order $expectedOrder,
        Order $preConditionedOrder,
        $shopgateCarrierId,
        $shippingCosts,
        $paymentCosts
    ) {
        $calculcatedOrder = $this->subjectUnderTest->updateOrderTotalsLegacy(
            $preConditionedOrder,
            $shippingCosts,
            $paymentCosts,
            $shopgateCarrierId
        );

        $this->assertEquals($expectedOrder->id_carrier, $calculcatedOrder->id_carrier);
        $this->assertEquals($expectedOrder->total_paid, $calculcatedOrder->total_paid);
        $this->assertEquals($expectedOrder->total_paid_real, $calculcatedOrder->total_paid_real);
        $this->assertEquals($expectedOrder->total_shipping, $calculcatedOrder->total_shipping);
    }

    /**
     * @return array
     */
    public function provideUpdateOrderTotalLegacyCases()
    {
        return array(
            'default'                                => array(
                new Order(101, 22, 22, 6.99),
                new Order(99, 20, 20, 4.99),
                101,
                5.99,
                1.00,
            ),
            'no shipping costs'                      => array(
                new Order(101, 16.01, 16.01, 1.00),
                new Order(99, 20, 20, 4.99),
                101,
                0.00,
                1.00,
            ),
            'no shipping costs and no payment costs' => array(
                new Order(101, 15.01, 15.01, 0.00),
                new Order(99, 20, 20, 4.99),
                101,
                0.00,
                0.00,
            ),
        );
    }

    /**
     * @param float $amountShopgatePaymentNet
     * @param int   $orderId
     * @param int   $shopgateCarrierId
     * @param float $amountGross
     * @param float $amountNet
     * @param float $amountShopgatePaymentGross
     *
     * @dataProvider provideCarrierCases
     */
    public function testUpdateCarrier(
        $amountShopgatePaymentNet,
        $orderId,
        $shopgateCarrierId,
        $amountGross,
        $amountNet,
        $amountShopgatePaymentGross
    ) {
        $orderCarrier = new OrderCarrier(5);

        /** @var OrderCarrier $orderCarrier */
        $orderCarrier = $this->subjectUnderTest->updateOrderCarrier(
            $orderCarrier,
            $orderId,
            $shopgateCarrierId,
            $amountShopgatePaymentGross,
            $amountShopgatePaymentNet,
            $amountGross,
            $amountNet
        );

        $this->assertEquals($orderId, $orderCarrier->id_order);
        $this->assertEquals($shopgateCarrierId, $orderCarrier->id_carrier);
        $this->assertEquals($amountGross + $amountShopgatePaymentGross, $orderCarrier->shipping_cost_tax_incl);
        $this->assertEquals($amountNet + $amountShopgatePaymentNet, $orderCarrier->shipping_cost_tax_excl);
    }

    /**
     * @return array
     */
    public function provideCarrierCases()
    {
        return array(
            'default' => array(
                5,
                101,
                102,
                119,
                100,
                5.95,
            ),
        );
    }

    /**
     * @param float $expectedAmountGross
     * @param float $amountShopgatePaymentNet
     * @param float $paymentTaxPercent
     *
     * @dataProvider provideAmountPaymentGrossCases
     */
    public function testAmountPaymentGross($expectedAmountGross, $amountShopgatePaymentNet, $paymentTaxPercent)
    {
        $this->assertEquals(
            $expectedAmountGross,
            $this->subjectUnderTest->getAmountPaymentGross($amountShopgatePaymentNet, $paymentTaxPercent)
        );
    }

    /**
     * @return array
     */
    public function provideAmountPaymentGrossCases()
    {
        return array(
            'default'  => array(
                119,
                100,
                19,
            ),
            'no taxes' => array(
                100,
                100,
                0,
            ),
        );
    }

    /**
     * @param float $expectedTaxRate
     * @param float $amountGross
     * @param float $amountNet
     *
     * @dataProvider provideTaxRateCases
     */
    public function testTaxRate($expectedTaxRate, $amountGross, $amountNet)
    {
        $this->assertEquals(
            $expectedTaxRate,
            $this->subjectUnderTest->calculateTaxRate($amountGross, $amountNet)
        );
    }

    /**
     * @return array
     */
    public function provideTaxRateCases()
    {
        return array(
            'default'    => array(
                19,
                119,
                100,
            ),
            'no taxes'   => array(
                0,
                100,
                100,
            ),
            'zero gross' => array(
                0,
                0,
                100,
            ),
            'zero net'   => array(
                0,
                100,
                0,
            ),
        );
    }
}
