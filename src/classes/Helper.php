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
class ShopgateHelper
{
    const DEFAULT_OFFER_LINKS_PATTERN = 'https://www.shopgate.com/%s/prestashop_offer';
    const DEFAULT_API_URL_PATTERN = '%s/modules/shopgate/api.php';
    const DEFAULT_YOUTUBE_URL_PATTERN = '//www.youtube.com/embed/%s?controls=0&showinfo=0&rel=0';
    const DEFAULT_YOUTUBE_ID    = 'I6UcmbGdZcw';
    const DEFAULT_YOUTUBE_ID_DE = 'z7EY_nakQDc';
    const DEFAULT_YOUTUBE_ID_PL = 'nx6d2L2J4y8';
    const DEFAULT_YOUTUBE_ID_FR = '0cbXcocbgkA';

    /**
     * returns the video links
     *
     * @param $context
     *
     * @return string
     */
    public static function getVideoLink($context)
    {
        switch ($context->language->iso_code) {
            case 'de':
                return ShopgateHelper::generateYoutubeLink(self::DEFAULT_YOUTUBE_ID_DE);
            case 'pl':
                return ShopgateHelper::generateYoutubeLink(self::DEFAULT_YOUTUBE_ID_PL);
            case 'fr':
                return ShopgateHelper::generateYoutubeLink(self::DEFAULT_YOUTUBE_ID_FR);
            default:
                return ShopgateHelper::generateYoutubeLink(self::DEFAULT_YOUTUBE_ID);
        }
    }

    /**
     * @param $context
     *
     * @return string
     */
    public static function getOfferLink($context)
    {
        switch ($context->language->iso_code) {
            case 'de':
                $country = 'de';
                break;
            case 'pl':
                $country = 'pl';
                break;
            case 'fr':
                $country = 'fr';
                break;
            default:
                $country = 'us';
        }

        return sprintf(self::DEFAULT_OFFER_LINKS_PATTERN, $country);
    }

    /**
     * @param $context
     *
     * @return string
     */
    public static function getApiUrl($context)
    {
        $shopModel = $context->shop;

        if (property_exists($shopModel, 'domain') && !empty($shopModel->domain)) {
            $path = $shopModel->domain;
        } elseif (property_exists($shopModel, 'physical_uri') && !empty($shopModel->physical_uri)) {
            $path = $shopModel->physical_uri;
        } elseif (property_exists($shopModel, 'virtual_uri') && !empty($shopModel->virtual_uri)) {
            $path = $shopModel->virtual_uri;
        } else {
            $path = _PS_BASE_URL_ . rtrim(__PS_BASE_URI__, '/');
        }

        $usingSecureMode = false;
        if (method_exists('Tools', 'usingSecureMode')) {
            $usingSecureMode = Tools::usingSecureMode();
        }

        if (strpos($path, 'http://') === false
            && strpos($path, 'https://') === false
        ) {
            $protocol = (Configuration::get('PS_SSL_ENABLED') || $usingSecureMode)
                ? 'https://'
                : 'http://';
            $path     = $protocol . $path;
        } elseif (strpos($path, 'https://') === false
            && (Configuration::get('PS_SSL_ENABLED') || $usingSecureMode)
        ) {
            $path = str_replace("http://", "https://", $path);
        }

        return sprintf(self::DEFAULT_API_URL_PATTERN, $path);
    }

    /**
     * @param $module
     *
     * @return array
     */
    public static function getEnvironments($module)
    {
        return array(
            'live'   => $module->l('Live'),
            'pg'     => $module->l('Playground'),
            'custom' => $module->l('Custom'),
        );
    }

    /**
     * @param $id
     *
     * @return string
     */
    protected static function generateYoutubeLink($id)
    {
        return sprintf(self::DEFAULT_YOUTUBE_URL_PATTERN, $id);
    }

    /**
     * @param array $parts
     *
     * @return string
     */
    public static function normalizePath($parts = array())
    {
        $path = '';
        foreach ($parts as $part) {
            $path .= $part;
        }

        $parts    = array();
        $path     = str_replace('\\', '/', $path);
        $path     = preg_replace('/\/+/', '/', $path);
        $segments = explode('/', $path);

        foreach ($segments as $segment) {
            if ($segment != '.') {
                $test = array_pop($parts);
                if (is_null($test)) {
                    $parts[] = $segment;
                } else {
                    if ($segment == '..') {
                        if ($test == '..') {
                            $parts[] = $test;
                        }

                        if ($test == '..' || $test == '') {
                            $parts[] = $segment;
                        }
                    } else {
                        $parts[] = $test;
                        $parts[] = $segment;
                    }
                }
            }
        }

        return implode('/', $parts);
    }

    /**
     * check table name
     *
     * @param $tableName
     *
     * @return bool
     */
    public static function checkTable($tableName)
    {
        foreach (Db::getInstance()->ExecuteS('SHOW TABLES') as $name) {
            if ($tableName == current($name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $needed
     *
     * @return array
     */
    public static function checkLoadedExtensions($needed = array())
    {
        $missing = array();

        foreach ($needed as $need) {
            if (!in_array($need, get_loaded_extensions())) {
                $missing[] = $need;
            }
        }

        return $missing;
    }

    /**
     * @return mixed|string
     */
    public static function calculateRedirect()
    {
        $shopgateConfig   = new ShopgateConfigPrestashop();
        $shopgateBuilder  = new ShopgateBuilder($shopgateConfig);
        $shopgateRedirect = $shopgateBuilder->buildRedirect();

        $controller = Tools::getValue('controller');

        /**
         * prepare controller for older versions
         */
        if (!$controller) {
            $fileInfo   = pathinfo($_SERVER['SCRIPT_NAME']);
            $controller = $fileInfo['filename'];
        }

        switch ($controller) {
            case 'category':
                return $shopgateRedirect->buildScriptCategory(Tools::getValue('id_category', 0));
            case 'product':
                return $shopgateRedirect->buildScriptItem(Tools::getValue('id_product', 0));
            case 'search':
                return $shopgateRedirect->buildScriptSearch(Tools::getValue('search_query', ''));
            case 'index':
                return $shopgateRedirect->buildScriptShop();
            default:
                if ($manufacturerId = Tools::getValue('id_manufacturer')) {
                    /** @var ManufacturerCore $manufacturer */
                    $manufacturer = new Manufacturer($manufacturerId);

                    return $shopgateRedirect->buildScriptBrand($manufacturer->name);
                }

                return $shopgateRedirect->buildScriptDefault();
        }
    }

    /**
     * @param ShopgateOrderItem $item
     *
     * @return array
     */
    public static function getProductIdentifiers(ShopgateOrderItem $item)
    {
        if (Tools::substr($item->getItemNumber(), 0, 2) == ShopgatePluginPrestashop::PREFIX) {
            return explode('-', Tools::substr($item->getItemNumber(), Tools::strlen(ShopgatePluginPrestashop::PREFIX)));
        } elseif (strpos($item->getItemNumber(), '-') !== false) {
            return explode('-', $item->getItemNumber());
        } else {
            return array($item->getItemNumber(), null);
        }
    }
}
