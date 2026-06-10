<?php

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

declare(strict_types=1);

namespace Vendic\RoheconApi\Model;

use Magento\Catalog\Api\Data\ProductInterface as CatalogProductInterface;
use Magento\Catalog\Api\ProductAttributeMediaGalleryManagementInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Vendic\RoheconApi\Api\ProductInterface;

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
     */
    public function getProductSkuById(int $pid): array
    {
        if (!$pid) {
            return $this->prepareData([
                'status_code' => 203,
                'status_message' => (string)__('No product id provided'),
            ]);
        }

        $rows = $this->productResource->getProductsSku([$pid]);

        if (!$rows) {
            return $this->prepareData([
                'status_code' => 404,
                'status_message' => (string)__('Product does not exist'),
            ]);
        }

        return $this->prepareData(['sku' => $rows[0]['sku']]);
    }

    /**
     * @inheritDoc
     */
    public function getWebsiteIdsBySku(string $sku): array
    {
        try {
            /** @var ProductModel $product */
            $product = $this->productRepository->get($sku);
        } catch (NoSuchEntityException) {
            return $this->prepareData([
                'status_code' => 404,
                'status_message' => (string)__('Product does not exist'),
            ]);
        }

        return $this->prepareData(['websites' => $product->getWebsiteIds()]);
    }

    /**
     * @inheritDoc
     */
    public function removeProductImage(string $sku = '', string $image = ''): array
    {
        if (!$sku) {
            return $this->prepareData([
                'status_code' => 203,
                'status_message' => (string)__('No sku provided'),
            ]);
        }

        try {
            $product = $this->productRepository->get($sku);
            $entryIds = $this->resolveEntryIds($product, $image);

            if ($image && $entryIds === []) {
                return $this->prepareData([
                    'status_code' => 404,
                    'status_message' => (string)__('Image not found on product'),
                ]);
            }

            foreach ($entryIds as $entryId) {
                $this->mediaGalleryManagement->remove($sku, $entryId);
            }
        } catch (NoSuchEntityException) {
            return $this->prepareData([
                'status_code' => 404,
                'status_message' => (string)__('Product does not exist'),
            ]);
        } catch (LocalizedException $e) {
            return $this->prepareData([
                'status_code' => 400,
                'status_message' => $e->getMessage(),
            ]);
        }

        return $this->prepareData([
            'status_code' => 1,
            'status_message' => (string)__('Product image removed'),
            'sku' => $sku,
            'image' => $image,
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
