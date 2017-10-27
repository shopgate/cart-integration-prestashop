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

if (version_compare(_PS_VERSION_, '1.5.0', '<')) {
    require_once(_PS_MODULE_DIR_ . 'shopgate' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'ModObjectModelCustomer.php');
} else {
    require_once(_PS_MODULE_DIR_ . 'shopgate' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'ModObjectModelCustomerDummy.php');
}

class ShopgateCustomerPrestashop extends ShopgateModObjectModelCustomer
{
    const DEFAULT_CUSTOMER_ADDRESS_IDENTIFIER_INVOICE  = 'invoice';
    const DEFAULT_CUSTOMER_ADDRESS_IDENTIFIER_DELIVERY = 'delivery';

    public $id_shopgate_customer;

    public $id_customer;

    public $customer_token;

    public $date_add;

    public static $definition
        = array(
            'table'   => 'shopgate_customer',
            'primary' => 'id_shopgate_customer',
            'fields'  => array(
                'id_customer'    => array(
                    'type'     => self::TYPE_INT,
                    'validate' => 'isUnsignedInt',
                    'required' => true,
                ),
                'customer_token' => array(
                    'type'     => self::TYPE_STRING,
                    'required' => true,
                ),
                'date_add'       => array('type' => self::TYPE_DATE),
            ),
        );

    public function getFields()
    {
        if (!parent::validateFields()) {
            ShopgateLogger::getInstance()->log(
                'Validation for shopgate database fields invalid',
                ShopgateLogger::LOGTYPE_ERROR
            );
        }

        $fields                         = array();
        $fields['id_shopgate_customer'] = (int)($this->id_shopgate_customer);
        $fields['id_customer']          = (int)($this->id_customer);
        $fields['customer_token']       = $this->customer_token;
        $fields['date_add']             = $this->date_add;

        return $fields;
    }

    /**
     * @param CustomerCore $customer
     *
     * @return mixed
     * @throws PrestaShopExceptionCustomerCore
     */
    public static function getToken($customer)
    {
        if ($customer->validateFields() && !ShopgateCustomerPrestashop::hasCustomerToken($customer->id)) {
            $customerItem                 = new ShopgateCustomerPrestashop();
            $customerItem->customer_token = md5($customer->id . $customer->email . microtime());
            $customerItem->id_customer    = $customer->id;
            $customerItem->add();
        }

        if ($id = ShopgateCustomerPrestashop::hasCustomerToken($customer->id)) {
            $shopgateCustomer = new ShopgateCustomerPrestashop($id);

            return $shopgateCustomer->customer_token;
        }

        return null;
    }

    /**
     * @param $customerId
     *
     * @return mixed
     */
    public static function hasCustomerToken($customerId)
    {
        return ShopgateCustomerPrestashop::getIdShopgateCustomerByFilter('id_customer', $customerId);
    }

    /**
     * @param string $token
     *
     * @return mixed
     */
    public function getCustomerByToken($token)
    {
        $shopgateCustomerId = ShopgateCustomerPrestashop::getIdShopgateCustomerByFilter('customer_token', $token);
        $shopgateCustomer   = new ShopgateCustomerPrestashop($shopgateCustomerId);

        return new Customer($shopgateCustomer->id_customer);
    }

    /**
     * @param $key
     * @param $value
     *
     * @return mixed
     */
    protected static function getIdShopgateCustomerByFilter($key, $value)
    {
        if (version_compare(_PS_VERSION_, '1.5.0', '<')) {
            $query
                = 'SELECT id_shopgate_customer
						FROM ' . _DB_PREFIX_ . 'shopgate_customer
						WHERE ' . pSQL($key) . '=\'' . pSQL($value) . '\'';

            return Db::getInstance()->getValue($query);
        }

        $query = new DbQuery();
        $query->select(ShopgateCustomerPrestashop::$definition['primary']);
        $query->from(ShopgateCustomerPrestashop::$definition['table']);
        $query->where($key . ' = \'' . pSQL($value) . '\'');

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
    }
}
