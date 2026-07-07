<?php
declare(strict_types=1);

namespace Panth\CheckoutSuccess\Plugin\Order;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Sales\Block\Order\Items;

class ItemImagePlugin
{
    private ProductRepositoryInterface $productRepository;
    private ImageHelper $imageHelper;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        ImageHelper $imageHelper
    ) {
        $this->productRepository = $productRepository;
        $this->imageHelper = $imageHelper;
    }

    public function afterToHtml(Items $subject, string $result): string
    {
        $order = $subject->getOrder();
        if (!$order) {
            return $result;
        }

        $imageMap = [];
        foreach ($order->getAllVisibleItems() as $item) {
            try {
                $product = $this->productRepository->getById((int) $item->getProductId());
                $imageUrl = $this->imageHelper->init($product, 'product_thumbnail_image')
                    ->setImageFile($product->getThumbnail())
                    ->resize(96, 96)
                    ->getUrl();
                $imageMap[$item->getItemId()] = $imageUrl;
            } catch (\Exception $e) {
                $imageMap[$item->getItemId()] = '';
            }
        }

        $subject->setData('panth_item_images', $imageMap);

        return $result;
    }
}
