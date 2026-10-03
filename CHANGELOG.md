# Changelog

All notable changes to this extension are documented here. The format
is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.0.11] - 2026-10-03

### Fixed
- Items Ordered shows the options the customer chose for each item (configurable attributes such as size and colour, custom options, additional options) as plain text under the SKU, and fractional quantities are no longer rounded down (Qty: 1.5 instead of 1).
- Create Account links to Magento's guest-to-customer flow (`checkout/account/delegateCreate`), so the registration form is pre-filled from the order and the order is linked to the new account. The card is shown only when customer registration is allowed and the order email has no account yet.
- Between 768px and 1023px the page uses one column; the two-column grid starts at 1024px, so item names, SKUs and order details no longer wrap word by word on tablets.
- Additional CMS Block has a "-- None --" option, so the optional block can be removed again.
- Show Order Number and Show Order Date also apply to the order reference under the page heading.
- Decorative icons are hidden from screen readers, links have a visible keyboard focus outline on Luma and Hyva, the check mark animation and button motion respect reduced-motion settings, and the trust signals CMS block uses h3 headings (an upgrade patch updates the installed block when its markup is unchanged).
- The no-order fallback uses the page styles and theme button instead of inline navy styles.
- The Custom Scripts help text lists all twelve placeholders; the Download Invoice link opens with `rel="noopener"`.
