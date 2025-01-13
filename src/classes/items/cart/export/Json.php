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
class ShopgateItemsCartExportJson extends ShopgateItemsCart
{
    /**
     * default dummy first name
     */
    const DEFAULT_CUSTOMER_FIRST_NAME = 'shopgate';
    /**
     * default dummy last name
     */
    const DEFAULT_CUSTOMER_LAST_NAME = 'shopgate';
    /**
     * default dummy email
     */
    const DEFAULT_CUSTOMER_EMAIL = 'example@shopgate.com';
    /**
     * default dummy password
     */
    const DEFAULT_CUSTOMER_PASSWD = '123shopgate';
    /**
     * default dummy alias
     */
    const DEFAULT_ADDRESS_ALIAS = 'shopgate_check_cart';
    /**
     * default check stock qty
     */
    const DEFAULT_QTY_TO_CHECK = 1;

    /**
     * @var Address
     */
    protected $prestashopDeliveryAddress;

    /**
     * @var Address
     */
    protected $prestashopInvoiceAddress;

    /**
     * @var bool
     */
    protected $isDummyCustomer = false;

    /**
     * @param ShopgateCart $cart
     *
     * @return array
     */
    public function checkStock(ShopgateCart $cart)
    {
        $result = array();

        foreach ($cart->getItems() as $item) {
            $cartItem = new ShopgateCartItem();
            $cartItem->setItemNumber($item->getItemNumber());

            list($productId, $attributeId) = ShopgateHelper::getProductIdentifiers($item);

            /** @var ProductCore $product */
            if (version_compare(_PS_VERSION_, '1.5.2.0', '<')) {
                $product = new BWProduct($productId, true, $this->getPlugin()->getLanguageId());
            } else {
                $product = new Product($productId, $this->getPlugin()->getLanguageId());
            }

            if (empty($attributeId) && !empty($productId) && $product->hasAttributes()) {
                $result[] = $cartItem;
                continue;
            }

            $product->loadStockData();

            /**
             * validate attributes
             */
            if ($product->hasAttributes()) {
                $invalidAttribute = false;
                $message          = '';

                if (!$attributeId) {
                    $cartItem->setError(ShopgateLibraryException::UNKNOWN_ERROR_CODE);
                    $cartItem->setErrorText('attributeId required');
                    $message          = 'attributeId required';
                    $invalidAttribute = true;
                } else {
                    $validAttributeId = false;

                    if (version_compare(_PS_VERSION_, '1.5.0', '<')) {
                        $attributeIds = BWProduct::getProductAttributesIds($productId);
                    } else {
                        $attributeIds = $product->getProductAttributesIds($productId, true);
                    }

                    foreach ($attributeIds as $attribute) {
                        if ($attributeId == $attribute['id_product_attribute']) {
                            $validAttributeId = true;
                            continue;
                        }
                    }

                    if (!$validAttributeId) {
                        $invalidAttribute = true;
                        $message          = 'invalid attributeId';
                    }
                }

                if ($invalidAttribute) {
                    $cartItem->setError(ShopgateLibraryException::UNKNOWN_ERROR_CODE);
                    $cartItem->setErrorText($message);
                    $result[] = $cartItem;
                    continue;
                }
            }

            if ($product->id) {
                if (version_compare(_PS_VERSION_, '1.4.5.1', '<')) {
                    $quantity = Product::getQuantity($product->id, $attributeId);
                } elseif (version_compare(_PS_VERSION_, '1.5.0', '<')) {
                    $quantity = $product->getStockAvailable();
                } else {
                    $quantity = StockAvailable::getQuantityAvailableByProduct(
                        $productId,
                        $attributeId,
                        $this->getPlugin()->getContext()->shop->id
                    );
                }

                $cartItem->setStockQuantity($quantity);
                $cartItem->setIsBuyable(
                    $product->available_for_order
                    && ($attributeId
                        ? Attribute::checkAttributeQty(
                            $attributeId,
                            ShopgateItemsCartExportJson::DEFAULT_QTY_TO_CHECK
                        )
                        : $product->checkQty(ShopgateItemsCartExportJson::DEFAULT_QTY_TO_CHECK))
                    || Product::isAvailableWhenOutOfStock($product->out_of_stock)
                        ? 1
                        : 0
                );
            } else {
                $cartItem->setError(ShopgateLibraryException::CART_ITEM_PRODUCT_NOT_FOUND);
                $cartItem->setErrorText(ShopgateLibraryException::getMessageFor($cartItem->getError()));
            }

            $result[] = $cartItem;
        }

        return $result;
    }

    /**
     * @param ShopgateCart                   $shopgateCart
     * @param ShopgateContextHelper          $shopgateContextHelper
     * @param ShopgateHelperCouponValidation $couponValidation
     *
     * @return array
     */
    public function checkCart(
        ShopgateCart $shopgateCart,
        ShopgateContextHelper $shopgateContextHelper,
        ShopgateHelperCouponValidation $couponValidation
    ) {
        if ($shopgateCart->getExternalCustomerId()) {
            $customer = $this->getLoggedInCustomer($shopgateCart->getExternalCustomerId());
        } else {
            $customer = $this->getCustomerDummy();
        }

        $shopgateContextHelper->setCustomer($customer);
        $shopgateContextHelper->setCartCustomerId($customer->id);

        if ($shopgateCart->getDeliveryAddress()) {
            $this->prestashopDeliveryAddress = $this->convertShopgateDeliveryAddress(
                $shopgateCart,
                $shopgateContextHelper
            );
        }

        if ($shopgateCart->getInvoiceAddress()) {
            $this->prestashopInvoiceAddress = $this->convertShopgateInvoiceAddress(
                $shopgateCart,
                $shopgateContextHelper
            );
        }

        $prestashopCarrierId = $this->getPrestashopCarrierId($shopgateCart);
        $shopgateContextHelper->saveCartCarrierId($prestashopCarrierId);

        /**
         * don't change the method execution order
         */
        return array(
            'items'            => $this->addItems(
                $shopgateCart,
                $shopgateContextHelper,
                $this->prestashopDeliveryAddress
            ),
            'external_coupons' => $couponValidation->getValidatedCoupons(
                $shopgateCart->getExternalCoupons(),
                $this->extractCarrierId($shopgateCart)
            ),
            'currency'         => $shopgateContextHelper->getCurrencyIsoCode(),
            'customer'         => $this->getCustomerGroups($shopgateContextHelper->getCustomer()),
            'shipping_methods' => $this->getCarriers($this->prestashopDeliveryAddress, $shopgateContextHelper),
            'payment_methods'  => $this->getValidPaymentMethods($shopgateContextHelper),
        );
    }

    /**
     * @param ShopgateCartBase $shopgateCart
     *
     * @return int|null
     */
    private function extractCarrierId(ShopgateCartBase $shopgateCart)
    {
        if (!$shopgateCart->getShippingInfos()) {
            return null;
        }

        // suppressing the notice if string is not unserializable, since that is expected
        $apiResponse = @unserialize($shopgateCart->getShippingInfos()->getApiResponse());
        if (empty($apiResponse) || !is_array($apiResponse)) {
            return null;
        }

        return (!empty($apiResponse['carrierId']))
            ? $apiResponse['carrierId']
            : null;
    }

    /**
     * We try to emulate the payment method selection in the best possible way.
     *
     * the logic is copied, mixed and adapted from version 1.4.2.5 and 1.6.0.9
     *
     * @param ShopgateContextHelper $shopgateContextHelper
     *
     * @return array
     */
    protected function getValidPaymentMethods(ShopgateContextHelper $shopgateContextHelper)
    {
        $paymentMethods = array();
        if (version_compare(_PS_VERSION_, '1.5.0', '<')) {
            global $cookie;
            $cookie->id_customer = $shopgateContextHelper->getCartCustomerId();
        }

        if (
            !$shopgateContextHelper->getCartCustomerId()
            || !Customer::customerIdExistsStatic($shopgateContextHelper->getCartCustomerId())
            || Customer::isBanned($shopgateContextHelper->getCartCustomerId())
        ) {
            // customer must be assigned, exist and not banned
            return $paymentMethods;
        }
        $address_delivery = new Address($shopgateContextHelper->getCartDeliveryAddressId());

        $address_invoice = $shopgateContextHelper->getCartDeliveryAddressId()
        == $shopgateContextHelper->getCartInvoiceAddressId()
            ? $address_delivery
            : new Address($shopgateContextHelper->getCartInvoiceAddressId());

        if (
            !$shopgateContextHelper->getCartDeliveryAddressId()
            || !$shopgateContextHelper->getCartInvoiceAddressId()
            || !Validate::isLoadedObject($address_delivery)
            || !Validate::isLoadedObject($address_invoice)
            || $address_invoice->deleted
            || $address_delivery->deleted
        ) {
            // the invoice and delivery adress must be valid
            return $paymentMethods;
        }

        if (!$shopgateContextHelper->getCartCurrencyId()) {
            // A currency must be selected
            return $paymentMethods;
        }

        $hookArgs = array(
            'cookie' => $shopgateContextHelper->getCookie(),
            'cart'   => $shopgateContextHelper->getCart(),
        );

        $HookHelper              = new HookHelper();
        $generatedPaymentMethods = $HookHelper->hook($hookArgs, $shopgateContextHelper->getContext());

        foreach ($generatedPaymentMethods as $generatedPaymentMethod) {
            $paymentMethod = new ShopgatePaymentMethod();
            $paymentMethod->setId($generatedPaymentMethod);
            $paymentMethods[] = $paymentMethod;
        }

        return $paymentMethods;
    }

    /**
     * @param Customer $prestashopCustomer
     *
     * @return ShopgateCartCustomer
     */
    protected function getCustomerGroups($prestashopCustomer)
    {
        $shopgateCartCustomer = new ShopgateCartCustomer();

        if ($prestashopCustomer instanceof Customer) {
            $shopgateCartCustomerGroups = array();
            foreach ($prestashopCustomer->getGroups() as $prestashopCustomerGroupId) {
                $customerGroup = new ShopgateCartCustomerGroup();
                $customerGroup->setId($prestashopCustomerGroupId);
                $shopgateCartCustomerGroups[] = $customerGroup;
            }

            if (empty($shopgateCartCustomerGroups)) {
                $shopgateCartCustomerGroups[] = $this->getDefaultPrestashopCustomerGroup();
            }

            $shopgateCartCustomer->setCustomerGroups($shopgateCartCustomerGroups);
        }

        return $shopgateCartCustomer;
    }

    /**
     * @return ShopgateCartCustomerGroup
     */
    public function getDefaultPrestashopCustomerGroup()
    {
        $customerGroup = new ShopgateCartCustomerGroup();
        $customerGroup->setId($this->getDefaultCustomerGroupId());

        return $customerGroup;
    }

    /**
     * @param ShopgateCart          $shopgateCart
     * @param ShopgateContextHelper $shopgateContextHelper
     * @param Address | null        $prestashopCartDeliveryAddress
     *
     * @return array
     */
    protected function addItems(
        ShopgateCart $shopgateCart,
        ShopgateContextHelper $shopgateContextHelper,
        $prestashopCartDeliveryAddress
    ) {
        $resultItems = array();

        foreach ($shopgateCart->getItems() as $item) {
            list($productId, $attributeId) = ShopgateHelper::getProductIdentifiers($item);

            /** @var ProductCore $product */
            $product = new Product($productId);

            $resultItem = new ShopgateCartItem();
            $resultItem->setItemNumber($item->getItemNumber());
            $resultItem->setStockQuantity($product->getQuantity($product->id, $attributeId));

            $unitAmount        = $product->getPrice(false, $attributeId, 6, null, false, true, $item->getQuantity());
            $unitAmountWithTax = $product->getPrice(true, $attributeId, 6, null, false, true, $item->getQuantity());

            $resultItem->setUnitAmount($unitAmount);
            $resultItem->setUnitAmountWithTax($unitAmountWithTax);

            $resultItem->setOptions($item->getOptions());
            $resultItem->setAttributes($item->getAttributes());
            $resultItem->setInputs($item->getInputs());

            /**
             * validate product
             */
            if (!$this->validateProduct($product, $attributeId)) {
                $this->addItemException(
                    $resultItem,
                    ShopgateLibraryException::CART_ITEM_PRODUCT_NOT_FOUND,
                    sprintf('ProductId #%s AttributeId #%s', $productId, $attributeId)
                );
                $resultItems[] = $resultItem;
                continue;
            }

            $addItemResult = $shopgateContextHelper->updateCartQuantity(
                $item->getQuantity(),
                $productId,
                $attributeId,
                ($prestashopCartDeliveryAddress && $prestashopCartDeliveryAddress->id)
                    ? $prestashopCartDeliveryAddress->id
                    : 0
            );
            if ($addItemResult != 1) {
                $resultItem->setIsBuyable(false);
                $resultItem->setQtyBuyable($product->getQuantity($productId, $attributeId));

                /**
                 * add error
                 */
                switch ($addItemResult) {
                    case -1:
                        $resultItem->setQtyBuyable(
                            $attributeId
                                ? (int)Attribute::getAttributeMinimalQty($attributeId)
                                : (int)$product->minimal_quantity
                        );

                        $minimalQuantity = ($attributeId)
                            ? (int)Attribute::getAttributeMinimalQty($attributeId)
                            : (int)$product->minimal_quantity;

                        $this->addItemException(
                            $resultItem,
                            ShopgateLibraryException::CART_ITEM_REQUESTED_QUANTITY_UNDER_MINIMUM_QUANTITY,
                            sprintf(Tools::displayError('You must add %d minimum quantity'), $minimalQuantity)
                        );
                        break;
                    default:
                        $this->addItemException(
                            $resultItem,
                            ShopgateLibraryException::CART_ITEM_REQUESTED_QUANTITY_NOT_AVAILABLE,
                            Tools::displayError('There isn\'t enough product in stock.')
                        );
                        break;
                }
            } else {
                $resultItem->setIsBuyable(true);
                $resultItem->setQtyBuyable((int)$item->getQuantity());
            }

            $resultItems[] = $resultItem;
        }

        return $resultItems;
    }

    /**
     * @param ProductCore $product
     * @param int         $attributeId
     *
     * @return bool
     */
    protected function validateProduct(ProductCore $product, $attributeId)
    {
        if (Validate::isLoadedObject($product)) {
            if ($attributeId) {
                if (version_compare(_PS_VERSION_, '1.5.0', '<')) {
                    $attributeIds = BWProduct::getProductAttributesIds($product->id);
                } else {
                    $attributeIds = $product->getProductAttributesIds($product->id, true);
                }

                foreach ($attributeIds as $id) {
                    if ($id['id_product_attribute'] == $attributeId) {
                        return true;
                    }
                }

                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * create carriers
     *
     * @param Address               $prestashopCartDeliveryAddress
     * @param ShopgateContextHelper $shopgateContextHelper
     *
     * @return array
     *
     * @throws ShopgateLibraryException
     */
    protected function getCarriers($prestashopCartDeliveryAddress, ShopgateContextHelper $shopgateContextHelper)
    {
        $resultsCarrier   = array();
        $mobileCarrierUse = unserialize(base64_decode(Configuration::get('SG_MOBILE_CARRIER')));

        if (!$prestashopCartDeliveryAddress) {
            return $resultsCarrier;
        }

        $carrierList = version_compare(_PS_VERSION_, '1.5.0.1', '<')
            ? Carrier::getCarriersForOrder(
                Address::getZoneById($prestashopCartDeliveryAddress->id),
                $shopgateContextHelper->getCustomer()->getGroups(),
                $shopgateContextHelper->getCart()
            )
            : $shopgateContextHelper->getCart()->simulateCarriersOutput(null, true);

        foreach ($carrierList as $carrier) {
            $realCarrierId = version_compare(_PS_VERSION_, '1.5.0.1', '<')
                ? $carrier['id_carrier']
                : rtrim($shopgateContextHelper->getCart()->desintifier($carrier['id_carrier']), ',');

            /** @var CarrierCore $prestashopCarrier */
            $prestashopCarrier = new Carrier($realCarrierId, $shopgateContextHelper->getLanguageId());
            $taxRulesGroup     = new TaxRulesGroup($prestashopCarrier->getIdTaxRulesGroup($shopgateContextHelper->getContext()));
            $resultCarrier     = new ShopgateShippingMethod();

            /**
             * Check is defined as mobile carrier
             */
            if (is_array($mobileCarrierUse)) {
                $carrierIdList = explode(',', $realCarrierId);
                foreach ($carrierIdList as $carrierId) {
                    if (empty($mobileCarrierUse[$carrierId])) {
                        continue;
                    }
                }
            }

            $resultCarrier->setId($realCarrierId);
            $resultCarrier->setTitle($carrier['name']);
            $resultCarrier->setDescription($carrier['delay']);
            $resultCarrier->setSortOrder($carrier['position'] ?? 1);
            $resultCarrier->setAmount($carrier['price_tax_exc']);
            $resultCarrier->setAmountWithTax($carrier['price']);
            $resultCarrier->setTaxClass($taxRulesGroup->name);

            if (version_compare(_PS_VERSION_, '1.5.0', '<')) {
                $carrierTax = Tax::getCarrierTaxRate($prestashopCarrier->id, $prestashopCartDeliveryAddress->id);
            } else {
                $carrierTax = $prestashopCarrier->getTaxesRate($prestashopCartDeliveryAddress);
            }

            $resultCarrier->setTaxPercent($carrierTax);
            $resultCarrier->setInternalShippingInfo(serialize(array('carrierId' => $realCarrierId)));

            $resultsCarrier[] = $resultCarrier;
        }

        return $resultsCarrier;
    }

    /**
     * @param int $customerId
     *
     * @return Customer
     *
     * @throws ShopgateLibraryException in case the customer couldn't be loaded
     */
    public function getLoggedInCustomer($customerId)
    {
        $customer         = new Customer($customerId);
        $customer->logged = 1;

        if (!Validate::isLoadedObject($customer)) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::UNKNOWN_ERROR_CODE,
                sprintf(
                    'Customer with id #%s not found',
                    $customerId
                ) .
                true,
                false
            );
        }

        return $customer;
    }

    /**
     * @param ShopgateAddress $shopgateAddress
     *
     * @return Address
     * @throws ShopgateLibraryException
     */
    protected function createAddress(ShopgateAddress $shopgateAddress)
    {
        /** @var AddressCore $prestashopAddress */
        $prestashopAddress = new Address();

        /** @var CountryCore $country */
        $country = $this->getCountryByIsoCode($shopgateAddress->getCountry());

        $prestashopAddress->id_country   = $country->id;
        $prestashopAddress->alias        = self::DEFAULT_ADDRESS_ALIAS;
        $prestashopAddress->firstname    = $shopgateAddress->getFirstName();
        $prestashopAddress->lastname     = $shopgateAddress->getLastName();
        $prestashopAddress->address1     = $shopgateAddress->getStreet1();
        $prestashopAddress->postcode     = $shopgateAddress->getZipcode();
        $prestashopAddress->city         = $shopgateAddress->getCity();
        $prestashopAddress->country      = $shopgateAddress->getCountry();
        $prestashopAddress->phone        = $shopgateAddress->getPhone()
            ? $shopgateAddress->getPhone()
            : 1;
        $prestashopAddress->phone_mobile = $shopgateAddress->getMobile()
            ? $shopgateAddress->getMobile()
            : 1;

        /**
         * check is state iso code available
         */
        if ($shopgateAddress->getState() != '' && $country->contains_states) {
            $prestashopAddress->id_state = $this->getStateIdByIsoCode($shopgateAddress->getState());
        }

        $prestashopAddress->company = $shopgateAddress->getCompany();

        return $prestashopAddress;
    }

    /**
     * @param string $isoCode
     *
     * @return null | int
     *
     * @throws ShopgateLibraryException
     */
    protected function getStateIdByIsoCode($isoCode)
    {
        $stateId = null;
        if (!$isoCode) {
            return $stateId;
        }

        $stateParts = explode('-', $isoCode);
        if (!is_array($stateParts)) {
            return $stateId;
        }

        if (count($stateParts) == 2) {
            $stateId = State::getIdByIso($stateParts[1], $this->getCountryIdByIsoCode($stateParts[0]));
        } else {
            $stateId = State::getIdByIso($stateParts[0]);
        }

        if (!$stateId) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::UNKNOWN_ERROR_CODE,
                ' invalid or empty iso code #' . $isoCode,
                true,
                false
            );
        }

        return $stateId;
    }

    /**
     * @param string $isoCode
     *
     * @return int
     *
     * @throws ShopgateLibraryException
     */
    protected function getCountryIdByIsoCode($isoCode)
    {
        if ($isoCode && $countryId = Country::getByIso($isoCode)) {
            return $countryId;
        }

        throw new ShopgateLibraryException(
            ShopgateLibraryException::UNKNOWN_ERROR_CODE,
            ' invalid or empty iso code #' . $isoCode,
            true,
            false
        );
    }

    /**
     * @param string $isoCode
     *
     * @return Country
     *
     * @throws ShopgateLibraryException
     */
    protected function getCountryByIsoCode($isoCode)
    {
        if (!$isoCode || !$countryId = Country::getByIso($isoCode)) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::UNKNOWN_ERROR_CODE,
                ' invalid or empty iso code #' . $isoCode,
                true,
                false
            );
        }

        return new Country($countryId);
    }

    /**
     * add a item exception
     *
     * @param ShopgateCartItem $item
     * @param                  $code
     * @param mixed            $message
     */
    protected function addItemException(ShopgateCartItem $item, $code, $message = false)
    {
        $item->setError($code);

        if ($message) {
            $item->setErrorText($message);
        } else {
            $item->setErrorText(ShopgateLibraryException::getMessageFor($code));
        }
    }

    /**
     * clear DB
     */
    public function __destruct()
    {
        $prestashopContext = $this->getPlugin()->getContext();

        if ($prestashopContext && $this->isDummyCustomer) {
            $prestashopContext->customer->delete();
            // "delete" function calls deleteByIdCustomer.
            // In version 1.4.x.x this logic only deletes discounts defined to an user, but not the user itself.
            // It's needed to delete the user entry manually
            if (version_compare(_PS_VERSION_, '1.5.0', '<')) {
                $this->getDb()->getInstance()->Execute(
                    'DELETE FROM `' . _DB_PREFIX_ . 'customer`
                WHERE `id_customer` = ' . (int)($prestashopContext->customer->id)
                );
            }
        }

        if ($this->prestashopDeliveryAddress && $this->prestashopDeliveryAddress->id) {
            $this->prestashopDeliveryAddress->delete();
        }

        if ($this->prestashopInvoiceAddress && $this->prestashopInvoiceAddress->id) {
            $this->prestashopInvoiceAddress->delete();
        }

        if ($prestashopContext->cart->id) {
            $prestashopContext->cart->delete();
        }
    }

    /**
     * @return Customer
     */
    protected function getCustomerDummy()
    {
        $customer                   = new Customer();
        $customer->lastname         = self::DEFAULT_CUSTOMER_LAST_NAME;
        $customer->firstname        = self::DEFAULT_CUSTOMER_FIRST_NAME;
        $customer->email            = self::DEFAULT_CUSTOMER_EMAIL;
        $customer->setWsPasswd(self::DEFAULT_CUSTOMER_PASSWD);
        $customer->newsletter       = 0;
        $customer->optin            = 0;
        $customer->id_default_group = $this->getDefaultCustomerGroupId();

        $this->isDummyCustomer = true;

        $customer->add();

        return $customer;
    }

    /**
     * @return string
     */
    protected function getDefaultCustomerGroupId()
    {
        return Configuration::get('PS_UNIDENTIFIED_GROUP');
    }

    /**
     * @param ShopgateCart $shopgateCart
     *
     * @return int
     *
     * @throws ShopgateLibraryException
     */
    protected function getPrestashopCarrierId(ShopgateCart $shopgateCart)
    {
        $shippingModel = new ShopgateShipping($this->getModule());
        $carrierId     = $shippingModel->getCarrierId($shopgateCart);

        /** @var CarrierCore $carrier */
        $carrier = new Carrier($carrierId);

        if (!Validate::isLoadedObject($carrier)) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::UNKNOWN_ERROR_CODE,
                sprintf('Invalid carrier ID #%s', $carrierId),
                true,
                false
            );
        }

        return $carrier->id;
    }

    /**
     * @param ShopgateAddress $address
     * @param string          $shopgateCartPhone
     */
    protected function updateShopgateAddressPhoneNumber(ShopgateAddress $address, $shopgateCartPhone)
    {
        if (!$address->getPhone()) {
            $address->setPhone($shopgateCartPhone);
        }
    }

    /**
     * @param ShopgateAddress $shopgateAddress
     * @param int             $customerId
     *
     * @return Address
     */
    protected function createPrestashopAddress(ShopgateAddress $shopgateAddress, $customerId)
    {
        $prestashopAddress              = $this->createAddress($shopgateAddress);
        $prestashopAddress->id_customer = $customerId;
        $prestashopAddress->save();

        return $prestashopAddress;
    }

    /**
     * @param ShopgateCart          $shopgateCart
     * @param ShopgateContextHelper $shopgateContextHelper
     *
     * @return Address
     */
    protected function convertShopgateDeliveryAddress(
        ShopgateCart $shopgateCart,
        ShopgateContextHelper $shopgateContextHelper
    ) {
        $this->updateShopgateAddressPhoneNumber($shopgateCart->getDeliveryAddress(), $shopgateCart->getPhone());

        $prestashopDeliveryAddress = $this->createPrestashopAddress(
            $shopgateCart->getDeliveryAddress(),
            $shopgateContextHelper->getCustomerId()
        );

        $shopgateContextHelper->saveCartDeliveryAddressId($prestashopDeliveryAddress->id);

        return $prestashopDeliveryAddress;
    }

    /**
     * @param ShopgateCart          $shopgateCart
     * @param ShopgateContextHelper $shopgateContextHelper
     *
     * @return Address
     */
    protected function convertShopgateInvoiceAddress(
        ShopgateCart $shopgateCart,
        ShopgateContextHelper $shopgateContextHelper
    ) {
        $this->updateShopgateAddressPhoneNumber($shopgateCart->getInvoiceAddress(), $shopgateCart->getPhone());

        $prestashopInvoiceAddress = $this->createPrestashopAddress(
            $shopgateCart->getInvoiceAddress(),
            $shopgateContextHelper->getCustomerId()
        );

        $shopgateContextHelper->saveCartInvoiceAddressId($prestashopInvoiceAddress->id);

        return $prestashopInvoiceAddress;
    }
}
