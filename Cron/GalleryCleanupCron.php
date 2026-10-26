<?php

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

declare(strict_types=1);

namespace Vendic\RohecomApi\Cron;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Filesystem;
use Psr\Log\LoggerInterface;

class GalleryCleanupCron
{
    private const string XML_PATH_ENABLED = 'vendic_rohecom_api/cron/gallery_cleanup_cron_enabled';
    private const string GALLERY_TABLE = 'catalog_product_entity_media_gallery';
    private const string MEDIA_PATH_PREFIX = 'catalog/product/';

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ResourceConnection $resourceConnection,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly Filesystem $filesystem
    ) {
    }

    public function execute(): void
    {
        if (!$this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED)) {
            return;
        }

        $orphanedValueIds = $this->findOrphanedValueIds();
        if ($orphanedValueIds === []) {
            return;
        }

        $this->resourceConnection->getConnection()->delete(
            $this->resourceConnection->getTableName(self::GALLERY_TABLE),
            ['value_id IN (?)' => $orphanedValueIds]
        );

        $this->logger->info(
            sprintf('Removed %d orphaned product gallery entries.', count($orphanedValueIds)),
            ['value_ids' => $orphanedValueIds]
        );
    }

    private function findOrphanedValueIds(): array
    {
        $connection = $this->resourceConnection->getConnection();
        $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);

        $files = $connection->fetchPairs(
            $connection->select()
                ->from($this->resourceConnection->getTableName(self::GALLERY_TABLE), ['value_id', 'value'])
                ->where('media_type = ?', 'image')
        );

        $orphaned = [];
        foreach ($files as $valueId => $file) {
            if (!$mediaDirectory->isExist(self::MEDIA_PATH_PREFIX . ltrim((string)$file, '/'))) {
                $orphaned[] = (int)$valueId;
            }
        }

        return $orphaned;
    }
}
