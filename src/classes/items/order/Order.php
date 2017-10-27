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
class ShopgateItemsOrder extends ShopgateItemsAbstract
{
    /**
     * @inheritdoc
     * @throws ShopgateLibraryException
     */
    public function __construct($plugin, ShopgateDb $db)
    {
        parent::__construct($plugin, $db);

        /** @var CartCore $cart */
        $cart              = new Cart();
        $cart->id_lang     = $this->getPlugin()->getLanguageId();
        $cart->id_currency = $this->getPlugin()->getContext()->currency->id;
        $cart->recyclable  = 0;
        $cart->gift        = 0;

        $this->getPlugin()->getContext()->cart = $cart;

        /**
         * check / create shopgate carrier
         */
        /** @var CarrierCore $sgCarrier */
        $sgCarrier = new Carrier(Configuration::get('SG_CARRIER_ID'));

        if (!$sgCarrier->id) {
            $shopgateShippingModel = new ShopgateShipping(new ShopGate());
            $shopgateShippingModel->createShopgateCarrier();
        }

        /**
         * check all needed table columns
         */
        $shopGate = new ShopGate();

        if (!$shopGate->updateTables()) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::PLUGIN_DATABASE_ERROR,
                sprintf('Cannot update shopgate_order_table')
            );
        }
    }

    /**
     * @return Cart|CartCore
     */
    public function getCart()
    {
        return $this->getPlugin()->getContext()->cart;
    }

    /**
     * @param int    $customerId
     * @param int    $limit
     * @param int    $offset
     * @param string $orderDateFrom
     * @param string $sortOrder
     *
     * @return mixed
     */
    public function getCustomerOrders(
        $customerId,
        $limit = 10,
        $offset = 0,
        $orderDateFrom = '',
        $sortOrder = 'created_desc'
    ) {
        $orders = Order::getCustomerOrders($customerId);
        $orders = $this->sortCoreOrders($orders, $sortOrder);

        $orderCount = 0;
        $result     = array();

        if ($orderDateFrom != '') {
            $dateTime      = new DateTime($orderDateFrom);
            $orderDateFrom = $dateTime->getTimestamp();
        } else {
            $orderDateFrom = false;
        }

        foreach ($orders as $order) {
            /**
             * handle offset
             */
            if ($orderCount < $offset) {
                $orderCount++;
                continue;
            }

            /**
             * handle date from
             */
            if ($orderDateFrom) {
                $dateTime             = new DateTime($order['date_add']);
                $orderDateFromCompare = $dateTime->getTimestamp();

                if ($orderDateFromCompare < $orderDateFrom) {
                    $orderCount++;
                    continue;
                }
            }

            /**
             * handle limit
             */
            if ($orderCount == $limit) {
                break;
            }

            $result[] = $order;
            $orderCount++;
        }

        return $result;
    }

    /**
     * @param array  $orders
     * @param string $sort
     *
     * @return mixed
     */
    protected function sortCoreOrders($orders, $sort)
    {
        switch ($sort) {
            case 'created_asc':
                $this->arraySortByColumn($orders, 'date_add', SORT_ASC);
                break;
            case 'created_desc':
                $this->arraySortByColumn($orders, 'date_add', SORT_DESC);
                break;
        }

        return $orders;
    }

    /**
     * @param     $arr
     * @param     $col
     * @param int $dir
     */
    protected function arraySortByColumn(&$arr, $col, $dir = SORT_ASC)
    {
        $sortCol = array();
        foreach ($arr as $key => $row) {
            $sortCol[$key] = $row[$col];
        }
        array_multisort($sortCol, $dir, $arr);
    }
}
