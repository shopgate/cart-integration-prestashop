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
class ShopgateItemsOrderExportJson extends ShopgateItemsOrder
{
    /** @var  CustomerCore */
    protected $_currentCustomer;

    /** @var array */
    protected $_result = array();

    /** @var array */
    protected $_orderTaxes = array();

    public function getOrders($customerToken, $customerLanguage, $limit, $offset, $orderDateFrom, $sortOrder)
    {
        $shopgateCustomerModel  = new ShopgateCustomerPrestashop();
        $this->_currentCustomer = $shopgateCustomerModel->getCustomerByToken($customerToken);

        if (!$this->_currentCustomer->validateFields(false)) {
            throw new ShopgateLibraryException(ShopgateLibraryException::PLUGIN_CUSTOMER_TOKEN_INVALID);
        }

        $orders = $this->getCustomerOrders($this->_currentCustomer->id, $limit, $offset, $orderDateFrom, $sortOrder);

        foreach ($orders as $orderItem) {
            /** @var OrderCore $orderCore */
            $orderCore = new Order($orderItem['id_order']);
            $order     = new ShopgateExternalOrder();

            $order->setOrderNumber($orderCore->id);
            $order->setExternalOrderNumber($orderCore->reference);
            $order->setExternalOrderId($orderCore->id);

            /** @var OrderStateCore $orderStatus */
            $orderStatus = new OrderState($orderCore->getCurrentState());

            $order->setStatusName($orderStatus->name[$this->getPlugin()->getLanguageId()]);
            $order->setStatusColor($orderStatus->color);

            $order->setCreatedTime($orderCore->date_add);
            $order->setMail($this->_currentCustomer->email);

            $order->setDeliveryAddress(
                $this->_getAddress(
                    $orderCore->id_address_delivery,
                    ShopgateCustomerPrestashop::DEFAULT_CUSTOMER_ADDRESS_IDENTIFIER_DELIVERY
                )
            );

            $order->setInvoiceAddress(
                $this->_getAddress(
                    $orderCore->id_address_invoice,
                    ShopgateCustomerPrestashop::DEFAULT_CUSTOMER_ADDRESS_IDENTIFIER_DELIVERY
                )
            );

            $order->setItems($this->_getOrderItems($orderCore));
            $order->setExternalCoupons($this->_getCartRules($orderCore));

            /** @var CurrencyCore $currency */
            $currency = Currency::getCurrency($orderCore->id_currency);
            $order->setCurrency($currency['iso_code']);

            $order->setAmountComplete(
                isset($orderCore->total_paid_tax_incl)
                    ? $orderCore->total_paid_tax_incl
                    : $orderCore->total_paid
            );

            $order->setIsPaid($orderCore->hasBeenPaid());
            $order->setPaymentMethod($orderCore->payment);

            $order->setIsShippingCompleted($orderCore->hasBeenShipped());
            $order->setShippingCompletedTime(
                $orderCore->hasBeenShipped()
                    ? $orderCore->delivery_date
                    : null
            );

            $order->setDeliveryNotes($this->_getDeliveryNotes($orderCore));
            $order->setExtraCosts($this->_getExtraCost($orderCore));
            $order->setOrderTaxes($this->_getOrderTaxes());

            $this->_result[] = $order;
        }

        return $this->_result;
    }

    /**
     * @param int    $addressId
     * @param string $addressType
     *
     * @return ShopgateAddress
     */
    protected function _getAddress($addressId, $addressType)
    {
        $addressItem = new ShopgateAddress();
        /** @var AddressCore $addressCore */
        $addressCore = new Address($addressId);
        $addressItem->setId($addressCore->id);

        switch ($addressType) {
            case ShopgateCustomerPrestashop::DEFAULT_CUSTOMER_ADDRESS_IDENTIFIER_DELIVERY:
                $addressItem->setIsDeliveryAddress(true);
                break;
            case ShopgateCustomerPrestashop::DEFAULT_CUSTOMER_ADDRESS_IDENTIFIER_INVOICE:
                $addressItem->setIsInvoiceAddress(true);
                break;
        }

        $addressItem->setFirstName($addressCore->firstname);
        $addressItem->setLastName($addressCore->lastname);
        $addressItem->setCompany($addressCore->company);
        $addressItem->setStreet1($addressCore->address1);
        $addressItem->setStreet2($addressCore->address2);
        $addressItem->setZipcode($addressCore->postcode);
        $addressItem->setCity($addressCore->city);
        $addressItem->setCountry(Country::getIsoById($addressCore->id_country));

        $states = State::getStates($this->getPlugin()->getLanguageId());
        foreach ($states as $state) {
            /** @var StateCore $state */
            if ($state['id_country'] == ($addressCore->id_state)) {
                $addressItem->setState($state->iso_code);
            }
        }

        return $addressItem;
    }

    /**
     * @param OrderCore $orderCore
     *
     * @return array
     */
    protected function _getExtraCost($orderCore)
    {
        $result = array();
        if ($orderCore->total_shipping_tax_excl > 0) {
            $extraCost = new ShopgateExternalOrderExtraCost();
            $extraCost->setType('shipping');
            $extraCost->setAmount($orderCore->total_shipping_tax_excl);
            $extraCost->setTaxPercent($orderCore->carrier_tax_rate);
            $result[] = $extraCost;
        }

        return $result;
    }

    /**
     * @param OrderCore $order
     *
     * @return array
     */
    protected function _getOrderItems($order)
    {
        $result = array();

        if (method_exists($order, 'getOrderDetailList')) {
            $orderItems = $order->getOrderDetailList();
        } else {
            $orderItems = $order->getProducts();
        }

        foreach ($orderItems as $item) {
            /** @var OrderDetailCore $orderItemCore */
            $orderItemCore = new OrderDetail($item['id_order_detail']);
            $orderItem     = new ShopgateExternalOrderItem();

            $orderItem->setItemNumber($orderItemCore->product_id);
            //$orderItem->setItemNumberPublic()
            $orderItem->setQuantity($orderItemCore->product_quantity);
            $orderItem->setName($orderItemCore->product_name);
            $orderItem->setUnitAmount($orderItemCore->unit_price_tax_excl);
            $orderItem->setUnitAmountWithTax($orderItemCore->unit_price_tax_incl);
            $orderItem->setTaxPercent($orderItemCore->tax_rate);

            /** @var CurrencyCore $currency */
            $currency = Currency::getCurrency($order->id_currency);
            $orderItem->setCurrency($currency['iso_code']);

            if (!array_key_exists($orderItemCore->tax_rate, $this->_orderTaxes)) {
                $this->_orderTaxes[$orderItemCore->tax_rate] = array(
                    'tax_name'       => $orderItemCore->tax_name,
                    'price_tax_excl' => $orderItemCore->unit_price_tax_excl * $orderItemCore->product_quantity,
                );
            } else {
                $taxInfoItem                                 = $this->_orderTaxes[$orderItemCore->tax_rate];
                $taxInfoItem['price_tax_excl']               = $taxInfoItem['price_tax_excl'] + ($orderItemCore->unit_price_tax_excl * $orderItemCore->product_quantity);
                $this->_orderTaxes[$orderItemCore->tax_rate] = $taxInfoItem;
            }

            $result[] = $orderItem;
        }

        return $result;
    }

    /**
     * @param OrderCore $order
     *
     * @return array
     * @throws PrestaShopDatabaseException
     */
    protected function _getCartRules($order)
    {
        $result = array();

        foreach ($order->getDiscounts() as $item) {
            if (array_key_exists('id_order_cart_rule', $item)) {
                /** @var OrderCartRuleCore $cartRuleItem */
                $cartRuleItem = new OrderCartRule($item['id_order_cart_rule']);
            } else {
                /** @var OrderDiscountCore $cartRuleItem */
                $cartRuleItem = new OrderDiscount($item['id_order_discount']);
            }

            $resultItem = new ShopgateExternalCoupon();

            $resultItem->setCode($cartRuleItem->name);
            $resultItem->setAmountNet($cartRuleItem->value_tax_excl);
            $resultItem->setAmountGross($cartRuleItem->value);
            $resultItem->setIsFreeShipping($cartRuleItem->free_shipping);

            $result[] = $resultItem;
        }

        return $result;
    }

    /**
     * @return array
     */
    protected function _getOrderTaxes()
    {
        $result = array();
        foreach ($this->_orderTaxes as $taxPercent => $orderTax) {
            $orderTaxItem = new ShopgateExternalOrderTax();
            $orderTaxItem->setLabel($orderTax['tax_name']);
            $orderTaxItem->setTaxPercent($taxPercent);
            $orderTaxItem->setAmount($orderTax['price_tax_excl']);
            $result[] = $orderTaxItem;
        }

        return $result;
    }

    /**
     * @param OrderCore $order
     *
     * @return array
     */
    protected function _getDeliveryNotes($order)
    {
        $result = array();

        if (method_exists($order, 'getShipping')) {
            foreach ($order->getShipping() as $item) {
                /** @var OrderCarrierCore $orderCarrier */
                $orderCarrier = new OrderCarrier($item['id_order_carrier']);

                /** @var CarrierCore $carrier */
                $carrier = new Carrier($orderCarrier->id_carrier);

                $deliveryNote = new ShopgateDeliveryNote();
                $deliveryNote->setShippingServiceId($carrier->name);
                $deliveryNote->setTrackingNumber(
                    $orderCarrier->tracking_number
                        ? $orderCarrier->tracking_number
                        : null
                );
                $deliveryNote->setShippingTime($orderCarrier->date_add);

                $result[] = $deliveryNote;
            }
        }

        return $result;
    }
}
