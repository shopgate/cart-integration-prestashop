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
class ShopgateShipping
{
    const DEFAULT_PLUGIN_API_KEY = 'PLUGINAPI';
    const DEFAULT_EXTERNAL_MODULE_CARRIER_NAME = 'shopgate';
    const MODULE_CARRIER_NAME = 'shopgate';
    const SG_ALL_CARRIERS = 5;
    const CARRIER_CODE_ALL = 'All';

    /** @var ShopGate */
    protected $module;

    /** @var array */
    protected $shipping_service_list;

    /** @var null|ShopgateDb */
    protected $shopgateDb = null;

    /**
     * @param ShopGate $module
     */
    public function __construct($module)
    {
        $this->shopgateDb            = new ShopgateDb();
        $this->shipping_service_list = array(
            ShopgateDeliveryNote::OTHER      => $module->l('Other'),
            ShopgateDeliveryNote::DHL        => $module->l('DHL'),
            ShopgateDeliveryNote::DHLEXPRESS => $module->l('DHL Express'),
            ShopgateDeliveryNote::DP         => $module->l('Deutsche Post'),
            ShopgateDeliveryNote::DPD        => $module->l('DPD'),
            ShopgateDeliveryNote::FEDEX      => $module->l('FedEx'),
            ShopgateDeliveryNote::GLS        => $module->l('GLS'),
            ShopgateDeliveryNote::HLG        => $module->l('Hermes'),
            ShopgateDeliveryNote::TNT        => $module->l('TNT'),
            ShopgateDeliveryNote::TOF        => $module->l('trans-o-flex'),
            ShopgateDeliveryNote::UPS        => $module->l('UPS'),
            ShopgateDeliveryNote::LAPOSTE    => $module->l('LA POSTE'),
            ShopgateDeliveryNote::COLL_STORE => $module->l('Store Pickup'),
        );
    }

    /**
     * returns the shipping service list
     *
     * @return array
     */
    public function getShippingServiceList()
    {
        return $this->shipping_service_list;
    }

    /**
     * @param ShopgateCartBase $shopgateCart
     *
     * @return mixed
     */
    public function getCarrierId(ShopgateCartBase $shopgateCart)
    {
        switch ($shopgateCart->getShippingType()) {
            case self::DEFAULT_PLUGIN_API_KEY:
                if ($carrierId = $this->readCarrierIdFromInternalShippingInfo($shopgateCart)) {
                    return $carrierId;
                }

                break;
            default:

                /**
                 * use always default carrier if shipping cost uses. will updated after place order to shopgate
                 */
                if ($shopgateCart->getShippingInfos() && $shopgateCart->getShippingInfos()->getAmountGross() > 0) {
                    return Configuration::get('SG_CARRIER_ID');
                }

                if ($shopgateCart->getShippingGroup()) {
                    $carrierMapping = $this->getCarrierMapping();
                    if (is_array($carrierMapping)) {
                        foreach ($carrierMapping as $key => $value) {
                            if ($shopgateCart->getShippingGroup() == $key) {
                                return $value;
                            }
                        }
                    }

                    break;
                }
        }

        return Configuration::get('SG_CARRIER_ID');
    }

    /**
     * @param ShopgateCartBase $shopgateCart
     *
     * @return int | null
     */
    protected function readCarrierIdFromInternalShippingInfo(ShopgateCartBase $shopgateCart)
    {
        $internalShippingInfo = unserialize($shopgateCart->getShippingInfos()->getInternalShippingInfo());

        if (!is_array($internalShippingInfo) || $shopgateCart->getShippingType() !== self::DEFAULT_PLUGIN_API_KEY) {
            return null;
        }

        if (isset($internalShippingInfo['carrierId'])) {
            return (int)$internalShippingInfo['carrierId'];
        }

        if ($shopgateCart->getShippingInfos()->getName()) {
            return $shopgateCart->getShippingInfos()->getName();
        }

        return null;
    }

    /**
     * @return array|mixed
     */
    public function getCarrierMapping()
    {
        $carrierMapping = unserialize(base64_decode(Configuration::get('SG_CARRIER_MAPPING')));

        if (!is_array($carrierMapping)) {
            $carrierMapping = array();
            foreach ($this->getShippingServiceList() as $key => $value) {
                $carrierMapping[$key] = Configuration::get('SG_CARRIER_ID');
            }
        }

        return $carrierMapping;
    }

    /**
     * @param ShopgateOrder $apiOrder
     *
     * @return string
     */
    public function getMappingHtml(ShopgateOrder $apiOrder)
    {
        switch ($apiOrder->getShippingType()) {
            /**
             * read system
             */
            case self::DEFAULT_PLUGIN_API_KEY:
                return $this->_getNameByCarrierId($apiOrder->getShippingInfos()->getName());
            /**
             * switch from mapping
             */
            default:
                return
                    sprintf(
                        '%s (%s - %s)',
                        $this->_getNameByCarrierId(
                            $this->getCarrierId($apiOrder)
                        ),
                        $apiOrder->getShippingType(),
                        $apiOrder->getShippingInfos()->getDisplayName()
                    );
        }
    }

    /**
     * @param $carrierId
     *
     * @return string
     */
    protected function _getNameByCarrierId($carrierId)
    {
        /** @var CarrierCore $carrierItem */
        $carrierItem = new Carrier($carrierId);

        return $carrierItem->name;
    }

    /**
     * create shopgate carrier
     */
    public function createShopgateCarrier()
    {
        /** @var CarrierCore $carrier */
        $carrier                                               = new Carrier();
        $carrier->name                                         = self::MODULE_CARRIER_NAME;
        $carrier->active                                       = true;
        $carrier->deleted                                      = true;
        $carrier->shipping_handling                            = false;
        $carrier->range_behavior                               = true;
        $carrier->delay[Configuration::get('PS_LANG_DEFAULT')] = self::MODULE_CARRIER_NAME;
        $carrier->shipping_external                            = true;
        $carrier->is_module                                    = true;
        $carrier->external_module_name                         = self::DEFAULT_EXTERNAL_MODULE_CARRIER_NAME;
        $carrier->need_range                                   = true;

        foreach (Language::getLanguages() as $language) {
            $carrier->delay[$language['id_lang']] = 'Depends on Shopgate selected carrier';
        }

        if ($carrier->add()) {
            $groups = Group::getGroups(true);
            foreach ($groups as $group) {
                $this->shopgateDb->autoExecute(
                    _DB_PREFIX_ . 'carrier_group',
                    array(
                        'id_carrier' => (int)$carrier->id,
                        'id_group'   => (int)$group['id_group'],
                    ),
                    'INSERT'
                );
            }

            /** @var RangePriceCore $rangePrice */
            $rangePrice             = new RangePrice();
            $rangePrice->id_carrier = $carrier->id;
            $rangePrice->delimiter1 = '0';
            $rangePrice->delimiter2 = '1000000';
            $rangePrice->add();

            /** @var RangeWeightCore $rangeWeight */
            $rangeWeight             = new RangeWeight();
            $rangeWeight->id_carrier = $carrier->id;
            $rangeWeight->delimiter1 = '0';
            $rangeWeight->delimiter2 = '1000000';
            $rangeWeight->add();

            $zones = Zone::getZones(true);
            foreach ($zones as $zone) {
                /** @var $zone ZoneCore */
                $this->shopgateDb->autoExecute(
                    _DB_PREFIX_ . 'carrier_zone',
                    array('id_carrier' => (int)$carrier->id, 'id_zone' => (int)$zone['id_zone']),
                    'INSERT'
                );
                $this->shopgateDb->autoExecuteWithNullValues(
                    _DB_PREFIX_ . 'delivery',
                    array(
                        'id_carrier'      => $carrier->id,
                        'id_range_price'  => (int)$rangePrice->id,
                        'id_range_weight' => null,
                        'id_zone'         => (int)$zone['id_zone'],
                        'price'           => '0',
                    ),
                    'INSERT'
                );
                $this->shopgateDb->autoExecuteWithNullValues(
                    _DB_PREFIX_ . 'delivery',
                    array(
                        'id_carrier'      => $carrier->id,
                        'id_range_price'  => null,
                        'id_range_weight' => (int)$rangeWeight->id,
                        'id_zone'         => (int)$zone['id_zone'],
                        'price'           => '0',
                    ),
                    'INSERT'
                );
            }
        }

        Configuration::updateValue('SG_CARRIER_ID', $carrier->id);
    }

    /**
     * @param int        $id_lang
     * @param bool|false $active_countries
     * @param bool|false $active_carriers
     * @param null       $contain_states
     *
     * @return array
     * @throws PrestaShopDatabaseException
     */
    public static function getDeliveryCountries(
        $id_lang,
        $active_countries = false,
        $active_carriers = false,
        $contain_states = null
    ) {
        if (!Validate::isBool($active_countries) || !Validate::isBool($active_carriers)) {
            die(Tools::displayError("getDeliveryCountries"));
        }

        $states = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT s.*
            FROM `' . _DB_PREFIX_ . 'state` s
            ORDER BY s.`name` ASC'
        );

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT cl.*,c.*, cl.`name` AS country, zz.`name` AS zone
            FROM `' . _DB_PREFIX_ . 'country` c
            LEFT JOIN `' . _DB_PREFIX_ . 'country_lang` cl ON (c.`id_country` = cl.`id_country` AND cl.`id_lang` = '
            . (int)$id_lang . ')
            INNER JOIN (`' . _DB_PREFIX_ . 'carrier_zone` cz INNER JOIN `' . _DB_PREFIX_
            . 'carrier` cr ON ( cr.id_carrier = cz.id_carrier AND cr.deleted = 0 ' .
            ($active_carriers
                ? 'AND cr.active = 1) '
                : ') ') . '
            LEFT JOIN `' . _DB_PREFIX_ . 'zone` zz ON cz.id_zone = zz.id_zone) ON zz.`id_zone` = c.`id_zone`
            WHERE 1
            ' . ($active_countries
                ? 'AND c.active = 1'
                : '') . '
            ' . (!is_null($contain_states)
                ? 'AND c.`contains_states` = ' . (int)$contain_states
                : '') . '
            ORDER BY cl.name ASC'
        );

        $countries = array();
        foreach ($result as &$country) {
            $countries[$country['id_country']] = $country;
        }
        foreach ($states as &$state) {
            if (isset($countries[$state['id_country']])) { /* Does not keep the state if its country has been disabled and not selected */
                if ($state['active'] == 1) {
                    $countries[$state['id_country']]['states'][] = $state;
                }
            }
        }

        return $countries;
    }

    /**
     * @param ShopgateOrder $shopgateOrder
     * @param OrderCore     $order
     *
     * @return bool
     */
    public function manipulateCarrier($shopgateOrder, $order)
    {
        if (!$shopgateOrder instanceof ShopgateOrder) {
            return false;
        }

        if ($shopgateOrder->getShippingType() == self::DEFAULT_PLUGIN_API_KEY) {
            return false;
        }

        $shopgateCarrierId = Configuration::get('SG_CARRIER_ID');

        if (version_compare(_PS_VERSION_, '1.5.0.17', '<')) {
            $order = $this->updateOrderTotalsLegacy(
                $order,
                $shopgateOrder->getShippingInfos()->getAmountGross(),
                $shopgateOrder->getAmountShopgatePayment(),
                $shopgateCarrierId
            );

            return $order->save();
        }

        $amountShopgatePaymentGross = $this->getAmountPaymentGross(
            $shopgateOrder->getAmountShopgatePayment(),
            $shopgateOrder->getPaymentTaxPercent()
        );

        $orderCarrier = new OrderCarrier($this->getIdOrderCarrier($order));
        $orderCarrier = $this->updateOrderCarrier(
            $orderCarrier,
            (int)$order->id,
            $shopgateCarrierId,
            $amountShopgatePaymentGross,
            $shopgateOrder->getAmountShopgatePayment(),
            $shopgateOrder->getShippingInfos()->getAmountGross(),
            $shopgateOrder->getShippingInfos()->getAmountNet()
        );

        $order = $this->updateOrderTotals(
            $order,
            $shopgateCarrierId,
            $amountShopgatePaymentGross,
            $shopgateOrder->getAmountShopgatePayment(),
            $shopgateOrder->getShippingInfos()->getAmountGross(),
            $shopgateOrder->getShippingInfos()->getAmountNet()
        );

        $orderPayments = $order->getOrderPayments();
        $result        = false;
        if (!empty($orderPayments) && is_array($orderPayments)) {
            // we just add one $orderPayment while importing orders therefore we just take the first entry of $orderPayments
            $orderPayment = $this->updateOrderPayment($order, current($orderPayments));
            $result       = $orderPayment->save();
        }

        return $result && $order->save() && $orderCarrier->save();
    }

    /**
     * @param OrderCore    $order
     * @param OrderPayment $orderPayment
     *
     * @return orderPaymentCore
     */
    public function updateOrderPayment($order, $orderPayment)
    {
        $orderPayment->amount = $order->total_paid;

        return $orderPayment;
    }

    /**
     * This method adapts the order totals for Prestashop versions before 1.5.0.17
     *
     * @param OrderCore $order
     * @param float     $shippingCosts
     * @param float     $paymentCosts
     * @param int       $shopgateCarrierId
     *
     * @return OrderCore
     */
    public function updateOrderTotalsLegacy($order, $shippingCosts, $paymentCosts, $shopgateCarrierId)
    {
        $additionalCosts      = $shippingCosts + $paymentCosts;
        $originalShippingCost = $order->total_shipping;

        $order->id_carrier      = (int)$shopgateCarrierId;
        $order->total_paid      += $additionalCosts - $originalShippingCost;
        $order->total_paid_real += $additionalCosts - $originalShippingCost;
        $order->total_shipping  = $additionalCosts;

        return $order;
    }

    /**
     * Method will only work in Prestashop versions since 1.5.0.17
     *
     * @param OrderCore $order
     * @param int       $shopgateCarrierId
     * @param float     $amountShopgatePaymentGross
     * @param float     $amountShopgatePaymentNet
     * @param float     $amountShippingGross
     * @param float     $amountShippingNet
     *
     * @return OrderCore
     */
    public function updateOrderTotals(
        $order,
        $shopgateCarrierId,
        $amountShopgatePaymentGross,
        $amountShopgatePaymentNet,
        $amountShippingGross,
        $amountShippingNet
    ) {
        $originalShippingCostGross = $order->total_shipping_tax_incl;
        $originalShippingCostNet   = $order->total_shipping_tax_excl;

        $order->id_carrier       = (int)$shopgateCarrierId;
        $order->carrier_tax_rate = $this->calculateTaxRate($amountShippingGross, $amountShippingNet);

        $order->total_shipping_tax_incl = $amountShippingGross + $amountShopgatePaymentGross;
        $order->total_shipping_tax_excl = $amountShippingNet + $amountShopgatePaymentNet;
        $order->total_shipping          = $amountShippingGross + $amountShopgatePaymentGross;

        $order->total_paid_tax_incl = ($order->total_paid_tax_incl - $originalShippingCostGross) + $amountShippingGross
            + $amountShopgatePaymentGross;
        $order->total_paid_tax_excl = ($order->total_paid_tax_excl - $originalShippingCostNet) + $amountShippingNet
            + $amountShopgatePaymentNet;

        $order->total_paid      = $order->total_paid_tax_incl;
        $order->total_paid_real = $order->total_paid_tax_incl;

        return $order;
    }

    /**
     * @param OrderCarrier $orderCarrier
     * @param int          $orderId
     * @param int          $shopgateCarrierId
     * @param float        $amountShopgatePaymentGross
     * @param float        $amountShopgatePaymentNet
     * @param float        $amountShippingGross
     * @param float        $amountShippingNet
     *
     * @return OrderCarrier
     */
    public function updateOrderCarrier(
        $orderCarrier,
        $orderId,
        $shopgateCarrierId,
        $amountShopgatePaymentGross,
        $amountShopgatePaymentNet,
        $amountShippingGross,
        $amountShippingNet
    ) {
        $orderCarrier->id_order               = (int)$orderId;
        $orderCarrier->id_carrier             = (int)$shopgateCarrierId;
        $orderCarrier->shipping_cost_tax_incl = $amountShippingGross + $amountShopgatePaymentGross;
        $orderCarrier->shipping_cost_tax_excl = $amountShippingNet + $amountShopgatePaymentNet;

        return $orderCarrier;
    }

    /**
     * @param float $amountShopgatePaymentNet
     * @param float $paymentTaxPercent
     *
     * @return float
     */
    public function getAmountPaymentGross($amountShopgatePaymentNet, $paymentTaxPercent)
    {
        if ($paymentTaxPercent > 0) {
            $amountShopgatePaymentNet += (($amountShopgatePaymentNet / 100) * $paymentTaxPercent);
        }

        return $amountShopgatePaymentNet;
    }

    /**
     * Method $order->getIdOrderCarrier() is only available since Prestashop version 1.5.5.0
     *
     * @param OrderCore $order
     *
     * @return int
     */
    protected function getIdOrderCarrier($order)
    {
        if (version_compare(_PS_VERSION_, '1.5.5.0', '<')) {
            return (int)Db::getInstance()->getValue(
                '
				SELECT `id_order_carrier`
				FROM `' . _DB_PREFIX_ . 'order_carrier`
				WHERE `id_order` = ' . (int)$order->id
            );
        }

        return (int)$order->getIdOrderCarrier();
    }

    /**
     * @param float $amountGross
     * @param float $amountNet
     *
     * @return float
     */
    public function calculateTaxRate($amountGross, $amountNet)
    {
        if ($amountGross == 0 || $amountNet == 0) {
            return 0.0;
        }

        return abs(100 * (($amountGross / $amountNet) - 1));
    }
}
