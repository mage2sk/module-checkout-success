<?php
declare(strict_types=1);

namespace Panth\CheckoutSuccess\Test\Unit\Config;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Config\Dom;
use Panth\CheckoutSuccess\Block\Success;
use Panth\CheckoutSuccess\Model\Config\Source\CmsBlock;
use Panth\CheckoutSuccess\Model\Config\Source\SuccessLayout;
use Panth\CheckoutSuccess\Observer\AddEnabledLayoutHandle;
use PHPUnit\Framework\TestCase;

class ModuleConfigurationTest extends TestCase
{
    private const MODULE = 'Panth_CheckoutSuccess';

    private string $moduleDir;

    protected function setUp(): void
    {
        $path = (new ComponentRegistrar())->getPath(ComponentRegistrar::MODULE, self::MODULE);
        $this->assertNotNull($path, 'Panth_CheckoutSuccess is not registered');
        $this->moduleDir = realpath($path);
    }

    private function load(string $relative, ?string $schemaUrn = null): \DOMXPath
    {
        $file = $this->moduleDir . '/' . $relative;
        $this->assertFileExists($file);
        $dom = new \DOMDocument();
        $this->assertTrue($dom->load($file), $relative . ' is not well formed');
        if ($schemaUrn !== null) {
            $errors = Dom::validateDomDocument($dom, $schemaUrn);
            $this->assertSame([], array_map('strval', $errors), $relative . ' violates ' . $schemaUrn);
        }

        return new \DOMXPath($dom);
    }

    private function values(\DOMXPath $xpath, string $query): array
    {
        $result = [];
        foreach ($xpath->query($query) as $node) {
            $result[] = trim($node->nodeValue);
        }

        return $result;
    }

    public function testRegistrationPointsToModuleRoot(): void
    {
        $this->assertSame(realpath(dirname(__DIR__, 3)), $this->moduleDir);
    }

    public function testModuleSequence(): void
    {
        $xpath = $this->load('etc/module.xml', 'urn:magento:framework:Module/etc/module.xsd');
        $this->assertSame(
            ['Panth_Core', 'Magento_Checkout', 'Magento_Sales', 'Magento_Cms'],
            $this->values($xpath, '//module[@name="Panth_CheckoutSuccess"]/sequence/module/@name')
        );
    }

    public function testObserverIsFrontendOnly(): void
    {
        $xpath = $this->load('etc/frontend/events.xml', 'urn:magento:framework:Event/etc/events.xsd');
        $this->assertSame(
            [AddEnabledLayoutHandle::class],
            $this->values($xpath, '//event[@name="layout_load_before"]/observer/@instance')
        );
        $this->assertFileDoesNotExist($this->moduleDir . '/etc/events.xml');
    }

    public function testAclResourceGuardsConfigSection(): void
    {
        $acl = $this->load('etc/acl.xml', 'urn:magento:framework:Acl/etc/acl.xsd');
        $this->assertSame(
            ['Panth_CheckoutSuccess::config'],
            $this->values($acl, '//resource[@id="Magento_Config::config"]/resource/@id')
        );
        $system = $this->load('etc/adminhtml/system.xml');
        $this->assertSame(
            ['Panth_CheckoutSuccess::config'],
            $this->values($system, '//section[@id="panth_checkout_success"]/resource')
        );
        $this->assertSame(['panth'], $this->values($system, '//section[@id="panth_checkout_success"]/tab'));
    }

    public function testSystemFieldsAndSourceModels(): void
    {
        $xpath = $this->load('etc/adminhtml/system.xml');
        $base = '//section[@id="panth_checkout_success"]/group';
        $this->assertSame(['general', 'content', 'style', 'tracking'], $this->values($xpath, $base . '/@id'));
        $this->assertSame(
            ['show_order_number', 'show_order_date', 'show_order_items', 'show_order_totals', 'show_shipping_info',
                'show_payment_info', 'show_create_account', 'show_continue_shopping', 'cms_block'],
            $this->values($xpath, $base . '[@id="content"]/field/@id')
        );
        $this->assertSame(
            [CmsBlock::class],
            $this->values($xpath, $base . '[@id="content"]/field[@id="cms_block"]/source_model')
        );
        $this->assertSame(
            [SuccessLayout::class],
            $this->values($xpath, $base . '[@id="style"]/field[@id="layout"]/source_model')
        );
        foreach ($xpath->query($base . '/field[comment]') as $field) {
            $this->assertStringContainsString('comment', $field->getAttribute('translate'), $field->getAttribute('id'));
        }
        $comment = implode('', $this->values($xpath, $base . '[@id="tracking"]/field[@id="custom_scripts"]/comment'));
        foreach (['orderId', 'orderTotal', 'orderSubtotal', 'orderCurrency', 'customerEmail', 'paymentTitle',
                     'shippingTitle', 'couponCode', 'orderItemCount', 'shippingAmount', 'taxAmount', 'discountAmount'] as $var) {
            $this->assertStringContainsString('{{' . $var . '}}', $comment);
        }
    }

    public function testDefaults(): void
    {
        $xpath = $this->load('etc/config.xml', 'urn:magento:module:Magento_Store:etc/config.xsd');
        $base = '/config/default/panth_checkout_success/';
        $this->assertSame(['1'], $this->values($xpath, $base . 'general/enabled'));
        foreach (['order_number', 'order_date', 'order_items', 'order_totals', 'shipping_info', 'payment_info',
                     'create_account', 'continue_shopping'] as $section) {
            $this->assertSame(['1'], $this->values($xpath, $base . 'content/show_' . $section), $section);
        }
        $this->assertSame(['two-column'], $this->values($xpath, $base . 'style/layout'));
        $this->assertSame(['Thank you for your order!'], $this->values($xpath, $base . 'style/thank_you_title'));
        $this->assertSame([''], $this->values($xpath, $base . 'tracking/custom_scripts'));
    }

    public function testLayouts(): void
    {
        $schema = 'urn:magento:framework:View/Layout/etc/page_configuration.xsd';
        $success = $this->load('view/frontend/layout/checkout_onepage_success.xml', $schema);
        $this->assertSame([Success::class], $this->values($success, '//block[@name="panth.checkout.success"]/@class'));
        $this->assertSame(['false'], $this->values($success, '//block[@name="panth.checkout.success"]/@cacheable'));
        $this->assertSame(
            ['Magento\Sales\Block\Order\Totals'],
            $this->values($success, '//block[@name="panth.checkout.success"]/block[@name="order_totals"]/@class')
        );
        $this->assertSame([], $this->values($success, '//referenceBlock[@remove="true"]/@name'));

        $enabled = $this->load('view/frontend/layout/' . AddEnabledLayoutHandle::HANDLE . '.xml', $schema);
        $this->assertSame(
            ['page.main.title', 'checkout.success', 'checkout.registration'],
            $this->values($enabled, '//referenceBlock[@remove="true"]/@name')
        );
        $this->assertSame(
            ['Panth_CheckoutSuccess::css/checkout-success.css'],
            $this->values($enabled, '//head/css/@src')
        );
    }

    public function testStylesheetsKeepTwoColumnGridForDesktopOnly(): void
    {
        $css = (string) file_get_contents($this->moduleDir . '/view/frontend/web/css/checkout-success.css');
        $tablet = substr($css, strpos($css, '@media screen and (min-width: 768px)'));
        $tablet = substr($tablet, 0, strpos($tablet, '@media screen and (min-width: 1024px)'));
        $this->assertStringNotContainsString('grid-template-columns', $tablet);
        $this->assertStringContainsString('.panth-success-page a:focus-visible', $css);
        $this->assertStringContainsString('prefers-reduced-motion', $css);
        $this->assertStringContainsString('.panth-item-options', $css);

        $less = (string) file_get_contents($this->moduleDir . '/view/frontend/web/css/source/_module.less');
        $this->assertStringContainsString('.panth-success-page a:focus-visible', $less);
        $this->assertStringContainsString('.panth-item-options', $less);
        $this->assertSame(2, substr_count($less, 'display: grid;'));
    }
}
