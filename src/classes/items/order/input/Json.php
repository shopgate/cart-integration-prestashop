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
class ShopgateItemsInputOrderJson extends ShopgateItemsOrder
{
    /**
     * @param ShopgateOrder $order
     *
     * @return array
     * @throws PrestaShopException
     * @throws ShopgateLibraryException
     */
    public function addOrder(ShopgateOrder $order)
    {
        /**
         * check exits shopgate order
         */
        if (ShopgateOrderPrestashop::loadByOrderNumber($order->getOrderNumber())->status == 1) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::PLUGIN_DUPLICATE_ORDER,
                sprintf('external_order_id: %s', $order->getOrderNumber()),
                true
            );
        }

        try {
            $this->getDb()->beginTransaction();

            $shopgateOrderItem = new ShopgateOrderPrestashop();

            $context = Context::getContext();
            $context->cookie->__set('shopgateOrderNumber', $order->getOrderNumber());

            $customerModel = new ShopgateItemsCustomerImportJson($this->getPlugin(), $this->getDb());
            $paymentModel  = new ShopgatePayment($this->getModule());
            $shippingModel = new ShopgateShipping($this->getModule());

            /**
             * read / check customer
             */
            if (!$customerId = Customer::customerExists($order->getMail(), true, false)) {
                /**
                 * prepare customer
                 */
                $shopgateCustomerItem = new ShopgateCustomer();

                $shopgateCustomerItem->setLastName($order->getInvoiceAddress()->getLastName());
                $shopgateCustomerItem->setFirstName($order->getInvoiceAddress()->getFirstName());
                $shopgateCustomerItem->setGender($order->getInvoiceAddress()->getGender());
                $shopgateCustomerItem->setBirthday($order->getInvoiceAddress()->getBirthday());
                $shopgateCustomerItem->setNewsletterSubscription(
                    Configuration::get('SG_SUBSCRIBE_NEWSLETTER')
                        ? true
                        : false
                );

                $customerId = $customerModel->registerCustomer(
                    $order->getMail(),
                    md5(_COOKIE_KEY_ . time()),
                    $shopgateCustomerItem
                );
            }

            /** @var CustomerCore $customer */
            $customer = new Customer($customerId);

            /**
             * prepare cart
             */
            if (!$order->getDeliveryAddress()->getPhone()) {
                $order->getDeliveryAddress()->setPhone($order->getPhone());
            }
            if (!$order->getInvoiceAddress()->getPhone()) {
                $order->getInvoiceAddress()->setPhone($order->getPhone());
            }

            $customerAddresses                    = $customer->getAddresses($context->language->id);
            $this->getCart()->id_address_delivery = $this->getAddressId(
                $order->getDeliveryAddress(),
                $customerModel,
                $customer,
                $customerAddresses
            );
            $this->getCart()->id_address_invoice  = $this->getAddressId(
                $order->getInvoiceAddress(),
                $customerModel,
                $customer,
                $customerAddresses
            );

            $this->getCart()->id_customer = $customerId;
            $this->getCart()->secure_key  = $customer->secure_key;
            $this->getCart()->id_carrier  = $shippingModel->getCarrierId($order);

            $shopgateCustomFieldsHelper = new ShopgateCustomFieldsHelper();
            $shopgateCustomFieldsHelper->saveCustomFields($this->getCart(), $order->getCustomFields());

            $this->getCart()->add();

            /**
             * add cart items
             */
            foreach ($order->getItems() as $item) {
                list($productId, $attributeId) = ShopgateHelper::getProductIdentifiers($item);

                if ($productId == 0) {
                    continue;
                }

                $customizationId = $this->createCustomizationEntry($item);
                $updateCart      = $this->getCart()->updateQty(
                    $item->getQuantity(),
                    $productId,
                    $attributeId,
                    $customizationId,
                    'up',
                    $this->getCart()->id_address_delivery
                );

                if ($updateCart !== true) {
                    throw new Exception(
                        sprintf(
                            'product_id: %s, attribute_id: %s, quantity: %s, result: %s, reason: %s',
                            $productId,
                            $attributeId,
                            $item->getQuantity(),
                            $updateCart,
                            ($updateCart == -1
                                ? 'minimum quantity not reached'
                                : '')
                        )
                    );
                }
            }

            /**
             * coupons
             */
            foreach ($order->getExternalCoupons() as $coupon) {
                /** @var CartRuleCore $cartRule */
                $cartRule = new CartRule(CartRule::getIdByCode($coupon->getCode()));
                if (Validate::isLoadedObject($cartRule)) {
                    $this->getCart()->addCartRule($cartRule->id);
                    $this->getCart()->save();
                }
            }

            if (version_compare(_PS_VERSION_, '1.5.0.0', '>=')) {
                /**
                 * this field is not available in version 1.4.x.x
                 * set delivery option
                 */
                $delivery_option
                    = array(
                    $this->getCart()->id_address_delivery => $shippingModel->getCarrierId($order) . ',',
                );
                $this->getCart()->setDeliveryOption($delivery_option);
                $this->getCart()->save();
            }

            /**
             * store shopgate order
             */
            $shopgateOrderItem->fillFromOrder(
                $this->getCart(),
                $order,
                $this->getPlugin()->getShopgateConfig()->getShopNumber()
            );

            $shopgateOrderItem->save();

            /**
             * apply shopgate coupons
             */
            $virtualCartRules = $this->getCouponHelper()->createVirtualCartRules(
                $order,
                $this->getCart()->id_customer,
                $context->language->id,
                $context->currency->id
            );
            $this->getCouponHelper()->applyCartRules($this->getCart(), $virtualCartRules);

            /**
             * create order
             * get first item from order stats
             */
            $this->getCart()->save();

            $mailVars     = ($order->getPaymentMethod() == ShopgateOrder::PREPAY)
                ? array(
                    '{bankwire_owner}'   => Configuration::get('BANK_WIRE_OWNER'),
                    '{bankwire_details}' => nl2br(Configuration::get('BANK_WIRE_DETAILS')),
                    '{bankwire_address}' => nl2br(Configuration::get('BANK_WIRE_ADDRESS')),
                )
                : array();
            $idOrderState = $paymentModel->getOrderStateId($order);
            $idOrderState = reset($idOrderState);
            $validateOder = $this->getModule()->validateOrder(
                $this->getCart()->id,
                $idOrderState,
                $this->getCart()->getOrderTotal(true,
                    defined('Cart::BOTH')
                        ? Cart::BOTH
                        : 3
                ),
                $paymentModel->getPaymentTitleByKey($order->getPaymentMethod()),
                null,
                $mailVars,
                null,
                false,
                $this->getCart()->secure_key
            );

            $this->getDb()->commit();

            if ($validateOder) {
                $shopgateOrderItem->id_order = $this->getModule()->currentOrder;
                $shopgateOrderItem->status   = 1;
                $shopgateOrderItem->save();

                return array(
                    'external_order_id'     => $shopgateOrderItem->id_order,
                    'external_order_number' => $shopgateOrderItem->id_order,
                );
            }
        } catch (Exception $exception) {
            if (isset($shopgateOrderItem) && !$this->getDb()->engineSupportsTransactions()) {
                $shopgateOrderItem->delete();
            }

            try {
                if (!($exception instanceof ShopgateTransactionFailedException)) {
                    $this->getDb()->rollback();
                }
            } catch (ShopgateTransactionFailedException $rollbacktransactionFailedException) {
            }

            throw new ShopgateLibraryException(
                ShopgateLibraryException::UNKNOWN_ERROR_CODE,
                'Unable to create order:' . print_r(version_compare(_PS_VERSION_, '8', '>') ? $exception : $exception->getMessage(), true),
                true,
                true,
                $exception
            );
        }
    }

    /**
     * @param ShopgateOrder $order
     *
     * @return array
     * @throws ShopgateLibraryException
     */
    public function updateOrder(ShopgateOrder $order)
    {
        $paymentModel      = new ShopgatePayment($this->getModule());
        $shopgateOrderItem = ShopgateOrderPrestashop::loadByOrderNumber($order->getOrderNumber());

        /** @noinspection PhpParamsInspection */
        if (!Validate::isLoadedObject($shopgateOrderItem)) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::PLUGIN_ORDER_NOT_FOUND,
                'Order not found #' . $order->getOrderNumber(),
                true
            );
        }

        /** @var OrderCore $coreOrder */
        $coreOrder    = new Order($shopgateOrderItem->id_order);
        $returnValues = array(
            'external_order_id'     => $shopgateOrderItem->id_order,
            'external_order_number' => $shopgateOrderItem->id_order,
        );

        // check if the order is already shipped and stop processing if the order is shipped already
        $stopProcessing      = false;
        $currentOrderStateId = $coreOrder->getCurrentState();
        if ($currentOrderStateId) {
            $currentOrderState = new OrderState($currentOrderStateId);

            if (version_compare(_PS_VERSION_, '1.5.0.0', '>=')
                && is_object($currentOrderState)
                && property_exists($currentOrderState, 'shipped')
                && $currentOrderState->shipped
            ) {
                $stopProcessing = true;
            } elseif (version_compare(_PS_VERSION_, '1.5.0.0', '<')
                && in_array($currentOrderState->id, array(_PS_OS_DELIVERED_, _PS_OS_SHIPPING_))
            ) {
                $stopProcessing = true;
            }

            if ($stopProcessing) {
                return $returnValues;
            }
        }

        /**
         * get order states
         */
        $changedStates = $paymentModel->getOrderStateId($order, false);

        /**
         * apply changed states
         */
        foreach ($changedStates as $changedState) {
            $coreOrder->setCurrentState($changedState);
        }

        $shopgateOrderItem->updateFromOrder($order);
        $shopgateOrderItem->save();

        return $returnValues;
    }

    /**
     * @param ShopgateAddress                 $shopgateAddress
     * @param ShopgateItemsCustomerImportJson $customerModel
     * @param                                 $customer
     * @param array                           $customerAddresses
     *
     * @return int
     */
    protected function getAddressId(
        ShopgateAddress $shopgateAddress,
        ShopgateItemsCustomerImportJson $customerModel,
        $customer,
        array $customerAddresses
    ) {
        if ($prestashopAddress = $customerModel->findAddress(
            $customerModel->createPrestashopAddressObject($shopgateAddress, $customer),
            $customerAddresses
        )
        ) {
            return $prestashopAddress['id_address'];
        } else {
            return $customerModel->createAddress($shopgateAddress, $customer);
        }
    }

    /**
     * Helps create the customization input.
     *
     * @param ShopgateOrderItem $item
     *
     * @return bool
     */
    private function createCustomizationEntry(ShopgateOrderItem $item)
    {
        $inputHelper     = new ShopgateItemsInput($this->getDb());
        $customizationId = $inputHelper->createCustomization(
            $item,
            $this->getCart()->id,
            $this->getCart()->id_address_delivery
        );
        $inputHelper->createCustomizedData($customizationId, $item->getInputs());

        return $customizationId;
    }
}
