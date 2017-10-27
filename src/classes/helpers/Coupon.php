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
class ShopgateCouponHelper
{
    /**
     * @param ShopgateCartBase $cartBase
     * @param int              $customerId
     * @param int              $langId
     * @param int              $currencyId
     *
     * @return CartRule[]
     */
    public function createVirtualCartRules(ShopgateCartBase $cartBase, $customerId, $langId, $currencyId)
    {
        $cartRules = array();

        if (_PS_VERSION_ < '1.5') {
            return $cartRules;
        }

        foreach ($cartBase->getItems() as $cartItem) {
            /** @var $item ShopgateOrderItem */
            if ($cartItem->getType() !== ShopgateOrderItem::TYPE_SHOPGATE_COUPON) {
                continue;
            }

            $cartRule = $this->createCartRule(
                $customerId,
                $langId,
                $cartItem->getName(),
                $cartItem->getUnitAmount(),
                $currencyId
            );

            if (!Validate::isLoadedObject($cartRule)) {
                continue;
            }

            $cartRules[] = $cartRule;
        }

        return $cartRules;
    }

    /**
     * @param Cart       $prestashopCart
     * @param CartRule[] $cartRules
     */
    public function applyCartRules($prestashopCart, array $cartRules)
    {
        if (_PS_VERSION_ < '1.5') {
            return;
        }

        foreach ($cartRules as $cartRule) {
            $prestashopCart->addCartRule($cartRule->id);
        }
    }

    /**
     * @param int    $customerId
     * @param int    $langId
     * @param string $cartRuleName
     * @param float  $cartRuleAmount
     * @param int    $currencyId
     *
     * @return CartRule
     */
    protected function createCartRule($customerId, $langId, $cartRuleName, $cartRuleAmount, $currencyId)
    {
        $cartRule                     = new CartRule();
        $cartRule->name               = array($langId => $cartRuleName);
        $cartRule->reduction_amount   = abs($cartRuleAmount);
        $cartRule->id_customer        = $customerId;
        $cartRule->date_from          = date('Y-m-d');
        $cartRule->date_to            = date('Y-m-d 23:59:59');
        $cartRule->reduction_currency = $currencyId;
        $cartRule->add();

        return $cartRule;
    }
}
