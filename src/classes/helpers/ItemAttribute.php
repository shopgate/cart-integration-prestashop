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
class ShopgateItemAttributeHelper
{
    /** @var int */
    private $exportLanguageId;

    /**
     * @param int $exportLanguageId
     */
    public function __construct($exportLanguageId)
    {
        $this->exportLanguageId = $exportLanguageId;
    }

    /**
     * We use method "getAttributeCombinaisons" (instead of method "getAttributeCombinations")
     * because its available in all Prestashop versions. In later versions method "getAttributeCombinaisons" will
     * just call method "getAttributeCombinations"
     *
     * @param Product $prestashopProduct
     *
     * @return ShopgateItemsProductAttribute[]
     */
    public function getProductAttributes(Product $prestashopProduct)
    {
        $prestashopAttributes = $prestashopProduct->getAttributeCombinations($this->exportLanguageId);
        $productAttributes    = array();
        
        foreach ($prestashopAttributes as $prestashopAttribute) {
            if (!isset($productAttributes[$prestashopAttribute['id_product_attribute']])) {
                $productAttribute = new ShopgateItemsProductAttribute(
                    $prestashopAttribute['id_product_attribute'],
                    $prestashopAttribute['default_on'],
                    $prestashopAttribute['wholesale_price'],
                    $prestashopAttribute['minimal_quantity'],
                    $prestashopAttribute['unit_price_impact'],
                    $prestashopAttribute['quantity']
                );

                $productAttributes[$prestashopAttribute['id_product_attribute']] = $productAttribute;
            }

            $productAttribute = $productAttributes[$prestashopAttribute['id_product_attribute']];

            $productAttribute->addAttributeCombination(
                new ShopgateItemsAttributeCombination(
                    new ShopgateItemsAttributeGroup(
                        $prestashopAttribute['id_attribute_group'],
                        $prestashopAttribute['group_name']
                    ),
                    new ShopgateItemsAttribute(
                        $prestashopAttribute['id_attribute'],
                        $prestashopAttribute['attribute_name']
                    )
                )
            );
        }

        return $productAttributes;
    }

    /**
     * @param Product $prestashopProduct
     *
     * @return ShopgateItemsAttributeGroup[]
     */
    public function getAttributeGroups(Product $prestashopProduct)
    {
        $attributeGroups = array();
        foreach ($this->getProductAttributes($prestashopProduct) as $productAttribute) {
            foreach ($productAttribute->getAttributeCombinations() as $productCombination) {
                $attributeGroups[] = $productCombination->getAttributeGroup();
            }
            break;
        }

        return $attributeGroups;
    }
}
