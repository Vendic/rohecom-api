<?php

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

declare(strict_types=1);

namespace Vendic\RohecomApi\Observer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ConfigurableProductPriceCleanup implements ObserverInterface
{
    public function execute(Observer $observer): void
    {
        /** @var Product|null $product */
        $product = $observer->getEvent()->getData('product');
        if (!$product instanceof Product) {
            return;
        }

        if ($product->getTypeId() === ConfigurableType::TYPE_CODE) {
            $product->unsetData(ProductInterface::PRICE);
            $product->unsetData('special_price');
        }
    }
}
