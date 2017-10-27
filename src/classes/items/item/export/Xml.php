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
class ShopgateItemsItemExportXml extends Shopgate_Model_Catalog_Product
{
    public function setUid()
    {
        parent::setUid($this->item['id_product']);
    }

    /**
     * set last update
     */
    public function setLastUpdate()
    {
        parent::setLastUpdate($this->item['date_upd'] . ' ' . date('T'));
    }

    /**
     * set name
     */
    public function setName()
    {
        parent::setName($this->item['name']);
    }

    /**
     * set tax percent
     */
    public function setTaxPercent()
    {
        parent::setTaxPercent((float)$this->getAdditionalInfo('tax_percent'));
    }

    /**
     * set tax class
     */
    public function setTaxClass()
    {
        parent::setTaxClass($this->getAdditionalInfo('tax_class'));
    }

    /**
     * set currency
     */
    public function setCurrency()
    {
        parent::setCurrency($this->getAdditionalInfo('currency'));
    }

    /**
     * set description
     */
    public function setDescription()
    {
        parent::setDescription($this->getAdditionalInfo('description'));
    }

    public function setPrice()
    {
        parent::setPrice($this->getAdditionalInfo('price'));
    }

    /**
     * set weight unit
     */
    public function setWeightUnit()
    {
        parent::setWeightUnit(Tools::strtolower(Configuration::get('PS_WEIGHT_UNIT')));
    }

    /**
     * set weight
     */
    public function setWeight()
    {
        parent::setWeight($this->item['weight']);
    }

    /**
     * set images
     */
    public function setImages()
    {
        parent::setImages($this->getAdditionalInfo('images'));
    }

    /**
     * set categories
     *
     */
    public function setCategoryPaths()
    {
        parent::setCategoryPaths($this->getAdditionalInfo('categories'));
    }

    /**
     * set the product deep link
     */
    public function setDeepLink()
    {
        parent::setDeeplink($this->getAdditionalInfo('deeplink'));
    }

    /**
     * set shipping
     */
    public function setShipping()
    {
        parent::setShipping($this->getAdditionalInfo('shipping'));
    }

    /**
     * add manufacturer
     */
    public function setManufacturer()
    {
        parent::setManufacturer($this->getAdditionalInfo('manufacturer'));
    }

    /**
     * add properties
     */
    public function setProperties()
    {
        parent::setProperties($this->getAdditionalInfo('properties'));
    }

    /**
     * add visibility
     */
    public function setVisibility()
    {
        parent::setVisibility($this->getAdditionalInfo('visibility'));
    }

    /**
     * stock
     */
    public function setStock()
    {
        parent::setStock($this->getAdditionalInfo('stock'));
    }

    /**
     * add identifiers
     */
    public function setIdentifiers()
    {
        parent::setIdentifiers($this->getAdditionalInfo('identifiers'));
    }

    /**
     * add tags
     */
    public function setTags()
    {
        parent::setTags($this->getAdditionalInfo('tags'));
    }

    /**
     * add promotion sort order
     */
    public function setPromotionSortOrder()
    {
        $this->getAdditionalInfo('promotion')
            ? parent::setPromotionSortOrder(1)
            : false;
    }

    /**
     * add internal order info
     */
    public function setInternalOrderInfo()
    {
    }

    /**
     * add relations
     */
    public function setRelations()
    {
        if ($this->getAdditionalInfo('relations')) {
            $relationsModel = new Shopgate_Model_Catalog_Relation();
            /**
             * @todo change to crosssell is available
             */
            $relationsModel->setType(Shopgate_Model_Catalog_Relation::DEFAULT_RELATION_TYPE_UPSELL);

            foreach ($this->getAdditionalInfo('relations') as $relation) {
                $relationsModel->addValue($relation['id_product']);
            }

            if (count($relationsModel->getValues())) {
                parent::setRelations(array($relationsModel));
            }
        }
    }

    /**
     * add age rating
     */
    public function setAgeRating()
    {
    }

    /**
     * add attributes
     */
    public function setAttributeGroups()
    {
        parent::setAttributeGroups($this->getAdditionalInfo('attribute_groups'));
    }

    /**
     * add inputs
     */
    public function setInputs()
    {
        parent::setInputs($this->getAdditionalInfo('inputs'));
    }

    /**
     * set children
     */
    public function setChildren()
    {
        parent::setChildren($this->getAdditionalInfo('children'));
    }

    /**
     * @param $key
     *
     * @return mixed
     */
    protected function getAdditionalInfo($key)
    {
        return array_key_exists($key, $this->item['_additional_info'])
            ? $this->item['_additional_info'][$key]
            : false;
    }
}
