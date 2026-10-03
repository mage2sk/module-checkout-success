<?php
declare(strict_types=1);

namespace Panth\CheckoutSuccess\Test\Unit\Observer;

use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\View\Layout\ProcessorInterface;
use Magento\Framework\View\LayoutInterface;
use Panth\CheckoutSuccess\Helper\Data;
use Panth\CheckoutSuccess\Observer\AddEnabledLayoutHandle;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class AddEnabledLayoutHandleTest extends TestCase
{
    private function observer(?string $action, $layout): Observer
    {
        $event = new Event(['full_action_name' => $action, 'layout' => $layout]);
        return new Observer(['event' => $event]);
    }

    private function helper(bool $enabled): Data
    {
        $helper = $this->createMock(Data::class);
        $helper->method('isEnabled')->willReturn($enabled);
        return $helper;
    }

    private function layout(int $expectedHandles): LayoutInterface
    {
        $update = $this->createMock(ProcessorInterface::class);
        $update->expects($this->exactly($expectedHandles))
            ->method('addHandle')
            ->with(AddEnabledLayoutHandle::HANDLE);
        $layout = $this->createMock(LayoutInterface::class);
        $layout->method('getUpdate')->willReturn($update);
        return $layout;
    }

    public function testHandleName(): void
    {
        $this->assertSame('panth_checkout_success_enabled', AddEnabledLayoutHandle::HANDLE);
    }

    public function testAddsHandleOnSuccessPageWhenEnabled(): void
    {
        (new AddEnabledLayoutHandle($this->helper(true)))
            ->execute($this->observer('checkout_onepage_success', $this->layout(1)));
    }

    public function testSkipsWhenDisabled(): void
    {
        (new AddEnabledLayoutHandle($this->helper(false)))
            ->execute($this->observer('checkout_onepage_success', $this->layout(0)));
    }

    public function testSkipsOtherPagesWithoutReadingConfig(): void
    {
        $helper = $this->createMock(Data::class);
        $helper->expects($this->never())->method('isEnabled');
        foreach (['cms_index_index', 'checkout_cart_index', 'multishipping_checkout_success', null] as $action) {
            (new AddEnabledLayoutHandle($helper))->execute($this->observer($action, $this->layout(0)));
        }
    }

    public function testIgnoresMissingLayout(): void
    {
        $helper = $this->createMock(Data::class);
        $helper->expects($this->never())->method('isEnabled');
        (new AddEnabledLayoutHandle($helper))->execute($this->observer('checkout_onepage_success', null));
    }
}
