# Magento 2 Checkout Success Page

Panth Checkout Success replaces the content of the Magento 2 order success page (`checkout/onepage/success`) with a configurable order confirmation page. When enabled, it removes the default page title, success message and guest registration blocks and renders its own block in their place, showing the order number and date, the items ordered with thumbnails, the shipping and billing addresses, the order totals, the payment and shipping method, an account creation prompt for guests, an optional CMS block and a custom script slot with order value placeholders.

It is intended for merchants who want a fuller confirmation page and a place to run conversion tracking scripts with real order values. Every section can be switched on or off in the admin configuration. The product page lists Hyva and Luma as supported themes; the module ships a single template and a standalone CSS file that is loaded through layout XML, with no theme-specific code.

Product page: [kishansavaliya.com/magento-2-checkout-success.html](https://kishansavaliya.com/magento-2-checkout-success.html)

## Features

- Replaces the default success page content through layout XML; the block is marked non-cacheable. The removal of the default blocks is applied only while the module is enabled in configuration.
- Two page layouts selectable in admin: "Single Column (Centered)" or "Two Column (Details + Summary)".
- Configurable "Thank You Title" and "Thank You Message", settable per website and store view.
- Order details card with order number, order date, payment method title and shipping method title.
- Items list with product thumbnail (rendered at 48x48 from a 96x96 image), name, SKU, the selected options (configurable attributes, custom options and additional options, as plain text), quantity (fractional quantities kept) and row total.
- Shipping address rendered with Magento's order address renderer; billing address shown alongside only when it differs from the shipping address.
- Order summary with subtotal, shipping, tax, discount (each shown only when non-zero), any additional order totals added by other modules to the `order_totals` child block (for example extra fees) and grand total.
- "Create Account" card linking to `checkout/account/delegateCreate`, which opens the registration form pre-filled from the order and links the order to the new account. Shown to guest orders only, when customer registration is allowed and the order email has no account on the website.
- "View My Orders" link and, when the order has an invoice, a "Download Invoice" link, shown to logged-in customers only.
- "Continue Shopping" button linking to the store base URL.
- Optional CMS block rendered below the order details, selected from the existing CMS blocks ("-- None --" hides it).
- "Custom Scripts" field whose content is injected into the page with twelve `{{...}}` placeholders replaced by order values, each JavaScript-escaped.
- A sample CMS block with identifier `panth_checkout_success_bottom` (title "Checkout Success - Trust Signals") is created by a data patch during `setup:upgrade`; it is not selected automatically.
- Fallback message with a "Continue Shopping" link when the success URL is opened without an order in the checkout session.

## Compatibility

| Platform | Versions |
|---|---|
| Magento Open Source | 2.4.4 to 2.4.8 (as published on the product page) |
| Adobe Commerce | 2.4.4 to 2.4.8 (as published on the product page) |
| PHP | 8.1, 8.2, 8.3, 8.4 (`~8.1.0||~8.2.0||~8.3.0||~8.4.0` in `composer.json`) |
| Themes | Hyva and Luma (as published on the product page); the module contains no theme-specific templates or code |

Composer constraints on Magento packages: `magento/framework` ^103.0, `magento/module-checkout` ^100.4, `magento/module-sales` ^103.0, `magento/module-catalog` ^104.0, `magento/module-cms` ^104.0, `magento/module-customer` ^103.0, `magento/module-config` ^101.2, `magento/module-store` ^101.1, `magento/module-backend` ^102.0.

## Requirements

- Magento Open Source or Adobe Commerce 2.4.4 to 2.4.8
- PHP 8.1, 8.2, 8.3 or 8.4
- `mage2kishan/module-core` ^1.0 (module `Panth_Core`), installed by Composer as a dependency; it provides the "Panth Extensions" configuration tab
- The `magento/*` packages listed under Compatibility; `composer.json` declares no suggested packages

## Installation

```bash
composer require mage2kishan/module-checkout-success
bin/magento module:enable Panth_Core Panth_CheckoutSuccess
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

`setup:di:compile` is only needed in production mode. The module ships a CSS file under `view/frontend/web`, so static content must be deployed in production mode.

Check the module is enabled:

```bash
bin/magento module:status Panth_CheckoutSuccess
```

## Configuration

Admin path: Stores > Configuration > Panth Extensions > Checkout Success Page. All fields can be set at default, website and store view scope. Access is controlled by the ACL resource `Panth_CheckoutSuccess::config` ("Panth Checkout Success").

### General

| Setting | Default | What it does |
|---|---|---|
| Enable Custom Success Page | Yes | When set to No, the module's template renders nothing and the default Magento success page (page title, success message and guest registration block) is shown instead. |

### Content Sections

| Setting | Default | What it does |
|---|---|---|
| Show Order Number | Yes | Shows the order number under the page heading and in the order details card. |
| Show Order Date | Yes | Shows the order date under the page heading and in the order details card. |
| Show Ordered Items | Yes | Shows the items list with thumbnails, SKU, quantity and row total. |
| Show Order Totals | Yes | Shows the order summary card. |
| Show Shipping Address | Yes | Shows the shipping method row in the order details card and the shipping address card (with the billing address when it differs). |
| Show Payment Method | Yes | Shows the payment method row in the order details card. |
| Show Create Account | Yes | Shows the "Create an Account" card to guest orders when registration is allowed and the order email has no account yet. |
| Show Continue Shopping Button | Yes | Shows the "Continue Shopping" button. |
| Additional CMS Block | (none) | CMS block rendered below the order details. Only active blocks assigned to the current store view (or to All Store Views) are rendered; when the identifier exists for several store views, the current store view version is used. |

### Appearance

| Setting | Default | What it does |
|---|---|---|
| Page Layout | Two Column (Details + Summary) | Two-column grid from 1024px wide (details on the left, summary on the right; one column below 1024px) or single centered column. |
| Thank You Title | Thank you for your order! | Page heading. |
| Thank You Message | We've received your order and will process it shortly. You'll receive a confirmation email with tracking details. | Text under the heading; hidden when empty. |

### Tracking & Scripts

| Setting | Default | What it does |
|---|---|---|
| Custom Scripts | (empty) | HTML/JS output at the end of the success page with placeholders replaced by order values. |

Placeholders available in "Custom Scripts": `{{orderId}}` (increment ID), `{{orderTotal}}`, `{{orderSubtotal}}`, `{{orderCurrency}}`, `{{customerEmail}}`, `{{paymentTitle}}`, `{{shippingTitle}}`, `{{couponCode}}`, `{{orderItemCount}}`, `{{shippingAmount}}`, `{{taxAmount}}`, `{{discountAmount}}`. Values are passed through `Magento\Framework\Escaper::escapeJs()` before replacement. Amounts are raw numeric values, not formatted prices.

Config paths (all under `panth_checkout_success/`): `general/enabled`, `content/show_order_number`, `content/show_order_date`, `content/show_order_items`, `content/show_order_totals`, `content/show_shipping_info`, `content/show_payment_info`, `content/show_create_account`, `content/show_continue_shopping`, `content/cms_block`, `style/layout`, `style/thank_you_title`, `style/thank_you_message`, `tracking/custom_scripts`.

With the defaults, every section is shown, the two-column layout is used, no CMS block is selected and no custom scripts are output.

## Usage

After an order is placed, Magento redirects to `checkout/onepage/success`. The module reads the last real order from the checkout session and renders, in this order:

1. Header: check icon, "Thank You Title", "Thank You Message", and "Order #<increment id> . <date>" (each part follows Show Order Number / Show Order Date). For logged-in customers the order number links to the order view page.
2. Main column: "Order Details" card (order number, order date, payment method, shipping method), "Items Ordered" card, "Shipping Address" card (plus "Billing Address" when different).
3. Sidebar column: "Order Summary" totals, "Create an Account" card (guests only), "View My Orders" link (logged-in only), "Download Invoice" link (logged-in customers whose order has an invoice), "Continue Shopping" button.
4. The selected CMS block, if any.
5. The "Custom Scripts" output, if any.

In the single-column layout the same cards are rendered in the same order without the grid. The shipping address card and shipping method row are omitted when the order has no shipping address or shipping description (for example virtual orders). If the success URL is opened without an order in the session, a short thank-you message and a "Continue Shopping" link are shown instead.

The date is formatted with the store locale (`IntlDateFormatter::LONG`). Prices are formatted with the order currency.

Admin: all settings live under Stores > Configuration > Panth Extensions > Checkout Success Page. The module adds no admin menu items, grids or routes.

Templates and assets that can be overridden in a theme:

- `view/frontend/templates/success.phtml` (block `panth.checkout.success`)
- `view/frontend/layout/checkout_onepage_success.xml`
- `view/frontend/layout/panth_checkout_success_enabled.xml`
- `view/frontend/web/css/checkout-success.css` (loaded through the layout `<head>`)
- `view/frontend/web/css/source/_module.less` (for themes using the Luma LESS pipeline)

## Developer Notes

- Module name: `Panth_CheckoutSuccess`; Composer package: `mage2kishan/module-checkout-success`; PHP namespace: `Panth\CheckoutSuccess`.
- Module sequence: `Panth_Core`, `Magento_Checkout`, `Magento_Sales`, `Magento_Cms`.
- `Block\Success` (extends `Magento\Framework\View\Element\Template`): public methods include `getOrder()`, `isEnabled()`, `getLayoutMode()`, `getThankYouTitle()`, `getThankYouMessage()`, `showSection(string $section)`, `getFormattedDate()`, `getOrderItems()`, `getOrderItemImages()`, `getShippingAddress()`, `getBillingAddress()`, `isBillingDifferentFromShipping()`, `getPaymentMethodTitle()`, `getShippingMethodTitle()`, `isGuestOrder()`, `isCustomerLoggedIn()`, `getOrderUrl()`, `getMyOrdersUrl()`, `hasInvoices()`, `getInvoicePrintUrl()`, `getContinueShoppingUrl()`, `getCouponCode()`, `getCustomerEmail()`, `getItemOptions(Item $item)`, `formatQty($qty)`, `canCreateAccount()`, `getCreateAccountUrl()`, `getAdditionalTotals()`, `getCmsBlockHtml()`, `getCustomScriptsHtml()`.
- `Helper\Data`: `isEnabled()`, `getLayout()`, `getThankYouTitle()`, `getThankYouMessage()`, `showSection()`, `getCmsBlockId()`, `getCustomScripts()`, `processVariables(string $content, array $orderData)`, `getConfigValue(string $group, string $field, $storeId = null)`.
- `Model\Config\Source\SuccessLayout`: option source with values `single-column` and `two-column`.
- `Model\Config\Source\CmsBlock`: CMS block option source with a leading "-- None --" option.
- `Observer\AddEnabledLayoutHandle`: listens to `layout_load_before` in the frontend area and adds the layout handle `panth_checkout_success_enabled` to `checkout_onepage_success` when "Enable Custom Success Page" is Yes for the current store.
- The block reads the order only from the checkout session (last real order); no order ID is taken from the request.
- `Setup\Patch\Data\CreateSuccessCmsBlock`: creates the `panth_checkout_success_bottom` CMS block once, if it does not already exist.
- `Setup\Patch\Data\UpdateSuccessCmsBlockMarkup`: updates the installed `panth_checkout_success_bottom` block to h3 headings and screen-reader-hidden icons when it still contains the original markup; edited content is left as it is.
- Layout: `checkout_onepage_success.xml` adds the block `panth.checkout.success` with `cacheable="false"`. `panth_checkout_success_enabled.xml` removes `page.main.title`, `checkout.success` and `checkout.registration`, adds the body class `panth-checkout-success` and loads the CSS file.
- ACL resource: `Panth_CheckoutSuccess::config` under `Magento_Config::config`.
- No database tables: the module has no `db_schema.xml`, no routes, no console commands, no cron jobs and no web API endpoints.

## Uninstallation

```bash
bin/magento module:disable Panth_CheckoutSuccess
composer remove mage2kishan/module-checkout-success
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

The module creates no database tables. The configuration values under `panth_checkout_success/*` in `core_config_data`, the CMS block `panth_checkout_success_bottom` and the data patch entry in `patch_list` remain after removal and can be deleted manually if required.

## Support

- Product page: [kishansavaliya.com/magento-2-checkout-success.html](https://kishansavaliya.com/magento-2-checkout-success.html)
- Contact form: [kishansavaliya.com/contact](https://kishansavaliya.com/contact)
- Email: kishansavaliyakb@gmail.com
- Bug reports: [GitHub issues](https://github.com/mage2sk/module-checkout-success/issues)

## Documentation

[USER_GUIDE.md](USER_GUIDE.md) covers installation (Composer and manual zip), verifying the module is active, each configuration group, the two layout modes, the CMS block slot, custom tracking scripts with placeholder examples, and a troubleshooting table.

## License

Commercial software license. See [LICENSE.txt](LICENSE.txt) in this repository.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Links

- Website: [kishansavaliya.com](https://kishansavaliya.com)
- All extensions: [kishansavaliya.com/magento-extensions.html](https://kishansavaliya.com/magento-extensions.html)
- GitHub: [mage2sk/module-checkout-success](https://github.com/mage2sk/module-checkout-success)
- Packagist: [mage2kishan/module-checkout-success](https://packagist.org/packages/mage2kishan/module-checkout-success)
