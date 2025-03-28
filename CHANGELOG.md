# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
### Fixed
- tax rules will no longer be exported when no taxes are assigned
- PrestaShop 1.7 compatibility

### Added
- support for PrestaShop 8.x
- default payment method mapping for invoice and SIX Saferpay

### Removed
- support for PrestaShop < 1.7.x

## [2.9.85] - 2018-02-01
### Fixed
- configuration issue

## [2.9.84] - 2018-01-29
### Changed
- use constant instead of string as key for LA POST shipping service element when adding it to shipping_service_list
- set plugin to use version 2.9.71 of cart-integration-sdk instead of 2.9.69
### Fixed
- added store pickup to shipping_service_list
- reset of Shopgate configuration for multi shops when settings were updated remotely

## [2.9.83] - 2017-10-27
### Changed
- migrated Shopgate integration for Prestashop to GitHub
- extended error handling for database transactions
### Fixed
- fixed order totals when order shipping is a manually created shipping method

## 2.9.82
- added support from version 1.7.-

## 2.9.81
- fixed a bug that caused zero-value coupons after a shipping method had been selected

## 2.9.80
- fixed api url generations so it will use https when shop uses https
- fixed missing attribute groups in product export

## 2.9.79
- uses shopgate library 2.9.65

## 2.9.78
- fixed basic price tax issue in product export

## 2.9.77
- fixed add_order issue in shipping class
- fixed saving of sent configuration values via method set_setting

## 2.9.76
- fixed wrong order total for orders with shopgate coupons
- fixed wrong calculation of shipping methods in cart validation
- fixed missing tier prices in product export
- fixed wrong shipping method costs with "pay in store" orders

## 2.9.75
- cleaning up check_cart and add_order warnings
- uses shopgate library 2.9.61

## 2.9.74
- fixed shipping state issue
- uses shopgate library 2.9.60
- fixed duplicate orders in case of errors during order import
- fixed invalid carrier error in check cart
- fixed missing payment methods in cart validation

## 2.9.73
- fixed missing shipping methods in check_cart
- fixed add_order issue for orders with manually shipping cost
- fixed shopgate coupons not showing as vouchers in order detail page
- added a database wrapper for future test mocking

## 2.9.72
- added product customization printing on order page
- fixed error when importing orders with invalid carriers
- fixed when saving shopgate order after add_order fails mid way

## 2.9.71
- fixed Shopgate Coupons for older versions

## 2.9.70
- fixed getCustomer - retrieve state and country as ISO

## 2.9.69
- fixed price calculation in check_cart

## 2.9.68
- fixed missing bank data in prepayment confirmation mails

## 2.9.67
- added shopgate coupon support
- fixed issue on install missing table columns

## 2.9.66
- fixed issue on check_cart method
- fixed compatibility issue with Prestashop version 1.4.x.x
- fixed export for multilanguage shops

## 2.9.65
- fixed issue on install hooks

## 2.9.64
- fixed a bug in stock management for older versions

## 2.9.63
- fixed a bug in exporting tier prices tied to a specific currency
- fixed a bug in cancel orders

## 2.9.62
- fixed a bug that caused importing orders to fail

## 2.9.61
- fixed a bug that caused importing orders to fail
- fixed duplicate customer addresses in order import and customer registration
- fixed usage of wrong languages during export

## 2.9.60
- fixed shop_numbers settings in config
- fixed disabled use countries on check_cart request
- fix register customer / set optin

## 2.9.59
- fixed set_is_active settings in config
- reestablished compatibility to Prestashop version 1.4.x.x because of some smarty v2 template issues
- fixed caching issue which caused errors on importing orders

## 2.9.58
- fixed config shop_number for multilanguage

## 2.9.57
- fixed export of root categories in prestashop versions 1.4.x.x
- fixed multilanguage based on shopsystem lang
- fixed possibility to set shop is active and handle alternate tag

## 2.9.56
- fixing sort order for categories
- implemented return of payment methods in method get_settings
- implemented restriction of valid payment methods while checking/validating the cart

## 2.9.55
- added support of allowing product based carriers
- uses Shopgate Library 2.9.36
- fixed order status change after the order was already shipped

## 2.9.54
- fixed compatibility issue for creating a constant
- improved compatibility for multi shop support in product export
- fixed issue with wrong cover image for products

## 2.9.53
- added redirect to mobil brand product listing
- shipments can now also be transferred to shopgate via cron (set_shipping_completed)
- added sku to product export
- fixed currency issue
- fixed empty identifiers in product export
- fixed issues in reviews export
- fixed a small product export issue in case product database entries are corrupt
- fixed duplicate discount for specific coupons

## 2.9.52
- fix state for customer addresses during order import
- fix shopgate carrier
- fixed product tax class export
- inactive products are no longer exported

## 2.9.51
- added custom fields mapping to database fields in case its possible
- fixed display problem of Shopgate orders in the Prestashop backend

## 2.9.50
- new translation files for ES, IT, NL, PL, PT

## 2.9.49
- optimized get items query
- added selected shipping method to check_cart
- fix get installed plugins on ping method for invalid plugins
- added check cart error messages from system
- fixed missing shipping costs on imported orders
- fixed compatibility issues for older Prestashop versions 1.4.x.x
- increased performance of XML uids product export
- fixed product import issue because of invalid field "tax percent"
- cancellations are supported now

## 2.9.48
- fixing mobile redirect for older versions

## 2.9.47
- fixed empty phone number for addresses
- changed default carrier to shopgate
- fixed issue in stock check of products
- fixed issues in returning shipping methods during cart validation
- fixed configuration mapping (Mobile carrier) for shipping methods
- fixed product export for product relations
- fixed product export field tax class
- uses Shopgate Library 2.9.21

## 2.9.46
- fixed bug with similar products
- fixing issue with voucher tax

## 2.9.45
- fixed issue with availability text
- fixed issue in export of tax rules
- fix config issues on install
- uses Shopgate Library 2.9.19

## 2.9.44
- fixing config issues for older PHP versions

## 2.9.43
- Fix issue in Shopgate Order Model
- Fix issue include backward_compatibility
- raised library version to 1.9.17
- fixed compatibility issue with Prestashop version 1.4.x.x
- fixed installation issues

## 2.9.42
- Fix issue on create Shopgate carrier
- Fix issue in category export
- Fix issue in XML validation
- Fix Tax issues
- Fix issues with mobile redirect

## 2.9.41
- Fixing backward_compatibility issue

## 2.9.40
- root categories can now be exported either on top-level or simply as sub categories (configuration setting)

## 2.9.39
- release of the new refactored plugin
- improved compatibility with module eu_legal (Attention: This entry refers to the old version of the plugin)

## 2.9.38
- Optimize item available view
- Fixing ShogateOrder problems

## 2.9.37
- added export methods for reviews - XML and CSV
- fixed compatibility issue with our shopgate_orders table in Prestashop >= 1.6.0.11

## 2.9.36
- fixed error in check_cart
- fixed error in add_order
- fixed error while calculating tier prices
- fixed error in mobile redirect

## 2.9.35
- fixed issue on configurations page of the module in older versions

## 2.9.34
- fixed issue in product export of tax class

## 2.9.33
- fixed issue in export of tax rates and product tax classes

## 2.9.32
- fixed a bug in export of tier prices
- fixed compatibility issue in version Prestashop 1.5.0.x

## 2.9.30
- fixed a bug in check_cart (concerning state codes)

## 2.9.29
- changed default value for not shipping blocked and not paid orders
- added validate cart rule combinations with check_cart
- add additional price to import options
- enabled register_customer
- bug in getting the tax rates fixed
- bug in setting the zipcode range for taxes fixed
- add inputs for order

## 2.9.28
- translate stock messages
- fix available_for_order
- added possibility to disable carrier for mobile shopping
- fix sale price for children

## 2.9.27
- fix tax for tier price
- fix deprecated prefix DB

## 2.9.26
- added base price for child products

## 2.9.25
- fix version number

## 2.9.24
- fix logic for description
- part 2 - optimize structure for prestashop validator
- fix check curl

## 2.9.23
- base price calculated on price type

## 2.9.22
- update lib

## 2.9.21
- added base price
- part 1 - optimize structure for prestashop validator

## 2.9.20
- fix category import

## 2.9.19
- fix sale price

## 2.9.18
- update getSettings (zip_ranges)
- fix config for older php versions

## 2.9.17
- fix categories for multistore
- change identifier - reference to sku
- update payment mapping / order status
- fix root categories for multistore

## 2.9.16
- fix sort order for coupon
- fix invalid / not active country codes

## 2.9.15
- fix customer group on addOrder

## 2.9.14
- change logic for availability text

## 2.9.13
- fix validate coupon for registered customers
- fix export is_saleable for child products

## 2.9.12
- fix default shop id for tier prices

## 2.9.11
- fix default attribute id for redirect

## 2.9.10
- fix admin_template for version 1.3
- fix attributegroup label (public)

## 2.9.9
- fix default customer group
- fix backorders (children) for version < 1.5

## 2.9.8
- add shop id condition to special price query
- fix backorders for version < 1.5

## 2.9.7
- fix class_exists

## 2.9.6
- export price variant (net/gross) in configuration

## 2.9.5
- order detail view - custom fields
- check active price rules
- fix price for reduced products

## 2.9.4
- optimized check_cart

## 2.9.3
- fix image info for version < 1.3.3

## 2.9.2
- fix image link for version < 1.3.3

## 2.9.1
- fix setIsSaleable for version < 1.5

## 2.9.0
- uses shopgate library 2.9.x
- enable get reviews

## 2.8.9
- Fix activation check_cart >= 1.5
- checkTable specific_price

## 2.8.8
- Fix XML setImages

## 2.8.7
- Fix autoload on check_class

## 2.8.6
- Fix getRequiredAddressFields for version < 1.5

## 2.8.5
- Fix getSettings for version < 1.5

## 2.8.4
- Fix Product Identifier for check_cart XML / CSV

## 2.8.3
- fix product position in category for xml and csv

## 2.8.2
- added new functions getOrders and syncFavouriteList

## 2.8.1
- fix create Customer

## 2.8.0
- new Lib 2.8.x
- sorting images for xml and CSV

## 2.7.4
- change default tax from GROSS to NET

## 2.7.2
- extend getSettings method

## 2.7.1
- new lib version added
- extended ping method

## 2.6.22
- Fix getSettings - check is global tax active
- added register customer method

## 2.6.21
- set order_state PS_OS_PREPARATION for COD
- Fix getSettings - check is tax active

## 2.6.20
- added AggregateChildren / Tierprices for XML export
- Fix getSettings / Tax

## 2.6.19
- added config setting - export rootcategory
- adjust exception codes for minimum and maximum stock available
- quick fix for empty delivery and shipping address phone numbers

## 2.6.18
- Fix: added state und phone for check cart
- fill data on ping for supported_fields_check_cart and supported_fields_get_settings

## 2.6.17
- Fix: delete "myconfig.php" on deinstall

## 2.6.16
- Fix set_settings for Multistore

## 2.6.15
- Fix comment standards for Prestashop

## 2.6.14
- Fix coding standards for Prestashop

## 2.6.13
- XML Item Export / Export Price without discount

## 2.6.12
- XML Item Export / fix Percent for tier prices

## 2.6.11
- fix remove configuration data on deinstall / extend get_settings

## 2.6.10
- fix category sorting

## 2.6.9
- fix admin order hook

## 2.6.8
- Fix backwards compatibility

## 2.6.7
- fix shop id for version > 1.5

## 2.6.6
- enable get settings as default

## 2.6.5
- fix configuration data from database

## 2.6.4
- Fix PluginModelCategoryObject

## 2.6.3
- added missing PluginModelCategoryObject

## 2.6.2
- API function XML export for products and categories

## 2.6.1
- added API function - get_settings

## 2.6.0
- Fixed problem with non-existing parent categories

## 2.5.11
- fix bug root category for version 1.4.x

## 2.5.10
- fix product category id export

## 2.5.9
- fix product category id export

## 2.5.8
- fix product category id export

## 2.5.7
- enable_default_redirect = false (0)

## 2.5.6
- improved picture export for US plugin
- multistore functionality

## 2.5.6
- improved creation of customer at order import
- new logos and design of shopgate configuration
- improved german, english and french translations
- if plugin shipping method is used it gets imported well while adding the order

## 2.5.4
- product images in Prestashop < 1.4.1.0 get exported in correct sort order as well
- new configuration for newsletter subscription of new mobile customer
- remove call of deprecated method for Prestashop >= 1.5.0.10
- fix export of categories with no parent selected for Prestashop >= 1.5.0.0

## 2.5.3
- exports original product images instead of thickbox

## 2.5.2
- new configuration for product export (short-)description
- extended compatibility of dropdown options in module configuration for Prestashop >=1.3

## 2.5.1
- feature: enabled mobile use of shop coupons for Prestashop >= 1.4
- feature: added La Poste as shipping provider
- uses Shopgate Library 2.5.3
- enabled check_cart und redeem_coupon action for Prestashop >= 1.4

## 2.5.0
- update config layout
- uses Shopgate Library 2.5.1

## 2.4.6
- fix not tax class available

## 2.4.5
- fix deep link for no indexed products

## 2.4.4
- Smarty Bug in admin_order.tpl gefixt and check is array

## 2.4.3
- fix Smarty bug in admin_order.tpl

## 2.4.2
- fixed issue with compatibility in Prestashop version 1.3.x.x (class loader)

## 2.4.1
- fixed issue with compatibility in Prestashop version 1.3.x.x

## 2.4.0
- uses Shopgate Library 2.4.0

## 2.3.7
- fixed issue with product export (tax) in Prestashop version 1.4.1.0
- uses Shopgate Library 2.3.9

## 2.3.6
- fixed issue of products without tax classes on product export
- added french translation
- changed paypal status for paid order

## 2.3.5
- fixed issue in order import (exception Swift_Message_MimeException)

## 2.3.4
- adapted code for Prestashop code conventions

## 2.3.3
- Shopgate Library moved to directory "/vendors"
- Shopgate Prestashop module code published under AFL license
- uses Shopgate Library 2.3.6

## 2.3.2
- uses Shopgate Library 2.3.5
- adapted code for Prestashop code conventions

## 2.3.1
- fixed issue in order import if stock quantity was 0
- uses Shopgate Library 2.3.4
- adapted code for Prestashop code conventions

## 2.3.0
- fixed issue with stock management in product export
- adapted code for Prestashop code conventions
- only home page, product detail pages and category pages are redirected to the mobile web site from now on. There's a new setting for specifying whether or not other pages should also be redirected.
- uses Shopgate Library 2.3.3
- merged with US plugin version

## 1.1.13
- fixed issue on deinstallation of the module
- fixed error when importing orders without telephone numbers
- shops that declare the birthday field as mandatory will now get '0000-00-00' when no birthday was passed

## 1.1.12
- order status for merchant payment method prepayment changed
- basic prices are now exported

## 1.1.11
- company names longer than 32 characters get shortened now; the full name is mentioned in the new comments field
- as a preparation for multi-language support the shop number is now saved and displayed along with an order
- uses Shopgate Library 2.1.26

## 1.1.10
- fixed issue with product weight for variations

## 1.1.9
- support of Prestashop version 1.3.x.x
- uses Shopgate Library 2.1.25
- fixed issue with addresses
- fixed bug in the "url_deeplink" on product's export

## 1.1.8
- fixed compatibility issue for Prestashop version lower 1.4.4.0
- fixed issue "Fatal error (OrderHistory -> id_order_state is empty)"

## 1.1.7
- fixed issue with mobile redirect

## 1.1.6
- configuration fields "mobile Website" / "shop is active" removed
- js header output in <head> HTML tag
- <link rel="alternate" ...> HTML tag output in <head>
- uses Shopgate Library 2.1.24

## 1.1.5
- fixed issue with different currencies
- fixed issue with wrong order status
- uses Shopgate Library 2.1.18

## 1.1.4
- fixed issue with delivery time in products export

## 1.1.3
- fixed issue SEO-urls for products and categories

## 1.1.2
- fixed issue product variants (deliverytime, discounts)
- fixed url issues for Prestashop 1.5.x.x
- uses Shopgate Library 2.1.17

## 1.1.1
- recycling package is not selected by default
- currency in configuration is now selectable
- fixes issue category images with use of SEO links
- fixed issue export weight
- fixed issue order of products/categories
- fixed issue with inactive products
- Delivery time is now transfered correct
- uses Shopgate Library 2.1.15

## 1.1.0
- fixed translation issues in backend
- fixed PHP < 5.3 compatibility issues
- uses Shopgate Library 2.1.12

## 1.0.10
- uses Shopgate Library 2.1.11

## 1.0.4
- Min quantity check enable/disable configurations added
- Out of stock check enable/disable configurations added
- 2.0.25 Shopgate library version

## 1.03
- Compatibility fix for 1.4.4.1 PS version
- uses Shopgate Library 2.0.23

## 1.02
- Fix configurations(server_custom_url)
- Fix Older Prestashop(< 1.4.4v) include eval
- Fix hookHeader method (echo mobile header)

## 1.01
- Shopgate Mobile Redirect implementation (alias and cname configuratios added)
- 2.0.18 - Shopgate library version
- Module configuration error catch
- Deactivates shopgate shop on uninstall

[Unreleased]: https://github.com/shopgate/cart-integration-prestashop/compare/2.9.85...HEAD
[2.9.85]: https://github.com/shopgate/cart-integration-prestashop/compare/2.9.84...2.9.85
[2.9.84]: https://github.com/shopgate/cart-integration-prestashop/compare/2.9.83...2.9.84
[2.9.83]: https://github.com/shopgate/cart-integration-prestashop/tree/2.9.83
