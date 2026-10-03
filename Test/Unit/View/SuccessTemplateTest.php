<?php
declare(strict_types=1);

namespace Panth\CheckoutSuccess\Test\Unit\View;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Panth\CheckoutSuccess\Block\Success;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class SuccessTemplateTest extends TestCase
{
    private const TEMPLATE = __DIR__ . '/../../../view/frontend/templates/success.phtml';

    private array $sections = [];

    private function item(): Item
    {
        $item = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getItemId', 'getName', 'getSku', 'getQtyOrdered', 'getRowTotal'])
            ->getMock();
        $item->method('getItemId')->willReturn(7);
        $item->method('getName')->willReturn('Sample <Watch>');
        $item->method('getSku')->willReturn('sample-watch');
        $item->method('getQtyOrdered')->willReturn('1.5000');
        $item->method('getRowTotal')->willReturn('165.0000');

        return $item;
    }

    private function order(): Order
    {
        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getIncrementId', 'formatPrice', 'getSubtotal', 'getShippingAmount', 'getTaxAmount',
                'getDiscountAmount', 'getGrandTotal'])
            ->getMock();
        $order->method('getIncrementId')->willReturn('2000000070');
        $order->method('formatPrice')->willReturnCallback(static fn ($v) => '<span class="price">$' . number_format((float) $v, 2) . '</span>');
        $order->method('getSubtotal')->willReturn('165.0000');
        $order->method('getShippingAmount')->willReturn('5.0000');
        $order->method('getTaxAmount')->willReturn('0.0000');
        $order->method('getDiscountAmount')->willReturn('-10.0000');
        $order->method('getGrandTotal')->willReturn('160.0000');

        return $order;
    }

    private function block(array $overrides = []): Success
    {
        $item = $this->item();
        $values = array_merge([
            'isEnabled' => true,
            'getOrder' => $this->order(),
            'getLayoutMode' => 'two-column',
            'getThankYouTitle' => 'Thank you <b>now</b>',
            'getThankYouMessage' => 'We got it.',
            'isCustomerLoggedIn' => false,
            'getOrderUrl' => 'https://shop.example.com/sales/order/view/order_id/9/',
            'getFormattedDate' => 'October 3, 2026',
            'getPaymentMethodTitle' => 'Check / Money order',
            'getShippingMethodTitle' => 'Flat Rate - Fixed',
            'getOrderItems' => [$item],
            'getOrderItemImages' => [7 => 'https://shop.example.com/media/w.jpg'],
            'getItemOptions' => [['label' => 'Strap', 'value' => 'Brown <b>Leather</b> & Co']],
            'getShippingAddress' => 'Testy McTestface<br/>1 Fake St',
            'getBillingAddress' => 'Billing Person<br/>2 Fake St',
            'isBillingDifferentFromShipping' => true,
            'getAdditionalTotals' => [['label' => 'Small Order Fee', 'value' => '<span class="price">$5.00</span>']],
            'canCreateAccount' => true,
            'getCreateAccountUrl' => 'https://shop.example.com/checkout/account/delegateCreate/',
            'getMyOrdersUrl' => 'https://shop.example.com/sales/order/history/',
            'hasInvoices' => false,
            'getInvoicePrintUrl' => 'https://shop.example.com/sales/order/printInvoice/order_id/9/',
            'getContinueShoppingUrl' => 'https://shop.example.com/',
            'getCmsBlockHtml' => '<div class="trust">Trust</div>',
            'getCustomScriptsHtml' => '<script>window.t="2000000070";</script>',
        ], $overrides);
        $block = $this->getMockBuilder(Success::class)
            ->disableOriginalConstructor()
            ->onlyMethods(array_merge(array_keys($values), ['showSection', 'formatQty', 'escapeHtml', 'escapeHtmlAttr', 'escapeUrl']))
            ->getMock();
        foreach ($values as $method => $value) {
            $block->method($method)->willReturn($value);
        }
        $block->method('showSection')->willReturnCallback(fn (string $s) => $this->sections[$s] ?? true);
        $block->method('formatQty')->willReturnCallback(static fn ($q) => rtrim(rtrim(number_format((float) $q, 4, '.', ''), '0'), '.'));
        $escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        $block->method('escapeHtml')->willReturnCallback($escape);
        $block->method('escapeHtmlAttr')->willReturnCallback($escape);
        $block->method('escapeUrl')->willReturnCallback($escape);

        return $block;
    }

    private function render(Success $block): string
    {
        $renderer = static function (string $file) use ($block): string {
            ob_start();
            include $file;
            return (string) ob_get_clean();
        };

        return $renderer(self::TEMPLATE);
    }

    private function xpath(string $html): \DOMXPath
    {
        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new \DOMXPath($dom);
    }

    private function text(\DOMXPath $xpath, string $query): string
    {
        $node = $xpath->query($query)->item(0);
        return $node ? trim((string) preg_replace('/\s+/', ' ', $node->textContent)) : '';
    }

    public function testRendersNothingWhenDisabled(): void
    {
        $this->assertSame('', trim($this->render($this->block(['isEnabled' => false]))));
    }

    public function testFallbackWithoutOrderUsesThemeButton(): void
    {
        $html = $this->render($this->block(['getOrder' => null]));
        $xpath = $this->xpath($html);

        $this->assertSame(1, $xpath->query('//div[contains(@class,"panth-success-fallback")]')->length);
        $this->assertSame('Thank you for your order!', $this->text($xpath, '//h1'));
        $this->assertSame('https://shop.example.com/', $xpath->query('//a[@class="panth-continue-btn"]')->item(0)->getAttribute('href'));
        $this->assertStringNotContainsString('style=', $html);
        $this->assertStringNotContainsString('<style', $html);
        $this->assertStringNotContainsStringIgnoringCase('1a1a2e', $html);
    }

    public function testFullPage(): void
    {
        $html = $this->render($this->block());
        $xpath = $this->xpath($html);

        $this->assertSame(1, $xpath->query('//div[contains(@class,"panth-success-page panth-success-two-column")]')->length);
        $this->assertSame(1, $xpath->query('//div[contains(@class,"panth-success-grid")]')->length);
        $this->assertSame(1, $xpath->query('//h1')->length);
        $this->assertSame('Thank you <b>now</b>', $this->text($xpath, '//h1'));
        $this->assertSame("Order #2000000070 \u{00B7} October 3, 2026", $this->text($xpath, '//p[@class="panth-success-order-ref"]'));
        $this->assertSame(0, $xpath->query('//a[@class="panth-order-link"]')->length);
        $this->assertSame(
            ['Order Details', 'Items Ordered (1)', 'Shipping Address', 'Billing Address', 'Order Summary', 'Create an Account'],
            array_map(fn ($n) => trim((string) preg_replace('/\s+/', ' ', $n->textContent)), iterator_to_array($xpath->query('//h2')))
        );
        $this->assertStringNotContainsString('<!--', $html);
        foreach ($xpath->query('//svg') as $svg) {
            $this->assertSame('true', $svg->getAttribute('aria-hidden'));
            $this->assertSame('false', $svg->getAttribute('focusable'));
        }
    }

    public function testOrderMeta(): void
    {
        $xpath = $this->xpath($this->render($this->block()));
        $labels = array_map(fn ($n) => trim($n->textContent), iterator_to_array($xpath->query('//span[@class="panth-meta-label"]')));
        $values = array_map(fn ($n) => trim($n->textContent), iterator_to_array($xpath->query('//span[@class="panth-meta-value"]')));

        $this->assertSame(['Order Number', 'Order Date', 'Payment Method', 'Shipping Method'], $labels);
        $this->assertSame(['#2000000070', 'October 3, 2026', 'Check / Money order', 'Flat Rate - Fixed'], $values);
    }

    public function testItemsWithOptionsAndDecimalQty(): void
    {
        $html = $this->render($this->block());
        $xpath = $this->xpath($html);

        $img = $xpath->query('//div[@class="panth-item-image"]/img')->item(0);
        $this->assertSame('https://shop.example.com/media/w.jpg', $img->getAttribute('src'));
        $this->assertSame('Sample <Watch>', $img->getAttribute('alt'));
        $this->assertSame('lazy', $img->getAttribute('loading'));
        $this->assertSame('Sample <Watch>', $this->text($xpath, '//span[@class="panth-item-name"]'));
        $this->assertSame('SKU: sample-watch', $this->text($xpath, '//span[@class="panth-item-sku"]'));
        $this->assertSame('Strap:', $this->text($xpath, '//dl[@class="panth-item-options"]/div/dt'));
        $this->assertSame('Brown <b>Leather</b> & Co', $this->text($xpath, '//dl[@class="panth-item-options"]/div/dd'));
        $this->assertStringContainsString('Brown &lt;b&gt;Leather&lt;/b&gt; &amp; Co', $html);
        $this->assertSame('Qty: 1.5', $this->text($xpath, '//span[@class="panth-item-qty"]'));
        $this->assertSame('$165.00', $this->text($xpath, '//span[@class="panth-item-price"]'));
    }

    public function testItemWithoutImageOrOptions(): void
    {
        $xpath = $this->xpath($this->render($this->block(['getOrderItemImages' => [7 => ''], 'getItemOptions' => []])));

        $this->assertSame(0, $xpath->query('//div[@class="panth-item-image"]')->length);
        $this->assertSame(0, $xpath->query('//dl[@class="panth-item-options"]')->length);
    }

    public function testAddresses(): void
    {
        $xpath = $this->xpath($this->render($this->block()));
        $this->assertSame(1, $xpath->query('//div[contains(@class,"panth-address-card panth-address-two-col")]')->length);
        $this->assertSame(2, $xpath->query('//div[@class="panth-address"]')->length);

        $same = $this->xpath($this->render($this->block(['isBillingDifferentFromShipping' => false])));
        $this->assertSame(0, $same->query('//div[contains(@class,"panth-address-two-col")]')->length);
        $this->assertSame(1, $same->query('//div[@class="panth-address"]')->length);

        $virtual = $this->xpath($this->render($this->block(['getShippingAddress' => null])));
        $this->assertSame(0, $virtual->query('//div[contains(@class,"panth-address-card")]')->length);
    }

    public function testTotals(): void
    {
        $xpath = $this->xpath($this->render($this->block()));
        $rows = array_map(
            fn ($n) => trim((string) preg_replace('/\s+/', ' ', $n->textContent)),
            iterator_to_array($xpath->query('//div[contains(@class,"panth-total-row")]'))
        );

        $this->assertSame([
            'Subtotal $165.00', 'Shipping $5.00', 'Discount $-10.00', 'Small Order Fee $5.00', 'Grand Total $160.00',
        ], $rows);
    }

    public function testGuestActions(): void
    {
        $xpath = $this->xpath($this->render($this->block()));

        $this->assertSame(
            'https://shop.example.com/checkout/account/delegateCreate/',
            $xpath->query('//a[@class="panth-create-account-btn"]')->item(0)->getAttribute('href')
        );
        $this->assertSame(0, $xpath->query('//a[@class="panth-my-orders-btn"]')->length);
        $this->assertSame(0, $xpath->query('//a[@class="panth-invoice-btn"]')->length);
        $this->assertSame('Continue Shopping', $this->text($xpath, '//a[@class="panth-continue-btn"]'));

        $noAccount = $this->xpath($this->render($this->block(['canCreateAccount' => false])));
        $this->assertSame(0, $noAccount->query('//div[contains(@class,"panth-account-card")]')->length);
    }

    public function testLoggedInCustomerActions(): void
    {
        $xpath = $this->xpath($this->render($this->block([
            'isCustomerLoggedIn' => true,
            'canCreateAccount' => false,
            'hasInvoices' => true,
        ])));

        $link = $xpath->query('//a[@class="panth-order-link"]')->item(0);
        $this->assertSame('https://shop.example.com/sales/order/view/order_id/9/', $link->getAttribute('href'));
        $this->assertSame(
            'https://shop.example.com/sales/order/history/',
            $xpath->query('//a[@class="panth-my-orders-btn"]')->item(0)->getAttribute('href')
        );
        $invoice = $xpath->query('//a[@class="panth-invoice-btn"]')->item(0);
        $this->assertSame('_blank', $invoice->getAttribute('target'));
        $this->assertSame('noopener', $invoice->getAttribute('rel'));
    }

    public function testSectionsCanBeHidden(): void
    {
        $this->sections = array_fill_keys([
            'order_number', 'order_date', 'order_items', 'order_totals', 'shipping_info', 'payment_info',
            'continue_shopping',
        ], false);
        $html = $this->render($this->block(['canCreateAccount' => false, 'getCmsBlockHtml' => '', 'getCustomScriptsHtml' => '']));
        $xpath = $this->xpath($html);

        $this->assertSame(1, $xpath->query('//h1')->length);
        $this->assertSame(0, $xpath->query('//p[@class="panth-success-order-ref"]')->length);
        $this->assertSame(0, $xpath->query('//div[contains(@class,"panth-success-card")]')->length);
        $this->assertSame(0, $xpath->query('//a')->length);
        $this->assertStringNotContainsString('2000000070', $html);
    }

    public function testHeaderReferenceFollowsNumberAndDateFlags(): void
    {
        $this->sections = ['order_number' => false];
        $xpath = $this->xpath($this->render($this->block()));
        $this->assertSame('October 3, 2026', $this->text($xpath, '//p[@class="panth-success-order-ref"]'));

        $this->sections = ['order_date' => false];
        $xpath = $this->xpath($this->render($this->block()));
        $this->assertSame('Order #2000000070', $this->text($xpath, '//p[@class="panth-success-order-ref"]'));
    }

    public function testShippingInfoFlagHidesMethodAndAddress(): void
    {
        $this->sections = ['shipping_info' => false, 'payment_info' => false];
        $xpath = $this->xpath($this->render($this->block()));
        $labels = array_map(fn ($n) => trim($n->textContent), iterator_to_array($xpath->query('//span[@class="panth-meta-label"]')));

        $this->assertSame(['Order Number', 'Order Date'], $labels);
        $this->assertSame(0, $xpath->query('//div[contains(@class,"panth-address-card")]')->length);
    }

    public function testSingleColumnLayout(): void
    {
        $xpath = $this->xpath($this->render($this->block(['getLayoutMode' => 'single-column'])));

        $this->assertSame(1, $xpath->query('//div[contains(@class,"panth-success-single-column")]')->length);
        $this->assertSame(0, $xpath->query('//div[contains(@class,"panth-success-grid")]')->length);
    }

    public function testCmsBlockAndScripts(): void
    {
        $html = $this->render($this->block());
        $xpath = $this->xpath($html);

        $this->assertSame('Trust', $this->text($xpath, '//div[@class="panth-success-cms-block"]/div[@class="trust"]'));
        $this->assertStringContainsString('<script>window.t="2000000070";</script>', $html);

        $none = $this->xpath($this->render($this->block(['getCmsBlockHtml' => ''])));
        $this->assertSame(0, $none->query('//div[@class="panth-success-cms-block"]')->length);
    }

    public function testMessageIsOptionalAndEscaped(): void
    {
        $html = $this->render($this->block(['getThankYouMessage' => '<script>alert(1)</script>']));
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);

        $xpath = $this->xpath($this->render($this->block(['getThankYouMessage' => ''])));
        $this->assertSame(0, $xpath->query('//p[@class="panth-success-message"]')->length);
    }
}
