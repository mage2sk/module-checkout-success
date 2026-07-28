# Changelog

All notable changes to this extension are documented here. The format
is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.0.6]

### Changed
- Replaced typographic characters (em dashes, curly quotes, ellipsis) with plain ASCII punctuation. No functional changes.

---

## [1.0.5] - 2026-07-07

### Changed
- Code cleanup: removed redundant inline comments and docblocks from the PHP source. No functional changes.

---

## [1.0.4] - 2026-06-18

### Changed
- README rewritten to match the standard Panth Infotech template: added
  SEO meta comment, Quick Answer block, Who Is It For section, grouped
  Key Features, full Configuration table matching system.xml, How It
  Works walkthrough, Quick Links table, and closing CTA. Canonical and
  all Marketplace links now point to the live product page. Removed
  inaccurate commercemarketplace.adobe.com links.

---

## [1.0.0] - Initial release

### Added
- **Custom checkout success page** replacing the default Magento order
  confirmation with a modern, card-based layout.
- **Two layout modes** - Two Column (details + summary sidebar) and
  Single Column (centered). Selectable from admin configuration.
- **Order details card** - order number, date, payment method, and
  shipping method in a clean meta grid.
- **Ordered items list** with product thumbnails (96x96), names, SKUs,
  quantities, and row totals.
- **Shipping & billing address rendering** via Magento's address
  renderer. Billing address displayed only when different from shipping.
- **Order totals summary** - subtotal, shipping, tax, discount, and
  grand total.
- **Guest account creation prompt** - shown to guest customers with a
  link to the registration page.
- **Invoice PDF download link** - shown to logged-in customers when
  invoices are available.
- **Continue Shopping button** - links back to the store home page.
- **CMS block slot** - configurable CMS block rendered below order
  details for trust signals, upsells, or custom content.
- **Custom tracking scripts** - HTML/JS injection with 12 order
  variable placeholders (`{{orderId}}`, `{{orderTotal}}`, etc.) for
  GA4, Facebook Pixel, or any conversion tracker. All values are
  JS-escaped for safety.
- **Per-section visibility toggles** - each content section can be
  independently shown or hidden from admin config.
- **Configurable thank-you title and message** per store view.
- **Data patch** - installs a sample "Checkout Success - Trust Signals"
  CMS block with four trust-signal cards (Free Returns, Fast Shipping,
  24/7 Support, Secure Payments).
- **Session expiry fallback** - friendly message when the order session
  has expired instead of an empty page.
- **Admin ACL resource** - `Panth_CheckoutSuccess::config` for
  role-based access control.

### Compatibility
- Magento Open Source / Commerce / Cloud 2.4.4 - 2.4.8
- PHP 8.1, 8.2, 8.3, 8.4

---

## Support

For all questions, bug reports, or feature requests:

- **Email:** kishansavaliyakb@gmail.com
- **Website:** https://kishansavaliya.com
- **WhatsApp:** +91 84012 70422
