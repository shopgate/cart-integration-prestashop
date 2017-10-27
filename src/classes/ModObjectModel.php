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
abstract class ShopgateModObjectModel extends ObjectModel
{
    /** List of field types */
    const TYPE_INT     = 1;
    const TYPE_BOOL    = 2;
    const TYPE_STRING  = 3;
    const TYPE_FLOAT   = 4;
    const TYPE_DATE    = 5;
    const TYPE_HTML    = 6;
    const TYPE_NOTHING = 7;
    const TYPE_SQL     = 8;
    /** List of data to format */
    const FORMAT_COMMON = 1;
    const FORMAT_LANG   = 2;
    const FORMAT_SHOP   = 3;
    /** List of association types */
    const HAS_ONE  = 1;
    const HAS_MANY = 2;

    /** @var string SQL Table name */
    protected $table = 'shopgate_order';

    /** @var string SQL Table identifier */
    protected $identifier = 'id_shopgate_order';

    public $total_shipping;

    public static function updateShippingPrice($price)
    {
        $sql = 'UPDATE ' . _DB_PREFIX_ . 'delivery AS d SET price="' . (float)$price . '" WHERE d.id_carrier = ' . (int)Configuration::get(
                'SG_CARRIER_ID'
            );

        return Db::getInstance()->execute($sql);
    }
}
