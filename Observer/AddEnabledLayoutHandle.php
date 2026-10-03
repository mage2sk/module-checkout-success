<?php
declare(strict_types=1);

namespace Panth\CheckoutSuccess\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\LayoutInterface;
use Panth\CheckoutSuccess\Helper\Data;

class AddEnabledLayoutHandle implements ObserverInterface
{
    public const HANDLE = 'panth_checkout_success_enabled';

    private Data $helper;

    public function __construct(Data $helper)
    {
        $this->helper = $helper;
    }

    public function execute(Observer $observer): void
    {
        if ($observer->getEvent()->getData('full_action_name') !== 'checkout_onepage_success') {
            return;
        }

        $layout = $observer->getEvent()->getData('layout');
        if (!$layout instanceof LayoutInterface || !$this->helper->isEnabled()) {
            return;
        }

        $layout->getUpdate()->addHandle(self::HANDLE);
    }
}
