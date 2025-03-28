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

/**
 * Class ShopgateItemsBasePrice
 */
class ShopgateItemsBasePrice
{
    /** @var ShopgatePrestashopVersion */
    private $prestashopVersion;

    /** @var string */
    private $currencyIsoCode;

    /**
     * @param ShopgatePrestashopVersion $prestashopVersion
     * @param string                    $currencyIsoCode
     */
    public function __construct(ShopgatePrestashopVersion $prestashopVersion, $currencyIsoCode)
    {
        $this->prestashopVersion = $prestashopVersion;
        $this->currencyIsoCode   = $currencyIsoCode;
    }

    /**
     * @param float  $basePrice
     * @param string $basePriceUnit
     *
     * @return string
     */
    public function getBasePriceFormatted($basePrice, $basePriceUnit)
    {
        $basePrice = number_format($basePrice, 2);

        return $basePrice . ' ' . $this->currencyIsoCode . (!empty($basePriceUnit)
                ? ' / ' . $basePriceUnit
                : '');
    }

    /**
     * @param float      $basePrice
     * @param bool       $useTax
     * @param float|null $taxRate
     *
     * @return float
     */
    public function calculateBasePrice($basePrice, $useTax, $taxRate = null)
    {
        if ($useTax && $taxRate !== null) {
            $basePrice = $this->addTaxes($basePrice, $taxRate);
        }

        return $this->round($basePrice);
    }

    /**
     * @param float      $childProductPriceExclTax
     * @param float      $parentProductUnitPriceRatio
     * @param int        $unitPriceImpact (1 => Increase, 0 => None, -1 => Reduction)
     * @param bool       $useTax
     * @param float|null $taxRate
     *
     * @return float
     */
    public function calculateChildBasePrice(
        $childProductPriceExclTax,
        $parentProductUnitPriceRatio,
        $unitPriceImpact,
        $useTax,
        $taxRate = null
    ) {
        $basePrice = ($childProductPriceExclTax / $parentProductUnitPriceRatio);

        if ($useTax && $taxRate !== null) {
            $basePrice = $this->addTaxes($basePrice, $taxRate);

            if (version_compare($this->prestashopVersion->getVersion(), '1.6.0.11', '>=')) {
                $unitPriceImpact = $this->addTaxes($unitPriceImpact, $taxRate);
            }
        }

        $basePrice += $unitPriceImpact;

        return $this->round($basePrice);
    }

    /**
     * @param int $unitPriceImpact
     *
     * @return bool
     */
    public function basePriceAffectsChildren($unitPriceImpact)
    {
        return isset($unitPriceImpact) && $unitPriceImpact != 0;
    }

    /**
     * @param Product $product
     *
     * @return bool
     */
    public function basePriceActive($product)
    {
        return $product->unit_price != 0;
    }

    /**
     * @param float $price
     *
     * @return float
     */
    private function round($price)
    {
        return (float)round($price * 100) / 100;
    }

    /**
     * @param float $price
     * @param float $taxRate
     *
     * @return float
     */
    private function addTaxes($price, $taxRate)
    {
        return $price + ($price * $taxRate / 100);
    }
}
