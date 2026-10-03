<?php
declare(strict_types=1);

namespace Panth\CheckoutSuccess\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Escaper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    private Escaper $escaper;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        Escaper $escaper
    ) {
        parent::__construct($context);
        $this->escaper = $escaper;
    }

    const XML_PATH_PREFIX = 'panth_checkout_success/';

    public function getConfigValue(string $group, string $field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_PREFIX . $group . '/' . $field,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function isEnabled($storeId = null): bool
    {
        return (bool) $this->getConfigValue('general', 'enabled', $storeId);
    }

    public function getLayout($storeId = null): string
    {
        return (string) ($this->getConfigValue('style', 'layout', $storeId) ?: 'two-column');
    }

    public function getThankYouTitle($storeId = null): string
    {
        return (string) ($this->getConfigValue('style', 'thank_you_title', $storeId) ?: 'Thank you for your order!');
    }

    public function getThankYouMessage($storeId = null): string
    {
        return (string) ($this->getConfigValue('style', 'thank_you_message', $storeId) ?: '');
    }

    public function showSection(string $section, $storeId = null): bool
    {
        return (bool) $this->getConfigValue('content', 'show_' . $section, $storeId);
    }

    public function getCmsBlockId($storeId = null): ?string
    {
        $value = $this->getConfigValue('content', 'cms_block', $storeId);
        return $value ? (string) $value : null;
    }

    public function getCustomScripts($storeId = null): ?string
    {
        return $this->getConfigValue('tracking', 'custom_scripts', $storeId);
    }

    public function processVariables(string $content, array $orderData): string
    {
        $replacements = [
            '{{orderId}}' => $this->escaper->escapeJs((string) ($orderData['increment_id'] ?? '')),
            '{{orderTotal}}' => $this->escaper->escapeJs((string) ($orderData['grand_total'] ?? '')),
            '{{orderSubtotal}}' => $this->escaper->escapeJs((string) ($orderData['subtotal'] ?? '')),
            '{{orderCurrency}}' => $this->escaper->escapeJs((string) ($orderData['currency_code'] ?? '')),
            '{{customerEmail}}' => $this->escaper->escapeJs((string) ($orderData['customer_email'] ?? '')),
            '{{paymentTitle}}' => $this->escaper->escapeJs((string) ($orderData['payment_title'] ?? '')),
            '{{shippingTitle}}' => $this->escaper->escapeJs((string) ($orderData['shipping_title'] ?? '')),
            '{{couponCode}}' => $this->escaper->escapeJs((string) ($orderData['coupon_code'] ?? '')),
            '{{orderItemCount}}' => $this->escaper->escapeJs((string) ($orderData['item_count'] ?? '')),
            '{{shippingAmount}}' => $this->escaper->escapeJs((string) ($orderData['shipping_amount'] ?? '')),
            '{{taxAmount}}' => $this->escaper->escapeJs((string) ($orderData['tax_amount'] ?? '')),
            '{{discountAmount}}' => $this->escaper->escapeJs((string) ($orderData['discount_amount'] ?? '')),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }
}
