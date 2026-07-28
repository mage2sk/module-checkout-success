# Panth Checkout Success - User Guide

Panth_CheckoutSuccess replaces the default Magento order confirmation
page with a modern, fully configurable success page. This guide walks
store administrators through installation, configuration, and
customization.

---

## Table of contents

1. [Installation](#1-installation)
2. [Verifying the module is active](#2-verifying-the-module-is-active)
3. [Configuration](#3-configuration)
4. [Layout modes](#4-layout-modes)
5. [CMS block slot](#5-cms-block-slot)
6. [Custom tracking scripts](#6-custom-tracking-scripts)
7. [Troubleshooting](#7-troubleshooting)

---

## 1. Installation

### Composer (recommended)

```bash
composer require mage2kishan/module-checkout-success
bin/magento module:enable Panth_CheckoutSuccess
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

### Manual zip

1. Download the extension package zip
2. Extract to `app/code/Panth/CheckoutSuccess`
3. Run the same `module:enable ... cache:flush` commands above

---

## 2. Verifying the module is active

```bash
bin/magento module:status Panth_CheckoutSuccess
# Module is enabled
```

Place a test order and confirm the new success page appears after
checkout.

---

## 3. Configuration

Navigate to **Stores > Configuration > Panth Extensions > Checkout
Success Page**.

### General

| Setting | Default | Description |
|---|---|---|
| Enable Custom Success Page | Yes | When disabled, Magento's default success page is used |

### Content Sections

Toggle visibility for each section independently:

- **Show Order Number** - order number in the details card
- **Show Order Date** - order date in the details card
- **Show Ordered Items** - product list with thumbnails, SKUs, qty, and row totals
- **Show Order Totals** - subtotal, shipping, tax, discount, grand total
- **Show Shipping Address** - shipping address (billing shown only when different)
- **Show Payment Method** - payment method name
- **Show Create Account** - account creation prompt for guest customers
- **Show Continue Shopping** - button linking back to the home page
- **Additional CMS Block** - select a CMS block to render below order details

### Appearance

- **Page Layout** - "Two Column (Details + Summary)" or "Single Column (Centered)"
- **Thank You Title** - main heading displayed at the top
- **Thank You Message** - optional subheading below the title

### Tracking & Scripts

- **Custom Scripts** - raw HTML/JS injected into the success page. Supports variable placeholders that are replaced with real order data at render time.

---

## 4. Layout modes

### Two Column (default)

Left column contains order details, ordered items, and addresses.
Right column (sidebar) contains order totals, account creation, order
history link, invoice download, and continue shopping button.

### Single Column

All content is centered in a single column. Ideal for stores that
prefer a simpler, focused layout.

Switch between layouts at **Stores > Configuration > Panth Extensions >
Checkout Success Page > Appearance > Page Layout**.

---

## 5. CMS block slot

The module installs a sample CMS block called **"Checkout Success -
Trust Signals"** (identifier: `panth_checkout_success_bottom`) during
`setup:upgrade`. This block displays four trust-signal cards: Free
Returns, Fast Shipping, 24/7 Support, and Secure Payments.

To use it:
1. Go to **Stores > Configuration > Panth Extensions > Checkout Success
   Page > Content Sections > Additional CMS Block**
2. Select "Checkout Success - Trust Signals" (or any other CMS block)
3. Save and flush cache

You can edit the block content at **Content > Blocks** in the admin.

---

## 6. Custom tracking scripts

Inject conversion tracking code (GA4, Facebook Pixel, etc.) that
automatically includes order data.

**Example - Google Analytics 4 purchase event:**

```html
<script>
gtag('event', 'purchase', {
  transaction_id: '{{orderId}}',
  value: {{orderTotal}},
  currency: '{{orderCurrency}}',
  shipping: {{shippingAmount}},
  tax: {{taxAmount}},
  items: []
});
</script>
```

**Available placeholders:**

| Placeholder | Value |
|---|---|
| `{{orderId}}` | Order increment ID |
| `{{orderTotal}}` | Grand total |
| `{{orderSubtotal}}` | Subtotal |
| `{{orderCurrency}}` | Currency code (USD, EUR, etc.) |
| `{{customerEmail}}` | Customer email |
| `{{paymentTitle}}` | Payment method title |
| `{{shippingTitle}}` | Shipping method title |
| `{{couponCode}}` | Coupon code (if any) |
| `{{orderItemCount}}` | Number of visible items |
| `{{shippingAmount}}` | Shipping amount |
| `{{taxAmount}}` | Tax amount |
| `{{discountAmount}}` | Discount amount |

All values are escaped for safe use inside JavaScript.

---

## 7. Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| Default Magento success page still shows | Module disabled or cache stale | Verify `bin/magento module:status Panth_CheckoutSuccess` returns enabled; flush cache |
| Product thumbnails missing | Product has no thumbnail image assigned | Assign a thumbnail image to the product in the catalog |
| CMS block not rendering | Block not selected in config, or block is inactive | Check config setting and ensure the CMS block is active |
| Custom scripts not appearing | Custom Scripts field is empty or module disabled | Enter your script in the Tracking & Scripts config section |
| Session expired - fallback message shown | Customer revisited the success page after session expired | This is expected behavior; a friendly fallback message is displayed |

---

## Support

For all questions, bug reports, or feature requests:

- **Email:** kishansavaliyakb@gmail.com
- **Website:** https://kishansavaliya.com
- **WhatsApp:** +91 84012 70422
