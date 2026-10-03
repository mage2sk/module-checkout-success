<?php
declare(strict_types=1);

namespace Panth\CheckoutSuccess\Test\Unit\Model\Config\Source;

use Magento\Cms\Model\Config\Source\Block as CmsBlockSource;
use Panth\CheckoutSuccess\Model\Config\Source\CmsBlock;
use Panth\CheckoutSuccess\Model\Config\Source\SuccessLayout;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class SourceModelsTest extends TestCase
{
    public function testLayoutOptions(): void
    {
        $options = (new SuccessLayout())->toOptionArray();

        $this->assertSame(['single-column', 'two-column'], array_column($options, 'value'));
        $this->assertSame('Single Column (Centered)', (string) $options[0]['label']);
        $this->assertSame('Two Column (Details + Summary)', (string) $options[1]['label']);
    }

    public function testCmsBlockPrependsNoneAndKeepsValidBlocks(): void
    {
        $source = $this->createMock(CmsBlockSource::class);
        $source->method('toOptionArray')->willReturn([
            ['value' => 'panth_checkout_success_bottom', 'label' => 'Checkout Success - Trust Signals'],
            ['value' => '', 'label' => 'Empty'],
            'broken',
            ['label' => 'No value'],
            ['value' => 'footer_links_block', 'label' => 'Footer Links'],
        ]);

        $options = (new CmsBlock($source))->toOptionArray();

        $this->assertSame(['', 'panth_checkout_success_bottom', 'footer_links_block'], array_column($options, 'value'));
        $this->assertSame('-- None --', (string) $options[0]['label']);
        $this->assertSame('Footer Links', $options[2]['label']);
    }

    public function testCmsBlockOnlyNoneWithoutBlocks(): void
    {
        $source = $this->createMock(CmsBlockSource::class);
        $source->method('toOptionArray')->willReturn([]);

        $options = (new CmsBlock($source))->toOptionArray();

        $this->assertCount(1, $options);
        $this->assertSame('', $options[0]['value']);
    }
}
