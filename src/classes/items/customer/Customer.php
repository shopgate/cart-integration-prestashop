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
class ShopgateItemsCustomer extends ShopgateItemsAbstract
{
    /**
     * @var array
     */
    protected $gender = array(
        ShopgateCustomer::MALE   => 1,
        ShopgateCustomer::FEMALE => 2,
    );

    /**
     * @var array
     */
    public static $validationAddressFields = array(
        'id_customer',
        'company',
        'lastname',
        'firstname',
        'vat_number',
        'address1',
        'address2',
        'postcode',
        'city',
        'phone',
        'phone_mobile',
    );

    /**
     * @param $email
     * @param $password
     *
     * @return int
     */
    public function getCustomerIdByEmailAndPassword($email, $password)
    {
        /** @var CustomerCore $customer */
        $customer = new Customer();
        /** @var CustomerCore $authentication */
        $authentication = $customer->getByEmail(trim($email), trim($password));

        return $authentication->id;
    }

    /**
     * shopgate / prestashop :-(
     *
     * @param $value
     *
     * @return bool|int|string
     */
    public function mapGender($value)
    {
        foreach ($this->gender as $key => $val) {
            if (ctype_digit($value)) {
                if ($val == $value) {
                    return $key;
                }
            } elseif ($key == $value) {
                return $val;
            }
        }

        return false;
    }

    /**
     * @param mixed $addressOne
     * @param mixed $addressTwo
     *
     * @return bool
     */
    public function compareAddresses($addressOne, $addressTwo)
    {
        $addressOne = (object)$addressOne;
        $addressTwo = (object)$addressTwo;
        foreach (ShopgateItemsCustomer::$validationAddressFields as $field) {
            if ($addressOne->$field != $addressTwo->$field) {
                return false;
            }
        }

        return true;
    }
}
