<?php
declare(strict_types=1);

namespace Panth\CheckoutSuccess\Block;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address\Renderer as AddressRenderer;
use Panth\CheckoutSuccess\Helper\Data;

class Success extends Template
{
    private Data $helper;
    private CheckoutSession $checkoutSession;
    private CustomerSession $customerSession;
    private AddressRenderer $addressRenderer;
    private ProductRepositoryInterface $productRepository;
    private ImageHelper $imageHelper;
    private BlockRepositoryInterface $blockRepository;
    private FilterProvider $filterProvider;
    private ?Order $order = null;
    private ?array $orderItemImages = null;

    public function __construct(
        Context $context,
        Data $helper,
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        AddressRenderer $addressRenderer,
        ProductRepositoryInterface $productRepository,
        ImageHelper $imageHelper,
        BlockRepositoryInterface $blockRepository,
        FilterProvider $filterProvider,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper = $helper;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->addressRenderer = $addressRenderer;
        $this->productRepository = $productRepository;
        $this->imageHelper = $imageHelper;
        $this->blockRepository = $blockRepository;
        $this->filterProvider = $filterProvider;
    }

    public function isCustomerLoggedIn(): bool
    {
        return $this->customerSession->isLoggedIn();
    }

    public function getOrderUrl(): string
    {
        $order = $this->getOrder();
        if (!$order) {
            return '';
        }
        return $this->getUrl('sales/order/view', ['order_id' => $order->getId()]);
    }

    public function getMyOrdersUrl(): string
    {
        return $this->getUrl('sales/order/history');
    }

    public function isEnabled(): bool
    {
        return $this->helper->isEnabled();
    }

    public function getOrder(): ?Order
    {
        if ($this->order === null) {
            $this->order = $this->checkoutSession->getLastRealOrder();
        }
        return $this->order && $this->order->getId() ? $this->order : null;
    }

    public function getHelper(): Data
    {
        return $this->helper;
    }

    public function getLayoutMode(): string
    {
        return $this->helper->getLayout();
    }

    public function getThankYouTitle(): string
    {
        return $this->helper->getThankYouTitle();
    }

    public function getThankYouMessage(): string
    {
        return $this->helper->getThankYouMessage();
    }

    public function showSection(string $section): bool
    {
        return $this->helper->showSection($section, null);
    }

    public function getFormattedDate(): string
    {
        $order = $this->getOrder();
        if (!$order) {
            return '';
        }
        return $this->formatDate($order->getCreatedAt(), \IntlDateFormatter::LONG);
    }

    public function getOrderItems(): array
    {
        $order = $this->getOrder();
        if (!$order) {
            return [];
        }
        return $order->getAllVisibleItems();
    }

    public function getShippingAddress(): ?string
    {
        $order = $this->getOrder();
        if (!$order || !$order->getShippingAddress()) {
            return null;
        }
        return $this->addressRenderer->format($order->getShippingAddress(), 'html');
    }

    public function getBillingAddress(): ?string
    {
        $order = $this->getOrder();
        if (!$order || !$order->getBillingAddress()) {
            return null;
        }
        return $this->addressRenderer->format($order->getBillingAddress(), 'html');
    }

    public function isBillingDifferentFromShipping(): bool
    {
        $order = $this->getOrder();
        if (!$order || !$order->getBillingAddress() || !$order->getShippingAddress()) {
            return false;
        }
        $billing  = $order->getBillingAddress();
        $shipping = $order->getShippingAddress();

        return implode('|', [
            strtolower(trim((string) $billing->getFirstname())),
            strtolower(trim((string) $billing->getLastname())),
            strtolower(trim((string) implode(' ', (array) $billing->getStreet()))),
            strtolower(trim((string) $billing->getCity())),
            strtolower(trim((string) $billing->getPostcode())),
            strtolower(trim((string) $billing->getCountryId())),
        ]) !== implode('|', [
            strtolower(trim((string) $shipping->getFirstname())),
            strtolower(trim((string) $shipping->getLastname())),
            strtolower(trim((string) implode(' ', (array) $shipping->getStreet()))),
            strtolower(trim((string) $shipping->getCity())),
            strtolower(trim((string) $shipping->getPostcode())),
            strtolower(trim((string) $shipping->getCountryId())),
        ]);
    }

    public function getPaymentMethodTitle(): ?string
    {
        $order = $this->getOrder();
        if (!$order) {
            return null;
        }
        $payment = $order->getPayment();
        return $payment ? $payment->getMethodInstance()->getTitle() : null;
    }

    public function getShippingMethodTitle(): ?string
    {
        $order = $this->getOrder();
        if (!$order) {
            return null;
        }
        return $order->getShippingDescription();
    }

    public function isGuestOrder(): bool
    {
        $order = $this->getOrder();
        return $order && $order->getCustomerIsGuest();
    }

    public function getOrderItemImages(): array
    {
        if ($this->orderItemImages !== null) {
            return $this->orderItemImages;
        }

        $this->orderItemImages = [];
        $items = $this->getOrderItems();

        foreach ($items as $item) {
            try {
                $product = $this->productRepository->getById((int) $item->getProductId());
                $imageUrl = $this->imageHelper->init($product, 'product_thumbnail_image')
                    ->setImageFile($product->getThumbnail())
                    ->resize(96, 96)
                    ->getUrl();
                $this->orderItemImages[$item->getItemId()] = $imageUrl;
            } catch (\Exception $e) {
                $this->orderItemImages[$item->getItemId()] = '';
            }
        }

        return $this->orderItemImages;
    }

    public function getCouponCode(): ?string
    {
        $order = $this->getOrder();
        if (!$order) {
            return null;
        }
        $coupon = $order->getCouponCode();
        return $coupon ? (string) $coupon : null;
    }

    public function getCustomerEmail(): ?string
    {
        $order = $this->getOrder();
        if (!$order) {
            return null;
        }
        return $order->getCustomerEmail();
    }

    public function getCustomScriptsHtml(): string
    {
        $scripts = $this->helper->getCustomScripts();
        if (!$scripts) {
            return '';
        }

        $order = $this->getOrder();
        if (!$order) {
            return '';
        }

        return $this->helper->processVariables($scripts, [
            'increment_id' => $order->getIncrementId(),
            'grand_total' => $order->getGrandTotal(),
            'subtotal' => $order->getSubtotal(),
            'currency_code' => $order->getOrderCurrencyCode(),
            'customer_email' => $order->getCustomerEmail(),
            'payment_title' => $this->getPaymentMethodTitle() ?? '',
            'shipping_title' => $this->getShippingMethodTitle() ?? '',
            'coupon_code' => $order->getCouponCode() ?? '',
            'item_count' => (string) count($order->getAllVisibleItems()),
            'shipping_amount' => $order->getShippingAmount(),
            'tax_amount' => $order->getTaxAmount(),
            'discount_amount' => $order->getDiscountAmount(),
        ]);
    }

    public function getContinueShoppingUrl(): string
    {
        return $this->getBaseUrl();
    }

    public function hasInvoices(): bool
    {
        $order = $this->getOrder();
        if (!$order) {
            return false;
        }
        return $order->hasInvoices();
    }

    public function getInvoicePrintUrl(): string
    {
        $order = $this->getOrder();
        if (!$order) {
            return '';
        }
        return $this->getUrl('sales/order/printInvoice', ['order_id' => $order->getId()]);
    }

    public function getCmsBlockHtml(): string
    {
        $blockId = $this->helper->getCmsBlockId();
        if (!$blockId) {
            return '';
        }
        try {
            $cmsBlock = $this->blockRepository->getById($blockId);
            if (!$cmsBlock->isActive()) {
                return '';
            }
            return $this->filterProvider->getBlockFilter()->filter($cmsBlock->getContent());
        } catch (\Exception $e) {
            return '';
        }
    }
}
