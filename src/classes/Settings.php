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
class ShopgateSettings
{
    const PRODUCT_EXPORT_DESCRIPTION = 'DESCRIPTION';
    const PRODUCT_EXPORT_SHORT_DESCRIPTION = 'SHORT';
    const PRODUCT_EXPORT_BOTH_DESCRIPTIONS = 'BOTH';
    const DEFAULT_ORDER_NEW_STATE_KEY_PATTERN = 'SG_ONS_%s';

    private static $cacheTaxRuleByGroupId;

    /**
     * settings keys
     *
     * @return array
     */
    public static function getSettingKeys()
    {
        return array_keys(ShopgateSettings::getDefaultSettings());
    }

    /**
     * @return array
     */
    public static function getDefaultSettings()
    {
        $configuration = array(
            'PS_OS_SHOPGATE'                  => 0,
            'SG_LANGUAGE_ID'                  => 0,
            'SG_MIN_QUANTITY_CHECK'           => 0,
            'SG_OUT_OF_STOCK_CHECK'           => 0,
            'SG_PRODUCT_DESCRIPTION'          => self::PRODUCT_EXPORT_DESCRIPTION,
            'SG_SUBSCRIBE_NEWSLETTER'         => 0,
            'SG_EXPORT_ROOT_CATEGORIES'       => 0,
            'SG_CARRIER_MAPPING'              => array(),
            'SG_MOBILE_CARRIER'               => array(),
            'SHOPGATE_EXPORT_PRICE_TYPE'      => Shopgate_Model_Catalog_Price::DEFAULT_PRICE_TYPE_NET,
            'SHOPGATE_EXPORT_BASE_PRICE_TYPE' => Shopgate_Model_Catalog_Price::DEFAULT_PRICE_TYPE_NET,
            'SG_CANCELLATION_STATUS'          => 0,
        );

        return $configuration;
    }

    /**
     * @param array $carrierList
     * @param array $newSettings
     */
    public static function saveSettings(array $carrierList, array $newSettings)
    {
        $carrierIdColumn = version_compare(_PS_VERSION_, '1.5.0.1', '>=')
            ? 'id_reference'
            : 'id_carrier';

        $settings = array();
        foreach ($carrierList as $carrier) {
            $settings['SG_MOBILE_CARRIER'][(int)$carrier[$carrierIdColumn]] = 0;
        }

        foreach ($newSettings as $key => $value) {
            if (!empty($value) && is_array($value) && !empty($settings[$key])) {
                $settings[$key] = $value + $settings[$key];
            } else {
                $settings[$key] = $value;
            }
        }

        foreach ($settings as $key => $value) {
            if (in_array($key, ShopgateSettings::getSettingKeys())) {
                if (is_array($value)) {
                    $value = base64_encode(serialize($value));
                }
                Configuration::updateValue($key, htmlentities($value, ENT_QUOTES));
            }
        }
    }

    /**
     * @param $paymentIdentifier
     *
     * @return string
     */
    public static function getOrderStateKey($paymentIdentifier)
    {
        return sprintf(self::DEFAULT_ORDER_NEW_STATE_KEY_PATTERN, $paymentIdentifier);
    }

    /**
     * @param $module
     *
     * @return array
     */
    public static function getProductExportDescriptionsArray($module)
    {
        return array(
            self::PRODUCT_EXPORT_DESCRIPTION       => $module->l('Description'),
            self::PRODUCT_EXPORT_SHORT_DESCRIPTION => $module->l('Short Description'),
            self::PRODUCT_EXPORT_BOTH_DESCRIPTIONS => $module->l('Short Description + Description'),
        );
    }

    /**
     * @param $module
     *
     * @return array
     */
    protected static function getCustomerGroups($module)
    {
        /**
         * customer groups
         */
        $customerGroupsItems = Group::getGroups(
            $module->context->language->id,
            $module->context->shop->id
                ? $module->context->shop->id
                : false
        );

        $customerGroups = array();

        if (is_array($customerGroupsItems)) {
            foreach ($customerGroupsItems as $customerGroupsItem) {
                $group               = array();
                $group['id']         = $customerGroupsItem['id_group'];
                $group['name']       = $customerGroupsItem['name'];
                $group['is_default'] = $group['id'] == (int)Configuration::get('PS_GUEST_GROUP')
                    ? true
                    : false;
                $customerGroups[]    = $group;
            }
        }

        return $customerGroups;
    }

    /**
     * @param $module
     *
     * @return array
     */
    protected static function getProductTaxClasses($module)
    {
        $productTaxClassItems = Tax::getTaxes($module->context->language->id);
        $productTaxClasses    = array();

        if (is_array($productTaxClassItems) && Configuration::get('PS_TAX') == 1) {
            foreach ($productTaxClassItems as $productTaxClassItem) {
                $taxClass            = array();
                $taxClass['id']      = $productTaxClassItem['id_tax'];
                $taxClass['key']     = $productTaxClassItem['name'];
                $productTaxClasses[] = $taxClass;
            }
        }

        return $productTaxClasses;
    }

    public static function arrayValueExists($key, $value, $data)
    {
        if (!is_array($data)) {
            return false;
        }

        foreach ($data as $dataItem) {
            if ($dataItem[$key] == $value) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $module
     *
     * @return array
     */
    protected static function getTaxRatesOldVersions($module)
    {
        $taxRatesQuery
            = 'SELECT c.id_country,c.id_zone,c.iso_code,z.id_zone,z.name,tl.name,t.rate,t.id_tax,ts.id_state FROM '
              . _DB_PREFIX_ . 'country AS c
                        JOIN ' . _DB_PREFIX_ . 'zone AS z ON (c.id_zone=z.id_zone AND z.active=1)
                        JOIN ' . _DB_PREFIX_ . 'tax_zone AS tz ON tz.id_tax=z.id_zone
                        JOIN ' . _DB_PREFIX_ . 'tax_lang AS tl ON (tl.id_tax=tz.id_tax AND tl.id_lang='
              . (int)$module->context->language->id . ')
                        LEFT JOIN ' . _DB_PREFIX_ . 'tax AS t ON t.id_tax=tl.id_tax
                        LEFT JOIN ' . _DB_PREFIX_ . 'tax_state AS ts ON ts.id_tax=t.id_tax
                        WHERE c.active=1 AND t.active = 1';

        $resultRates    = Db::getInstance()->ExecuteS($taxRatesQuery);
        $resultTaxRates = array();
        foreach ($resultRates as $rate) {
            $taxItemTmp = array(
                'id'           => $rate['id_tax'] . '-' . $rate['id_country'] . '-' . $rate['id_zone'],
                'key'          => $rate['name'] . '-' . $rate['iso_code'],
                'display_name' => $rate['name'],
                'country'      => $rate['iso_code'],
                'zipcode_type' => 'all',
            );

            if (!empty($rate['id_state']) && !empty($rate['id_country']) && !empty($rate['id_zone'])) {
                $stateQuery   = 'SELECT * FROM ' . _DB_PREFIX_ . 'state AS s WHERE s.id_state=' . $rate['id_state']
                                . ' AND s.id_country=' . $rate['id_country'] . ' AND s.id_zone=' . $rate['id_zone'];
                $resultStates = Db::getInstance()->ExecuteS($stateQuery);
                foreach ($resultStates as $state) {
                    $taxItemTmp['state'] = $taxItemTmp['country'] . '-' . $state['iso_code'];
                    $resultTaxRates[]    = $taxItemTmp;
                }
            } else {
                $resultTaxRates[] = $taxItemTmp;
            }
        }

        return $resultTaxRates;
    }

    /**
     * returns the tax_id extracted from the passed array
     *
     * @param $taxRuleItem array can be array(array('id_tax')) or array(array(array('id_tax')))
     */
    protected static function getTaxIdFromTaxRule($taxRuleItem)
    {
        return (version_compare(_PS_VERSION_, '1.4.0.17', '>=')
            ? $taxRuleItem[0][0]['id_tax']
            : $taxRuleItem[0]['id_tax']);
    }

    /**
     * returns the key for the tax rate
     *
     * @param TaxCore $taxItem
     * @param string  $country
     * @param string  $state
     *
     * @return string the tax rate key
     */
    protected static function getTaxRateKey($taxItem, $country, $state)
    {
        return ($state
            ? $taxItem->name . '-' . $country . '-' . $state
            : $taxItem->name . '-' . $country);
    }

    /**
     * @param $module
     *
     * @return array
     */
    protected static function getTaxRates($module)
    {
        $taxRates = array();

        $taxRuleGroups = TaxRulesGroup::getTaxRulesGroups(true);

        foreach ($taxRuleGroups as $taxRuleGroup) {
            if (version_compare(_PS_VERSION_, '1.5.0.1', '<')) {
                $taxRules = TaxRule::getTaxRulesByGroupId($taxRuleGroup['id_tax_rules_group']);
            } else {
                $taxRules = TaxRule::getTaxRulesByGroupId(
                    $module->context->language->id,
                    $taxRuleGroup['id_tax_rules_group']
                );
            }

            // $idCountry is only relevant for Prestashop versions <1.5.0.1
            foreach ($taxRules as $idCountry => $taxRuleItem) {
                $resultTaxRate                 = array();
                $resultTaxRate['zipcode_type'] = 'all';
                if (version_compare(_PS_VERSION_, '1.5.0.1', '>=')) {
                    $taxRuleItemTmp = new TaxRule($taxRuleItem['id_tax_rule']);

                    $idCountry = $taxRuleItemTmp->id_country;
                    $idState   = $taxRuleItemTmp->id_state;
                    $idTax     = $taxRuleItemTmp->id_tax;

                    if (!empty($taxRuleItemTmp->zipcode_from) && !empty($taxRuleItemTmp->zipcode_to)) {
                        $resultTaxRate['zipcode_type']       = 'range';
                        $resultTaxRate['zipcode_range_from'] = $taxRuleItemTmp->zipcode_from
                            ? $taxRuleItemTmp->zipcode_from
                            : null;
                        $resultTaxRate['zipcode_range_to']   = $taxRuleItemTmp->zipcode_to
                            ? $taxRuleItemTmp->zipcode_to
                            : null;
                    }
                } else {
                    $idTax   = self::getTaxIdFromTaxRule($taxRuleItem);
                    $idState = key($taxRuleItem);
                }

                /** @var TaxCore $taxItem */
                $taxItem = new Tax($idTax, $module->context->language->id);
                $country = Country::getIsoById($idCountry);

                if (version_compare(_PS_VERSION_, '1.5.0.1', '<')) {
                    $state = State::getNameById($idState);
                } else {
                    $stateModel = new State($idState);
                    $state      = $stateModel->iso_code;
                }

                $resultTaxRate['key'] = self::getTaxRateKey($taxItem, $country, $state);

                //Fix for 1.4.x.x the taxes were exported multiple
                if (version_compare(_PS_VERSION_, '1.5.0', '<')
                    && self::arrayValueExists('key', $resultTaxRate['key'], $taxRates)
                ) {
                    continue;
                }
                if (!is_string($country)) {
                    $country = '';
                }
                $resultTaxRate['display_name'] = $taxItem->name;
                $resultTaxRate['tax_percent']  = $taxItem->rate;
                $resultTaxRate['country']      = $country;
                $resultTaxRate['state']        = (!empty($state))
                    ? $country . '-' . $state
                    : null;

                if ($taxItem->active && Configuration::get('PS_TAX') == 1) {
                    $taxRates[] = $resultTaxRate;
                }
            }
        }

        return $taxRates;
    }

    /**
     * @param $module
     *
     * @return mixed
     */
    protected static function getTaxRules($module)
    {
        $taxRules = array();

        $taxRuleGroups = TaxRulesGroup::getTaxRulesGroups(true);

        foreach ($taxRuleGroups as $taxRuleGroup) {
            /** @var TaxCore $taxItem */
            $taxItem = ShopgateSettings::getTaxItemByTaxRuleGroupId($taxRuleGroup['id_tax_rules_group']);

            $rule = array(
                'id'       => $taxRuleGroup['id_tax_rules_group'],
                'name'     => $taxRuleGroup['name'],
                'priority' => 0,
            );

            $rule['product_tax_classes'] = array(
                array(
                    'id'  => $taxItem->id,
                    'key' => is_array($taxItem->name)
                        ? reset($taxItem->name)
                        : '',
                ),
            );

            $rule['customer_tax_classes'] = array(
                array(
                    'key'        => 'default',
                    'is_default' => true,
                ),
            );

            $rule['tax_rates'] = array();

            if (version_compare(_PS_VERSION_, '1.5.0.1', '<')) {
                $taxRulesPrestashop = TaxRule::getTaxRulesByGroupId($taxRuleGroup['id_tax_rules_group']);
            } else {
                $taxRulesPrestashop = TaxRule::getTaxRulesByGroupId(
                    $module->context->language->id,
                    $taxRuleGroup['id_tax_rules_group']
                );
            }

            foreach ($taxRulesPrestashop as $idCountry => $taxRuleItem) {
                if (version_compare(_PS_VERSION_, '1.5.0.1', '>=')) {
                    $taxRuleItem = new TaxRule($taxRuleItem['id_tax_rule']);
                    $idTax       = $taxRuleItem->id_tax;
                    $idCountry   = $taxRuleItem->id_country;
                    $idState     = $taxRuleItem->id_state;
                } else {
                    $idTax   = self::getTaxIdFromTaxRule($taxRuleItem);
                    $idState = key($taxRuleItem);
                }
                
                // Fix for exporting rules without assigned tax rate
                if (!$idTax) {
                    continue;
                }

                /** @var TaxCore $taxItem */
                $taxItem = new Tax($idTax, $module->context->language->id);

                $country    = Country::getIsoById($idCountry);
                $stateModel = new State($idState);
                $state      = $stateModel->iso_code;

                $resultTaxRate        = array();
                $resultTaxRate['key'] = self::getTaxRateKey($taxItem, $country, $state);

                //Fix for 1.4.x.x the taxes were exported multiple
                if (self::arrayValueExists('key', $resultTaxRate['key'], $rule['tax_rates'])) {
                    continue;
                }

                $rule['tax_rates'][] = $resultTaxRate;
            }

            if ($taxItem->active && Configuration::get('PS_TAX') == 1) {
                $taxRules[] = $rule;
            }
        }

        return $taxRules;
    }

    /**
     * @param $productTaxClasses  array
     * @param $customerTaxClasses array
     * @param $taxRates           array
     *
     * @return array
     */
    protected static function getTaxRulesOldVersions($productTaxClasses, $customerTaxClasses, $taxRates)
    {
        $taxRules = array();
        foreach ($productTaxClasses as $productTaxClass) {
            $taxRule                          = array(
                'id'                   => '',
                'name'                 => '',
                'priority'             => 0,
                'customer_tax_classes' => $customerTaxClasses,
                'product_tax_classes'  => array(),
                'tax_rates'            => array(),
            );
            $taxRule['product_tax_classes'][] = $productTaxClass;
            $taxRule['id']                    = $productTaxClass['id'];
            $taxRule['name']                  = $productTaxClass['key'];
            foreach ($taxRates as $taxRate) {
                $taxIds = explode('-', $taxRate['id']);// first is the tax id
                $taxId  = $taxIds[0];
                if ((int)$productTaxClass['id'] == (int)$taxId) {
                    if (isset($taxRules[$taxId])) {
                        $taxRules[$taxId]['tax_rates'][] = $taxRule;
                        continue 2;
                    } else {
                        $taxRule['tax_rates'][] = array('id' => $taxRate['id'], 'key' => $taxRate['key']);
                    }
                }
            }

            if (isset($taxRule['tax_rates']) && count($taxRule['tax_rates']) == 0) {
                continue;
            }

            $taxRules[$taxRule['id']] = $taxRule;
        }

        return $taxRules;
    }

    /**
     * @param $ruleGroupId
     *
     * @return bool|Tax
     */
    public static function getTaxItemByTaxRuleGroupId($ruleGroupId)
    {
        if (!empty(self::$cacheTaxRuleByGroupId[$ruleGroupId])) {
            return self::$cacheTaxRuleByGroupId[$ruleGroupId];
        }

        $select = sprintf(
            'SELECT DISTINCT id_tax FROM `%stax_rule` WHERE id_tax_rules_group = %d',
            _DB_PREFIX_,
            $ruleGroupId
        );

        $result = Db::getInstance()->getRow($select);

        if (is_array($result) && isset($result['id_tax'])) {
            return self::$cacheTaxRuleByGroupId[$ruleGroupId] = new Tax($result['id_tax']);
        }

        return self::$cacheTaxRuleByGroupId[$ruleGroupId] = false;
    }

    /**
     * @return array all payment methods
     */
    protected static function getPaymentMethods()
    {
        $paymentModules          = array();
        $installedPaymentModules = self::getInstalledPaymentModules();

        foreach ($installedPaymentModules as $paymentModule) {
            $paymentModules[] = array('id' => $paymentModule['name']);
        }

        return $paymentModules;
    }

    /**
     * @return array all active payment methods
     */
    private static function getInstalledPaymentModules()
    {
        if (version_compare(_PS_VERSION_, '1.4.5.0', '<')) {
            $hookPayment = 'Payment';
            if (
                Db::getInstance()->getValue(
                    'SELECT `id_hook` FROM `' . _DB_PREFIX_ . 'hook` WHERE `name` = \'displayPayment\''
                )
            ) {
                $hookPayment = 'displayPayment';
            }

            $paymentModules = Db::getInstance()->executeS(
                'SELECT DISTINCT m.`id_module`, h.`id_hook`, m.`name`, hm.`position`
                FROM `' . _DB_PREFIX_ . 'module` m
                LEFT JOIN `' . _DB_PREFIX_ . 'hook_module` hm ON hm.`id_module` = m.`id_module`
                LEFT JOIN `' . _DB_PREFIX_ . 'hook` h ON hm.`id_hook` = h.`id_hook`
                WHERE h.`name` = \'' . pSQL($hookPayment) . '\''
            );
        } else {
            $paymentModules = PaymentModule::getInstalledPaymentModules();
        }

        if ($paymentModules === null) {
            $paymentModules = array();
        }

        return $paymentModules;
    }

    /**
     * @param ShopgatePluginPrestashop $module
     *
     * @return array
     */
    public static function getShopgateSettings($module)
    {
        $result                                = array();
        $result['customer_groups']             = ShopgateSettings::getCustomerGroups($module);
        $result['payment_methods']             = ShopgateSettings::getPaymentMethods();
        $result['tax']['product_tax_classes']  = ShopgateSettings::getProductTaxClasses($module);
        $result['tax']['customer_tax_classes'] = array(
            array(
                'key'        => 'default',
                'is_default' => true,
            ),
        );

        if (version_compare(_PS_VERSION_, '1.4.0.4', '<=')) {
            $result['tax']['tax_rates'] = ShopgateSettings::getTaxRatesOldVersions($module);
            $result['tax']['tax_rules'] = ShopgateSettings::getTaxRulesOldVersions(
                $result['tax']['product_tax_classes'],
                $result['tax']['customer_tax_classes'],
                $result['tax']['tax_rates']
            );
        } else {
            $result['tax']['tax_rates'] = ShopgateSettings::getTaxRates($module);
            $result['tax']['tax_rules'] = ShopgateSettings::getTaxRules($module);
        }

        /**
         * allowed_shipping_countries and allowed_address_countries
         */
        $result['allowed_shipping_countries'] = array();
        $result['allowed_address_countries']  = array();

        $countryResultItems = array();
        $addressResultItems = array();

        foreach (ShopgateShipping::getDeliveryCountries($module->context->language->id, true, true) as $country) {
            if (isset($country['states']) && is_array($country['states'])) {
                $resultStates = array();
                foreach ($country['states'] as $state) {
                    $resultStates[] = $state['iso_code'];
                }
            } else {
                $resultStates = array(ShopgateShipping::CARRIER_CODE_ALL);
            }
            $countryResultItems[$country['iso_code']] = $resultStates;
        }

        foreach ($countryResultItems as $country => $state) {
            $item = array(
                'country' => $country,
                'state'   => $state,
            );

            $result['allowed_shipping_countries'][] = $item;
        }

        foreach (Country::getCountries($module->context->language->id, true) as $country) {
            if (isset($country['states']) && is_array($country['states'])) {
                $resultStates = array();
                foreach ($country['states'] as $state) {
                    $resultStates[] = $state['iso_code'];
                }
            } else {
                $resultStates = array(ShopgateShipping::CARRIER_CODE_ALL);
            }

            $addressResultItems[$country['iso_code']] = $resultStates;
        }

        foreach ($addressResultItems as $country => $state) {
            $item = array(
                'country' => $country,
                'state'   => $state,
            );

            $result['allowed_address_countries'][] = $item;
        }

        return $result;
    }
}
