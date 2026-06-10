<?php

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

declare(strict_types=1);

namespace Vendic\RoheconApi\Model;

use Magento\Catalog\Api\CategoryManagementInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category as CategoryModel;
use Magento\Framework\Api\Data\ImageContentInterfaceFactory;
use Magento\Framework\Api\ImageContentValidatorInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Vendic\RoheconApi\Api\CategoryInterface;

class Category implements CategoryInterface
{
    private const array SUPPORTED_TYPES = [
        'image/jpg' => ['mime' => 'image/jpeg', 'extension' => 'jpg'],
        'image/jpeg' => ['mime' => 'image/jpeg', 'extension' => 'jpg'],
        'image/pjpeg' => ['mime' => 'image/jpeg', 'extension' => 'jpg'],
        'image/gif' => ['mime' => 'image/gif', 'extension' => 'gif'],
        'image/png' => ['mime' => 'image/png', 'extension' => 'png'],
    ];

    private const int MAX_IMAGE_BYTES = 5242880;

    private const string CATEGORY_IMAGE_DIR = 'catalog/category';

    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly CategoryManagementInterface $categoryManagement,
        private readonly Filesystem $filesystem,
        private readonly ImageContentValidatorInterface $imageContentValidator,
        private readonly ImageContentInterfaceFactory $imageContentFactory,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * @inheritDoc
     *
     * @throws LocalizedException
     */
    public function categoryInfoImageUrl(int $cid): array
    {
        try {
            /** @var CategoryModel $category */
            $category = $this->categoryRepository->get($cid);
        } catch (NoSuchEntityException) {
            throw new NoSuchEntityException(__('Category does not exist'));
        }

        return $this->prepareData([
            'status_code' => 1,
            'status_message' => 'Successfully fetched category info for id: ' . $cid,
            'image_url' => $category->getImageUrl(),
            'description' => $category->getData('description'),
        ]);
    }

    /**
     * @inheritDoc
     *
     * @throws LocalizedException
     */
    public function getRootCategoryIds(): array
    {
        $tree = $this->categoryManagement->getTree(1, 1);

        $roots = [];
        foreach ($tree->getChildrenData() as $item) {
            $roots[] = $item->getId();
        }

        return $this->prepareData([
            'status_code' => 1,
            'roots' => $roots,
        ]);
    }

    /**
     * @inheritDoc
     *
     * @throws LocalizedException
     */
    public function categoryUpdateImage(int $categoryId, string $mime, string $data): array
    {
        if (!isset(self::SUPPORTED_TYPES[$mime])) {
            throw new LocalizedException(__('Unsupported image mime type: %1', $mime));
        }
        if (strlen($data) > (int)ceil(self::MAX_IMAGE_BYTES / 3) * 4) {
            throw new LocalizedException(__('Image exceeds the maximum allowed size'));
        }

        try {
            /** @var CategoryModel $category */
            $category = $this->categoryRepository->get($categoryId);
        } catch (NoSuchEntityException) {
            throw new NoSuchEntityException(__('Category does not exist'));
        }

        $type = self::SUPPORTED_TYPES[$mime];
        $fileName = 'cat-' . $categoryId . '.' . $type['extension'];

        $imageContent = $this->imageContentFactory->create()
            ->setBase64EncodedData($data)
            ->setType($type['mime'])
            ->setName($fileName);

        $this->imageContentValidator->isValid($imageContent);

        $binary = base64_decode($data, true);
        if ($binary === false) {
            throw new LocalizedException(__('Invalid base64 image data'));
        }

        $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA)
            ->writeFile(self::CATEGORY_IMAGE_DIR . '/' . $fileName, $binary);

        $category->setData('image', $fileName);
        $category->setData('thumbnail', $fileName);
        $this->saveInDefaultScope($category);

        return $this->prepareData([
            'status_code' => 1,
            'status_message' => 'Successfully saved image for category',
            'file_location' => $fileName,
        ]);
    }

    /**
     * @inheritDoc
     *
     * @throws LocalizedException
     */
    public function categoryRemoveImage(int $cid): array
    {
        try {
            /** @var CategoryModel $category */
            $category = $this->categoryRepository->get($cid);
        } catch (NoSuchEntityException) {
            return $this->prepareData([
                'status_code' => 202,
                'status_message' => 'Category does not exist',
            ]);
        }

        $image = $category->getData('image');
        if (!$image) {
            return $this->prepareData([
                'status_code' => 404,
                'status_message' => 'File could not be found',
            ]);
        }

        $category->setData('image', '');
        $category->setData('thumbnail', '');
        $this->saveInDefaultScope($category);

        $mediaWriter = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $relativePath = self::CATEGORY_IMAGE_DIR . '/' . ltrim((string)$image, '/');
        $absolutePath = $mediaWriter->getAbsolutePath($relativePath);

        if ($mediaWriter->isExist($relativePath)) {
            $mediaWriter->delete($relativePath);
        }

        return $this->prepareData([
            'status_code' => 1,
            'status_message' => 'File successfully removed',
            'image_removed' => $absolutePath,
        ]);
    }

    /**
     * @throws LocalizedException
     */
    private function saveInDefaultScope(CategoryModel $category): void
    {
        $currentStoreId = $this->storeManager->getStore()->getId();
        $this->storeManager->setCurrentStore(Store::DEFAULT_STORE_ID);

        try {
            $this->categoryRepository->save($category);
        } finally {
            $this->storeManager->setCurrentStore($currentStoreId);
        }
    }

    private function prepareData(array $payload): array
    {
        return ['data_item' => $payload];
    }
}
