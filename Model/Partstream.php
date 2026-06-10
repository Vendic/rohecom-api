<?php

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

declare(strict_types=1);

namespace Vendic\RoheconApi\Model;

use Magento\Catalog\Api\CategoryManagementInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category as CategoryModel;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Eav\Api\AttributeSetManagementInterface;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Eav\Api\Data\AttributeSetInterfaceFactory;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Vendic\RoheconApi\Api\PartstreamInterface;

class Partstream implements PartstreamInterface
{
    private const string PARTSTREAM_NAME = 'Partstream';

    public function __construct(
        private readonly CategoryManagementInterface $categoryManagement,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly CategoryFactory $categoryFactory,
        private readonly CategoryCollectionFactory $categoryCollectionFactory,
        private readonly AttributeSetRepositoryInterface $attributeSetRepository,
        private readonly AttributeSetManagementInterface $attributeSetManagement,
        private readonly AttributeSetInterfaceFactory $attributeSetFactory,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly EavConfig $eavConfig
    ) {
    }

    /**
     * @inheritDoc
     */
    public function partstreamInfo(): array
    {
        try {
            return $this->prepareData([
                'categoryId' => $this->getOrCreateCategory($this->getFirstRootCategoryId()),
                'attributeSetId' => $this->getOrCreateAttributeSet(),
            ]);
        } catch (LocalizedException $e) {
            return $this->prepareData([
                'status_code' => 400,
                'status_message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @throws NoSuchEntityException
     */
    private function getFirstRootCategoryId(): int
    {
        $tree = $this->categoryManagement->getTree(1, 1);

        foreach ($tree->getChildrenData() as $item) {
            return (int)$item->getId();
        }

        return 2;
    }

    /**
     * @throws CouldNotSaveException
     * @throws LocalizedException
     */
    private function getOrCreateCategory(int $parentId): int
    {
        /** @var CategoryModel $existingCategory */
        $existingCategory = $this->categoryCollectionFactory->create()
            ->addAttributeToFilter('name', self::PARTSTREAM_NAME)->setPageSize(1)
            ->getFirstItem();

        if ($existingCategory->getId()) {
            return (int)$existingCategory->getId();
        }

        $category = $this->categoryFactory->create()
            ->setName(self::PARTSTREAM_NAME)
            ->setParentId($parentId)
            ->setIsActive(true)
            ->setData('include_in_menu', false)
            ->setData('display_mode', 'PRODUCTS')
            ->setData('is_anchor', 1);

        return (int)$this->categoryRepository->save($category)
            ->getId();
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws InputException
     */
    private function getOrCreateAttributeSet(): int
    {
        $entityType = $this->eavConfig->getEntityType(Product::ENTITY);
        $entityTypeId = (int)$entityType->getId();

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('attribute_set_name', self::PARTSTREAM_NAME)
            ->addFilter('entity_type_id', $entityTypeId)
            ->create();

        $items = $this->attributeSetRepository->getList($searchCriteria)->getItems();

        if ($items) {
            $existing = reset($items);

            return (int)$existing->getAttributeSetId();
        }

        $attributeSet = $this->attributeSetFactory->create();
        $attributeSet->setEntityTypeId($entityTypeId);
        $attributeSet->setAttributeSetName(self::PARTSTREAM_NAME);
        $attributeSet->setSortOrder(200);

        $created = $this->attributeSetManagement->create(
            Product::ENTITY,
            $attributeSet,
            (int)$entityType->getDefaultAttributeSetId()
        );

        return (int)$created->getAttributeSetId();
    }

    private function prepareData(array $payload): array
    {
        return ['data_item' => $payload];
    }
}
