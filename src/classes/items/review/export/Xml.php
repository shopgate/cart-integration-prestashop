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
class ShopgateItemsReviewExportXml extends Shopgate_Model_Review
{
    /**
     * set uid
     */
    public function setUid()
    {
        parent::setUid($this->item['id_product_comment']);
    }

    /**
     * set item uid
     */
    public function setItemUid()
    {
        parent::setItemUid($this->item['id_product']);
    }

    /**
     * set score
     */
    public function setScore()
    {
        parent::setScore((int)$this->item['grade'] * 2);
    }

    /**
     * set reviewer name
     */
    public function setReviewerName()
    {
        parent::setReviewerName($this->item['customer_name']);
    }

    /**
     * set date
     */
    public function setDate()
    {
        parent::setDate(date('Y-m-d', strtotime($this->item['date_add'])));
    }

    /**
     * set title
     */
    public function setTitle()
    {
        parent::setTitle($this->item['title']);
    }

    /**
     * set text
     */
    public function setText()
    {
        parent::setText($this->item['content']);
    }
}
