<?php

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

declare(strict_types=1);

namespace Vendic\RoheconApi\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Vendic\RoheconApi\Api\FileInterface;

class File implements FileInterface
{
    private const string UPLOAD_DIR = 'upload';
    private const string MANIFEST = 'manifest.json';
    private const int MAX_FILE_BYTES = 20971520;

    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly Filesystem $filesystem,
        private readonly Json $json
    ) {
    }

    /**
     * @inheritDoc
     *
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function fileAdd(string $filename, string $label, string $productId, string $position, string $data)
    {
        $filename = $this->sanitizeFilename(quoted_printable_decode($filename));
        $label = quoted_printable_decode($label);
        $pid = (int)$productId;

        $this->productRepository->getById($pid);

        $binary = base64_decode($data, true);
        if ($binary === false) {
            throw new LocalizedException(__('Invalid base64 file data'));
        }
        if (strlen($binary) > self::MAX_FILE_BYTES) {
            throw new LocalizedException(__('File exceeds the maximum allowed size'));
        }

        $this->getMediaWriter()->writeFile(self::UPLOAD_DIR . '/' . $pid . '/' . $filename, $binary);

        $manifest = $this->getManifest($pid);
        $manifest[] = ['filename' => $filename, 'label' => $label, 'position' => $position];

        $this->saveManifest($pid, $manifest);

        return $this->prepareResponseData([
            'status_code' => 1,
            'status_message' => 'Successfully saved file',
            'new_file' => $filename,
            'productId' => $productId,
            'position' => $position,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function fileExists(string $filename)
    {
        $filename = $this->sanitizeFilename($filename);
        $relativePath = self::UPLOAD_DIR . '/' . $filename;

        $reader = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
        $exists = $reader->isExist($relativePath);

        return $this->prepareResponseData([
            'status_code' => 1,
            'status_message' => '',
            'path' => $reader->getAbsolutePath($relativePath),
            'file_exists' => $exists ? '1' : '0',
        ]);
    }

    /**
     * @inheritDoc
     */
    public function fileList(string $productId)
    {
        return $this->getManifest((int)$productId);
    }

    /**
     * @inheritDoc
     *
     * @throws FileSystemException
     */
    public function unlinkFileFromProduct(string $productId, string $filename)
    {
        $filename = $this->sanitizeFilename($filename);
        $pid = (int)$productId;

        $writer = $this->getMediaWriter();
        $relativePath = self::UPLOAD_DIR . '/' . $pid . '/' . $filename;
        if ($writer->isExist($relativePath)) {
            $writer->delete($relativePath);
        }

        $manifest = $this->removeByFilename($this->getManifest($pid), $filename);
        $this->saveManifest($pid, $manifest);

        return $this->prepareResponseData([
            'status_code' => 1,
            'status_message' => 'File removed successfully',
        ]);
    }

    /**
     * @inheritDoc
     *
     * @throws FileSystemException
     */
    public function updatePosition(string $productId, string $filename, string $position)
    {
        $filename = $this->sanitizeFilename($filename);
        $pid = (int)$productId;

        $manifest = $this->removeByFilename($this->getManifest($pid), $filename);
        $manifest[] = ['filename' => $filename, 'position' => $position];
        $this->saveManifest($pid, $manifest);

        return $this->prepareResponseData([
            'status_code' => 1,
            'status_message' => 'Successfully updated position',
            'filename' => $filename,
            'productId' => $productId,
            'position' => $position,
        ]);
    }

    private function sanitizeFilename(string $filename): string
    {
        return basename(str_replace('\\', '/', $filename));
    }

    /**
     * @throws FileSystemException
     */
    private function getMediaWriter(): WriteInterface
    {
        return $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
    }

    private function getManifest(int $productId): array
    {
        $reader = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
        $relativePath = self::UPLOAD_DIR . '/' . $productId . '/' . self::MANIFEST;

        if (!$reader->isExist($relativePath)) {
            return [];
        }

        $content = $reader->readFile($relativePath);
        if ($content === '') {
            return [];
        }

        $decoded = $this->json->unserialize($content);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @throws FileSystemException
     */
    private function saveManifest(int $productId, array $manifest): void
    {
        $writer = $this->getMediaWriter();
        $relativePath = self::UPLOAD_DIR . '/' . $productId . '/' . self::MANIFEST;
        $writer->writeFile($relativePath, (string)$this->json->serialize(array_values($manifest)));
    }

    private function removeByFilename(array $manifest, string $filename): array
    {
        return array_values(
            array_filter(
                $manifest,
                static fn($entry) => !isset($entry['filename']) || $entry['filename'] !== $filename
            )
        );
    }

    private function prepareResponseData(array $payload): array
    {
        return ['data_item' => $payload];
    }
}
