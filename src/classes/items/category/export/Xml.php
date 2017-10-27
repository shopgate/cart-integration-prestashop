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
class ShopgateItemsCategoryExportXml extends Shopgate_Model_Catalog_Category
{
    /**
     * set uid
     */
    public function setUid()
    {
        parent::setUid($this->item['category_number']);
    }

    /**
     * set name
     */
    public function setName()
    {
        parent::setName($this->item['category_name']);
    }

    /**
     * set parent uid
     */
    public function setParentUid()
    {
        parent::setParentUid($this->item['parent_id']);
    }

    /**
     * set sort order
     */
    public function setSortOrder()
    {
        parent::setSortOrder($this->item['order_index']);
    }

    /**
     * set deep link
     */
    public function setDeeplink()
    {
        parent::setDeeplink($this->item['url_deeplink']);
    }

    /**
     * set is anchor
     */
    public function setIsAnchor()
    {
        parent::setIsAnchor(false);
    }

    /**
     * set is active
     */
    public function setIsActive()
    {
        parent::setIsActive($this->item['is_active']);
    }

    /**
     * set image
     */
    public function setImage()
    {
        $imageItem = new Shopgate_Model_Media_Image();
        $imageItem->setUid($this->item['category_number']);
        $imageItem->setUrl($this->item['url_image']);
        parent::setImage($imageItem);
    }
}
