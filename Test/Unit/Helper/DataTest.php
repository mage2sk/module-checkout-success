<?php
declare(strict_types=1);

namespace Panth\CheckoutSuccess\Test\Unit\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Escaper;
use Magento\Store\Model\ScopeInterface;
use Panth\CheckoutSuccess\Helper\Data;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DataTest extends TestCase
{
    private array $requestedStores = [];

    private function helper(array $values): Data
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(
            function (string $path, string $scope = 'default', $store = null) use ($values) {
                $this->assertSame(ScopeInterface::SCOPE_STORE, $scope, $path . ' must be read at store scope');
                $this->requestedStores[$path] = $store;
                return $values[$path] ?? null;
            }
        );
        $context = $this->createMock(Context::class);
        $context->method('getScopeConfig')->willReturn($scopeConfig);
        $escaper = $this->createMock(Escaper::class);
        $escaper->method('escapeJs')->willReturnCallback(
            static fn (string $value): string => strtr($value, [
                '\\' => '\\\\',
                '"' => '\\u0022',
                "'" => '\\u0027',
                '<' => '\\u003C',
                '>' => '\\u003E',
            ])
        );

        return new Data($context, $escaper);
    }

    public function testConfigValueUsesPrefixAndStore(): void
    {
        $helper = $this->helper(['panth_checkout_success/style/layout' => 'single-column']);

        $this->assertSame('single-column', $helper->getConfigValue('style', 'layout', 3));
        $this->assertSame(3, $this->requestedStores['panth_checkout_success/style/layout']);
        $this->assertSame('panth_checkout_success/', Data::XML_PATH_PREFIX);
    }

    public function testEnabledFlag(): void
    {
        $this->assertTrue($this->helper(['panth_checkout_success/general/enabled' => '1'])->isEnabled());
        $this->assertFalse($this->helper(['panth_checkout_success/general/enabled' => '0'])->isEnabled());
        $this->assertFalse($this->helper([])->isEnabled());
    }

    public function testLayoutFallsBackToTwoColumn(): void
    {
        $this->assertSame('two-column', $this->helper([])->getLayout());
        $this->assertSame('two-column', $this->helper(['panth_checkout_success/style/layout' => ''])->getLayout());
        $this->assertSame(
            'single-column',
            $this->helper(['panth_checkout_success/style/layout' => 'single-column'])->getLayout()
        );
    }

    public function testThankYouTitleAndMessage(): void
    {
        $this->assertSame('Thank you for your order!', $this->helper([])->getThankYouTitle());
        $this->assertSame(
            'Cheers',
            $this->helper(['panth_checkout_success/style/thank_you_title' => 'Cheers'])->getThankYouTitle()
        );
        $this->assertSame('', $this->helper([])->getThankYouMessage());
        $this->assertSame(
            'See you soon',
            $this->helper(['panth_checkout_success/style/thank_you_message' => 'See you soon'])->getThankYouMessage()
        );
    }

    public static function sectionProvider(): array
    {
        return [
            ['order_number'], ['order_date'], ['order_items'], ['order_totals'],
            ['shipping_info'], ['payment_info'], ['create_account'], ['continue_shopping'],
        ];
    }

    #[DataProvider('sectionProvider')]
    public function testShowSectionReadsContentFlag(string $section): void
    {
        $path = 'panth_checkout_success/content/show_' . $section;
        $this->assertTrue($this->helper([$path => '1'])->showSection($section));
        $this->assertFalse($this->helper([$path => '0'])->showSection($section));
        $this->assertFalse($this->helper([])->showSection($section));
    }

    public function testCmsBlockIdIsNullWhenEmpty(): void
    {
        $path = 'panth_checkout_success/content/cms_block';
        $this->assertNull($this->helper([])->getCmsBlockId());
        $this->assertNull($this->helper([$path => ''])->getCmsBlockId());
        $this->assertSame(
            'panth_checkout_success_bottom',
            $this->helper([$path => 'panth_checkout_success_bottom'])->getCmsBlockId()
        );
        $this->assertSame('12', $this->helper([$path => 12])->getCmsBlockId());
    }

    public function testCustomScripts(): void
    {
        $path = 'panth_checkout_success/tracking/custom_scripts';
        $this->assertNull($this->helper([])->getCustomScripts());
        $this->assertSame('<script>x()</script>', $this->helper([$path => '<script>x()</script>'])->getCustomScripts());
    }

    public function testProcessVariablesReplacesEveryPlaceholder(): void
    {
        $template = implode('|', [
            '{{orderId}}', '{{orderTotal}}', '{{orderSubtotal}}', '{{orderCurrency}}', '{{customerEmail}}',
            '{{paymentTitle}}', '{{shippingTitle}}', '{{couponCode}}', '{{orderItemCount}}',
            '{{shippingAmount}}', '{{taxAmount}}', '{{discountAmount}}', '{{unknown}}',
        ]);
        $result = $this->helper([])->processVariables($template, [
            'increment_id' => '000000226',
            'grand_total' => 44.0,
            'subtotal' => '39.0000',
            'currency_code' => 'USD',
            'customer_email' => 'a@example.com',
            'payment_title' => 'Check / Money order',
            'shipping_title' => 'Flat Rate - Fixed',
            'coupon_code' => 'SAVE10',
            'item_count' => '2',
            'shipping_amount' => '5.0000',
            'tax_amount' => '0.0000',
            'discount_amount' => '-3.5000',
        ]);

        $this->assertSame(
            '000000226|44|39.0000|USD|a@example.com|Check / Money order|Flat Rate - Fixed|SAVE10|2|5.0000|0.0000|-3.5000|'
            . '{{unknown}}',
            $result
        );
    }

    public function testProcessVariablesEscapesForJavascriptAndDefaultsMissingValues(): void
    {
        $result = $this->helper([])->processVariables(
            'var e="{{customerEmail}}";var c="{{couponCode}}";var t="{{orderTotal}}";',
            ['customer_email' => '"</script><script>alert(1)</script>', 'coupon_code' => "it's"]
        );

        $this->assertStringNotContainsString('</script>', $result);
        $this->assertStringNotContainsString('"</', $result);
        $this->assertStringContainsString('it\\u0027s', $result);
        $this->assertStringContainsString('var t="";', $result);
    }
}
