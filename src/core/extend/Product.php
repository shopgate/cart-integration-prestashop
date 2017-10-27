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
class BWProduct extends Product
{
    /** @var int|null */
    protected $id_lang;

    public function __construct($idProduct = null, $full = false, $idLang = null)
    {
        $this->id_lang = $idLang;
        parent::__construct($idProduct, $full, $idLang);
    }

    /**
     * This function is available from version 1.5.0.1
     *
     * @param int $idProduct
     * @param int $langId
     *
     * @return array
     */
    public static function getAttributesInformationsByProduct($idProduct, $langId)
    {
        $result = array();
        if (Module::isInstalled('blocklayered')) {
            $nbCustomValues = Db::getInstance()->executeS(
                'SELECT DISTINCT la.`id_attribute`, la.`url_name` as `attribute`
                FROM `' . _DB_PREFIX_ . 'attribute` a
                LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac
                    ON (a.`id_attribute` = pac.`id_attribute`)
                LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute` pa
                    ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
                LEFT JOIN `' . _DB_PREFIX_ . 'layered_indexable_attribute_lang_value` la
                    ON (la.`id_attribute` = a.`id_attribute` AND la.`id_lang` = ' . (int)$langId . ')
                WHERE la.`url_name` IS NOT NULL
                AND pa.`id_product` = ' . (int)$idProduct
            );

            if (!empty($nbCustomValues)) {
                $tabIdAttribute = array();
                foreach ($nbCustomValues as $attribute) {
                    $tabIdAttribute[] = $attribute['id_attribute'];

                    $group = Db::getInstance()->executeS(
                        'SELECT g.`id_attribute_group`, g.`url_name` as `group`
                        FROM `' . _DB_PREFIX_ . 'layered_indexable_attribute_group_lang_value` g
                        LEFT JOIN `' . _DB_PREFIX_ . 'attribute` a
                            ON (a.`id_attribute_group` = g.`id_attribute_group`)
                        WHERE a.`id_attribute` = ' . (int)$attribute['id_attribute'] . '
                        AND g.`id_lang` = ' . (int)$langId . '
                        AND g.`url_name` IS NOT NULL'
                    );
                    if (empty($group)) {
                        $group = Db::getInstance()->executeS(
                            'SELECT g.`id_attribute_group`, g.`name` as `group`
                            FROM `' . _DB_PREFIX_ . 'attribute_group_lang` g
                            LEFT JOIN `' . _DB_PREFIX_ . 'attribute` a
                                ON (a.`id_attribute_group` = g.`id_attribute_group`)
                            WHERE a.`id_attribute` = ' . (int)$attribute['id_attribute'] . '
                            AND g.`id_lang` = ' . (int)$langId . '
                            AND g.`name` IS NOT NULL'
                        );
                    }
                    $result[] = array_merge($attribute, $group[0]);
                }
                $valuesNotCustom = Db::getInstance()->executeS(
                    'SELECT DISTINCT a.`id_attribute`, a.`id_attribute_group`, a.`id_attribute_group`, al.`name` as `attribute`, agl.`name` as `group`
                    FROM `' . _DB_PREFIX_ . 'attribute` a
                    LEFT JOIN `' . _DB_PREFIX_ . 'attribute_lang` al
                        ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = ' . (int)$langId . ')
                    LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl
                        ON (a.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = ' . (int)$langId . ')
                    LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac
                        ON (a.`id_attribute` = pac.`id_attribute`)
                    LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute` pa
                        ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
                    WHERE pa.`id_product` = ' . (int)$idProduct . '
                    AND a.`id_attribute` NOT IN(' . pSQL(implode(', ', $tabIdAttribute)) . ')'
                );
                $result            = array_merge($valuesNotCustom, $result);
            } else {
                $result = Db::getInstance()->executeS(
                    'SELECT DISTINCT a.`id_attribute`, a.`id_attribute_group`, al.`name` as `attribute`, agl.`name` as `group`
                    FROM `' . _DB_PREFIX_ . 'attribute` a
                    LEFT JOIN `' . _DB_PREFIX_ . 'attribute_lang` al
                        ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = ' . (int)$langId . ')
                    LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl
                        ON (a.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = ' . (int)$langId . ')
                    LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac
                        ON (a.`id_attribute` = pac.`id_attribute`)
                    LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute` pa
                        ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
                    WHERE pa.`id_product` = ' . (int)$idProduct
                );
            }
        } else {
            $result = Db::getInstance()->executeS(
                'SELECT DISTINCT a.`id_attribute`, a.`id_attribute_group`, al.`name` as `attribute`, agl.`name` as `group`
                FROM `' . _DB_PREFIX_ . 'attribute` a
                LEFT JOIN `' . _DB_PREFIX_ . 'attribute_lang` al
                    ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = ' . (int)$langId . ')
                LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl
                    ON (a.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = ' . (int)$langId . ')
                LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac
                    ON (a.`id_attribute` = pac.`id_attribute`)
                LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute` pa
                    ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
                WHERE pa.`id_product` = ' . (int)$idProduct
            );
        }

        return $result;
    }

    /**
     * Get all product attributes ids
     *
     * @since 1.5.0
     *
     * @param int $idProduct the id of the product
     *
     * @return array product attribute id list
     */
    public static function getProductAttributesIds($idProduct)
    {
        return Db::getInstance()->executeS(
            'SELECT pa.id_product_attribute
            FROM `' . _DB_PREFIX_ . 'product_attribute` pa
            WHERE pa.`id_product` = ' . (int)$idProduct
        );
    }

    /**
     * Fill the variables used for stock management
     */
    public function loadStockData()
    {
        // By default, the product quantity correspond to the available quantity to sell in the current shop
        if (Validate::isLoadedObject($this) && method_exists($this, 'getStockAvailable')) {
            $this->quantity = $this->getStockAvailable();
        }
    }
}
