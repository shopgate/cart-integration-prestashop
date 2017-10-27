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
class ShopgateItemsCategory extends ShopgateItemsAbstract
{
    /**
     * default pattern category image
     */
    const PS_CONST_IMAGE_TYPE_CATEGORY_DEFAULT = 'category%sdefault';

    /**
     * Checks if provided category is a root category
     *
     * @param CategoryCore $category
     *
     * @return bool
     */
    protected function isRootCategory($category)
    {
        $isRoot = property_exists($category, 'is_root_category')
            ? $category->is_root_category
            : !$category->id_parent;

        return (bool)$isRoot;
    }

    /**
     * @return array
     */
    public function getItems()
    {
        $categoryItems = array();
        $result        = array();

        $exportRootCategories = Configuration::get('SG_EXPORT_ROOT_CATEGORIES') == 1;

        $skippedRootCategories = array();

        foreach (Category::getSimpleCategories($this->getPlugin()->getLanguageId()) as $category) {
            /** @var CategoryCore $categoryInfo */
            $categoryInfo        = new Category($category['id_category']);
            $categoryLinkRewrite = $categoryInfo->getLinkRewrite(
                $categoryInfo->id_category,
                $this->getPlugin()->getLanguageId()
            );
            $isRootCategory      = $this->isRootCategory($categoryInfo);

            /**
             * skip root categories
             */
            if ($isRootCategory && !$exportRootCategories) {
                $skippedRootCategories[] = $categoryInfo->id_category;
                continue;
            }

            $categoryItem                    = array();
            $categoryItem['category_number'] = $categoryInfo->id_category;
            $categoryItem['category_name']   = $categoryInfo->getName($this->getPlugin()->getLanguageId());
            $categoryItem['parent_id']       = $isRootCategory || in_array(
                $categoryInfo->id_category,
                $skippedRootCategories
            )
                ? ''
                : $categoryInfo->id_parent;
            $categoryItem['is_active']       = $categoryInfo->active;

            $categoryItem['url_deeplink'] =
                $this->getPlugin()->getContext()->link->getCategoryLink(
                    $categoryInfo->id_category,
                    $categoryLinkRewrite,
                    $this->getPlugin()->getLanguageId()
                );

            $categoryImageUrl = $this->getPlugin()->getContext()->link->getCatImageLink(
                $categoryLinkRewrite,
                $categoryInfo->id_category,
                sprintf(self::PS_CONST_IMAGE_TYPE_CATEGORY_DEFAULT, '_')
            );

            $categoryItem['url_image']   = $categoryImageUrl;
            $categoryItem['position']    = $categoryInfo->position;
            $categoryItem['order_index'] = $categoryInfo->position;

            $categoryItems[] = $categoryItem;
        }

        /**
         * clean root categories
         */
        if (!$exportRootCategories) {
            foreach ($categoryItems as $key => $categoryItem) {
                if (in_array($categoryItem['parent_id'], $skippedRootCategories)) {
                    $categoryItems[$key]['parent_id'] = '';
                }
            }
        }

        /**
         * calculate max category position
         */
        $maxCategoryPosition = array();
        foreach ($categoryItems as $categoryItem) {
            $key = $categoryItem['parent_id'] == ''
                ? 'root'
                : $categoryItem['parent_id'];
            if (!array_key_exists($key, $maxCategoryPosition)) {
                $maxCategoryPosition[$key] = 1;
            } else {
                if ($categoryItem['position'] > $maxCategoryPosition[$key]) {
                    $maxCategoryPosition[$key] = $categoryItem['position'];
                }
            }
        }

        foreach ($categoryItems as $categoryItem) {
            $key                         = $categoryItem['parent_id'] == ''
                ? 'root'
                : $categoryItem['parent_id'];
            $categoryItem['order_index'] = $maxCategoryPosition[$key] - $categoryItem['order_index'];
            $result[]                    = $categoryItem;
        }

        return $result;
    }
}
