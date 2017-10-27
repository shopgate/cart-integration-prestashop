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
class ShopgateItemsProductAttribute
{
    /** @var int */
    private $id;

    /** @var bool */
    private $defaultOn;

    /** @var float */
    private $wholeSalePrice;

    /** @var int */
    private $minimalQuantity;

    /** @var float */
    private $unitPriceImpact;

    /** @var int */
    private $quantity;

    /** @var ShopgateItemsAttributeCombination[] */
    private $attributeCombinations;

    /**
     * @param int   $id
     * @param bool  $defaultOn
     * @param float $wholeSalePrice
     * @param int   $minimalQuantity
     * @param float $unitPriceImpact
     * @param int   $quantity
     */
    public function __construct(
        $id,
        $defaultOn,
        $wholeSalePrice,
        $minimalQuantity,
        $unitPriceImpact,
        $quantity
    ) {
        $this->id                    = $id;
        $this->defaultOn             = $defaultOn;
        $this->wholeSalePrice        = $wholeSalePrice;
        $this->minimalQuantity       = $minimalQuantity;
        $this->unitPriceImpact       = $unitPriceImpact;
        $this->quantity              = $quantity;
        $this->attributeCombinations = array();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return bool
     */
    public function isDefaultOn()
    {
        return $this->defaultOn;
    }

    /**
     * @param bool $defaultOn
     */
    public function setDefaultOn($defaultOn)
    {
        $this->defaultOn = $defaultOn;
    }

    /**
     * @return float
     */
    public function getWholeSalePrice()
    {
        return $this->wholeSalePrice;
    }

    /**
     * @param float $wholeSalePrice
     */
    public function setWholeSalePrice($wholeSalePrice)
    {
        $this->wholeSalePrice = $wholeSalePrice;
    }

    /**
     * @return int
     */
    public function getMinimalQuantity()
    {
        return $this->minimalQuantity;
    }

    /**
     * @param int $minimalQuantity
     */
    public function setMinimalQuantity($minimalQuantity)
    {
        $this->minimalQuantity = $minimalQuantity;
    }

    /**
     * @return float
     */
    public function getUnitPriceImpact()
    {
        return $this->unitPriceImpact;
    }

    /**
     * @param float $unitPriceImpact
     */
    public function setUnitPriceImpact($unitPriceImpact)
    {
        $this->unitPriceImpact = $unitPriceImpact;
    }

    /**
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param int $quantity
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
    }

    /**
     * @return ShopgateItemsAttributeCombination[]
     */
    public function getAttributeCombinations()
    {
        return $this->attributeCombinations;
    }

    /**
     * @param ShopgateItemsAttributeCombination $attributeCombination
     */
    public function addAttributeCombination(ShopgateItemsAttributeCombination $attributeCombination)
    {
        $this->attributeCombinations[] = $attributeCombination;
    }
}
