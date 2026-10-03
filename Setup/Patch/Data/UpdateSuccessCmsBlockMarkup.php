<?php
declare(strict_types=1);

namespace Panth\CheckoutSuccess\Setup\Patch\Data;

use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class UpdateSuccessCmsBlockMarkup implements DataPatchInterface
{
    public const IDENTIFIER = 'panth_checkout_success_bottom';

    private BlockRepositoryInterface $blockRepository;

    public function __construct(BlockRepositoryInterface $blockRepository)
    {
        $this->blockRepository = $blockRepository;
    }

    public function apply(): self
    {
        try {
            $block = $this->blockRepository->getById(self::IDENTIFIER);
        } catch (\Exception $e) {
            return $this;
        }

        $content = (string) $block->getContent();
        $updated = self::upgradeMarkup($content);
        if ($updated !== $content) {
            $block->setContent($updated);
            $this->blockRepository->save($block);
        }

        return $this;
    }

    public static function upgradeMarkup(string $content): string
    {
        if (strpos($content, 'panth-info-item') === false) {
            return $content;
        }

        return str_replace(
            ['<svg width="28"', '<h4>', '</h4>', '.panth-info-item h4 {'],
            ['<svg aria-hidden="true" focusable="false" width="28"', '<h3>', '</h3>', '.panth-info-item h3 {'],
            $content
        );
    }

    public static function getDependencies(): array
    {
        return [CreateSuccessCmsBlock::class];
    }

    public function getAliases(): array
    {
        return [];
    }
}
