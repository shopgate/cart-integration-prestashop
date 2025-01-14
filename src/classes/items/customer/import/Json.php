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
class ShopgateItemsCustomerImportJson extends ShopgateItemsCustomer
{
    /**
     * @param string           $user
     * @param string           $pass
     * @param ShopgateCustomer $customer
     *
     * @throws ShopgateLibraryException
     */
    public function registerCustomer($user, $pass, ShopgateCustomer $customer)
    {
        if (!Validate::isEmail($user)) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::PLUGIN_REGISTER_CUSTOMER_ERROR,
                'E-mail Address validation error',
                true
            );
        }

        if ($pass && !Validate::isAcceptablePasswordScore($pass)) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::PLUGIN_REGISTER_CUSTOMER_ERROR,
                'Password validation error',
                true
            );
        }

        /** @var CustomerCore | Customer $customerModel */
        $customerModel = new Customer();

        if ($customerModel->getByEmail($user)) {
            throw new ShopgateLibraryException(ShopgateLibraryException::REGISTER_USER_ALREADY_EXISTS);
        }

        $customerModel->optin      = 0;
        $customerModel->active     = 1;
        $customerModel->lastname   = $customer->getLastName();
        $customerModel->firstname  = $customer->getFirstName();
        $customerModel->email      = $user;
        $customerModel->id_gender  = $this->mapGender($customer->getGender());
        $customerModel->birthday   = $customer->getBirthday();
        $customerModel->newsletter = $customer->getNewsletterSubscription();

        if (version_compare(_PS_VERSION_, '8', '>')) {
            $customerModel->setWsPasswd($pass);
        } else {
            $customerModel->passwd = Tools::encrypt($pass);
        }

        $shopgateCustomFieldsHelper = new ShopgateCustomFieldsHelper();
        $shopgateCustomFieldsHelper->saveCustomFields($customerModel, $customer->getCustomFields());

        $validateMessage = $customerModel->validateFields(false, true);

        if ($validateMessage !== true) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::REGISTER_FAILED_TO_ADD_USER,
                $validateMessage,
                true
            );
        }

        $customerModel->save();

        /**
         * addresses
         */
        $customerAddresses = array();
        foreach ($customer->getAddresses() as $address) {
            try {
                $prestashopCustomerAddress = $this->createPrestashopAddressObject($address, $customerModel);
                if (!$this->findAddress($prestashopCustomerAddress, $customerAddresses)) {
                    $prestashopCustomerAddress->id = $this->createAddress($address, $customerModel);
                    $customerAddresses[]           = $prestashopCustomerAddress;
                }
            } catch (ShopgateLibraryException $ex) {
                $customerModel->delete();
                throw $ex;
            }
        }

        return $customerModel->id;
    }

    /**
     * @param ShopgateAddress $address
     * @param Customer        $customer
     *
     * @return int
     * @throws ShopgateLibraryException
     */
    public function createAddress(ShopgateAddress $address, $customer)
    {
        $addressItem = $this->createPrestashopAddressObject($address, $customer);

        $validateMessage = $addressItem->validateFields(false, true);

        if ($validateMessage !== true) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::REGISTER_FAILED_TO_ADD_USER,
                $validateMessage,
                true
            );
        }

        $addressItem->save();

        return $addressItem->id;
    }

    /**
     * @param ShopgateAddress $address
     * @param Customer        $customer
     *
     * @return AddressCore
     * @throws ShopgateLibraryException
     */
    public function createPrestashopAddressObject(ShopgateAddress $address, $customer)
    {
        /** @var AddressCore | Address $addressModel */
        $addressItem = new Address();

        $addressItem->id_customer = (int)$customer->id;
        $addressItem->lastname    = $address->getLastName();
        $addressItem->firstname   = $address->getFirstName();

        if ($address->getCompany()) {
            $addressItem->company = $address->getCompany();
        }

        $addressItem->address1 = $address->getStreet1();

        if ($address->getStreet2()) {
            $addressItem->address2 = $address->getStreet2();
        }

        $addressItem->city     = $address->getCity();
        $addressItem->postcode = $address->getZipcode();

        if (!Validate::isLanguageIsoCode($address->getCountry())) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::REGISTER_FAILED_TO_ADD_USER,
                'invalid country code: ' . $address->getCountry(),
                true
            );
        }

        $addressItem->id_country = Country::getByIso($address->getCountry());
        $country                 = new Country($addressItem->id_country);

        /**
         * prepare states
         */
        $stateParts = explode('-', $address->getState() ?? '');

        if (count($stateParts) == 2) {
            $address->setState($stateParts[1]);
        };

        if ($country->contains_states && $address->getState() && !Validate::isStateIsoCode($address->getState())) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::REGISTER_FAILED_TO_ADD_USER,
                'invalid state code: ' . $address->getState(),
                true
            );
        } elseif ($country->contains_states) {
            $addressItem->id_state = State::getIdByIso($address->getState());
        }

        $addressItem->alias        = $this->getModule()->l('Default');
        $addressItem->phone        = $address->getPhone();
        $addressItem->phone_mobile = $address->getMobile();

        $shopgateCustomFieldsHelper = new ShopgateCustomFieldsHelper();
        $shopgateCustomFieldsHelper->saveCustomFields($addressItem, $address->getCustomFields());

        return $addressItem;
    }

    /**
     * @param AddressCore $needleAddress
     * @param array[]     $customerAddresses
     *
     * @return AddressCore|null - null in case there is no match
     */
    public function findAddress($needleAddress, array $customerAddresses)
    {
        if (is_array($customerAddresses)) {
            foreach ($customerAddresses as $customerAddress) {
                if ($this->compareAddresses($customerAddress, $needleAddress)) {
                    return $customerAddress;
                }
            }
        }

        return null;
    }
}
