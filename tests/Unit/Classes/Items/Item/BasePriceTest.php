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

namespace Shopgate\Tests\Unit\Classes\Items\Item;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BasePriceTest extends TestCase
{
    const BASE_PRICE                    = 'base_price';
    const BASE_PRICE_UNIT               = 'base_unit';
    const CURRENCY_ISO_CODE             = 'currency_iso_code';
    const USE_TAX                       = 'use_tax';
    const TAX_RATE                      = 'tax_rate';
    const PRODUCT                       = 'product';
    const COMBINATION                   = 'combination';
    const CHILD_PRODUCT_PRICE           = 'child_product_price';
    const PARENT_UNIT_PRICE_RATIO       = 'parent_unit_price_ratio';
    const UNIT_PRICE_IMPACT             = 'unit_price_impact';
    const PRESTASHOP_VERSION            = 'prestashop_version';
    const EXPECTED_RESULT               = 'expected_result';
    const EXPECTED_BASE_PRICE_FORMATTED = 'expected_base_price_formatted';
    const EXPECTED_BASE_PRICE           = 'expected_base_price';

    /** @var \ShopgateItemsBasePrice $subjectUnderTest */
    protected $subjectUnderTest;

    /** @var \ShopgatePrestashopVersion */
    protected $prestashopVersion;

    public function setUp(): void
    {
        $this->prestashopVersion = new \ShopgatePrestashopVersion('1.7.1.1');
        $this->subjectUnderTest  = new \ShopgateItemsBasePrice(
            $this->prestashopVersion,
            'EUR'
        );
    }

    /**
     * @param string     $expectedBasePrice
     * @param string     $prestashopVersion
     * @param float      $childProductPriceWithTax
     * @param float      $parentUnitPriceRatio
     * @param int        $unitPriceImpact
     * @param bool       $useTax
     * @param float|null $taxRate
     *
     * @dataProvider provideChildrenBasePriceCases
     */
    public function testCalculateChildBasePrice(
        $expectedBasePrice,
        $prestashopVersion,
        $childProductPriceWithTax,
        $parentUnitPriceRatio,
        $unitPriceImpact,
        $useTax,
        $taxRate
    ) {
        $this->prestashopVersion->setVersion($prestashopVersion);
        $this->assertEquals(
            $expectedBasePrice,
            $this->subjectUnderTest->calculateChildBasePrice(
                $childProductPriceWithTax,
                $parentUnitPriceRatio,
                $unitPriceImpact,
                $useTax,
                $taxRate
            )
        );
    }

    /**
     * @return array
     */
    public function provideChildrenBasePriceCases()
    {
        return array(
            'no change'                                                        => array(
                self::EXPECTED_BASE_PRICE     => 40,
                self::PRESTASHOP_VERSION      => '1.7.1.1',
                self::CHILD_PRODUCT_PRICE     => 40,
                self::PARENT_UNIT_PRICE_RATIO => 1,
                self::UNIT_PRICE_IMPACT       => 0,
                self::USE_TAX                 => false,
                self::TAX_RATE                => null,
            ),
            'parent unit price ratio lower 1'                                  => array(
                self::EXPECTED_BASE_PRICE     => 80,
                self::PRESTASHOP_VERSION      => '1.7.1.1',
                self::CHILD_PRODUCT_PRICE     => 40,
                self::PARENT_UNIT_PRICE_RATIO => 0.5,
                self::UNIT_PRICE_IMPACT       => 0,
                self::USE_TAX                 => false,
                self::TAX_RATE                => null,
            ),
            'parent unit price ratio higher 1'                                 => array(
                self::EXPECTED_BASE_PRICE     => 26.67,
                self::PRESTASHOP_VERSION      => '1.7.1.1',
                self::CHILD_PRODUCT_PRICE     => 40,
                self::PARENT_UNIT_PRICE_RATIO => 1.5,
                self::UNIT_PRICE_IMPACT       => 0,
                self::USE_TAX                 => false,
                self::TAX_RATE                => null,
            ),
            'with taxes'                                                       => array(
                self::EXPECTED_BASE_PRICE     => 47.6,
                self::PRESTASHOP_VERSION      => '1.7.1.1',
                self::CHILD_PRODUCT_PRICE     => 40,
                self::PARENT_UNIT_PRICE_RATIO => 1,
                self::UNIT_PRICE_IMPACT       => 0,
                self::USE_TAX                 => true,
                self::TAX_RATE                => 19.00,
            ),
            'positive unit price impact'                                       => array(
                self::EXPECTED_BASE_PRICE     => 41,
                self::PRESTASHOP_VERSION      => '1.7.1.1',
                self::CHILD_PRODUCT_PRICE     => 40,
                self::PARENT_UNIT_PRICE_RATIO => 1,
                self::UNIT_PRICE_IMPACT       => 1,
                self::USE_TAX                 => false,
                self::TAX_RATE                => null,
            ),
            'negative unit price impact'                                       => array(
                self::EXPECTED_BASE_PRICE     => 39,
                self::PRESTASHOP_VERSION      => '1.7.1.1',
                self::CHILD_PRODUCT_PRICE     => 40,
                self::PARENT_UNIT_PRICE_RATIO => 1,
                self::UNIT_PRICE_IMPACT       => -1,
                self::USE_TAX                 => false,
                self::TAX_RATE                => null,
            ),
            'mixing with taxes, ratio and postive impact'                      => array(
                self::EXPECTED_BASE_PRICE     => 43.57,
                self::PRESTASHOP_VERSION      => '1.7.1.1',
                self::CHILD_PRODUCT_PRICE     => 17.806723,
                self::PARENT_UNIT_PRICE_RATIO => 0.5,
                self::UNIT_PRICE_IMPACT       => 1,
                self::USE_TAX                 => true,
                self::TAX_RATE                => 19.00,
            ),
            'mixing with taxes, ratio and negative impact'                     => array(
                self::EXPECTED_BASE_PRICE     => 41.19,
                self::PRESTASHOP_VERSION      => '1.7.1.1',
                self::CHILD_PRODUCT_PRICE     => 17.806723,
                self::PARENT_UNIT_PRICE_RATIO => 0.5,
                self::UNIT_PRICE_IMPACT       => -1,
                self::USE_TAX                 => true,
                self::TAX_RATE                => 19.00,
            ),
            'Until Prestashop 1.6.0.10 variant unit price impact has no taxes' => array(
                self::EXPECTED_BASE_PRICE     => 48.6,
                self::PRESTASHOP_VERSION      => '1.6.0.10',
                self::CHILD_PRODUCT_PRICE     => 40,
                self::PARENT_UNIT_PRICE_RATIO => 1,
                self::UNIT_PRICE_IMPACT       => 1,
                self::USE_TAX                 => true,
                self::TAX_RATE                => 19.00,
            ),
        );
    }

    /**
     * @param string     $expectedBasePrice
     * @param float      $basePrice
     * @param bool       $useTax
     * @param float|null $taxRate
     *
     * @dataProvider provideBasePriceCases
     */
    public function testCalculateBasePrice($expectedBasePrice, $basePrice, $useTax, $taxRate)
    {
        $this->assertEquals(
            $expectedBasePrice,
            $this->subjectUnderTest->calculateBasePrice($basePrice, $useTax, $taxRate)
        );
    }

    /**
     * @return array
     */
    public function provideBasePriceCases()
    {
        return array(
            'without tax' => array(
                self::EXPECTED_BASE_PRICE => 5.00,
                self::BASE_PRICE          => 5,
                self::USE_TAX             => false,
                self::TAX_RATE            => null,
            ),
            'with tax'    => array(
                self::EXPECTED_BASE_PRICE => 5.95,
                self::BASE_PRICE          => 5,
                self::USE_TAX             => true,
                self::TAX_RATE            => 19,
            ),
        );
    }

    /**
     * @param string $expectedResult
     * @param float  $basePrice
     * @param string $basePriceUnit
     *
     * @dataProvider provideBasePriceFormattedCases
     */
    public function testBasePriceFormatted($expectedResult, $basePrice, $basePriceUnit)
    {
        $this->assertEquals(
            $expectedResult,
            $this->subjectUnderTest->getBasePriceFormatted($basePrice, $basePriceUnit)
        );
    }

    /**
     * @return array
     */
    public function provideBasePriceFormattedCases()
    {
        return array(
            'without tax' => array(
                self::EXPECTED_BASE_PRICE_FORMATTED => '5.00 EUR / 100g',
                self::BASE_PRICE                    => 5,
                self::BASE_PRICE_UNIT               => '100g',
            ),
            'with tax'    => array(
                self::EXPECTED_BASE_PRICE_FORMATTED => '5.95 EUR / 100g',
                self::BASE_PRICE                    => 5.95,
                self::BASE_PRICE_UNIT               => '100g',
            ),
            'per kg'      => array(
                self::EXPECTED_BASE_PRICE_FORMATTED => '5.95 EUR / kg',
                self::BASE_PRICE                    => 5.95,
                self::BASE_PRICE_UNIT               => 'kg',
            ),
        );
    }

    /**
     * @param bool     $expectedResult
     * @param \Product $product
     *
     * @dataProvider provideBasePriceActiveCases
     */
    public function testBasePriceActive($expectedResult, \Product $product)
    {
        $this->assertEquals($expectedResult, $this->subjectUnderTest->basePriceActive($product));
    }

    /**
     * @return array
     */
    public function provideBasePriceActiveCases()
    {
        return array(
            'active'   => array(
                self::EXPECTED_RESULT => true,
                self::PRODUCT         => $this->getProductMock(2.99),
            ),
            'inactive' => array(
                self::EXPECTED_RESULT => false,
                self::PRODUCT         => $this->getProductMock(0),
            ),
        );
    }

    /**
     * @param bool         $expectedResult
     * @param \Combination $combination
     *
     * @dataProvider provideBasePriceAffectsChildrenCases
     */
    public function testBasePriceEffectsChildren($expectedResult, \Combination $combination)
    {
        $this->assertEquals(
            $expectedResult,
            $this->subjectUnderTest->basePriceAffectsChildren($combination->unit_price_impact)
        );
    }

    /**
     * @return array
     */
    public function provideBasePriceAffectsChildrenCases()
    {
        return array(
            'active 1'  => array(
                self::EXPECTED_RESULT => true,
                self::COMBINATION     => $this->getCombinationMock(1),
            ),
            'active -1' => array(
                self::EXPECTED_RESULT => true,
                self::COMBINATION     => $this->getCombinationMock(-1),
            ),
            'inactive'  => array(
                self::EXPECTED_RESULT => false,
                self::COMBINATION     => $this->getCombinationMock(0),
            ),
        );
    }

    /**
     * @param float $unitPrice
     *
     * @return \Product | MockObject
     */
    public function getProductMock($unitPrice)
    {
        $productMock             = $this->getMockBuilder('\Product')->getMock();
        $productMock->unit_price = $unitPrice;

        return $productMock;
    }

    /**
     * @param int $unitPriceImpact
     *
     * @return \Combination | MockObject
     */
    public function getCombinationMock($unitPriceImpact)
    {
        $productCombinationMock                    = $this->getMockBuilder('\Combination')->getMock();
        $productCombinationMock->unit_price_impact = $unitPriceImpact;

        return $productCombinationMock;
    }
}
