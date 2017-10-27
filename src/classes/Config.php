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
class ShopgateConfigPrestashop extends ShopgateConfig
{
    /**
     * default plugin name
     */
    const DEFAULT_PLUGIN_NAME = 'prestashop';
    /**
     * default config identifier
     */
    const DEFAULT_CONFIG_NAME = 'SHOPGATE_CONFIG';

    /** @var int */
    protected $_langId;

    /**
     * init defaults
     */
    protected function initDefaults()
    {
        $this->plugin_name = self::DEFAULT_PLUGIN_NAME;
        $this->enable_ping = 1;

        $this->supported_fields_check_cart
            = array(
            'external_coupons',
            'shipping_methods',
            'items',
            'payment_methods',
            'customer',
        );

        $this->enable_redirect_keyword_update = 1;
        $this->enable_ping                    = 1;
        $this->enable_add_order               = 1;
        $this->enable_update_order            = 1;
        $this->enable_check_cart              = 1;
        $this->enable_check_stock             = 1;
        $this->enable_get_orders              = 1;
        $this->enable_get_debug_info          = 1;
        $this->enable_redeem_coupons          = 1;
        $this->enable_get_reviews             = 1;
        $this->enable_get_items               = 1;
        $this->enable_get_categories          = 1;
        $this->enable_get_settings            = 1;
        $this->enable_register_customer       = 1;
        $this->enable_get_customer            = 1;
        $this->enable_add_order               = 1;
        $this->enable_get_log_file            = 1;
        $this->enable_cron                    = 1;
        $this->enable_clear_log_file          = 1;
        $this->enable_clear_cache             = 1;
        $this->enable_get_settings            = 1;
        $this->enable_set_settings            = 1;
        $this->enable_mobile_website          = 1;
    }

    /**
     * create defaults / load from config
     *
     * @return bool|void
     */
    public function startup()
    {
        if (!Configuration::get(self::DEFAULT_CONFIG_NAME)) {
            $this->cleanupConfig();
        }

        $this->setFromConfig();

        return true;
    }

    /**
     * set by key
     *
     * @param $key
     * @param $value
     */
    public function setByKey($key, $value)
    {
        $this->$key = $value;
    }

    /**
     * @param $langId
     */
    public function setLangId($langId)
    {
        $this->_langId = $langId;
    }

    /**
     * store config
     */
    public function store()
    {
        $vars = array();
        foreach (get_object_vars($this) as $key => $value) {
            $vars[$key] = $value;
        }

        Configuration::updateValue(self::DEFAULT_CONFIG_NAME, base64_encode(serialize($vars)));
    }

    /**
     * set from config
     */
    protected function setFromConfig()
    {
        $storedConfig = unserialize(base64_decode(Configuration::get(self::DEFAULT_CONFIG_NAME)));

        /**
         * fix for old set shop numbers in config
         */
        if ($this->isSerialized($storedConfig['shop_number'])) {
            $shopNumbers = unserialize($storedConfig['shop_number']);
        } else {
            $shopNumbers = $storedConfig['shop_number'];
        }

        if (!is_array($shopNumbers)) {
            $shopNumbers           = array();
            $isoCode               = Language::getIsoById(Configuration::get('PS_LANG_DEFAULT'));
            $shopNumbers[$isoCode] = $storedConfig['shop_number'];
        }

        if (Tools::getValue('shop_number')) {
            foreach ($shopNumbers as $lang => $shopNumber) {
                if (Tools::getValue('shop_number') == $shopNumber) {
                    $storedConfig['shop_number'] = $shopNumber;
                    $storedConfig['language']    = $lang;
                    break;
                }
            }
        }

        if (is_array($storedConfig)) {
            foreach ($storedConfig as $key => $value) {
                $this->$key = $value;
            }
        }
    }

    /**
     * @param array $fieldList
     * @param bool  $validate
     */
    public function save(array $fieldList, $validate = true)
    {
        $this->store();
    }

    /**
     * init folders
     */
    public function initFolders()
    {
        $result = array();

        /**
         * tmp folder
         */
        $this->createFolder($this->getPathByShopNumber($this->getShopNumber()));
        $result['export_folder_path'] = $this->getPathByShopNumber($this->getShopNumber());

        /**
         * logs
         */
        $this->createFolder($this->getPathByShopNumber($this->getShopNumber(), 'logs'));
        $result['log_folder_path'] = $this->getPathByShopNumber($this->getShopNumber(), 'logs');

        /**
         * cache
         */
        $this->createFolder($this->getPathByShopNumber($this->getShopNumber(), 'cache'));
        $result['cache_folder_path'] = $this->getPathByShopNumber($this->getShopNumber(), 'cache');

        return $result;
    }

    /**
     * create folder by path
     *
     * @param      $path
     * @param int  $mode
     * @param bool $recursive
     *
     * @throws ShopgateLibraryException
     */
    protected function createFolder($path, $mode = 0777, $recursive = true)
    {
        if (!is_dir($path)) {
            try {
                mkdir($path, $mode, $recursive);
            } catch (ShopgateLibraryException $e) {
                throw new ShopgateLibraryException(
                    ShopgateLibraryException::CONFIG_READ_WRITE_ERROR,
                    sprintf('The folder "%s" could not be created.', $path)
                );
            }
        }
    }

    /**
     * create empty config
     */
    public function cleanupConfig()
    {
        $this->initDefaults();
        $this->store();
    }

    /**
     * returns the path by shop number and type
     *
     * @param int   $shopNumber
     * @param mixed $type
     *
     * @return string
     */
    public function getPathByShopNumber($shopNumber, $type = false)
    {
        $tempFolder = sprintf(SHOPGATE_BASE_DIR . DS . '%s' . DS . $shopNumber, 'temp');

        switch ($type) {
            case 'logs':
                $tempFolder = sprintf('%s/%s', $tempFolder, 'logs');
                break;
            case 'cache':
                $tempFolder = sprintf('%s/%s', $tempFolder, 'cache');
                break;
        }

        return $tempFolder;
    }

    /**
     * @return int|null
     */
    public function getShopNumber()
    {
        $shopNumbers = parent::getShopNumber();

        /**
         * hack for prestashop version lower 1.5 because the method migrateNewShopNumber will not called on module update function.
         * it´s only used until the configuration will saved again.
         */
        if (is_string($shopNumbers)) {
            if ($this->isSerialized(base64_decode($shopNumbers))) {
                $shopNumbers = unserialize(base64_decode($shopNumbers));
            } elseif ($this->isSerialized($shopNumbers)) {
                $shopNumbers = unserialize($shopNumbers);
            }
        }

        $isoCode = $this->_langId
            ? Language::getIsoById($this->_langId)
            : Language::getIsoById(Configuration::get('PS_LANG_DEFAULT'));

        return (is_array($shopNumbers) && isset($shopNumbers[$isoCode]))
            ? $shopNumbers[$isoCode]
            : $shopNumbers;
    }

    /**
     * Tests if an input is valid PHP serialized string.
     *
     * Checks if a string is serialized using quick string manipulation
     * to throw out obviously incorrect strings. Unserialize is then run
     * on the string to perform the final verification.
     *
     * Valid serialized forms are the following:
     * <ul>
     * <li>boolean: <code>b:1;</code></li>
     * <li>integer: <code>i:1;</code></li>
     * <li>double: <code>d:0.2;</code></li>
     * <li>string: <code>s:4:"test";</code></li>
     * <li>array: <code>a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}</code></li>
     * <li>object: <code>O:8:"stdClass":0:{}</code></li>
     * <li>null: <code>N;</code></li>
     * </ul>
     *
     * @author         Chris Smith <code+php@chris.cs278.org>
     * @copyright      Copyright (c) 2009 Chris Smith (http://www.cs278.org/)
     * @license        http://sam.zoy.org/wtfpl/ WTFPL
     *
     * @param        string $value  Value to test for serialized form
     * @param        mixed  $result Result of unserialize() of the $value
     *
     * @return        boolean            True if $value is serialized data, otherwise false
     */
    public function isSerialized($value, &$result = null)
    {
        // Bit of a give away this one
        if (!is_string($value)) {
            return false;
        }
        // Serialized false, return true. unserialize() returns false on an
        // invalid string or it could return false if the string is serialized
        // false, eliminate that possibility.
        if ($value === 'b:0;') {
            $result = false;

            return true;
        }
        $length = strlen($value);
        $end    = '';
        switch ($value[0]) {
            case 's':
                if ($value[$length - 2] !== '"') {
                    return false;
                }
            // no break
            case 'b':
            case 'i':
            case 'd':
                // This looks odd but it is quicker than isset()ing
                $end .= ';';
            // no break
            case 'a':
            case 'O':
                $end .= '}';
                if ($value[1] !== ':') {
                    return false;
                }
                switch ($value[2]) {
                    case 0:
                    case 1:
                    case 2:
                    case 3:
                    case 4:
                    case 5:
                    case 6:
                    case 7:
                    case 8:
                    case 9:
                        break;
                    default:
                        return false;
                }
            // no break
            case 'N':
                $end .= ';';
                if ($value[$length - 1] !== $end[0]) {
                    return false;
                }
                break;
            default:
                return false;
        }
        if (($result = @unserialize($value)) === false) {
            $result = null;

            return false;
        }

        return true;
    }

    /**
     * @throws PrestaShopDatabaseException
     */
    public function migrateNewShopNumber()
    {
        $db     = Db::getInstance(true);
        $query  = 'SELECT `id_configuration`, `value` FROM `' . _DB_PREFIX_ . str_replace(
                '`',
                '\`',
                pSQL(Configuration::$definition['table'])
            ) . '` WHERE `name` = "' . pSQL(self::DEFAULT_CONFIG_NAME) . '"';
        $result = $db->executeS($query, false);
        while ($row = $db->nextRow($result)) {
            if (!$data = @unserialize(base64_decode($row['value']))) {
                $data = @unserialize($row['value']);
            }

            $newShopNumber = array();

            if ($shopNumbers = @unserialize($data['shop_number'])) {
                foreach ($shopNumbers as $lang => $shopNumber) {
                    $newShopNumber[$lang] = $shopNumber;
                }
            } else {
                $newShopNumber[Language::getIsoById(Configuration::get('PS_LANG_DEFAULT'))] = $data['shop_number'];
            }
            $data['shop_number'] = $newShopNumber;
            $value               = base64_encode(serialize($data));

            $db->update(
                Configuration::$definition['table'],
                array(
                    'value' => $value,
                ),
                'id_configuration=' . $row['id_configuration']
            );
        }
    }
}
