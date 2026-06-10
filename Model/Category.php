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
use Magento\Framework\Api\ImageProcessorInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Vendic\RoheconApi\Api\CategoryInterface;

class Category implements CategoryInterface
{
    /**
     * Incoming mime => the normalized mime getimagesizefromstring() reports, plus the file extension.
     */
    private const array SUPPORTED_TYPES = [
        'image/jpg' => [
            'mime' => 'image/jpeg',
            'extension' => 'jpg'
        ],
        'image/jpeg' => [
            'mime' => 'image/jpeg',
            'extension' => 'jpg'
        ],
        'image/pjpeg' => [
            'mime' => 'image/jpeg',
            'extension' => 'jpg'
        ],
        'image/gif' => [
            'mime' => 'image/gif',
            'extension' => 'gif'
        ],
        'image/png' => [
            'mime' => 'image/png',
            'extension' => 'png'
        ],
    ];

    private const int MAX_IMAGE_BYTES = 5242880;

    private const string CATEGORY_IMAGE_DIR = 'catalog/category';

    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly CategoryManagementInterface $categoryManagement,
        private readonly Filesystem $filesystem,
        private readonly ImageProcessorInterface $imageProcessor,
        private readonly ImageContentInterfaceFactory $imageContentFactory
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
            return $this->prepareResponseData([
                'status_code' => 202,
                'status_message' => 'Category does not exist',
            ]);
        }

        return $this->prepareResponseData([
            'status_code' => 1,
            'status_message' => 'succesfully fetched category info for id: ' . $cid,
            'image_url' => $category->getImageUrl(),
            'description' => $category->getData('description'),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getRootCategoryIds(): array
    {
        try {
            $tree = $this->categoryManagement->getTree(1, 1);
        } catch (LocalizedException $e) {
            return $this->prepareResponseData([
                'status_code' => 400,
                'status_message' => $e->getMessage(),
            ]);
        }

        $roots = [];
        foreach ($tree->getChildrenData() as $item) {
            $roots[] = $item->getId();
        }

        return $this->prepareResponseData([
            'status_code' => 1,
            'roots' => $roots,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function categoryUpdateImage(int $categoryId, string $mime, string $data): array
    {
        try {
            if (!isset(self::SUPPORTED_TYPES[$mime])) {
                throw new LocalizedException(__('Unsupported image mime type: %1', $mime));
            }
            if (strlen($data) > (int)ceil(self::MAX_IMAGE_BYTES / 3) * 4) {
                throw new LocalizedException(__('Image exceeds the maximum allowed size'));
            }

            /** @var CategoryModel $category */
            $category = $this->categoryRepository->get($categoryId);
            $type = self::SUPPORTED_TYPES[$mime];

            $imageContent = $this->imageContentFactory->create()
                ->setBase64EncodedData($data)
                ->setType($type['mime'])
                ->setName('cat-' . $categoryId . '.' . $type['extension']);

            $fileName = $this->imageProcessor->processImageContent(self::CATEGORY_IMAGE_DIR, $imageContent);
            if (!$fileName) {
                throw new LocalizedException(__('Could not save the category image'));
            }

            $category->setData('image', $fileName);
            $category->setData('thumbnail', $fileName);
            $this->categoryRepository->save($category);
        } catch (NoSuchEntityException) {
            return $this->prepareResponseData([
                'status_code' => 202,
                'status_message' => 'Category does not exist',
            ]);
        } catch (LocalizedException $e) {
            return $this->prepareResponseData([
                'status_code' => 400,
                'status_message' => $e->getMessage(),
            ]);
        }

        return $this->prepareResponseData([
            'status_code' => 1,
            'status_message' => 'succesfully saved image for category',
            'file_location' => $fileName,
        ]);
    }

    /**
     * @inheritDoc
     *
     * @throws FileSystemException
     */
    public function categoryRemoveImage(int $cid): array
    {
        try {
            /** @var CategoryModel $category */
            $category = $this->categoryRepository->get($cid);
        } catch (NoSuchEntityException) {
            return $this->prepareResponseData([
                'status_code' => 202,
                'status_message' => 'Category does not exist',
            ]);
        }

        $image = $category->getData('image');
        if (!$image) {
            return $this->prepareResponseData([
                'status_code' => 404,
                'status_message' => 'File could not be found',
            ]);
        }

        try {
            $category->setData('image', '');
            $category->setData('thumbnail', '');
            $this->categoryRepository->save($category);
        } catch (LocalizedException $e) {
            return $this->prepareResponseData([
                'status_code' => 400,
                'status_message' => $e->getMessage(),
            ]);
        }

        $mediaWriter = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);

        $relativePath = self::CATEGORY_IMAGE_DIR . '/' . ltrim((string)$image, '/');
        $absolutePath = $mediaWriter->getAbsolutePath($relativePath);

        if ($mediaWriter->isExist($relativePath)) {
            $mediaWriter->delete($relativePath);
        }

        return $this->prepareResponseData([
            'status_code' => 1,
            'status_message' => 'File succesfully removed',
            'image_removed' => $absolutePath,
        ]);
    }

    private function prepareResponseData(array $payload): array
    {
        return ['data_item' => $payload];
    }
}
