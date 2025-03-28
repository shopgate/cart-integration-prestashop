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
class ShopgateItemsItem extends ShopgateItemsAbstract
{
    /**
     * default divider item id and attribute id
     */
    const DEFAULT_ITEM_ATTRIBUTE_DIVIDER_PATTERN = '%s-%s';
    /**
     * default property deepens on stock
     */
    const DEFAULT_PROPERTY_DEPENDS_ON_STOCK = 'depends_on_stock';

    /**
     * @var Product
     */
    protected $currentProduct = false;

    /**
     * @var array
     */
    protected $currentAdditionalInfo = array();

    /** @var ShopgateItemsBasePrice */
    protected $basePriceHelper;

    /** @var ShopgateItemAttributeHelper */
    protected $itemAttributeHelper;

    /**
     * @param ShopgatePluginPrestashop    $plugin
     * @param ShopgateDb                  $db
     * @param ShopgateItemsBasePrice      $basePriceHelper
     * @param ShopgateItemAttributeHelper $itemAttributeHelper
     */
    public function __construct(
        ShopgatePluginPrestashop $plugin,
        ShopgateDb $db,
        ShopgateItemsBasePrice $basePriceHelper,
        ShopgateItemAttributeHelper $itemAttributeHelper
    ) {
        parent::__construct($plugin, $db);

        $this->basePriceHelper     = $basePriceHelper;
        $this->itemAttributeHelper = $itemAttributeHelper;
    }

    /**
     * @param null  $limit
     * @param null  $offset
     * @param array $uids
     *
     * @return array
     */
    public function getItems($limit = null, $offset = null, array $uids = array())
    {
        $products = array();

        foreach ($this->getProductIds($limit, $offset, $uids) as $product) {
            /*
             * prepare data
             */
            if (version_compare(_PS_VERSION_, '1.5.0.0', '<')) {
                $this->currentProduct = new Product($product['id_product'], true, $this->getPlugin()->getLanguageId());
            } else {
                $this->currentProduct = new Product(
                    $product['id_product'],
                    true,
                    $this->getPlugin()->getLanguageId(),
                    $this->getPlugin()->getContext()->shop->id
                );
            }

            $resultProduct               = get_object_vars($this->currentProduct);
            $this->currentAdditionalInfo = array();

            $this->addAdditionalInfo('currency', $this->getPlugin()->getContext()->currency->iso_code);
            $this->addAdditionalInfo('deeplink', $this->prepareDeeplink());
            $this->addAdditionalInfo(
                'images',
                $this->prepareImages($this->currentProduct->getImages($this->getPlugin()->getLanguageId()))
            );
            $this->addAdditionalInfo('price', $this->preparePrice());
            $this->addAdditionalInfo('categories', $this->prepareCategories());
            $this->addAdditionalInfo('shipping', $this->prepareShipping());
            $this->addAdditionalInfo('properties', $this->prepareProperties());
            $this->addAdditionalInfo('stock', $this->prepareStock());
            $this->addAdditionalInfo('identifiers', $this->prepareIdentifiers());
            $this->addAdditionalInfo('tags', $this->prepareTags());
            $this->addAdditionalInfo('attribute_groups', $this->prepareAttributeGroups());
            $this->addAdditionalInfo('inputs', $this->prepareInputs());
            $this->addAdditionalInfo('description', $this->prepareDescription());
            $this->addAdditionalInfo('promotion', $this->preparePromotion());
            $this->addAdditionalInfo('tax_percent', $this->prepareTaxPercent());
            $this->addAdditionalInfo('tax_class', $this->prepareTaxClass());
            $this->addAdditionalInfo('relations', $this->prepareRelations());

            /**
             * prepare children
             */
            $this->addAdditionalInfo('children', $this->prepareChildren());

            /**
             * manufacturer
             */
            $manufacturerItem = new Shopgate_Model_Catalog_Manufacturer();
            $manufacturerItem->setUid($this->currentProduct->id_manufacturer);
            $manufacturerItem->setTitle($this->currentProduct->manufacturer_name);
            $this->addAdditionalInfo('manufacturer', $manufacturerItem);

            /**
             * visibility
             */
            $visibilityItem = new Shopgate_Model_Catalog_Visibility();
            $visibilityItem->setLevel($this->mapVisibility($this->currentProduct->visibility));
            $this->addAdditionalInfo('visibility', $visibilityItem);

            $resultProduct['_additional_info'] = $this->currentAdditionalInfo;
            $resultProduct['id_product']       = $resultProduct['id'];

            $products[] = $resultProduct;
        }

        return $products;
    }

    /**
     * @param null  $limit
     * @param null  $offset
     * @param array $uids
     *
     * @return array
     * @throws PrestaShopDatabaseException
     */
    protected function getProductIds($limit = null, $offset = null, array $uids = array())
    {
        $shopId     = $this->getPlugin()->getContext()->shop->id;
        $conditions = array();

        $multishopJoin = '';
        $conditions[]  = 'products.active != 0 ';
        if (version_compare(_PS_VERSION_, '1.5.0.0', '>=') && !empty($shopId)) {
            $multishopJoin = 'INNER JOIN ' . _DB_PREFIX_
                . 'product_shop AS products_shop ON products_shop.id_product = products.id_product';
            $conditions[]  = 'products_shop.id_shop = ' . $shopId;
            $conditions[]  = 'products_shop.active = 1';
        }
        if (count($uids) > 0) {
            $conditions[] = 'products.id_product IN (' . implode(',', $uids) . ') ';
        }

        $select = sprintf(
            'SELECT DISTINCT products.id_product
             FROM %sproduct AS products
             %s
             %s
             ORDER BY products.id_product ASC
             %s',
            _DB_PREFIX_,
            $multishopJoin,
            $conditions
                ? 'WHERE ' . implode(' AND ', $conditions)
                : '',
            is_int($limit)
                ? 'LIMIT ' . $limit . (is_int($offset)
                    ? ' OFFSET ' . $offset
                    : '')
                : ''
        );

        return $this->getDb()->getInstance()->ExecuteS($select);
    }

    /**
     * @return float
     */
    public function prepareTaxPercent()
    {
        return (float)$this->currentProduct->tax_rate;
    }

    /**
     * @return bool
     */
    public function prepareRelations()
    {
        if (version_compare(_PS_VERSION_, '1.6.0.0', '>=')) {
            return Product::getAccessoriesLight($this->getPlugin()->getLanguageId(), $this->currentProduct->id);
        }

        return false;
    }

    /**
     * @return string
     */
    public function prepareTaxClass()
    {
        $taxRulesGroups  = TaxRulesGroupCore::getTaxRulesGroups(true);
        $idTaxRulesGroup = (int)Product::getIdTaxRulesGroupByIdProduct($this->currentProduct->id, null);

        foreach ($taxRulesGroups as $taxRulesGroup) {
            if ($taxRulesGroup['id_tax_rules_group'] == $idTaxRulesGroup) {
                $tax = ShopgateSettings::getTaxItemByTaxRuleGroupId($idTaxRulesGroup);

                $taxClassName = '';
                if (is_array($tax->name) && !empty($tax->name[$this->getPlugin()->getLanguageId()])) {
                    $taxClassName = $tax->name[$this->getPlugin()->getLanguageId()];
                } else {
                    if (is_array($tax->name)) {
                        // fallback: just in case for older Prestashop versions
                        $taxClassName = reset($tax->name);
                    }
                }

                return $taxClassName;
            }
        }

        return '';
    }

    /**
     * prepare children
     *
     * @return array
     */
    protected function prepareChildren()
    {
        $result = array();

        if ($this->currentProduct->hasAttributes()) {
            $combinationImages = $this->currentProduct->getCombinationImages($this->getPlugin()->getLanguageId());

            foreach ($this->itemAttributeHelper->getProductAttributes($this->currentProduct) as $productAttribute) {
                $childItemItem = new Shopgate_Model_Catalog_Product();
                $childItemItem->setIsChild(true);
                $childItemItem->setUid(
                    sprintf(
                        ShopgateItemsItem::DEFAULT_ITEM_ATTRIBUTE_DIVIDER_PATTERN,
                        $this->currentProduct->id,
                        $productAttribute->getId()
                    )
                );

                if ($productAttribute->isDefaultOn() == 1) {
                    $childItemItem->setIsDefaultChild(true);
                }

                /**
                 * price
                 */
                $priceItem = new Shopgate_Model_Catalog_Price();

                if ($productAttribute->getWholeSalePrice() > 0
                    && $productAttribute->getWholeSalePrice() != $this->currentProduct->wholesale_price
                ) {
                    /**
                     * setCost
                     */
                    $priceItem->setCost($this->convertPrice($productAttribute->getWholeSalePrice()));
                }

                if ($productAttribute->getMinimalQuantity() > 1) {
                    $priceItem->setMinimumOrderAmount($productAttribute->getMinimalQuantity());
                }

                $priceItem->setPrice(
                    $this->getItemPrice(
                        $this->currentProduct->id,
                        $productAttribute->getId(),
                        $this->isPriceGross()
                    )
                );

                if (
                    $this->basePriceHelper->basePriceActive($this->currentProduct)
                    && (
                        $this->basePriceHelper->basePriceAffectsChildren(
                            $productAttribute->getUnitPriceImpact()
                        )
                        || $this->currentProduct->price !== $priceItem->getPrice()
                    )
                ) {
                    $priceItem->setBasePrice(
                        $this->basePriceHelper->getBasePriceFormatted(
                            $this->basePriceHelper->calculateChildBasePrice(
                                $this->getItemPrice(
                                    $this->currentProduct->id,
                                    $productAttribute->getId(),
                                    false,
                                    true
                                ),
                                $this->currentProduct->unit_price_ratio,
                                $productAttribute->getUnitPriceImpact(),
                                $this->isBasePriceGross(),
                                $this->currentProduct->tax_rate
                            ),
                            $this->currentProduct->unity
                        )
                    );
                }

                $priceItem->setSalePrice(
                    $this->getItemPrice(
                        $this->currentProduct->id,
                        $productAttribute->getId(),
                        $this->isPriceGross(),
                        true
                    )
                );

                $childItemItem->setPrice($priceItem);

                /**
                 * tier prices
                 */
                foreach (
                    $this->getTierPrices($priceItem, $this->currentProduct->id, $productAttribute->getId()) as $tierPriceRule
                ) {
                    $priceItem->addTierPriceGroup($tierPriceRule);
                }

                /**
                 * tax class
                 */
                $childItemItem->setTaxClass($this->prepareTaxClass());

                /**
                 * stock
                 */
                $stockItem = new Shopgate_Model_Catalog_Stock();
                $stockItem->setStockQuantity($productAttribute->getQuantity());
                $stockItem->setMinimumOrderQuantity(
                    $productAttribute->getMinimalQuantity() > 1
                        ? $productAttribute->getMinimalQuantity()
                        : 0
                );

                /*
                 * needs to be set, because an internal function of Prestashop (Product::checkQty) needs to know what attribute is set
                 */
                $this->currentProduct->id_product_attribute = $productAttribute->getId();

                /*
                 * we need to check this for each child because of the different stock quantity a attribute can have
                 */
                $stockItem->setIsSaleable(
                    $this->prepareIsSaleable(
                        $this->currentProduct,
                        $stockItem->getStockQuantity()
                    )
                );
                $stockItem->setAvailabilityText(
                    $this->prepareAvailableText(
                        $stockItem->getIsSaleable(),
                        $stockItem->getStockQuantity(),
                        $this->currentProduct->out_of_stock
                    )
                );
                $childItemItem->setStock($stockItem);

                /**
                 * identifiers
                 */
                foreach ($this->prepareIdentifiers($productAttribute) as $identifier) {
                    $childItemItem->addIdentifier($identifier);
                }

                /**
                 * attribute
                 */
                foreach ($productAttribute->getAttributeCombinations() as $combinationItem) {
                    $attributeItem = new Shopgate_Model_Catalog_Attribute();
                    $attributeItem->setGroupUid($combinationItem->getAttributeGroup()->getId());
                    $attributeItem->setUid($combinationItem->getAttribute()->getId());
                    $attributeItem->setLabel($combinationItem->getAttribute()->getName());
                    $childItemItem->addAttribute($attributeItem);
                }

                /**
                 * images
                 */
                if (is_array($combinationImages)
                    && array_key_exists($productAttribute->getId(), $combinationImages)
                ) {
                    foreach ($this->prepareImages($combinationImages[$productAttribute->getId()]) as $image) {
                        $childItemItem->addImage($image);
                    }
                }

                $result[] = $childItemItem;
            }
        }

        return $result;
    }

    /**
     * prepare description
     *
     * @return string
     */
    protected function prepareDescription()
    {
        switch (Configuration::get('SG_PRODUCT_DESCRIPTION')) {
            case ShopgateSettings::PRODUCT_EXPORT_SHORT_DESCRIPTION:
                return $this->currentProduct->description_short;
            case ShopgateSettings::PRODUCT_EXPORT_BOTH_DESCRIPTIONS:
                $break = !empty($this->currentProduct->description_short) && !empty($this->currentProduct->description)
                    ? '<br />'
                    : '';

                return $this->currentProduct->description_short . $break . $this->currentProduct->description;
            default:
                return $this->currentProduct->description;
        }
    }

    /**
     * prepare deeplink
     *
     * @return mixed
     */
    protected function prepareDeeplink()
    {
        return $this->getPlugin()->getContext()->link->getProductLink(
            $this->currentProduct->id,
            $this->currentProduct->link_rewrite,
            $this->currentProduct->category,
            $this->currentProduct->ean13,
            $this->getPlugin()->getLanguageId()
        );
    }

    /**
     * prepare promotion
     */
    public function preparePromotion()
    {
        $nb = (int)Configuration::get('HOME_FEATURED_NBR');

        $category = version_compare(_PS_VERSION_, '1.5', '<')
            ? 1
            : $this->getPlugin()->getContext()->shop->getCategory();

        /** @var CategoryCore $category */
        $category = new Category(
            $category,
            $this->getPlugin()->getLanguageId()
        );
        $products = $category->getProducts(
            (int)Context::getContext()->language->id,
            1,
            ($nb
                ? $nb
                : 8),
            'position'
        );

        foreach ($products as $product) {
            /** @var ProductCore $product */
            if ($product['id_product'] == $this->currentProduct->id) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $key
     * @param        $value
     */
    protected function addAdditionalInfo($key, $value)
    {
        $this->currentAdditionalInfo[$key] = $value;
    }

    /**
     * prepare inputs
     *
     * @return array
     */
    protected function prepareInputs()
    {
        $result = array();

        if ($this->currentProduct->customizable) {
            $customizationFields = $this->currentProduct->getCustomizationFields($this->getPlugin()->getLanguageId());
            foreach ($customizationFields as $customizationField) {
                $inputItem = new Shopgate_Model_Catalog_Input();
                $inputItem->setUid($customizationField['id_customization_field']);
                $inputItem->setLabel($customizationField['name']);

                if ($customizationField['required'] == 1) {
                    $inputItem->setRequired(true);
                }

                switch ($customizationField['type']) {
                    case 0:
                        $inputItem->setType(Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_FILE);
                        break;
                    case 1:
                        $inputItem->setType(Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_TEXT);
                        break;
                }

                $result[] = $inputItem;
            }
        }

        return $result;
    }

    /**
     * prepare attribute groups
     *
     * @return array
     */
    protected function prepareAttributeGroups()
    {
        $result = array();

        if ($this->currentProduct->hasAttributes()) {
            $attributeGroups = $this->itemAttributeHelper->getAttributeGroups($this->currentProduct);

            $addedGroup = array();
            foreach ($attributeGroups as $attributeGroup) {
                if (!in_array($attributeGroup->getId(), $addedGroup)) {
                    $attributeItem = new Shopgate_Model_Catalog_AttributeGroup();
                    $attributeItem->setUid($attributeGroup->getId());
                    $attributeItem->setLabel($attributeGroup->getName());
                    $result[]     = $attributeItem;
                    $addedGroup[] = $attributeGroup->getId();
                }
            }
        }

        return $result;
    }

    /**
     * prepare identifier
     *
     * @param mixed $product
     *
     * @return array
     */
    protected function prepareIdentifiers($product = false)
    {
        $result  = array();
        $product = $product
            ? $product
            : $this->currentProduct;

        if (property_exists($product, 'reference') && !empty($product->reference)) {
            $identifierItem = new Shopgate_Model_Catalog_Identifier();
            $identifierItem->setType('SKU');
            $identifierItem->setValue($product->reference);
            $result[] = $identifierItem;
        }

        if (property_exists($product, 'upc') && !empty($product->upc)) {
            $identifierItem = new Shopgate_Model_Catalog_Identifier();
            $identifierItem->setType('UPC');
            $identifierItem->setValue($product->upc);
            $result[] = $identifierItem;
        }

        if (property_exists($product, 'ean13') && !empty($product->ean13)) {
            $identifierItem = new Shopgate_Model_Catalog_Identifier();
            $identifierItem->setType('EAN');
            $identifierItem->setValue($product->ean13);
            $result[] = $identifierItem;
        }

        return $result;
    }

    /**
     * prepare tags
     *
     * @return array
     */
    protected function prepareTags()
    {
        $result = array();

        if (is_array($this->currentProduct->tags)
            && array_key_exists($this->getPlugin()->getLanguageId(), $this->currentProduct->tags)
        ) {
            foreach ($this->currentProduct->tags[$this->getPlugin()->getLanguageId()] as $number => $value) {
                $tagItem = new Shopgate_Model_Catalog_Tag();
                $tagItem->setUid($number);
                $tagItem->setValue($value);
                $result[] = $tagItem;
            }
        }

        return $result;
    }

    /**
     * prepare stock
     *
     * @return Shopgate_Model_Catalog_Stock
     */
    protected function prepareStock()
    {
        $stockItem = new Shopgate_Model_Catalog_Stock();

        $stockItem->setMinimumOrderQuantity(
            $this->currentProduct->minimal_quantity > 1
                ? $this->currentProduct->minimal_quantity
                : 0
        );

        if (property_exists($this->currentProduct, self::DEFAULT_PROPERTY_DEPENDS_ON_STOCK)) {
            $stockItem->setUseStock($this->currentProduct->depends_on_stock);
        } else {
            $stockItem->setUseStock(Configuration::get('PS_STOCK_MANAGEMENT'));
        }

        $stockItem->setStockQuantity($this->currentProduct->quantity);
        $stockItem->setIsSaleable($this->prepareIsSaleable($this->currentProduct, $stockItem->getStockQuantity()));
        $stockItem->setAvailabilityText(
            $this->prepareAvailableText(
                $stockItem->getIsSaleable(),
                $stockItem->getStockQuantity(),
                $this->currentProduct->out_of_stock
            )
        );

        return $stockItem;
    }

    /**
     * calculates if the product is saleable or not
     *
     * @param $currentProduct Product
     *
     * @return int
     */
    protected function prepareIsSaleable($currentProduct, $stockQuantity)
    {
        $availableForOrder = isset($currentProduct->available_for_order) && $currentProduct->available_for_order != 1
            ? false
            : true;

        $saleable = 0;
        if ($availableForOrder) {
            if (version_compare(_PS_VERSION_, '1.5.0.1', '<')
                && (Product::isAvailableWhenOutOfStock($currentProduct->out_of_stock) || $stockQuantity > 0)
                || version_compare(_PS_VERSION_, '1.5.0.1', '>=') && $currentProduct->checkQty(1)
            ) {
                $saleable = 1;
            }
        }

        return $saleable;
    }

    /**
     * prepare the availability text
     *
     * @param $isSaleable               bool
     * @param $stockQuantity            int
     * @param $productOutOfStockSetting int
     *
     * @return mixed|string
     */
    protected function prepareAvailableText($isSaleable, $stockQuantity, $productOutOfStockSetting)
    {
        if ($isSaleable) {
            if ($stockQuantity <= 0 && Configuration::get('PS_STOCK_MANAGEMENT')
                && Product::isAvailableWhenOutOfStock($productOutOfStockSetting)
            ) {
                /**
                 * setAvailabilityText
                 */
                return $this->currentProduct->available_later;
            } else {
                /**
                 * setAvailabilityText
                 */
                return $this->currentProduct->available_now;
            }
        } else {
            return $this->getModule()->l('This product is no longer in stock');
        }
    }

    /**
     * prepare properties
     *
     * @return array
     */
    protected function prepareProperties()
    {
        $result     = array();
        $properties = Product::getFrontFeaturesStatic($this->getPlugin()->getLanguageId(), $this->currentProduct->id);

        foreach ($properties as $property) {
            $propertyItemObject = new Shopgate_Model_Catalog_Property();
            $propertyItemObject->setUid($property['id_feature']);
            $propertyItemObject->setLabel($property['name']);
            $propertyItemObject->setValue($property['value']);
            $result[] = $propertyItemObject;
        }

        return $result;
    }

    /**
     * prepare shipping
     *
     * @return Shopgate_Model_Catalog_Shipping
     */
    protected function prepareShipping()
    {
        $shippingItem = new Shopgate_Model_Catalog_Shipping();
        if ($this->currentProduct->additional_shipping_cost > 0) {
            $shippingItem->setAdditionalCostsPerUnit($this->currentProduct->additional_shipping_cost);
        }

        return $shippingItem;
    }

    /**
     * prepare categories
     *
     * @return array
     */
    protected function prepareCategories()
    {
        $result                       = array();
        $maxSortOrderByCategoryNumber = $this->getCategoryMaxSortOrder();

        foreach ($this->getCategoriesFromDb($this->currentProduct->id) as $category) {
            $categoryPathItem = new Shopgate_Model_Catalog_CategoryPath();
            $categoryPathItem->setUid($category['id_category']);

            $maxPosition     = array_key_exists($category['id_category'], $maxSortOrderByCategoryNumber)
                ? $maxSortOrderByCategoryNumber[$category['id_category']]
                : 0;
            $productPosition = $this->getProductPositionByIdAndCategoryId(
                $this->currentProduct->id,
                $category['id_category']
            );

            $categoryPathItem->setSortOrder($maxPosition - $productPosition);
            foreach ($this->getCategoryPathsFromModel($category['id_category']) as $path) {
                $categoryPathItem->addItem($path['level_depth'], $path['name']);
            }

            $result[] = $categoryPathItem;
        }

        return $result;
    }

    /**
     * prepare price
     *
     * @return Shopgate_Model_Catalog_Price
     */
    protected function preparePrice()
    {
        $priceItem = new Shopgate_Model_Catalog_Price();

        /**
         * set the price type
         */
        $priceItem->setType(
            Configuration::get('SHOPGATE_EXPORT_PRICE_TYPE')
                ? Configuration::get('SHOPGATE_EXPORT_PRICE_TYPE')
                : Shopgate_Model_Catalog_Price::DEFAULT_PRICE_TYPE_NET
        );

        /** @var $this ->item ProductCore */
        $priceItem->setPrice($this->getItemPrice($this->currentProduct->id, null, $this->isPriceGross()));

        if ($this->currentProduct->wholesale_price != 0) {
            $priceItem->setCost($this->convertPrice($this->currentProduct->wholesale_price));
        }

        $priceItem->setMinimumOrderAmount(
            $this->currentProduct->minimal_quantity > 1
                ? $this->currentProduct->minimal_quantity
                : 0
        );

        if ($this->basePriceHelper->basePriceActive($this->currentProduct)) {
            $priceItem->setBasePrice(
                $this->basePriceHelper->getBasePriceFormatted(
                    $this->basePriceHelper->calculateBasePrice(
                        $this->currentProduct->unit_price,
                        $this->isBasePriceGross(),
                        $this->currentProduct->tax_rate
                    ),
                    $this->currentProduct->unity
                )
            );
        }

        $priceItem->setSalePrice($this->getItemPrice($this->currentProduct->id, null, $this->isPriceGross(), true));

        /**
         * tier prices
         */
        foreach ($this->getTierPrices($priceItem, $this->currentProduct->id) as $tierPriceRule) {
            $priceItem->addTierPriceGroup($tierPriceRule);
        }

        return $priceItem;
    }

    /**
     * prepare images
     *
     * @param $images
     *
     * @return array
     */
    protected function prepareImages($images)
    {
        $result = array();

        foreach ($images as $image) {
            $imageItem = new Shopgate_Model_Media_Image();

            $imageItem->setUid($image['id_image']);
            $imageItem->setUrl(
                $this->getPlugin()->getContext()->link->getImageLink(
                    $this->currentProduct->link_rewrite[$this->getPlugin()->getLanguageId()],
                    $this->currentProduct->id . '-' . $image['id_image']
                )
            );

            $sortOrder = $image['cover']
                ? -1
                : $image['position'];

            $imageItem->setSortOrder($sortOrder);

            if ($imageInfo = $this->getImageInfo($image['id_image'])) {
                $imageItem->setAlt($imageInfo['legend']);
                $imageItem->setTitle($imageInfo['legend']);
            }

            $result[] = $imageItem;
        }

        return $result;
    }

    /**
     * returns image info by id
     *
     * @param int $imageId
     *
     * @return array
     */
    protected function getImageInfo($imageId)
    {
        $result = array();

        if (ShopgateHelper::checkTable(sprintf('%simage_lang', _DB_PREFIX_))) {
            $select = sprintf(
                'SELECT * FROM %simage_lang WHERE id_image = %s AND id_lang = %s',
                _DB_PREFIX_,
                $imageId,
                $this->getPlugin()->getLanguageId()
            );

            $result = $this->getDb()->getInstance()->getRow($select);
        }

        return $result;
    }

    /**
     * @param $itemId
     *
     * @return array
     */
    protected function getTierPricesFromDb($itemId)
    {
        /**
         * check table
         */
        if (ShopgateHelper::checkTable(sprintf('%sspecific_price', _DB_PREFIX_))) {
            $select = sprintf(
                'SELECT * FROM %sspecific_price WHERE id_product = %s',
                _DB_PREFIX_,
                $itemId
            );

            return $this->getDb()->getInstance()->ExecuteS($select);
        } else {
            return array();
        }
    }

    /**
     * returns an array of Shopgate_Model_Catalog_TierPrice
     *
     * @param Shopgate_Model_Catalog_Price $priceItem
     * @param int                          $itemId
     * @param int                          $variantId
     *
     * @return array of Shopgate_Model_Catalog_TierPrice
     */
    protected function getTierPrices($priceItem, $itemId, $variantId = null)
    {
        $tierPriceRules = array();

        $tierPrices = $this->getTierPricesFromDb($itemId);

        if (version_compare(_PS_VERSION_, '1.5.0.5', '<=')) {
            $shopId = $this->getPlugin()->getContext()->shop->getID();
        } else {
            $shopId = $this->getPlugin()->getContext()->shop->getContextShopID();
        }

        if (!is_array($tierPrices)) {
            return $tierPriceRules;
        }

        $overallValidRuleWithQuantityOneAvailable = false;
        $visitorRuleWithQuantityOneAvailable      = false;
        foreach ($tierPrices as $tierPrice) {
            if ($tierPrice['from_quantity'] == 1 && $tierPrice['id_group'] == 1) {
                $visitorRuleWithQuantityOneAvailable = true;
            }
            if ($tierPrice['from_quantity'] == 1 && $tierPrice['id_group'] == 0) {
                $overallValidRuleWithQuantityOneAvailable = true;
            }
        }

        $customerGroups = array();
        if ($visitorRuleWithQuantityOneAvailable && $overallValidRuleWithQuantityOneAvailable) {
            $customerGroups = Group::getGroups(
                Configuration::get('SHOPGATE_LANGUAGE_ID'),
                $this->getPlugin()->getContext()->shop->id
                    ? $this->getPlugin()->getContext()->shop->id
                    : false
            );
        }

        foreach ($tierPrices as $tierPrice) {
            if ($tierPrice['from_quantity'] == 1 && $tierPrice['id_group'] == 0
                && !$visitorRuleWithQuantityOneAvailable
            ) {
                /*
                 * In case the tier price starts from quantity 1 and the discount is available for all user
                 * this should be ignored as tier price because its already honoured as sale price
                 * Exception: There is a default/Visitor rule!
                 */
                continue;
            }

            if (($validFrom = strtotime($tierPrice['from'])) >= 0 && time() <= $validFrom) {
                /*
                 * the discount is valid from a specific date but the date is still in the future => ignore this rule
                 */
                continue;
            }

            if (($validTo = strtotime($tierPrice['to'])) >= 0 && time() >= $validTo) {
                /*
                 * the discount is valid to a specific date but the date is in the past => ignore this rule
                 */
                continue;
            }

            if (!empty($tierPrice['id_currency'])
                && $tierPrice['id_currency'] != $this->getPlugin()->getContext()->currency->id
            ) {
                /*
                 * the discount is only valid for one specific currency and we are not exporting this specific currency
                 */
                continue;
            }

            /*
             * hack for Prestashop versions >= 1.5.0.15. for more details see: file:
             * classes/SpecificPrice.php method: getSpecificPrice()
             * Since 1.5.0.15 Prestashop uses in case PS_QTY_DISCOUNT_ON_COMBINATION is set to 0 (default)
             * the cart quantity for finding the correct price.
             */
            Configuration::set('PS_QTY_DISCOUNT_ON_COMBINATION', 1);
            $finalPrice = $this->getItemPrice(
                $itemId,
                $variantId,
                $this->isPriceGross(),
                true,
                (int)$tierPrice['from_quantity']
            );

            $tierPriceItem = new Shopgate_Model_Catalog_TierPrice();
            $tierPriceItem->setFromQuantity($tierPrice['from_quantity']);
            $tierPriceItem->setReductionType(Shopgate_Model_Catalog_TierPrice::DEFAULT_TIER_PRICE_TYPE_FIXED);

            if (array_key_exists('id_group', $tierPrice) && $tierPrice['id_group'] != 0) {
                $tierPriceItem->setCustomerGroupUid($tierPrice['id_group']);

                if (version_compare(_PS_VERSION_, '1.4.0.17', '<')) {
                    /*
                     * We don't support customer group related prices in versions lower then 1.4.0.17
                     * because there is no proper way to let the shopping cart solution calculate the price
                     */
                    continue;
                } else {
                    $finalPrice = $this->calculateCustomerGroupPrice(
                        $shopId,
                        $itemId,
                        $variantId,
                        $tierPrice['id_group'],
                        (int)$tierPrice['from_quantity']
                    );
                }
            }

            if (version_compare(_PS_VERSION_, '1.5.0.0', '>=') && $tierPrice['from_quantity'] == 1
                && $tierPrice['id_group'] == 0
                && $visitorRuleWithQuantityOneAvailable
            ) {
                /**
                 * Customer groups have changed since 1.5.0.1. The customer group with id = 1 is called "Visitor"
                 * AND must be - with a quantity of 1 and in combination with another general rule that's also
                 * quantity of 1 - treated in a special way.
                 * The one rule must be split off in separate rules for each customer group except the Visitor rule
                 */
                $tierPriceItemCache = $tierPriceItem;
                foreach ($customerGroups as $customerGroup) {
                    if ($customerGroup['id_group'] == 1) {
                        /**
                         * skip Visitor price rule
                         */
                        continue;
                    }
                    $tierPriceItem = clone $tierPriceItemCache;
                    $tierPriceItem->setCustomerGroupUid($customerGroup['id_group']);

                    $finalPrice = $this->calculateCustomerGroupPrice(
                        $shopId,
                        $itemId,
                        $variantId,
                        $customerGroup['id_group'],
                        (int)$tierPrice['from_quantity']
                    );

                    $this->addTierPriceRule($priceItem->getSalePrice() - $finalPrice, $tierPriceItem, $tierPriceRules);
                }
                continue;
            }

            $this->addTierPriceRule($priceItem->getSalePrice() - $finalPrice, $tierPriceItem, $tierPriceRules);
        }

        return $tierPriceRules;
    }

    /**
     * calculates the reduction and decides if the tier price is added
     *
     * @param float                            $reducedAmount
     * @param Shopgate_Model_Catalog_TierPrice $tierPriceItem
     * @param array                            $tierPriceRules
     * @post $tierPriceRules contains the new tier price if the reductionAmount is not zero
     */
    protected function addTierPriceRule($reducedAmount, $tierPriceItem, &$tierPriceRules)
    {
        if ($reducedAmount != 0) {
            /*
             * In case a specific price rule (e.g. for Visitors) is automatic calculated as a general discount
             * the specific price rule will have an amount 0.
             * We need to prevent the export of such price rule by use of this condition
             */
            $tierPriceItem->setReduction($reducedAmount);
            $tierPriceRules[] = $tierPriceItem;
        }
    }

    /**
     * @return array
     */
    protected function getCategoryMaxSortOrder()
    {
        $maxSortOrderCategories = $this->getDb()->getInstance()->ExecuteS(
            'SELECT id_category, MAX(position) AS max_position
            FROM `' . _DB_PREFIX_ . 'category_product`
            GROUP BY `id_category`'
        );

        $maxSortOrderByCategoryNumber = array();
        foreach ($maxSortOrderCategories as $sortOrderCategory) {
            $maxSortOrderByCategoryNumber[$sortOrderCategory['id_category']] = $sortOrderCategory['max_position'];
        }

        return $maxSortOrderByCategoryNumber;
    }

    /**
     * @param $itemId
     *
     * @return array
     */
    protected function getCategoriesFromDb($itemId)
    {
        $select = sprintf(
            'SELECT
                        cp.id_category,
                        cp.position,
                        cl.name
                        FROM %scategory_product AS cp
                        LEFT JOIN %scategory_lang AS cl
                        ON cp.id_category = cl.id_category
                        WHERE cp.id_product = %s AND
                        cl.id_lang = %s
                        GROUP BY cp.id_category
                        ',
            _DB_PREFIX_,
            _DB_PREFIX_,
            (int)$itemId,
            (int)$this->getPlugin()->getLanguageId()
        );

        return $this->getDb()->getInstance()->ExecuteS($select);
    }

    /**
     * @param $product_id
     * @param $category_id
     *
     * @return mixed
     */
    protected function getProductPositionByIdAndCategoryId($product_id, $category_id)
    {
        return $this->getDb()->getInstance()->getValue(
            'SELECT position
             FROM `' . _DB_PREFIX_ . 'category_product`
             WHERE `id_product` = ' . (int)$product_id . ' AND `id_category` = ' . (int)$category_id
        );
    }

    /**
     * returns the parent categories
     *
     * @param int $categoryId
     *
     * @return array
     */
    protected function getCategoryPathsFromModel($categoryId)
    {
        $categoryModel = new Category($categoryId, $this->getPlugin()->getLanguageId());

        return $categoryModel->getParentsCategories($this->getPlugin()->getLanguageId());
    }

    /**
     * @param $originalType
     *
     * @return string
     */
    protected function mapVisibility($originalType)
    {
        switch ($originalType) {
            case 'both':
                return Shopgate_Model_Catalog_Visibility::DEFAULT_VISIBILITY_CATALOG_AND_SEARCH;
            case 'catalog':
                return Shopgate_Model_Catalog_Visibility::DEFAULT_VISIBILITY_CATALOG;
            case 'search':
                return Shopgate_Model_Catalog_Visibility::DEFAULT_VISIBILITY_SEARCH;
            case 'none':
                return Shopgate_Model_Catalog_Visibility::DEFAULT_VISIBILITY_NOT_VISIBLE;
        }
    }

    /**
     * calculate the prices
     *
     * @param      $productId
     * @param null $attributeId
     * @param bool $useTax
     * @param bool $useReduction
     * @param int  $quantity
     *
     * @return float
     */
    protected function getItemPrice(
        $productId,
        $attributeId = null,
        $useTax = false,
        $useReduction = false,
        $quantity = 1
    ) {
        return Product::getPriceStatic($productId, $useTax, $attributeId, 6, null, false, $useReduction, $quantity);
    }

    /**
     * calculates the price for a specific customer group. Method priceCalculation is available since 1.4.0.17
     *
     * @param int $shopId
     * @param int $itemId
     * @param int $variantId
     * @param int $groupId
     * @param int $qty
     *
     * @return float
     */
    protected function calculateCustomerGroupPrice($shopId, $itemId, $variantId, $groupId, $qty)
    {
        $specific_price = ''; // This needs to be passed by reference
        return Product::priceCalculation(
            $shopId,
            $itemId,
            $variantId,
            (int)Context::getContext()->country->id,
            0,
            0,
            (int)(Validate::isLoadedObject($this->getPlugin()->getContext()->currency)
                ? $this->getPlugin()
                    ->getContext()->currency->id
                : Configuration::get('PS_CURRENCY_DEFAULT')),
            $groupId,
            (int)$qty,
            $this->isPriceGross(),
            6,
            false,
            true,
            true,
            $specific_price,
            true,
            0,
            true,
            0,
            (int)$qty // without the $realQuantity Prestashop won't return most specific prices
        );
    }

    /**
     * @return bool
     */
    protected function isPriceGross()
    {
        return
            Configuration::get('SHOPGATE_EXPORT_PRICE_TYPE') == Shopgate_Model_Catalog_Price::DEFAULT_PRICE_TYPE_GROSS;
    }

    /**
     * @return bool
     */
    protected function isBasePriceGross()
    {
        return
            Configuration::get('SHOPGATE_EXPORT_BASE_PRICE_TYPE') == Shopgate_Model_Catalog_Price::DEFAULT_PRICE_TYPE_GROSS;
    }
}
