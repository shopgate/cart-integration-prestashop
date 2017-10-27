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
class ShopgatePluginPrestashop extends ShopgatePlugin
{
    const PREFIX = 'BD';

    /** @var ShopgateConfigPrestashop; */
    protected $config;

    /** @var Context this variable is initialized by file /../vendors/shopgate/prestashop-backward-compatibility/backward.php */
    public $context;

    /** @var Smarty this variable is initialized by file /../vendors/shopgate/prestashop-backward-compatibility/backward.php */
    public $smarty;

    /** @var ShopgateDb $db */
    protected $db;

    /** @var Shopgate_Helper_Logging_Strategy_LoggingInterface */
    protected $logger;

    /**
     * @param ShopgateDb $db
     */
    public function setDb($db)
    {
        $this->db = $db;
    }

    /**
     * @param Shopgate_Helper_Logging_Strategy_LoggingInterface $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function startup()
    {
    }

    public function initializeContext()
    {
        include_once SHOPGATE_DIR . 'vendors/shopgate/prestashop-backward-compatibility/backward.php';

        // Without this explicit setting of the currency, the system could choose the wrong one e.g. EUR instead of PLN
        $this->context->currency     = new Currency(Currency::getIdByIsoCode($this->config->getCurrency()));
        $this->context->language->id = Language::getIdByIso($this->config->getLanguage());
    }

    /**
     * @inheritdoc
     */
    public function cron($jobname, $params, &$message, &$errorcount)
    {
        switch ($jobname) {
            case 'set_shipping_completed':
                $this->setOrderShippingCompleted($message, $errorcount);
                break;
            case 'cancel_orders':
                $this->logger->log(
                    "cron executed job '" . $jobname . "'",
                    Shopgate_Helper_Logging_Strategy_LoggingInterface::LOGTYPE_DEBUG
                );
                $cancellationStatus = ConfigurationCore::get('SG_CANCELLATION_STATUS');

                $select = sprintf(
                    'SELECT '
                    . (version_compare(_PS_VERSION_, '1.5.0', '>=')
                        ? ' o.current_state,  '
                        : ' o.id_order, ') .
                    ' so.id_shopgate_order from %sshopgate_order as so
                        JOIN %sorders as o on so.id_order=o.id_order 
                        WHERE so.is_cancellation_sent_to_shopgate = 0',
                    _DB_PREFIX_,
                    _DB_PREFIX_
                );

                $result = $this->db->getInstance()->ExecuteS($select);

                if (empty($result)) {
                    $this->logger->log(
                        'no orders to cancel found for shop:' . $this->config->getShopNumber(),
                        Shopgate_Helper_Logging_Strategy_LoggingInterface::LOGTYPE_DEBUG
                    );

                    return;
                }

                foreach ($result as $order) {
                    $sgOrder = new ShopgateOrderPrestashop($order['id_shopgate_order']);

                    if (is_string($sgOrder->order_number)) {
                        $sgOrder->order_number = (int)$sgOrder->order_number;
                    }

                    if (version_compare(_PS_VERSION_, '1.5.0', '>=')) {
                        $state = $order['current_state'];
                    } else {
                        $stateObject = OrderHistory::getLastOrderState($order['id_order']);
                        $state       = $stateObject->id;
                    }

                    if ($state == $cancellationStatus) {
                        $sgOrder->cancelOrder($message);
                    } else {
                        $sgOrder->cancelOrder($message, true);
                    }
                }
                break;

            default:
                $this->logger->log(
                    "job '" . $jobname . "' not found",
                    Shopgate_Helper_Logging_Strategy_LoggingInterface::LOGTYPE_ERROR
                );
                throw new ShopgateLibraryException(
                    ShopgateLibraryException::PLUGIN_CRON_UNSUPPORTED_JOB,
                    'Job name: "' .
                    $jobname . '"',
                    true
                );
        }
    }

    /**
     * Triggered by cron job set_shipping_completed to sync shipments for orders
     * that were most likely update by ERP systems.
     *
     * @param string $message
     * @param int    $errorcount
     */
    public function setOrderShippingCompleted(&$message, &$errorcount)
    {
        $unsyncedOrders = ShopgateOrderPrestashop::getUnsyncedShopgatOrderIds($this->getLanguageId());
        foreach ($unsyncedOrders as $unsyncedOrder) {
            $this->logger->log(
                "Try to set shipping completed for order with shopgate-order-number #{$unsyncedOrder['order_number']}",
                Shopgate_Helper_Logging_Strategy_LoggingInterface::LOGTYPE_DEBUG
            );
            $sgOrder = ShopgateOrderPrestashop::loadByOrderId($unsyncedOrder['id_order']);
            $sgOrder->setShippingComplete($message, $errorcount);
        }
    }

    /**
     * @inheritdoc
     */
    public function getCustomer($user, $pass)
    {
        $customerModel = new ShopgateItemsCustomerExportJson($this, $this->db);

        return $customerModel->getCustomer($user, $pass);
    }

    /**
     * @inheritdoc
     */
    public function registerCustomer($user, $pass, ShopgateCustomer $customer)
    {
        $customerModel = new ShopgateItemsCustomerImportJson($this, $this->db);
        $customerModel->registerCustomer($user, $pass, $customer);
    }

    /**
     * @inheritdoc
     */
    public function addOrder(ShopgateOrder $order)
    {
        $orderModel = new ShopgateItemsInputOrderJson($this, $this->db);

        return $orderModel->addOrder($order);
    }

    /**
     * @inheritdoc
     */
    public function updateOrder(ShopgateOrder $order)
    {
        $orderModel = new ShopgateItemsInputOrderJson($this, $this->db);
        $orderModel->updateOrder($order);
    }

    /**
     * @inheritdoc
     */
    public function redeemCoupons(ShopgateCart $cart)
    {
        return array();
    }

    /**
     * @inheritdoc
     */
    public function checkCart(ShopgateCart $cart)
    {
        $cartModel     = new ShopgateItemsCartExportJson($this, $this->db);
        $contextHelper = new ShopgateContextHelper($this->getContext());

        return $cartModel->checkCart(
            $cart,
            $contextHelper,
            new ShopgateHelperCouponValidation(
                new ShopgatePrestashopVersion(_PS_VERSION_),
                $contextHelper,
                new ShopgateHelpersCouponCartRuleFactory()
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function checkStock(ShopgateCart $cart)
    {
        $cartModel = new ShopgateItemsCartExportJson($this, $this->db);

        return $cartModel->checkStock($cart);
    }

    /**
     * @inheritdoc
     */
    public function getSettings()
    {
        return ShopgateSettings::getShopgateSettings($this);
    }

    /**
     * @inheritdoc
     */
    protected function createItemsCsv()
    {
        /**
         * Deprecated way of exporting, so it will not be implemented
         */
    }

    /**
     * @inheritdoc
     */
    protected function createMediaCsv()
    {
        /**
         * Deprecated way of exporting, so it will not be implemented
         */
    }

    /**
     * @inheritdoc
     */
    protected function createCategoriesCsv()
    {
        /**
         * Deprecated way of exporting, so it will not be implemented
         */
    }

    /**
     * @inheritdoc
     */
    protected function createReviewsCsv()
    {
        /**
         * Deprecated way of exporting, so it will not be implemented
         */
    }

    /**
     * @inheritdoc
     */
    public function getOrders(
        $customerToken,
        $customerLanguage,
        $limit = 10,
        $offset = 0,
        $orderDateFrom = '',
        $sortOrder = 'created_desc'
    ) {
        $orderModel = new ShopgateItemsOrderExportJson($this, $this->db);

        return $orderModel->getOrders($customerToken, $customerLanguage, $limit, $offset, $orderDateFrom, $sortOrder);
    }

    /**
     * @inheritdoc
     */
    public function syncFavouriteList($customerToken, $items)
    {
        /**
         * Favorite list is not supported for this cart
         */
        return array();
    }

    /**
     * @inheritdoc
     */
    protected function createItems($limit = null, $offset = null, array $uids = array())
    {
        $itemsModel = new ShopgateItemsItem(
            $this,
            $this->db,
            new ShopgateItemsBasePrice(
                new ShopgatePrestashopVersion(_PS_VERSION_),
                $this->context->currency->iso_code
            ),
            new ShopgateItemAttributeHelper($this->getLanguageId())
        );

        foreach ($itemsModel->getItems($limit, $offset, $uids) as $product) {
            $row = new ShopgateItemsItemExportXml();
            $this->addItemModel($row->setItem($product)->generateData());
        }
    }

    /**
     * @inheritdoc
     */
    protected function createCategories($limit = null, $offset = null, array $uids = array())
    {
        $categoryModel = new ShopgateItemsCategory($this, $this->db);

        /**
         * Note that limit/offset are not used to retrieve categories
         */
        foreach ($categoryModel->getItems() as $category) {
            if (count($uids) > 0 && !in_array($category['id_category'], $uids)) {
                continue;
            }

            $row = new ShopgateItemsCategoryExportXml();
            $this->addCategoryModel($row->setItem($category)->generateData());
        }
    }

    /**
     * @inheritdoc
     */
    protected function createReviews($limit = null, $offset = null, array $uids = array())
    {
        /** @var ShopgateItemsReview $reviewModel */
        $reviewModel = new ShopgateItemsReview($this, $this->db);

        foreach ($reviewModel->getItems($limit, $offset) as $review) {
            if (count($uids) > 0 && !in_array($review['id_review'], $uids)) {
                continue;
            }

            $row = new ShopgateItemsReviewExportXml();
            $this->addReviewModel($row->setItem($review)->generateData());
        }
    }

    /**
     * @return Context
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return ShopgateConfigPrestashop
     */
    public function getShopgateConfig()
    {
        return $this->config;
    }

    /**
     * @return int
     */
    public function getLanguageId()
    {
        return $this->getContext()->language->id;
    }

    /**
     * @inheritdoc
     */
    public function createShopInfo()
    {
        $shopInfo = array(
            'category_count' => count(Category::getSimpleCategories($this->getLanguageId())),
            'item_count'     => count(Product::getSimpleProducts($this->getLanguageId())),
        );

        if ($this->config->getEnableGetReviewsCsv()) {
            /**
             * set review_count
             */
            $shopInfo['review_count'] = 0;
        }

        if ($this->config->getEnableGetMediaCsv()) {
            /**
             * media_count
             */
            $shopInfo['media_count'] = array();
        }

        $shopInfo['plugins_installed'] = array();

        foreach (Module::getModulesInstalled() as $module) {
            $shopInfo['plugins_installed'][] = array(
                'id'      => isset($module['id_module'])
                    ? $module['id_module']
                    : $module['name'],
                'name'    => $module['name'],
                'version' => $module['version'],
                'active'  => $module['active']
                    ? 1
                    : 0,
            );
        }

        return $shopInfo;
    }

    /**
     * @return array|mixed[]
     */
    public function createPluginInfo()
    {
        return array(
            'PS Version' => _PS_VERSION_,
            'Plugin'     => 'standard',
        );
    }
}
