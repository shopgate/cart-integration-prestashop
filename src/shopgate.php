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

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * define shopgate version
 */
define("SHOPGATE_PLUGIN_VERSION", "2.9.85");

/**
 * define module dir
 */
define('SHOPGATE_DIR', _PS_MODULE_DIR_ . 'shopgate/');

/**
 * require classes
 */

/**
 * extend
 */
if (!in_array('BWProduct', get_declared_classes()) && version_compare(_PS_VERSION_, '1.5.2.0', '<')) {
    require_once(SHOPGATE_DIR . 'core/extend/Product.php');
}

/**
 * Generic includes - all includes should be moved there later on
 */
require_once(SHOPGATE_DIR . 'includes.php');

/**
 * global
 */
require_once(SHOPGATE_DIR . 'vendors/autoload.php');
// restore autoloader
if (version_compare(_PS_VERSION_, '1.5.0.0', '<')) {
    spl_autoload_register('__autoload');
}
require_once(SHOPGATE_DIR . 'core/HookHelper.php');
require_once(SHOPGATE_DIR . 'classes/Config.php');
require_once(SHOPGATE_DIR . 'classes/Builder.php');
require_once(SHOPGATE_DIR . 'classes/Settings.php');
require_once(SHOPGATE_DIR . 'classes/Plugin.php');
require_once(SHOPGATE_DIR . 'classes/Payment.php');
require_once(SHOPGATE_DIR . 'classes/Order.php');
require_once(SHOPGATE_DIR . 'classes/Customer.php');
require_once(SHOPGATE_DIR . 'classes/CustomFields.php');

/**
 * database
 */
require_once(SHOPGATE_DIR . 'classes/database/TransactionFailedException.php');
require_once(SHOPGATE_DIR . 'classes/database/BeginTransactionFailedException.php');
require_once(SHOPGATE_DIR . 'classes/database/CommitTransactionFailedException.php');
require_once(SHOPGATE_DIR . 'classes/database/RollbackTransactionFailedException.php');

/**
 * Helpers
 */
require_once(SHOPGATE_DIR . 'classes/Helper.php');
require_once(SHOPGATE_DIR . 'classes/helpers/Coupon.php');
require_once(SHOPGATE_DIR . 'classes/helpers/ItemAttribute.php');

/**
 * review
 */
require_once(SHOPGATE_DIR . 'classes/items/review/Review.php');
require_once(SHOPGATE_DIR . 'classes/items/review/export/Xml.php');

/**
 * category
 */
require_once(SHOPGATE_DIR . 'classes/items/category/Category.php');
require_once(SHOPGATE_DIR . 'classes/items/category/export/Xml.php');

/**
 * items
 */
require_once(SHOPGATE_DIR . 'classes/items/item/Item.php');
require_once(SHOPGATE_DIR . 'classes/items/item/AttributeGroup.php');
require_once(SHOPGATE_DIR . 'classes/items/item/Attribute.php');
require_once(SHOPGATE_DIR . 'classes/items/item/AttributeCombination.php');
require_once(SHOPGATE_DIR . 'classes/items/item/ProductAttribute.php');
require_once(SHOPGATE_DIR . 'classes/items/item/export/Xml.php');

/**
 * customer
 */
require_once(SHOPGATE_DIR . 'classes/items/customer/Customer.php');
require_once(SHOPGATE_DIR . 'classes/items/customer/export/Json.php');
require_once(SHOPGATE_DIR . 'classes/items/customer/import/Json.php');

/**
 * order
 */
require_once(SHOPGATE_DIR . 'classes/items/order/input/Json.php');
require_once(SHOPGATE_DIR . 'classes/items/order/export/Json.php');

/**
 * cart
 */
require_once(SHOPGATE_DIR . 'classes/items/cart/Cart.php');
require_once(SHOPGATE_DIR . 'classes/items/cart/export/Json.php');

/**
 * Class ShopGate
 */
class ShopGate extends PaymentModule
{
    /**
     * install sql file
     */
    const INSTALL_SQL_FILE = '/setup/install.sql';

    /** @var  ShopgateShipping */
    protected $shopgateShippingModel;

    /** @var  ShopgatePayment */
    protected $shopgatePaymentModel;

    /** @var  array */
    protected $configurations;

    private $cancellationRequestAlreadySent;

    /**
     * init settings
     */
    public function __construct()
    {
        $this->bootstrap                      = true;
        $this->cancellationRequestAlreadySent = false;

        /**
         * fill models
         */
        $this->shopgateShippingModel = new ShopgateShipping($this);
        $this->shopgatePaymentModel  = new ShopgatePayment($this);

        $this->name = 'shopgate';
        if (version_compare(_PS_VERSION_, '1.5.0.0', '<')) {
            $this->tab = 'market_place';
        } else {
            $this->tab = 'mobile';
        }

        $this->version    = "2.9.85";
        $this->author     = 'Shopgate';
        $this->module_key = '';

        parent::__construct();

        $this->displayName = $this->l('Shopgate');
        $this->description = $this->l(
            'Sell your products with your individual app and a website optimized for mobile devices.'
        );

        include_once SHOPGATE_DIR . 'vendors/shopgate/prestashop-backward-compatibility/backward.php';
    }

    /**
     * install
     *
     * @return bool
     */
    public function install()
    {
        /**
         * delete shopgate config
         */
        Configuration::deleteByName(ShopgateConfigPrestashop::PRESTASHOP_CONFIG_KEY);

        /**
         * hooks
         */
        $registerHooks = array(
            'header',
            'adminOrder',
            'updateOrderStatus',
            'updateQuantity',
            'actionValidateOrder',
            'newOrder',
        );

        if (version_compare(_PS_VERSION_, '1.5.0.0', '>=')) {
            $registerHooks[] = 'displayMobileHeader';
            $registerHooks[] = 'actionUpdateQuantity';
        }

        /**
         * enable debug
         */
        ShopgateLogger::getInstance()->enableDebug();

        /**
         * set default settings
         */
        $this->configurations = ShopgateSettings::getDefaultSettings();

        /**
         * check parent install
         */
        ShopGate::log('INSTALLATION - calling parent::install()', ShopgateLogger::LOGTYPE_DEBUG);
        $result = parent::install();
        if (!$result) {
            ShopGate::log(
                'parent::install() failed; return value: ' . var_export($result, true),
                ShopgateLogger::LOGTYPE_ERROR
            );

            return false;
        }

        /**
         * check installed php extensions
         */
        $missingExtensions = ShopgateHelper::checkLoadedExtensions(
            array('curl')
        );

        if (count($missingExtensions) > 0) {
            foreach ($missingExtensions as $missingExtension) {
                ShopGate::log(
                    sprintf('Installation failed. %s is not installed or loaded.', $missingExtension),
                    ShopgateLogger::LOGTYPE_ERROR
                );
            }

            return false;
        }

        /**
         * register hooks
         */
        $this->registerHooks($registerHooks);

        /**
         * install tables
         */
        if (!$this->installTables()) {
            return false;
        }

        /**
         * update tables
         */
        if (!$this->updateTables()) {
            return false;
        }

        /**
         * install shopgate carrier
         */
        $this->shopgateShippingModel->createShopgateCarrier();

        /**
         * order states
         */
        ShopGate::log('INSTALLATION - adding order states', ShopgateLogger::LOGTYPE_DEBUG);
        $this->addOrderState('PS_OS_SHOPGATE', $this->l('Shipping blocked (Shopgate)'));

        /**
         * save default configuration
         */
        ShopGate::log('INSTALLATION - setting config values', ShopgateLogger::LOGTYPE_DEBUG);
        $this->configurations['SG_LANGUAGE_ID'] = Configuration::get('PS_LANG_DEFAULT');

        foreach ($this->configurations as $name => $value) {
            if (!Configuration::updateValue($name, $value)) {
                ShopGate::log(
                    sprintf(
                        'installation failed: unable to save configuration setting "%s" with value "%s"',
                        var_export($name, true),
                        var_export($value, true)
                    ),
                    ShopgateLogger::LOGTYPE_ERROR
                );

                return false;
            }
        }

        /** @todo register plugin */

        ShopGate::log('INSTALLATION - installation was successful', ShopgateLogger::LOGTYPE_DEBUG);

        return true;
    }

    /**
     * @param array $registerHooks
     */
    public function registerHooks(array $registerHooks)
    {
        ShopGate::log('INSTALLATION - registering hookpoints', ShopgateLogger::LOGTYPE_DEBUG);

        foreach ($registerHooks as $hook) {
            ShopGate::log(
                sprintf('INSTALLATION - registering hookpoint %s', $hook),
                ShopgateLogger::LOGTYPE_DEBUG
            );

            /**
             * Try to register hook if available.
             */
            $result = $this->registerHook($hook);
            if (!$result) {
                ShopGate::log(
                    sprintf('$this->registerHook("%s") failed; return value: %s', $hook, var_export($result, true)),
                    ShopgateLogger::LOGTYPE_ERROR
                );
            }
        }
    }

    /**
     * @return bool
     */
    protected function installTables()
    {
        ShopGate::log('INSTALLATION - fetching database object', ShopgateLogger::LOGTYPE_DEBUG);
        $db = Db::getInstance(true);

        if (!file_exists(dirname(__FILE__) . '/' . self::INSTALL_SQL_FILE)) {
            return (false);
        } else {
            if (!$sql = Tools::file_get_contents(dirname(__FILE__) . '/' . self::INSTALL_SQL_FILE)) {
                return (false);
            }
        }

        $sql = str_replace('PREFIX_', _DB_PREFIX_, $sql);
        $sql = preg_split("/;\s*[\r\n]+/", $sql);

        ShopGate::log('INSTALLATION - install tables', ShopgateLogger::LOGTYPE_DEBUG);

        foreach ($sql as $query) {
            if ($query) {
                if (!$db->execute(trim($query))) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * uninstall
     *
     * @return bool
     */
    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }

        /**
         * uninstall carrier
         */
        Db::getInstance()->Execute(
            'UPDATE `' . _DB_PREFIX_ . 'carrier` SET deleted = 1 WHERE `name` = "' . ShopgateShipping::MODULE_CARRIER_NAME . '"'
        );

        /**
         * delete shopgate config
         */
        Configuration::deleteByName(ShopgateConfigPrestashop::PRESTASHOP_CONFIG_KEY);

        return true;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        /** @var ShopgateConfigPrestashop $shopgateConfig */
        $shopgateConfig = new ShopgateConfigPrestashop();

        /** @var mixed $errorMessage */
        $errorMessage = false;

        /** @var LanguageCore $lang */
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));

        /**
         * prepare carrier list
         */
        $allCarriers     = (defined('Carrier::ALL_CARRIERS')
            ? Carrier::ALL_CARRIERS
            : ShopgateShipping::SG_ALL_CARRIERS);
        $carrierList     = Carrier::getCarriers($lang->id, true, false, false, null, $allCarriers);
        $carrierIdColumn = version_compare(_PS_VERSION_, '1.5.0.1', '>=')
            ? 'id_reference'
            : 'id_carrier';
        $nativeCarriers  = $carrierList;

        if (Tools::isSubmit('saveConfigurations')) {
            $shopgateConfig->loadArray(Tools::getValue('configs', array()));
            $shopgateConfig->initFolders();
            $shopgateConfig->saveConfigurationFields();

            ShopgateSettings::saveSettings($carrierList, Tools::getValue('settings', array()));
        }

        $languages = array();
        foreach (Language::getLanguages() as $l) {
            $languages[$l['iso_code']] = $l['name'];
        }

        $orderStates = array();
        foreach (OrderState::getOrderStates($lang->id) as $key => $orderState) {
            $orderStates[$orderState['id_order_state']] = $orderState['name'];
        }

        $newOrderStateMapping = array();
        foreach ($this->shopgatePaymentModel->getPaymentMethods() as $key => $method) {
            $newOrderStateMapping[ShopgateSettings::getOrderStateKey($key)] = $method;
        }

        /**
         * prepare css
         */
        if (version_compare(_PS_VERSION_, '1.6', '<')) {
            $configCss = 'configurations_without_bs.css';
        } else {
            $configCss = 'configurations.css';
        }

        $mobileCarrierUse     = unserialize(base64_decode(Configuration::get('SG_MOBILE_CARRIER')));
        $resultNativeCarriers = array();
        foreach ($nativeCarriers as $nativeCarrier) {
            if ($nativeCarrier['external_module_name'] != ShopgateShipping::DEFAULT_EXTERNAL_MODULE_CARRIER_NAME) {
                $nativeCarrier['identifier'] = $nativeCarrier[$carrierIdColumn];
                if (!is_array($mobileCarrierUse)) {
                    $nativeCarrier['mobile_used'] = 1;
                } elseif (!empty($mobileCarrierUse[$nativeCarrier['identifier']])) {
                    $nativeCarrier['mobile_used'] = 1;
                } else {
                    $nativeCarrier['mobile_used'] = 0;
                }

                $resultNativeCarriers[] = $nativeCarrier;
            }
        }

        $shopgateCarrier = new Carrier(Configuration::get('SG_CARRIER_ID'), $lang->id);
        $carrierList[]   = array('name' => $shopgateCarrier->name, 'id_carrier' => $shopgateCarrier->id);

        $priceTypes = array(
            Shopgate_Model_Catalog_Price::DEFAULT_PRICE_TYPE_NET   => $this->l('Net'),
            Shopgate_Model_Catalog_Price::DEFAULT_PRICE_TYPE_GROSS => $this->l('Gross'),
        );

        /**
         * prepare shop numbers
         */
        $shopNumbers = array();
        foreach (Language::getLanguages() as $lang) {
            $shopgateConfig->setLangId($lang['id_lang']);
            $shopNumber                     = $shopgateConfig->getShopNumber();
            $shopNumbers[$lang['iso_code']] = $shopNumber;
        }

        $shopgateConfig                = $shopgateConfig->toArray();
        $shopgateConfig['shop_number'] = $shopNumbers;

        /**
         * fill smarty params
         */
        $this->context->smarty->assign('error_message', $errorMessage);
        $this->context->smarty->assign('settings', Configuration::getMultiple(ShopgateSettings::getSettingKeys()));
        $this->context->smarty->assign('configs', $shopgateConfig);
        $this->context->smarty->assign('mod_dir', $this->_path);
        $this->context->smarty->assign('video_url', ShopgateHelper::getVideoLink($this->context));
        $this->context->smarty->assign('offer_url', ShopgateHelper::getOfferLink($this->context));
        $this->context->smarty->assign('api_url', ShopgateHelper::getApiUrl($this->context));
        $this->context->smarty->assign('currencies', Currency::getCurrencies());
        $this->context->smarty->assign('servers', ShopgateHelper::getEnvironments($this));
        $this->context->smarty->assign('shipping_service_list', $this->shopgateShippingModel->getShippingServiceList());
        $this->context->smarty->assign(
            'product_export_descriptions',
            ShopgateSettings::getProductExportDescriptionsArray($this)
        );
        $this->context->smarty->assign('languages', $languages);
        $this->context->smarty->assign('carrier_list', $carrierList);
        $this->context->smarty->assign('shippingModel', $this->shopgateShippingModel);
        $this->context->smarty->assign('configCss', $configCss);
        $this->context->smarty->assign('product_export_price_type', $priceTypes);
        $this->context->smarty->assign('native_carriers', $resultNativeCarriers);
        $this->context->smarty->assign('order_state_mapping', $orderStates);

        return $this->display(__FILE__, 'views/templates/admin/configurations.tpl');
    }

    /**
     * @param        $message
     * @param string $type
     */
    public static function log($message, $type = ShopgateLogger::LOGTYPE_ERROR)
    {
        ShopgateLogger::getInstance()->log($message, $type);
    }

    /**
     * @param $state
     * @param $name
     *
     * @return bool
     */
    private function addOrderState($state, $name)
    {
        $orderState = new OrderState((int)Configuration::get($state));
        if (!Validate::isLoadedObject($orderState)) {
            //Creating new order state
            $orderState->color       = 'lightblue';
            $orderState->unremovable = 1;
            $orderState->name        = array();

            foreach (Language::getLanguages() as $language) {
                $orderState->name[$language['id_lang']] = $name;
            }

            if (!$orderState->add()) {
                return false;
            }

            if (version_compare(_PS_VERSION_, '1.5.5.0', '>=')) {
                Tools::copy(
                    dirname(__FILE__) . '/logo.gif',
                    dirname(__FILE__) . '/../../img/os/' . (int)$orderState->id . '.gif'
                );
            } else {
                copy(
                    dirname(__FILE__) . '/logo.gif',
                    dirname(__FILE__) . '/../../img/os/' . (int)$orderState->id . '.gif'
                );
            }
        }

        return ($this->configurations[$state] = $orderState->id);
    }

    /**
     * @return mixed|string
     */
    public function hookHeader()
    {
        return ShopgateHelper::calculateRedirect();
    }

    /**
     * @return mixed|string
     */
    public function hookDisplayMobileHeader()
    {
        return ShopgateHelper::calculateRedirect();
    }

    /**
     * @param $params
     *
     * @return string
     */
    public function hookAdminOrder($params)
    {
        $shopgateOrder = ShopgateOrderPrestashop::loadByOrderId($params['id_order']);

        if ($shopgateOrder->id) {
            /** @var ShopgateOrder $apiOrder */
            $apiOrder = unserialize(base64_decode($shopgateOrder->shopgate_order));

            if (version_compare(_PS_VERSION_, '1.6.0.0', '<=')) {
                $oldShopVersion = true;
                $this->context->smarty->assign('image_enabled', $this->getAdminImageUrl('enabled.gif'));
                $this->context->smarty->assign('image_disabled', $this->getAdminImageUrl('disabled.gif'));
            } else {
                $oldShopVersion = false;
            }

            if (!is_object($apiOrder)) {
                try {
                    $order          = new Order($params['id_order']);
                    $shopgateConfig = new ShopgateConfigPrestashop();
                    $shopgateConfig->setLangId($order->id_lang);

                    $shopgateBuilder     = new ShopgateBuilder($shopgateConfig);
                    $shopgateMerchantApi = $shopgateBuilder->buildMerchantApi();
                    $shopgateOrders      = $shopgateMerchantApi->getOrders(
                        array('order_numbers[0]' => $shopgateOrder->order_number, 'with_items' => 1)
                    )->getData();

                    if (!empty($shopgateOrders[0])) {
                        $shopgateOrder->updateFromOrder($shopgateOrders[0]);
                        $apiOrder = $shopgateOrders[0];
                        $shopgateOrder->save();
                    }
                } catch (ShopgateMerchantApiException $e) {
                    /**
                     * can not be empty because of the Prestashop validator
                     */
                    unset($e);
                }
            }

            $this->context->smarty->assign(
                'isShopgateOrder',
                $shopgateOrder->id
                    ? true
                    : false
            );
            $this->context->smarty->assign('shopgateOrder', $shopgateOrder);
            $this->context->smarty->assign('apiOrder', $apiOrder);
            $this->context->smarty->assign('orderComments', Tools::jsonDecode(base64_decode($shopgateOrder->comments)));
            $this->context->smarty->assign('modDir', $this->_path);
            $this->context->smarty->assign('paymentModel', $this->shopgatePaymentModel);
            $this->context->smarty->assign('shippingModel', $this->shopgateShippingModel);
            $this->context->smarty->assign('mod_dir', $this->_path);
            $this->context->smarty->assign('old_shop_version', $oldShopVersion);

            /**
             * prepare css / js
             */
            if (version_compare(_PS_VERSION_, '1.6', '<')) {
                $orderCss  = 'order_without_bs.css';
                $requireJs = true;
            } else {
                $orderCss  = 'order.css';
                $requireJs = false;
            }

            $this->context->smarty->assign('requireJs', $requireJs);
            $this->context->smarty->assign('orderCss', $orderCss);

            /**
             * prepare show custom fields panel
             */
            if (is_object($apiOrder)
                && (count($apiOrder->getCustomFields())
                    || count($apiOrder->getInvoiceAddress()->getCustomFields())
                    || count($apiOrder->getDeliveryAddress()->getCustomFields()))
            ) {
                $this->context->smarty->assign('showCustomFieldsPanel', true);
            } else {
                $this->context->smarty->assign('showCustomFieldsPanel', false);
            }

            return $this->display(__FILE__, 'views/templates/admin/admin_order.tpl');
        }
    }

    /**
     * Carrie module methods
     *
     * @param $params
     * @param $shipping_cost
     *
     * @return float
     */
    public function getOrderShippingCost($params, $shipping_cost)
    {
        return (float)($this->getOrderShippingCostExternal($params, $shipping_cost) + $shipping_cost);
    }

    public function getOrderShippingCostExternal($cart)
    {
        $shopgateOrder = ShopgateOrderPrestashop::loadByCartId($cart->id);

        return Validate::isLoadedObject($shopgateOrder)
            ? $shopgateOrder->shipping_cost
            : 0;
    }

    /**
     * returns the complete url to an admin image
     *
     * @param $imageFileName
     *
     * @return string
     */
    private function getAdminImageUrl($imageFileName)
    {
        $adminImageUrl       = (defined('_PS_SHOP_DOMAIN_')
                ? 'http://' . _PS_SHOP_DOMAIN_
                : _PS_BASE_URL_) . _PS_ADMIN_IMG_ . $imageFileName;
        $adminImageLocalPath = _PS_IMG_DIR_ . 'admin/' . $imageFileName;

        return file_exists($adminImageLocalPath)
            ? $adminImageUrl
            : '';
    }

    /**
     * Prestashop had not prepared a hook point for partial
     * cancellations yet. In this case we need to use a hook point
     * where we are able to access the needed data.
     *
     * @param $param
     */
    public function hookActionAdminControllerSetMedia($param)
    {
        if (Tools::isSubmit('partialRefund')) {
            $this->callUpdateQuantityHook($param);
        }
    }

    /**
     * In Prestashop Versions lower than 1.5 there is only one database table
     * used for ("ps_hook") hook points. E.g. the hook "UpdateQuantity" is now
     * called "ActionUpdateQuantity"
     *
     * @param $param
     */
    public function hookActionUpdateQuantity($param)
    {
        $this->callUpdateQuantityHook($param);
    }

    /**
     * This is a wrapper function which will be called
     * from the hooks "hookActionUpdateQuantity" or
     * "hookActionAdminControllerSetMedia"
     *
     * @param $param
     */
    private function callUpdateQuantityHook($param)
    {
        if (!$this->cancellationRequestAlreadySent) {
            $this->cancellationRequestAlreadySent = true;
            $this->hookUpdateQuantity($param);
        }
    }

    /**
     * This method is called when the order was edited
     *
     * @param $param
     */
    public function hookUpdateQuantity($param)
    {
        // prevent prestashop for executing the hook point twice
        if ((version_compare(
                _PS_VERSION_,
                '1.5.0.0',
                '<'
            )) && !empty($param['product']) && (!($param['product'] instanceof ProductCore))
        ) {
            return;
        }
        // In this case we only need that hook if
        // the quantity of an order product changed
        // in version < 1.5.0 the posted field is named cancelProduct

        $idOrder       = Tools::getValue('id_order');
        $action        = Tools::getValue('action');
        $cancelProduct = Tools::getValue('cancelProduct');

        if (!empty($idOrder)
            && (
                (!empty($action) && $action == 'editProductOnOrder')
                || (!empty($cancelProduct))
                || Tools::isSubmit('partialRefund')
            )
        ) {
            $sgOrder = ShopgateOrderPrestashop::loadByOrderId($idOrder);
            $message = '';

            // check if this order is a shopgate order
            if (!is_null($sgOrder->order_number) && !is_null($sgOrder->id_order)) {
                $sgOrder->cancelOrder($message, true);
            }
        }
    }

    /**
     * This method gets called when the order status is changed in the admin area of the Prestashop backend
     *
     * @param $params
     */
    public function hookUpdateOrderStatus($params)
    {
        $id_order      = $params['id_order'];
        $newOrderState = $params['newOrderStatus'];
        $shopgateOrder = ShopgateOrderPrestashop::loadByOrderId($id_order);
        $order         = new Order($params['id_order']);

        $shopgateConfig = new ShopgateConfigPrestashop();
        $shopgateConfig->setLangId($order->id_lang);
        $cancellationStatus = Configuration::get('SG_CANCELLATION_STATUS');

        $shopgateBuilder     = new ShopgateBuilder($shopgateConfig);
        $shopgateMerchantApi = $shopgateBuilder->buildMerchantApi();

        if (!is_object($shopgateOrder) || !$shopgateOrder->id_shopgate_order) {
            return;
        }

        try {
            $shippedOrderStates = array();
            if (version_compare(_PS_VERSION_, '1.5.0.0', '>=')) {
                $orderStates = OrderState::getOrderStates($this->context->language->id);
                foreach ($orderStates as $orderState) {
                    if ($orderState['shipped']) {
                        $shippedOrderStates[$orderState['id_order_state']] = 1;
                    }
                }
            } else {
                // Default methods for Prestashop version < 1.5.0.0
                $shippedOrderStates[_PS_OS_DELIVERED_] = 1;
                $shippedOrderStates[_PS_OS_SHIPPING_]  = 1;
            }

            if (!empty($shippedOrderStates[$newOrderState->id])) {
                $shopgateMerchantApi->setOrderShippingCompleted($shopgateOrder->order_number);
            }

            $message = '';
            if ($cancellationStatus == $newOrderState->id) {
                $shopgateOrder->cancelOrder($message);
            }
        } catch (ShopgateMerchantApiException $e) {
            $msg              = new Message();
            $msg->message     = $this->l('On order state') . ': ' . $orderState->name . ' - ' . $this->l(
                    'Shopgate status was not updated because of following error'
                ) . ': ' . $e->getMessage();
            $msg->id_order    = $id_order;
            $msg->id_employee = isset($params['cookie']->id_employee)
                ? $params['cookie']->id_employee
                : 0;
            $msg->private     = true;
            $msg->add();
        }
    }

    /**
     * functionality thats executed after the installTables method and mostly because of database table changes in
     * later versions
     *
     * @return bool
     */
    public function updateTables()
    {
        if (!$this->insertColumnToTable(
            _DB_PREFIX_ . 'shopgate_order',
            'comments',
            'text NULL DEFAULT NULL',
            'shipping_cost'
        )
        ) {
            return false;
        }

        if (!$this->insertColumnToTable(
            _DB_PREFIX_ . 'shopgate_order',
            'status',
            "int(1) NOT NULL DEFAULT '0'",
            'comments'
        )
        ) {
            return false;
        }

        if (!$this->insertColumnToTable(
            _DB_PREFIX_ . 'shopgate_order',
            'shopgate_order',
            'text NULL DEFAULT NULL',
            'status'
        )
        ) {
            return false;
        }

        if (!$this->insertColumnToTable(
            _DB_PREFIX_ . 'shopgate_order',
            'is_cancellation_sent_to_shopgate',
            "int(1) NOT NULL DEFAULT '0'",
            'shopgate_order'
        )
        ) {
            return false;
        }

        if (!$this->insertColumnToTable(
            _DB_PREFIX_ . 'shopgate_order',
            'reported_cancellations',
            'text NULL DEFAULT NULL',
            'is_cancellation_sent_to_shopgate'
        )
        ) {
            return false;
        }

        if (!$this->insertColumnToTable(
            _DB_PREFIX_ . 'shopgate_order',
            'is_sent_to_shopgate',
            'int(1) NOT NULL DEFAULT 0',
            'reported_cancellations'
        )
        ) {
            return false;
        }

        return true;
    }

    /**
     * Insert a new column to database $tableName in case it is not yet inserted
     *
     * @param string $tableName         - full table name
     * @param string $fieldName         - the column to add
     * @param string $columnProperties
     * @param string $insertAfterColumn - the position to insert the column
     *
     * @return bool
     */
    protected function insertColumnToTable($tableName, $fieldName, $columnProperties, $insertAfterColumn)
    {
        $db = Db::getInstance(true);

        $this->log(
            'INSTALLATION - checking for field "' . $fieldName . '" inside table "' . $tableName . '"',
            ShopgateLogger::LOGTYPE_DEBUG
        );
        $db->Execute('SHOW COLUMNS FROM `' . $tableName . '` LIKE \'' . $fieldName . '\';', false);
        if ($db->Affected_Rows() == 0) {
            $this->log(
                'INSTALLATION - creating field "' . $fieldName . '" inside table "' . $tableName . '"',
                ShopgateLogger::LOGTYPE_DEBUG
            );
            if ($db->Execute(
                    'ALTER TABLE `' . $tableName . '` ADD `' . $fieldName . '` ' . $columnProperties . ' AFTER `' . $insertAfterColumn . '`;',
                    false
                ) === false
            ) {
                $this->log(
                    'installation failed: unable to add field "' . $fieldName . '" to table "' . $tableName . '". MySQL says: ' . var_export(
                        $db->getMsgError(),
                        true
                    ),
                    ShopgateLogger::LOGTYPE_ERROR
                );

                return false;
            }
        }

        return true;
    }

    /**
     * @param array $params
     *
     * @return bool
     */
    public function hookActionValidateOrder($params)
    {
        /** @var ContextCore $context */
        $context = Context::getContext();
        if ($context->cookie->__get('shopgateOrderNumber')) {
            $shopgateShippingModel = new ShopgateShipping(new ShopGate());
            $shopgateOrderMapper   = ShopgateOrderPrestashop::loadByOrderNumber(
                $context->cookie->__get('shopgateOrderNumber')
            );
            $shopgateOrder         = unserialize(base64_decode($shopgateOrderMapper->shopgate_order));
            $order                 = $params['order'];
            $shopgateShippingModel->manipulateCarrier($shopgateOrder, $order);
        }

        return true;
    }

    /**
     * @param array $params
     *
     * @return bool
     */
    public function hookNewOrder($params)
    {
        return $this->hookActionValidateOrder($params);
    }
}
