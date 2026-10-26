<?php

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

declare(strict_types=1);

namespace Vendic\RohecomApi\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Store\Model\ScopeInterface;
use Vendic\RohecomApi\Api\VersionInterface;
use Vendic\RohecomApi\Service\ModuleVersion;

class Version implements VersionInterface
{
    private const string XML_PATH_DEMO_NOTICE = 'design/head/demonotice';

    public function __construct(
        private readonly ProductMetadataInterface $productMetadata,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly ModuleVersion $moduleVersion
    ) {
    }

    /**
     * @inheritDoc
     */
    public function version(): array
    {
        return [
            'data_item' => $this->getVersionData()
        ];
    }

    public function getVersionData(): array
    {
        return [
            'status_code' => 1,
            'status_message' => 'Version fetched successfully',
            'version' => $this->moduleVersion->get(),
            'magento_version' => $this->productMetadata->getVersion(),
            'demo' => (int)$this->scopeConfig->getValue(self::XML_PATH_DEMO_NOTICE, ScopeInterface::SCOPE_STORE),
        ];
    }
}
