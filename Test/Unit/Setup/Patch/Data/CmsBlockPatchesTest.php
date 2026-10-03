<?php
declare(strict_types=1);

namespace Panth\CheckoutSuccess\Test\Unit\Setup\Patch\Data;

use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Api\Data\BlockInterface;
use Magento\Cms\Api\Data\BlockInterfaceFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Panth\CheckoutSuccess\Setup\Patch\Data\CreateSuccessCmsBlock;
use Panth\CheckoutSuccess\Setup\Patch\Data\UpdateSuccessCmsBlockMarkup;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CmsBlockPatchesTest extends TestCase
{
    private const LEGACY = '<div class="panth-info-item"><div class="panth-info-icon">'
        . '<svg width="28" height="28"></svg></div><h4>Free Returns</h4><p>x</p></div>'
        . '<style>.panth-info-item h4 { font-size: 15px; }</style>';

    public function testCreateSkipsExistingBlock(): void
    {
        $repository = $this->createMock(BlockRepositoryInterface::class);
        $repository->method('getById')->with('panth_checkout_success_bottom')
            ->willReturn($this->createMock(BlockInterface::class));
        $repository->expects($this->never())->method('save');
        $factory = $this->createMock(BlockInterfaceFactory::class);
        $factory->expects($this->never())->method('create');

        $patch = new CreateSuccessCmsBlock($repository, $factory);
        $this->assertSame($patch, $patch->apply());
    }

    public function testCreateSavesAccessibleTrustBlockForAllStores(): void
    {
        $repository = $this->createMock(BlockRepositoryInterface::class);
        $repository->method('getById')->willThrowException(new NoSuchEntityException(__('missing')));
        $block = $this->getMockBuilder(\Magento\Cms\Model\Block::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $factory = $this->createMock(BlockInterfaceFactory::class);
        $factory->method('create')->willReturn($block);
        $repository->expects($this->once())->method('save')->with($block);

        (new CreateSuccessCmsBlock($repository, $factory))->apply();

        $content = (string) $block->getContent();
        $this->assertSame('panth_checkout_success_bottom', $block->getIdentifier());
        $this->assertSame('Checkout Success - Trust Signals', $block->getTitle());
        $this->assertTrue((bool) $block->getData('is_active'));
        $this->assertSame([0], $block->getData('store_id'));
        $this->assertSame(4, substr_count($content, '<svg aria-hidden="true" focusable="false"'));
        $this->assertSame(4, substr_count($content, '<h3>'));
        $this->assertStringNotContainsString('<h4>', $content);
        $this->assertSame([], CreateSuccessCmsBlock::getDependencies());
        $this->assertSame([], (new CreateSuccessCmsBlock($repository, $factory))->getAliases());
    }

    public function testUpgradeMarkupFixesLegacyContent(): void
    {
        $updated = UpdateSuccessCmsBlockMarkup::upgradeMarkup(self::LEGACY);

        $this->assertStringContainsString('<svg aria-hidden="true" focusable="false" width="28"', $updated);
        $this->assertStringContainsString('<h3>Free Returns</h3>', $updated);
        $this->assertStringContainsString('.panth-info-item h3 {', $updated);
        $this->assertStringNotContainsString('h4', $updated);
        $this->assertSame($updated, UpdateSuccessCmsBlockMarkup::upgradeMarkup($updated));
    }

    public function testUpgradeMarkupLeavesMerchantContentAlone(): void
    {
        $custom = '<h4>Our promise</h4><svg width="28"></svg>';
        $this->assertSame($custom, UpdateSuccessCmsBlockMarkup::upgradeMarkup($custom));
    }

    public function testUpdatePatchSavesOnlyWhenChanged(): void
    {
        $block = $this->createMock(BlockInterface::class);
        $block->method('getContent')->willReturn(self::LEGACY);
        $block->expects($this->once())->method('setContent')
            ->with(UpdateSuccessCmsBlockMarkup::upgradeMarkup(self::LEGACY));
        $repository = $this->createMock(BlockRepositoryInterface::class);
        $repository->method('getById')->with(UpdateSuccessCmsBlockMarkup::IDENTIFIER)->willReturn($block);
        $repository->expects($this->once())->method('save')->with($block);

        $patch = new UpdateSuccessCmsBlockMarkup($repository);
        $this->assertSame($patch, $patch->apply());
        $this->assertSame([CreateSuccessCmsBlock::class], UpdateSuccessCmsBlockMarkup::getDependencies());
        $this->assertSame([], $patch->getAliases());
    }

    public function testUpdatePatchNoopForUpToDateOrMissingBlock(): void
    {
        $block = $this->createMock(BlockInterface::class);
        $block->method('getContent')->willReturn(UpdateSuccessCmsBlockMarkup::upgradeMarkup(self::LEGACY));
        $block->expects($this->never())->method('setContent');
        $repository = $this->createMock(BlockRepositoryInterface::class);
        $repository->method('getById')->willReturn($block);
        $repository->expects($this->never())->method('save');
        (new UpdateSuccessCmsBlockMarkup($repository))->apply();

        $missing = $this->createMock(BlockRepositoryInterface::class);
        $missing->method('getById')->willThrowException(new NoSuchEntityException(__('missing')));
        $missing->expects($this->never())->method('save');
        (new UpdateSuccessCmsBlockMarkup($missing))->apply();
    }
}
