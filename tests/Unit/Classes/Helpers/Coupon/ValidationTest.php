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

namespace Shopgate\Tests\Unit\Classes\Helpers\Coupon;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ValidationTest extends TestCase
{
    /** @var \ShopgateHelperCouponValidation */
    private $subjectUnderTest;

    /** @var \ShopgatePrestashopVersion|MockObject */
    private $prestashopVersionMock;

    /** @var \ShopgateContextHelper|MockObject */
    private $contextHelperMock;

    /** @var \ShopgateHelpersCouponCartRuleFactory|MockObject */
    private $cartRuleFactoryMock;

    public function setUp(): void
    {
        $this->prestashopVersionMock = $this
            ->getMockBuilder('ShopgatePrestashopVersion')
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextHelperMock = $this
            ->getMockBuilder('ShopgateContextHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->cartRuleFactoryMock = $this
            ->getMockBuilder('ShopgateHelpersCouponCartRuleFactory')
            ->getMock();

        $this->subjectUnderTest = new \ShopgateHelperCouponValidation(
            $this->prestashopVersionMock,
            $this->contextHelperMock,
            $this->cartRuleFactoryMock
        );
    }

    /**
     * @param \ShopgateExternalCoupon $externalCoupon
     * @param int                     $carrierId
     * @param \ShopgateExternalCoupon $expectedExternalCoupon
     * @param bool                    $couponsSupported
     *
     * @dataProvider provideValidateCouponFixtures
     */
    public function testValidateCoupon(
        \ShopgateExternalCoupon $externalCoupon,
        $carrierId,
        \ShopgateExternalCoupon $expectedExternalCoupon,
        $couponsSupported = true
    ) {
        // some case independent test values to ensure dependencies are called correctly
        $cartRuleId   = 123;
        $context      = 'usually an object, but for the test a string will do';
        $languageId   = 789;
        $cartProducts = array('product 1 data ...', 'product 2 data ...');
        $package      = array(
            'products'   => $cartProducts,
            'id_carrier' => $carrierId,
        );

        // set up version helper mock
        $this->prestashopVersionMock
            ->expects($this->once())
            ->method('isBelow')
            ->with('1.5.0.0')
            ->willReturn(!$couponsSupported);

        // set up context helper mock
        $this->contextHelperMock
            ->expects($this->once())
            ->method('getCurrencyIsoCode')
            ->willReturn('EUR');

        $this->contextHelperMock
            ->expects(
                null !== $carrierId
                    ? $this->once()
                    : $this->never()
            )
            ->method('getCartProducts')
            ->willReturn($cartProducts);

        $this->contextHelperMock
            ->expects(
                $couponsSupported
                    ? $this->exactly(2)
                    : $this->never()
            )
            ->method('getLanguageId')
            ->willReturn($languageId);

        $this->contextHelperMock
            ->expects(
                $couponsSupported
                    ? $this->exactly(2)
                    : $this->never()
            )
            ->method('getContext')
            ->willReturn($context);

        $this->contextHelperMock
            ->expects(
                $expectedExternalCoupon->getIsValid()
                    ? $this->once()
                    : $this->never()
            )
            ->method('addCartRule')
            ->willReturn($cartRuleId);

        // set up cart rule factory mock
        $this->cartRuleFactoryMock
            ->expects(
                $couponsSupported
                    ? $this->once()
                    : $this->never()
            )
            ->method('getByCouponCode')
            ->with($externalCoupon->getCode())
            ->willReturn(
                $this->buildCartRuleMock(
                    $cartRuleId,
                    $carrierId,
                    $expectedExternalCoupon,
                    $couponsSupported,
                    $languageId,
                    $context,
                    $package
                )
            );

        $this->subjectUnderTest->validateCoupon($externalCoupon, $carrierId);

        $this->assertEquals(
            $expectedExternalCoupon,
            $externalCoupon
        );
    }

    /**
     * @return array
     */
    public function provideValidateCouponFixtures()
    {
        $noCarrierId         = null;
        $carrierId           = 123;
        $couponsSupported    = true;
        $couponsNotSupported = false;
        $couponCode          = 'awsumthx!';
        $couponDescription   = 'Shiny coupon codes :)';
        $couponData          = $this->getCouponData($couponCode, $couponDescription);

        return array(
            'Coupons unsupported'                             => array(
                $couponData['incoming'],
                $noCarrierId,
                $couponData['expected invalid'],
                $couponsNotSupported,
            ),
            'Coupons supported, coupon invalid'               => array(
                $couponData['incoming'],
                $noCarrierId,
                $couponData['expected invalid'],
                $couponsSupported,
            ),
            'Coupons supported, coupon valid, no carrier ID'  => array(
                $couponData['incoming'],
                $noCarrierId,
                $couponData['expected valid'],
                $couponsSupported,
            ),
            'Coupons supported, coupon valid, has carrier ID' => array(
                $couponData['incoming'],
                $carrierId,
                $couponData['expected valid'],
                $couponsSupported,
            ),
        );
    }

    private function buildCoupon(array $data)
    {
        $coupon = new \ShopgateExternalCoupon($data);

        // workaround that will initialize the "camelizeCache" property for all fields,
        // enabling assertEquals() to work properly
        $coupon->toArray();

        return $coupon;
    }

    /**
     * @param $couponCode
     * @param $couponDescription
     *
     * @return array
     */
    private function getCouponData($couponCode, $couponDescription)
    {
        return array(
            'incoming'         => $this->buildCoupon(
                array(
                    'code'              => $couponCode,
                    'is_valid'          => null,
                    'not_valid_message' => null,
                    'currency'          => null,
                    'amount'            => null,
                    'amount_net'        => null,
                    'amount_gross'      => null,
                    'tax_type'          => 'auto',
                )
            ),
            'expected invalid' => $this->buildCoupon(
                array(
                    'code'              => $couponCode,
                    'is_valid'          => false,
                    'not_valid_message' => 'This voucher does not exists.',
                    'currency'          => 'EUR',
                    'tax_type'          => 'not_taxable',
                )
            ),
            'expected valid'   => $this->buildCoupon(
                array(
                    'code'              => $couponCode,
                    'is_valid'          => true,
                    'not_valid_message' => null,
                    'currency'          => 'EUR',
                    'amount'            => null,
                    'amount_net'        => null,
                    'amount_gross'      => 2.38,
                    'description'       => $couponDescription,
                    'tax_type'          => 'not_taxable',
                )
            ),
        );
    }

    /**
     * @param int                     $cartRuleId
     * @param int                     $carrierId
     * @param \ShopgateExternalCoupon $expectedExternalCoupon
     * @param bool                    $couponsSupported
     * @param int                     $languageId
     * @param mixed                   $context
     * @param array                   $package
     *
     * @return MockObject
     */
    private function buildCartRuleMock(
        $cartRuleId,
        $carrierId,
        \ShopgateExternalCoupon $expectedExternalCoupon,
        $couponsSupported,
        $languageId,
        $context,
        array $package
    ) {
        $cartRuleMock = $this
            ->getMockBuilder('CartRuleCore')
            ->disableOriginalConstructor()
            ->setMethods(array('getFieldByLang', 'getByCouponCode', 'getContextualValue', 'checkValidity'))
            ->getMock();

        $cartRuleMock->id = $cartRuleId;

        $cartRuleMock
            ->expects(
                $couponsSupported
                    ? $this->exactly(2)
                    : $this->never()
            )
            ->method('getFieldByLang')
            ->withConsecutive(array('description', $languageId), array('name', $languageId))
            ->willReturnOnConsecutiveCalls(
                $expectedExternalCoupon->getDescription(),
                $expectedExternalCoupon->getName()
            );

        $cartRuleMock
            ->expects(
                $couponsSupported
                    ? $this->once()
                    : $this->never()
            )
            ->method('getContextualValue')
            ->with(true, $context, null,
                null === $carrierId
                    ? null
                    : $package
            )
            ->willReturn($expectedExternalCoupon->getAmountGross());

        $cartRuleMock
            ->expects(
                $couponsSupported
                    ? $this->once()
                    : $this->never()
            )
            ->method('checkValidity')
            ->with($context, false, true)
            ->willReturn(
                $expectedExternalCoupon->getIsValid()
                    ? false
                    : $expectedExternalCoupon->getNotValidMessage()
            );

        return $cartRuleMock;
    }
}
