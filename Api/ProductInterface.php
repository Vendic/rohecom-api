<?php

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

declare(strict_types=1);

namespace Vendic\RohecomApi\Api;

/**
 * @api
 */
interface ProductInterface
{
    /**
     * @param int $pid
     *
     * @return array
     */
    public function getProductSkuById(int $pid): array;

    /**
     * @param string $sku
     *
     * @return array
     */
    public function getWebsiteIdsBySku(string $sku): array;

    /**
     * @param string $sku
     * @param string $image
     *
     * @return array
     */
    public function removeProductImage(string $sku = '', string $image = ''): array;
}
