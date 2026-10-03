<?php
declare(strict_types=1);

namespace Panth\CheckoutSuccess\Test\Unit\Block;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Registration;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\LayoutInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Block\Order\Totals;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address;
use Magento\Sales\Model\Order\Address\Renderer as AddressRenderer;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\Order\Payment;
use Magento\Store\Model\Store;
use Panth\CheckoutSuccess\Block\Success;
use Panth\CheckoutSuccess\Helper\Data;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class SuccessTest extends TestCase
{
    private Data $helper;
    private CheckoutSession $checkoutSession;
    private CustomerSession $customerSession;
    private AddressRenderer $addressRenderer;
    private ProductRepositoryInterface $productRepository;
    private ImageHelper $imageHelper;
    private Registration $registration;
    private AccountManagementInterface $accountManagement;
    private UrlInterface $urlBuilder;
    private LoggerInterface $logger;
    private array $sections = [];
    private bool $loggedIn = false;

    protected function setUp(): void
    {
        $this->helper = $this->createMock(Data::class);
        $this->helper->method('showSection')->willReturnCallback(fn (string $section) => $this->sections[$section] ?? true);
        $this->checkoutSession = $this->getMockBuilder(CheckoutSession::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getLastRealOrder'])
            ->getMock();
        $this->customerSession = $this->createMock(CustomerSession::class);
        $this->customerSession->method('isLoggedIn')->willReturnCallback(fn () => $this->loggedIn);
        $this->addressRenderer = $this->createMock(AddressRenderer::class);
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->imageHelper = $this->createMock(ImageHelper::class);
        $this->registration = $this->createMock(Registration::class);
        $this->accountManagement = $this->createMock(AccountManagementInterface::class);
        $this->urlBuilder = $this->createMock(UrlInterface::class);
        $this->urlBuilder->method('getUrl')->willReturnCallback(
            static fn ($route, $params = []) => 'https://shop.example.com/' . trim((string) $route, '/') . '/'
                . ($params ? http_build_query($params) : '')
        );
        $this->urlBuilder->method('getBaseUrl')->willReturn('https://shop.example.com/');
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    private function block(?Order $order): Success
    {
        $this->checkoutSession->method('getLastRealOrder')->willReturn($order ?? $this->order(['getId' => null]));
        $context = $this->createMock(Context::class);
        $context->method('getUrlBuilder')->willReturn($this->urlBuilder);
        $context->method('getLogger')->willReturn($this->logger);

        return new Success(
            $context,
            $this->helper,
            $this->checkoutSession,
            $this->customerSession,
            $this->addressRenderer,
            $this->productRepository,
            $this->imageHelper,
            $this->registration,
            $this->accountManagement
        );
    }

    private function order(array $values = []): Order
    {
        $values += [
            'getId' => 15,
            'getIncrementId' => '000000226',
            'getCustomerIsGuest' => 1,
            'getCustomerEmail' => 'guest@example.com',
        ];
        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods(array_merge(array_keys($values), ['formatPrice']))
            ->getMock();
        foreach ($values as $method => $value) {
            $order->method($method)->willReturn($value);
        }
        $order->method('formatPrice')->willReturnCallback(static fn ($v) => '$' . number_format((float) $v, 2));

        return $order;
    }

    private function address(array $data): Address
    {
        $address = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getFirstname', 'getLastname', 'getStreet', 'getCity', 'getPostcode', 'getCountryId'])
            ->getMock();
        $data += ['firstname' => 'Testy', 'lastname' => 'McTestface', 'street' => ['1 Fake St'], 'city' => 'Testville',
            'postcode' => '90001', 'country' => 'US'];
        $address->method('getFirstname')->willReturn($data['firstname']);
        $address->method('getLastname')->willReturn($data['lastname']);
        $address->method('getStreet')->willReturn($data['street']);
        $address->method('getCity')->willReturn($data['city']);
        $address->method('getPostcode')->willReturn($data['postcode']);
        $address->method('getCountryId')->willReturn($data['country']);

        return $address;
    }

    private function item(int $id, int $productId, ?array $options = null): Item
    {
        $item = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getItemId', 'getProductId', 'getProductOptions'])
            ->getMock();
        $item->method('getItemId')->willReturn($id);
        $item->method('getProductId')->willReturn($productId);
        $item->method('getProductOptions')->willReturn($options);

        return $item;
    }

    public function testNoOrderInSession(): void
    {
        $block = $this->withTotals($this->block(null), false);

        $this->assertNull($block->getOrder());
        $this->assertSame('', $block->getOrderUrl());
        $this->assertSame('', $block->getFormattedDate());
        $this->assertSame([], $block->getOrderItems());
        $this->assertNull($block->getShippingAddress());
        $this->assertNull($block->getBillingAddress());
        $this->assertFalse($block->isBillingDifferentFromShipping());
        $this->assertNull($block->getPaymentMethodTitle());
        $this->assertNull($block->getShippingMethodTitle());
        $this->assertFalse($block->isGuestOrder());
        $this->assertNull($block->getCouponCode());
        $this->assertNull($block->getCustomerEmail());
        $this->assertSame('', $block->getCustomScriptsHtml());
        $this->assertFalse($block->hasInvoices());
        $this->assertSame('', $block->getInvoicePrintUrl());
        $this->assertSame([], $block->getAdditionalTotals());
        $this->assertSame([], $block->getOrderItemImages());
        $this->assertFalse($block->canCreateAccount());
    }

    public function testOrderIsLoadedOnce(): void
    {
        $order = $this->order();
        $this->checkoutSession->expects($this->once())->method('getLastRealOrder')->willReturn($order);
        $context = $this->createMock(Context::class);
        $context->method('getUrlBuilder')->willReturn($this->urlBuilder);
        $block = new Success(
            $context,
            $this->helper,
            $this->checkoutSession,
            $this->customerSession,
            $this->addressRenderer,
            $this->productRepository,
            $this->imageHelper,
            $this->registration,
            $this->accountManagement
        );

        $this->assertSame($order, $block->getOrder());
        $this->assertSame($order, $block->getOrder());
    }

    public function testUrls(): void
    {
        $block = $this->block($this->order());

        $this->assertSame('https://shop.example.com/sales/order/view/order_id=15', $block->getOrderUrl());
        $this->assertSame('https://shop.example.com/sales/order/history/', $block->getMyOrdersUrl());
        $this->assertSame('https://shop.example.com/sales/order/printInvoice/order_id=15', $block->getInvoicePrintUrl());
        $this->assertSame('https://shop.example.com/checkout/account/delegateCreate/', $block->getCreateAccountUrl());
        $this->assertSame('https://shop.example.com/', $block->getContinueShoppingUrl());
    }

    public function testHelperDelegation(): void
    {
        $this->helper->method('isEnabled')->willReturn(true);
        $this->helper->method('getLayout')->willReturn('single-column');
        $this->helper->method('getThankYouTitle')->willReturn('Thanks!');
        $this->helper->method('getThankYouMessage')->willReturn('Soon.');
        $this->sections = ['order_items' => false];
        $block = $this->block($this->order());

        $this->assertTrue($block->isEnabled());
        $this->assertSame('single-column', $block->getLayoutMode());
        $this->assertSame('Thanks!', $block->getThankYouTitle());
        $this->assertSame('Soon.', $block->getThankYouMessage());
        $this->assertFalse($block->showSection('order_items'));
        $this->assertTrue($block->showSection('order_totals'));
        $this->assertSame($this->helper, $block->getHelper());
    }

    public function testOrderDetails(): void
    {
        $method = $this->createMock(MethodInterface::class);
        $method->method('getTitle')->willReturn('Check / Money order');
        $payment = $this->createMock(Payment::class);
        $payment->method('getMethodInstance')->willReturn($method);
        $items = [$this->item(1, 10), $this->item(2, 11)];
        $block = $this->block($this->order([
            'getPayment' => $payment,
            'getShippingDescription' => 'Flat Rate - Fixed',
            'getAllVisibleItems' => $items,
            'getCouponCode' => 'SAVE10',
            'hasInvoices' => true,
        ]));

        $this->assertSame('Check / Money order', $block->getPaymentMethodTitle());
        $this->assertSame('Flat Rate - Fixed', $block->getShippingMethodTitle());
        $this->assertSame($items, $block->getOrderItems());
        $this->assertSame('SAVE10', $block->getCouponCode());
        $this->assertSame('guest@example.com', $block->getCustomerEmail());
        $this->assertTrue($block->isGuestOrder());
        $this->assertTrue($block->hasInvoices());
    }

    public function testPaymentTitleIsNullWhenMethodUnavailable(): void
    {
        $payment = $this->createMock(Payment::class);
        $payment->method('getMethodInstance')->willThrowException(new LocalizedException(__('gone')));
        $this->assertNull($this->block($this->order(['getPayment' => $payment]))->getPaymentMethodTitle());
        $this->assertNull($this->block($this->order(['getPayment' => null]))->getPaymentMethodTitle());
    }

    public function testEmptyCouponIsNull(): void
    {
        $this->assertNull($this->block($this->order(['getCouponCode' => '']))->getCouponCode());
    }

    public function testAddressesAreRenderedAsHtml(): void
    {
        $shipping = $this->address([]);
        $billing = $this->address(['city' => 'Elsewhere']);
        $this->addressRenderer->method('format')->willReturnCallback(
            static fn ($address, $type) => $type . ':' . $address->getCity()
        );
        $block = $this->block($this->order(['getShippingAddress' => $shipping, 'getBillingAddress' => $billing]));

        $this->assertSame('html:Testville', $block->getShippingAddress());
        $this->assertSame('html:Elsewhere', $block->getBillingAddress());
        $this->assertTrue($block->isBillingDifferentFromShipping());
    }

    public static function sameAddressProvider(): array
    {
        return [
            'identical' => [[], [], false],
            'case and spaces' => [['firstname' => ' TESTY ', 'city' => 'testville'], [], false],
            'street differs' => [['street' => ['2 Other Rd']], [], true],
            'postcode differs' => [['postcode' => '10001'], [], true],
            'country differs' => [['country' => 'GB'], [], true],
            'last name differs' => [['lastname' => 'Other'], [], true],
        ];
    }

    #[DataProvider('sameAddressProvider')]
    public function testBillingComparison(array $billing, array $shipping, bool $different): void
    {
        $block = $this->block($this->order([
            'getShippingAddress' => $this->address($shipping),
            'getBillingAddress' => $this->address($billing),
        ]));

        $this->assertSame($different, $block->isBillingDifferentFromShipping());
    }

    public function testVirtualOrderHasNoShippingAddress(): void
    {
        $block = $this->block($this->order(['getShippingAddress' => null, 'getBillingAddress' => $this->address([])]));

        $this->assertNull($block->getShippingAddress());
        $this->assertFalse($block->isBillingDifferentFromShipping());
    }

    public function testItemOptionsCollectsAllOptionTypesAsPlainText(): void
    {
        $item = $this->item(1, 10, [
            'attributes_info' => [['label' => 'Size', 'value' => 'M'], ['label' => 'Color', 'value' => 'Blue']],
            'options' => [
                ['label' => 'Engraving', 'value' => 'LUMA QA', 'print_value' => 'LUMA QA'],
                ['label' => 'Strap', 'value' => 'x', 'print_value' => 'Brown &lt;b&gt;Leather&lt;/b&gt; &amp; Co'],
                ['label' => 'Logo', 'value' => '<a href="https://x/">logo.png</a> 10 x 10 px.'],
                ['label' => 'Extras', 'value' => ['Cloth', 'Box']],
                ['label' => '', 'value' => 'orphan'],
                ['label' => 'Empty', 'value' => ''],
                'broken',
            ],
            'additional_options' => [['label' => 'Gift wrap', 'value' => 'Yes']],
            'info_buyRequest' => ['qty' => 1],
        ]);

        $this->assertSame([
            ['label' => 'Size', 'value' => 'M'],
            ['label' => 'Color', 'value' => 'Blue'],
            ['label' => 'Engraving', 'value' => 'LUMA QA'],
            ['label' => 'Strap', 'value' => 'Brown <b>Leather</b> & Co'],
            ['label' => 'Logo', 'value' => 'logo.png 10 x 10 px.'],
            ['label' => 'Extras', 'value' => 'Cloth, Box'],
            ['label' => 'Gift wrap', 'value' => 'Yes'],
        ], $this->block($this->order())->getItemOptions($item));
    }

    public function testItemWithoutOptions(): void
    {
        $block = $this->block($this->order());
        $this->assertSame([], $block->getItemOptions($this->item(1, 10, null)));
        $this->assertSame([], $block->getItemOptions($this->item(1, 10, ['info_buyRequest' => ['qty' => 2]])));
    }

    public static function qtyProvider(): array
    {
        return [
            [1, '1'], ['2.0000', '2'], [1.5, '1.5'], ['0.2500', '0.25'], ['3.99999', '4'], [null, '0'], [12.125, '12.125'],
        ];
    }

    #[DataProvider('qtyProvider')]
    public function testFormatQty($qty, string $expected): void
    {
        $this->assertSame($expected, $this->block($this->order())->formatQty($qty));
    }

    public function testCanCreateAccountForGuestWithFreeEmail(): void
    {
        $store = $this->createMock(Store::class);
        $store->method('getWebsiteId')->willReturn(1);
        $this->registration->method('isAllowed')->willReturn(true);
        $this->accountManagement->expects($this->once())->method('isEmailAvailable')
            ->with('guest@example.com', 1)->willReturn(true);

        $this->assertTrue($this->block($this->order(['getStore' => $store]))->canCreateAccount());
    }

    public function testCannotCreateAccountWhenEmailTaken(): void
    {
        $store = $this->createMock(Store::class);
        $store->method('getWebsiteId')->willReturn(1);
        $this->registration->method('isAllowed')->willReturn(true);
        $this->accountManagement->method('isEmailAvailable')->willReturn(false);

        $this->assertFalse($this->block($this->order(['getStore' => $store]))->canCreateAccount());
    }

    public function testCreateAccountRules(): void
    {
        $this->registration->method('isAllowed')->willReturn(true);

        $this->assertFalse($this->block($this->order(['getCustomerIsGuest' => 0]))->canCreateAccount());

        $this->sections = ['create_account' => false];
        $this->assertFalse($this->block($this->order())->canCreateAccount());
    }

    public function testCannotCreateAccountWhenLoggedIn(): void
    {
        $this->loggedIn = true;
        $this->registration->method('isAllowed')->willReturn(true);

        $block = $this->block($this->order());
        $this->assertTrue($block->isCustomerLoggedIn());
        $this->assertFalse($block->canCreateAccount());
    }

    public function testCannotCreateAccountWhenRegistrationDisabled(): void
    {
        $this->registration->method('isAllowed')->willReturn(false);
        $this->accountManagement->expects($this->never())->method('isEmailAvailable');

        $this->assertFalse($this->block($this->order())->canCreateAccount());
    }

    public function testCreateAccountShownWhenEmailCheckFailsOrEmailMissing(): void
    {
        $this->registration->method('isAllowed')->willReturn(true);
        $this->accountManagement->method('isEmailAvailable')->willThrowException(new \RuntimeException('db'));
        $store = $this->createMock(Store::class);

        $this->assertTrue($this->block($this->order(['getStore' => $store]))->canCreateAccount());
        $this->assertTrue($this->block($this->order(['getCustomerEmail' => null]))->canCreateAccount());
    }

    public function testOrderItemImagesAreCachedAndFailSoft(): void
    {
        $product = $this->getMockBuilder(Product::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $product->setData('thumbnail', '/g/3/thumb.jpg');
        $this->productRepository->expects($this->exactly(2))->method('getById')->willReturnCallback(
            static function (int $id) use ($product) {
                if ($id === 99) {
                    throw new NoSuchEntityException(__('deleted'));
                }
                return $product;
            }
        );
        $this->imageHelper->method('init')->with($product, 'product_thumbnail_image')->willReturnSelf();
        $this->imageHelper->method('setImageFile')->with('/g/3/thumb.jpg')->willReturnSelf();
        $this->imageHelper->method('resize')->with(96, 96)->willReturnSelf();
        $this->imageHelper->method('getUrl')->willReturn('https://shop.example.com/media/thumb.jpg');
        $block = $this->block($this->order(['getAllVisibleItems' => [$this->item(1, 10), $this->item(2, 99)]]));

        $expected = [1 => 'https://shop.example.com/media/thumb.jpg', 2 => ''];
        $this->assertSame($expected, $block->getOrderItemImages());
        $this->assertSame($expected, $block->getOrderItemImages());
    }

    public function testCustomScriptsPassOrderData(): void
    {
        $this->helper->method('getCustomScripts')->willReturn('<script>t("{{orderId}}")</script>');
        $this->helper->expects($this->once())->method('processVariables')->willReturnCallback(
            function (string $content, array $data) {
                $this->assertSame('000000226', $data['increment_id']);
                $this->assertSame('44.0000', $data['grand_total']);
                $this->assertSame('USD', $data['currency_code']);
                $this->assertSame('guest@example.com', $data['customer_email']);
                $this->assertSame('', $data['payment_title']);
                $this->assertSame('Flat Rate - Fixed', $data['shipping_title']);
                $this->assertSame('', $data['coupon_code']);
                $this->assertSame('2', $data['item_count']);
                return str_replace('{{orderId}}', $data['increment_id'], $content);
            }
        );
        $block = $this->block($this->order([
            'getGrandTotal' => '44.0000',
            'getSubtotal' => '39.0000',
            'getOrderCurrencyCode' => 'USD',
            'getShippingDescription' => 'Flat Rate - Fixed',
            'getCouponCode' => null,
            'getAllVisibleItems' => [$this->item(1, 10), $this->item(2, 11)],
            'getShippingAmount' => '5.0000',
            'getTaxAmount' => '0.0000',
            'getDiscountAmount' => '0.0000',
            'getPayment' => null,
        ]));

        $this->assertSame('<script>t("000000226")</script>', $block->getCustomScriptsHtml());
    }

    public function testCustomScriptsEmptyWhenNotConfigured(): void
    {
        $this->helper->method('getCustomScripts')->willReturn(null);
        $this->helper->expects($this->never())->method('processVariables');

        $this->assertSame('', $this->block($this->order())->getCustomScriptsHtml());
    }

    private function withTotals(Success $block, $totalsBlock): Success
    {
        $layout = $this->createMock(LayoutInterface::class);
        $layout->method('getChildName')->willReturn('order_totals');
        $layout->method('getBlock')->with('order_totals')->willReturn($totalsBlock);
        $block->setLayout($layout);

        return $block;
    }

    public function testAdditionalTotalsListsOnlyExtraNonZeroRows(): void
    {
        $order = $this->order();
        $totals = $this->getMockBuilder(Totals::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setOrder', 'toHtml', 'getTotals'])
            ->getMock();
        $totals->expects($this->once())->method('setOrder')->with($order)->willReturnSelf();
        $totals->method('toHtml')->willReturn('');
        $totals->method('getTotals')->willReturn([
            'subtotal' => new DataObject(['label' => 'Subtotal', 'value' => 39]),
            'shipping' => new DataObject(['label' => 'Shipping', 'value' => 5]),
            'grand_total' => new DataObject(['label' => 'Grand Total', 'value' => 53.5]),
            'small_order_fee' => new DataObject(['label' => 'Small Order Fee', 'value' => 5]),
            'zero_fee' => new DataObject(['label' => 'Zero Fee', 'value' => 0]),
            'rendered' => new DataObject(['label' => 'Gift Card', 'value' => 3, 'block_name' => 'giftcard']),
            'payment_fee' => new DataObject(['label' => 'Payment Fee', 'value' => '1.5']),
            'scalar' => 'not a total',
        ]);

        $rows = $this->withTotals($this->block($order), $totals)->getAdditionalTotals();

        $this->assertSame([
            ['label' => 'Small Order Fee', 'value' => '$5.00'],
            ['label' => 'Payment Fee', 'value' => '$1.50'],
        ], $rows);
    }

    public function testAdditionalTotalsFailSoft(): void
    {
        $totals = $this->getMockBuilder(Totals::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setOrder', 'toHtml', 'getTotals'])
            ->getMock();
        $totals->method('toHtml')->willThrowException(new \RuntimeException('broken renderer'));
        $this->logger->expects($this->once())->method('warning')
            ->with($this->stringContains('broken renderer'));

        $this->assertSame([], $this->withTotals($this->block($this->order()), $totals)->getAdditionalTotals());
    }

    public function testAdditionalTotalsEmptyWithoutTotalsChild(): void
    {
        $this->assertSame([], $this->withTotals($this->block($this->order()), false)->getAdditionalTotals());
    }

    public function testCmsBlockHtml(): void
    {
        $this->helper->method('getCmsBlockId')->willReturn('panth_checkout_success_bottom');
        $cms = $this->getMockBuilder(\Magento\Cms\Block\Block::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['toHtml'])
            ->getMock();
        $cms->method('toHtml')->willReturnCallback(
            static fn () => '<div>' . $cms->getData('block_id') . '</div>'
        );
        $layout = $this->createMock(LayoutInterface::class);
        $layout->method('createBlock')->with(\Magento\Cms\Block\Block::class)->willReturn($cms);
        $block = $this->block($this->order());
        $block->setLayout($layout);

        $this->assertSame('<div>panth_checkout_success_bottom</div>', $block->getCmsBlockHtml());
    }

    public function testCmsBlockHtmlEmptyWhenNotConfiguredOrBroken(): void
    {
        $this->helper->method('getCmsBlockId')->willReturnOnConsecutiveCalls(null, 'missing');
        $layout = $this->createMock(LayoutInterface::class);
        $layout->method('createBlock')->willThrowException(new \RuntimeException('no block'));
        $block = $this->block($this->order());
        $block->setLayout($layout);

        $this->assertSame('', $block->getCmsBlockHtml());
        $this->assertSame('', $block->getCmsBlockHtml());
    }
}
