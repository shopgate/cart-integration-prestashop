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
class ShopgateItemsReview extends ShopgateItemsAbstract
{
    /**
     * @param null $limit
     * @param null $offset
     *
     * @return array
     */
    public function getItems($limit = null, $offset = null)
    {
        $reviews = array();

        if (ShopgateHelper::checkTable(sprintf('%sproduct_comment', _DB_PREFIX_))) {
            $reviews = $this->getDb()->getInstance()->ExecuteS(
                sprintf(
                    'SELECT * FROM %sproduct_comment WHERE validate = 1 AND deleted = 0%s%s',
                    _DB_PREFIX_,
                    is_int($limit)
                        ? ' LIMIT ' . $limit
                        : '',
                    is_int($offset)
                        ? ' OFFSET ' . $offset
                        : ''
                )
            );
        }

        return $reviews;
    }
}
