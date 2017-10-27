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
class ShopgateItemsAbstract
{
    const PREFIX = 'BD';

    /** @var  ShopgatePluginPrestashop */
    protected $plugin;

    /** @var ShopGate */
    protected $module;

    /** @var ShopgateDb */
    protected $db;

    /**
     * @param ShopgatePluginPrestashop $plugin
     * @param ShopgateDb               $db
     */
    public function __construct($plugin, ShopgateDb $db)
    {
        $this->plugin = $plugin;
        $this->db     = $db;
        $this->module = new ShopGate();
    }

    /**
     * @return ShopgatePluginPrestashop
     */
    public function getPlugin()
    {
        return $this->plugin;
    }

    /**
     * @return ShopGate
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * @return ShopgateDb
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * @param float $price
     *
     * @return float
     */
    public function convertPrice($price)
    {
        if (Configuration::get('PS_CURRENCY_DEFAULT') != $this->plugin->getContext()->currency->id) {
            // Conversion rate of the default must not be 1.00 so we shouldn't convert the standard currency
            return (float)$this->plugin->getContext()->currency->conversion_rate * $price;
        }

        return (float)$price;
    }

    /**
     * @return ShopgateCouponHelper
     */
    public function getCouponHelper()
    {
        return new ShopgateCouponHelper();
    }
}
