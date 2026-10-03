<?php
declare(strict_types=1);

namespace Panth\CheckoutSuccess\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class SuccessLayout implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'single-column', 'label' => __('Single Column (Centered)')],
            ['value' => 'two-column', 'label' => __('Two Column (Details + Summary)')],
        ];
    }
}
