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
class ShopgateItemsCustomerExportJson extends ShopgateItemsCustomer
{
    /**
     * @param $user
     * @param $pass
     *
     * @return ShopgateCustomer
     * @throws ShopgateLibraryException
     */
    public function getCustomer($user, $pass)
    {
        $customerId = $this->getCustomerIdByEmailAndPassword($user, $pass);

        if (!$customerId) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::PLUGIN_WRONG_USERNAME_OR_PASSWORD,
                'Username or password is incorrect'
            );
        }

        /** @var CustomerCore $customer */
        $customer = new Customer($customerId);

        $shopgateCustomer = new ShopgateCustomer();

        $shopgateCustomer->setCustomerId($customer->id);
        $shopgateCustomer->setCustomerNumber($customer->id);
        $shopgateCustomer->setFirstName($customer->firstname);
        $shopgateCustomer->setLastName($customer->lastname);
        $shopgateCustomer->setGender($this->mapGender($customer->id_gender));
        $shopgateCustomer->setBirthday($customer->birthday);
        $shopgateCustomer->setMail($customer->email);
        $shopgateCustomer->setNewsletterSubscription($customer->newsletter);
        $shopgateCustomer->setCustomerToken(ShopgateCustomerPrestashop::getToken($customer));

        $addresses = array();

        foreach ($customer->getAddresses($this->getPlugin()->getLanguageId()) as $address) {
            $addressItem = new ShopgateAddress();

            $addressItem->setId($address['id_address']);
            $addressItem->setFirstName($address['firstname']);
            $addressItem->setLastName($address['lastname']);
            $addressItem->setCompany($address['company']);
            $addressItem->setStreet1($address['address1']);
            $addressItem->setStreet2($address['address2']);
            $addressItem->setCity($address['city']);
            $addressItem->setZipcode($address['postcode']);
            $addressItem->setCountry(Country::getIsoById($address['id_country']));
            $addressItem->setState(State::getIdByIso($address['id_state']));
            $addressItem->setPhone($address['phone']);
            $addressItem->setMobile($address['phone_mobile']);

            if ($address['alias'] == 'Default invoice address') {
                $addressItem->setAddressType(ShopgateAddress::INVOICE);
            } elseif ($address['alias'] == 'Default delivery address') {
                $addressItem->setAddressType(ShopgateAddress::DELIVERY);
            } else {
                $addressItem->setAddressType(ShopgateAddress::BOTH);
            }

            $addresses[] = $addressItem;
        }

        $shopgateCustomer->setAddresses($addresses);

        /**
         * customer groups
         */
        $customerGroups = array();

        if (is_array($customer->getGroups())) {
            foreach ($customer->getGroups() as $customerGroupId) {
                $groupItem = new Group(
                    $customerGroupId,
                    $this->getPlugin()->getLanguageId(),
                    $this->getPlugin()->getContext()->shop->id
                        ? $this->getPlugin()->getContext()->shop->id
                        : false
                );

                $group = new ShopgateCustomerGroup();
                $group->setId($groupItem->id);
                $group->setName($groupItem->name);
                $customerGroups[] = $group;
            }
        }

        $shopgateCustomer->setCustomerGroups($customerGroups);

        return $shopgateCustomer;
    }
}
