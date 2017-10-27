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
class ShopgateHelperCouponValidation
{
    /** @var ShopgatePrestashopVersion */
    private $prestashopVersion;

    /** @var ShopgateContextHelper */
    private $contextHelper;

    /** @var ShopgateHelpersCouponCartRuleFactory */
    private $cartRuleFactory;

    /**
     * @param ShopgatePrestashopVersion            $prestashopVersion
     * @param ShopgateContextHelper                $contextHelper
     * @param ShopgateHelpersCouponCartRuleFactory $cartRuleFactory
     */
    public function __construct(
        ShopgatePrestashopVersion $prestashopVersion,
        ShopgateContextHelper $contextHelper,
        ShopgateHelpersCouponCartRuleFactory $cartRuleFactory
    ) {
        $this->prestashopVersion = $prestashopVersion;
        $this->contextHelper     = $contextHelper;
        $this->cartRuleFactory   = $cartRuleFactory;
    }

    /**
     * @param ShopgateExternalCoupon[] $externalCoupons
     * @param int|null                 $carrierId
     *
     * @return ShopgateExternalCoupon[] A list of validated coupons with the proper fields set.
     */
    public function getValidatedCoupons(array $externalCoupons, $carrierId = null)
    {
        foreach ($externalCoupons as $externalCoupon) {
            $this->validateCoupon($externalCoupon, $carrierId);
        }

        return $externalCoupons;
    }

    /**
     * @param ShopgateExternalCoupon $externalCoupon
     * @param int|null               $carrierId
     */
    public function validateCoupon(ShopgateExternalCoupon $externalCoupon, $carrierId = null)
    {
        // assume every coupon invalid until proven otherwise
        $this->markInvalid($externalCoupon);

        // coupons not supported for versions below 1.5.0.0; just leave it marked invalid
        if ($this->prestashopVersion->isBelow('1.5.0.0')) {
            return;
        }

        $cartRule = $this->cartRuleFactory->getByCouponCode($externalCoupon->getCode());

        // no cart rule found means the coupon is invalid
        if (null === $cartRule) {
            return;
        }

        $this->initializeFromCartRule($cartRule, $externalCoupon, $carrierId);
        $this->validateFromCartRule($cartRule, $externalCoupon);
    }

    /**
     * @param ShopgateExternalCoupon $externalCoupon
     *
     * @post $externalCoupon will have the current context's currency set, be invalid and have an error message
     */
    private function markInvalid(ShopgateExternalCoupon $externalCoupon)
    {
        $externalCoupon->setCurrency($this->contextHelper->getCurrencyIsoCode());
        $externalCoupon->setIsValid(false);
        $externalCoupon->setNotValidMessage('This voucher does not exists.');
        $externalCoupon->setTaxType('not_taxable');
    }

    /**
     * @param CartRuleCore           $cartRule
     * @param ShopgateExternalCoupon $externalCoupon
     * @param int|null               $carrierId
     *
     * @post $externalCoupon will have its name, description, tax type and gross amount set according to the cart
     *       rule's configuration.
     */
    private function initializeFromCartRule(
        CartRuleCore $cartRule,
        ShopgateExternalCoupon $externalCoupon,
        $carrierId = null
    ) {
        $package = !empty($carrierId)
            ? array('products' => $this->contextHelper->getCartProducts(), 'id_carrier' => $carrierId)
            : null;

        $description = $cartRule->getFieldByLang(
            'description',
            $this->contextHelper->getLanguageId()
        );

        $amountGross = $cartRule->getContextualValue(
            true,
            $this->contextHelper->getContext(),
            null,
            $package
        );

        $externalCoupon->setName($cartRule->getFieldByLang('name', $this->contextHelper->getLanguageId()));
        $externalCoupon->setDescription($description);
        $externalCoupon->setAmountGross($amountGross);
    }

    /**
     * @param CartRuleCore           $cartRule
     * @param ShopgateExternalCoupon $externalCoupon
     *
     * @post $externalCoupon will have its validity and a "not valid" message set if applicable.
     * @post The cart rule will be added to the Prestashop context object.
     */
    private function validateFromCartRule(CartRuleCore $cartRule, ShopgateExternalCoupon $externalCoupon)
    {
        $validateException = $cartRule->checkValidity(
            $this->contextHelper->getContext(),
            false,
            true
        );

        if ($validateException) {
            $externalCoupon->setIsValid(false);
            $externalCoupon->setNotValidMessage($validateException);
        } else {
            $externalCoupon->setIsValid(true);
            $externalCoupon->setNotValidMessage(null);
            $this->contextHelper->addCartRule($cartRule->id);
        }
    }
}
