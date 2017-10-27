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
class ShopgateContextHelper
{
    /** @var Context */
    private $prestashopContext;

    /**
     * @param Context $prestashopContext
     */
    public function __construct(Context $prestashopContext)
    {
        $this->prestashopContext = $prestashopContext;
    }

    /**
     * @param int $prestashopDeliveryAddressId
     */
    public function saveCartDeliveryAddressId($prestashopDeliveryAddressId)
    {
        $this->prestashopContext->cart->id_address_delivery = $prestashopDeliveryAddressId;
        $this->prestashopContext->cart->save();
    }

    /**
     * @param int $prestashopInvoiceAddressId
     */
    public function saveCartInvoiceAddressId($prestashopInvoiceAddressId)
    {
        $this->prestashopContext->cart->id_address_invoice = $prestashopInvoiceAddressId;
        $this->prestashopContext->cart->save();
    }

    /**
     * @param int $carrierId
     */
    public function saveCartCarrierId($carrierId)
    {
        $this->prestashopContext->cart->id_carrier = $carrierId;
        $this->prestashopContext->cart->save();
    }

    /**
     * @param int $cartRuleId
     */
    public function addCartRule($cartRuleId)
    {
        $this->prestashopContext->cart->addCartRule($cartRuleId);
        $this->prestashopContext->cart->save();
    }

    /**
     * @param int $quantity
     * @param int $productId
     * @param int $attributeId
     * @param int $prestashopDeliveryAddressId
     *
     * @return bool|int
     */
    public function updateCartQuantity($quantity, $productId, $attributeId, $prestashopDeliveryAddressId)
    {
        $addItemResult = $this->prestashopContext->cart->updateQty(
            $quantity,
            $productId,
            $attributeId,
            false,
            'up',
            $prestashopDeliveryAddressId
        );
        $this->prestashopContext->cart->save();

        return $addItemResult;
    }

    /**
     * @param Currency
     *
     * @return string
     */
    public function getCurrencyIsoCode()
    {
        return $this->getCurrency()->iso_code;
    }

    /**
     * @return Currency
     */
    public function getCurrency()
    {
        return $this->prestashopContext->currency;
    }

    /**
     * @return int
     */
    public function getCartCurrencyId()
    {
        return $this->prestashopContext->cart->id_currency;
    }

    /**
     * @param Customer $customer
     */
    public function setCustomer(Customer $customer)
    {
        $this->prestashopContext->customer = $customer;
    }

    /**
     * @param int $customerId
     */
    public function setCartCustomerId($customerId)
    {
        $this->prestashopContext->cart->id_customer = $customerId;
    }

    /**
     * @return int
     */
    public function getCartDeliveryAddressId()
    {
        return $this->prestashopContext->cart->id_address_delivery;
    }

    /**
     * @return int
     */
    public function getCartInvoiceAddressId()
    {
        return $this->prestashopContext->cart->id_address_invoice;
    }

    /**
     * @return int
     */
    public function getCartCustomerId()
    {
        return $this->prestashopContext->cart->id_customer;
    }

    /**
     * @return array An associative array defined by Prestashop containing information about products in the cart.
     */
    public function getCartProducts()
    {
        return $this->prestashopContext->cart->getProducts();
    }

    /**
     * @return int
     */
    public function getCustomerId()
    {
        return $this->prestashopContext->customer->id;
    }

    /**
     * @return Customer
     */
    public function getCustomer()
    {
        return $this->prestashopContext->customer;
    }

    /**
     * @return Cart
     */
    public function getCart()
    {
        return $this->prestashopContext->cart;
    }

    /**
     * @return Cookie
     */
    public function getCookie()
    {
        return $this->prestashopContext->cookie;
    }

    /**
     * @return int
     */
    public function getLanguageId()
    {
        return $this->prestashopContext->language->id;
    }

    /**
     * @return Context
     */
    public function getContext()
    {
        return $this->prestashopContext;
    }
}
