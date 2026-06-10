<?php

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

declare(strict_types=1);

namespace Vendic\RoheconApi\Controller\Version;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Vendic\RoheconApi\Model\Version;

class Index implements HttpGetActionInterface
{
    public function __construct(
        private readonly JsonFactory $resultJsonFactory,
        private readonly Version $version
    ) {
    }

    public function execute(): ResultInterface
    {
        return $this->resultJsonFactory->create()
            ->setData($this->version->getVersionData());
    }
}
