<?php

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

declare(strict_types=1);

namespace Vendic\RohecomApi\Service;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory as OptionCollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Swatches\Model\ResourceModel\Swatch as SwatchResource;
use Magento\Swatches\Model\ResourceModel\Swatch\CollectionFactory as SwatchCollectionFactory;
use Magento\Swatches\Model\Swatch;
use Magento\Swatches\Model\SwatchFactory;
use Throwable;

class SwatchTextUpdater
{
    public function __construct(
        private readonly ProductAttributeRepositoryInterface $attributeRepository,
        private readonly StoreManagerInterface $storeManager,
        private readonly OptionCollectionFactory $optionCollectionFactory,
        private readonly SwatchCollectionFactory $swatchCollectionFactory,
        private readonly SwatchFactory $swatchFactory,
        private readonly SwatchResource $swatchResource,
        private readonly ResourceConnection $resourceConnection
    ) {
    }

    /**
     * @throws NoSuchEntityException
     * @throws Throwable
     */
    public function execute(string $attributeCode): void
    {
        $attributeId = (int)$this->attributeRepository->get($attributeCode)->getAttributeId();
        $connection = $this->resourceConnection->getConnection();

        $connection->beginTransaction();
        try {
            foreach ($this->storeManager->getStores(true) as $store) {
                $this->updateStore($attributeId, (int)$store->getId());
            }
            $connection->commit();
        } catch (Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    /**
     * @throws AlreadyExistsException
     */
    private function updateStore(int $attributeId, int $storeId): void
    {
        $options = $this->optionCollectionFactory->create()
            ->setAttributeFilter($attributeId)
            ->setStoreFilter($storeId);

        $labels = [];
        foreach ($options as $option) {
            $optionId = (int)$option->getId();
            if ($optionId > 0) {
                $labels[$optionId] = (string)$option->getData('value');
            }
        }

        if ($labels === []) {
            return;
        }

        $existing = $this->loadSwatches(array_keys($labels), $storeId);

        foreach ($labels as $optionId => $value) {
            $swatch = $existing[$optionId] ?? null;

            if (!$swatch instanceof Swatch) {
                $swatch = $this->newSwatch($optionId, $storeId);
            }

            $swatch->setData('value', $value);
            $this->swatchResource->save($swatch);
        }
    }

    private function loadSwatches(array $optionIds, int $storeId): array
    {
        $collection = $this->swatchCollectionFactory->create()
            ->addFieldToFilter('store_id', ['eq' => $storeId])
            ->addFieldToFilter('option_id', ['in' => $optionIds]);

        $map = [];
        foreach ($collection as $swatch) {
            $map[(int)$swatch->getData('option_id')] = $swatch;
        }

        return $map;
    }

    private function newSwatch(int $optionId, int $storeId): Swatch
    {
        return $this->swatchFactory->create()
            ->setData('option_id', $optionId)
            ->setData('store_id', $storeId)
            ->setData('type', Swatch::SWATCH_TYPE_TEXTUAL);
    }
}
