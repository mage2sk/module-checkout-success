<?php
declare(strict_types=1);

namespace Panth\CheckoutSuccess\Setup\Patch\Data;

use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Api\Data\BlockInterfaceFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class CreateSuccessCmsBlock implements DataPatchInterface
{
    private BlockRepositoryInterface $blockRepository;
    private BlockInterfaceFactory $blockFactory;

    public function __construct(
        BlockRepositoryInterface $blockRepository,
        BlockInterfaceFactory $blockFactory
    ) {
        $this->blockRepository = $blockRepository;
        $this->blockFactory = $blockFactory;
    }

    public function apply(): self
    {
        $identifier = 'panth_checkout_success_bottom';

        try {
            $this->blockRepository->getById($identifier);

            return $this;
        } catch (\Exception $e) {
        }

        $content = <<<'HTML'
<div class="panth-success-info-block">
    <div class="panth-info-grid">
        <div class="panth-info-item">
            <div class="panth-info-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#0D9488" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            </div>
            <h4>Free Returns</h4>
            <p>Changed your mind? Return any item within 30 days - no questions asked.</p>
        </div>
        <div class="panth-info-item">
            <div class="panth-info-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#0D9488" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
            </div>
            <h4>Fast Shipping</h4>
            <p>Most orders ship within 1-2 business days. Track your package anytime.</p>
        </div>
        <div class="panth-info-item">
            <div class="panth-info-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#0D9488" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.56 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.5a16 16 0 0 0 6 6l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            </div>
            <h4>24/7 Support</h4>
            <p>Have a question? Our team is ready to help around the clock.</p>
        </div>
        <div class="panth-info-item">
            <div class="panth-info-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#0D9488" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            </div>
            <h4>Secure Payments</h4>
            <p>Your payment info is encrypted and never stored on our servers.</p>
        </div>
    </div>
</div>
<style>
.panth-success-info-block {
    margin-top: 40px;
    padding: 32px 24px;
    background: #f8fafc;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
}
.panth-info-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
}
@media (max-width: 900px) {
    .panth-info-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 540px) {
    .panth-info-grid { grid-template-columns: 1fr; }
    .panth-success-info-block { padding: 24px 16px; }
}
.panth-info-item {
    text-align: center;
    padding: 8px;
}
.panth-info-icon {
    width: 56px;
    height: 56px;
    background: #f0fdfa;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
}
.panth-info-item h4 {
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 6px;
}
.panth-info-item p {
    font-size: 13px;
    color: #64748b;
    line-height: 1.6;
    margin: 0;
}
</style>
HTML;

        $block = $this->blockFactory->create();
        $block->setIdentifier($identifier);
        $block->setTitle('Checkout Success - Trust Signals');
        $block->setContent($content);
        $block->setIsActive(true);
        $block->setStoreId([0]);
        $this->blockRepository->save($block);

        return $this;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
