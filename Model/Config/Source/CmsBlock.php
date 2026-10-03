<?php
declare(strict_types=1);

namespace Panth\CheckoutSuccess\Model\Config\Source;

use Magento\Cms\Model\Config\Source\Block as CmsBlockSource;
use Magento\Framework\Data\OptionSourceInterface;

class CmsBlock implements OptionSourceInterface
{
    private CmsBlockSource $blockSource;

    public function __construct(CmsBlockSource $blockSource)
    {
        $this->blockSource = $blockSource;
    }

    public function toOptionArray(): array
    {
        $options = [['value' => '', 'label' => __('-- None --')]];
        foreach ((array) $this->blockSource->toOptionArray() as $option) {
            if (is_array($option) && isset($option['value']) && (string) $option['value'] !== '') {
                $options[] = $option;
            }
        }

        return $options;
    }
}
