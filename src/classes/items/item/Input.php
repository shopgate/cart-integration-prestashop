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
class ShopgateItemsInput
{
    /**
     * @var ShopgateDb
     */
    protected $db;

    public function __construct(ShopgateDb $db)
    {
        $this->db = $db;
    }

    /**
     * Creates customizations and references them to the cart
     *
     * @param ShopgateOrderItem $item
     * @param string | int      $cartId
     * @param int               $idAddressDelivery
     *
     * @return bool
     */
    public function createCustomization(ShopgateOrderItem $item, $cartId, $idAddressDelivery = 0)
    {
        if (!$item->getInputs()) {
            return false;
        }

        $insert = array(
            'id_cart'    => $cartId,
            'id_product' => $item->getItemNumber(),
        );
        if (version_compare(_PS_VERSION_, '1.5.0.0', '>=')) {
            $insert['id_address_delivery'] = $idAddressDelivery;
            $insert['in_cart']             = 1;
        }

        if (version_compare(_PS_VERSION_, '8', '>')) {
            $this->db->getInstance()->insert('customization', $insert);
        } else {
            $this->db->getInstance()->autoExecuteWithNullValues(_DB_PREFIX_ . 'customization', $insert, 'INSERT');
        }

        

        return $this->db->getInstance()->Insert_ID();
    }

    /**
     * @param int                      $customizationId
     * @param ShopgateOrderItemInput[] $inputs
     */
    public function createCustomizedData($customizationId, $inputs)
    {
        if (empty($inputs) || empty($customizationId)) {
            return;
        }

        if (version_compare(_PS_VERSION_, '8', '>')) {
            foreach ($inputs as $input) {
                $type = $this->getSystemInputType($input);
                $this->db->getInstance()->insert(
                    'customized_data',
                    array(
                        'id_customization' => $customizationId,
                        'type'             => $type,
                        'index'            => $input->getInputNumber(),
                        'value'            => $input->getUserInput(),
                    )
                );
            }
        } else {
            foreach ($inputs as $input) {
                $this->db->getInstance()->autoExecuteWithNullValues(
                    _DB_PREFIX_ . 'customized_data',
                    array(
                        'id_customization' => $customizationId,
                        'type'             => $type,
                        'index'            => $input->getInputNumber(),
                        'value'            => $input->getUserInput(),
                    ),
                    'INSERT'
                );
            }
        }
    }

    /**
     * Translates shopgate input type into
     * system type ID to save to the DB
     * 1 - text field
     * 2 - file field, we current do not support it though
     *
     * @param ShopgateOrderItemInput $input
     *
     * @return int
     */
    public function getSystemInputType(ShopgateOrderItemInput $input)
    {
        return $input->getType() === Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_TEXT
            ? 1
            : 2;
    }
}
