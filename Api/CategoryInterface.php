<?php

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

declare(strict_types=1);

namespace Vendic\RoheconApi\Api;

/**
 * @api
 */
interface CategoryInterface
{
    /**
     * @param int $cid
     *
     * @return array
     */
    public function categoryInfoImageUrl(int $cid): array;

    /**
     * @return array
     */
    public function getRootCategoryIds(): array;

    /**
     * @param int $categoryId
     * @param string $mime
     * @param string $data
     *
     * @return array
     */
    public function categoryUpdateImage(int $categoryId, string $mime, string $data): array;

    /**
     * @param int $cid
     *
     * @return array
     */
    public function categoryRemoveImage(int $cid): array;
}
