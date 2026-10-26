<?php

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

declare(strict_types=1);

namespace Vendic\RohecomApi\Model;

use Magento\Catalog\Api\Data\ProductInterface as CatalogProductInterface;
use Magento\Catalog\Api\ProductAttributeMediaGalleryManagementInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Vendic\RohecomApi\Api\ProductInterface;

class Product implements ProductInterface
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductResource $productResource,
        private readonly ProductAttributeMediaGalleryManagementInterface $mediaGalleryManagement
    ) {
    }

    /**
     * @inheritDoc
     *
     * @throws NoSuchEntityException
     */
    public function getProductSkuById(int $pid): array
    {
        if (!$pid) {
            return $this->prepareData([
                'status_code' => 203,
                'status_message' => 'No product id provided',
            ]);
        }

        $rows = $this->productResource->getProductsSku([$pid]);
        if (!$rows) {
            throw new NoSuchEntityException(__('Product does not exist'));
        }

        return $this->prepareData(['sku' => $rows[0]['sku']]);
    }

    /**
     * @inheritDoc
     *
     * @throws NoSuchEntityException
     */
    public function getWebsiteIdsBySku(string $sku): array
    {
        /** @var ProductModel $product */
        $product = $this->productRepository->get($sku);

        return $this->prepareData(['websites' => $product->getWebsiteIds()]);
    }

    /**
     * @inheritDoc
     *
     * @throws NoSuchEntityException
     * @throws StateException
     */
    public function removeProductImage(string $sku = '', string $image = ''): array
    {
        if (!$sku) {
            return $this->prepareData([
                'status_code' => 203,
                'status_message' => 'No sku provided',
            ]);
        }

        $product = $this->productRepository->get($sku);
        $entryIds = $this->resolveEntryIds($product, $image);

        if ($image && $entryIds === []) {
            return $this->prepareData([
                'status_code' => 404,
                'status_message' => 'Image not found on product',
            ]);
        }

        foreach ($entryIds as $entryId) {
            $this->mediaGalleryManagement->remove($sku, $entryId);
        }

        return $this->prepareData([
            'status_code' => 1,
            'status_message' => 'Product image removed',
            'sku' => $sku,
            'removed' => count($entryIds),
        ]);
    }

    private function resolveEntryIds(CatalogProductInterface $product, string $image): array
    {
        $entryIds = [];

        foreach ($product->getMediaGalleryEntries() ?? [] as $entry) {
            if ($image === '' || $entry->getFile() === $image) {
                $entryIds[] = (int)$entry->getId();
            }
        }

        return $entryIds;
    }

    private function prepareData(array $payload): array
    {
        return ['data_item' => $payload];
    }
}
