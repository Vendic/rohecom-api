<?php

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

declare(strict_types=1);

namespace Vendic\RohecomApi\Api;

/**
 * @api
 */
interface FileInterface
{
    /**
     * Add a (base64) file to a product.
     *
     * @return mixed
     */
    public function fileAdd(string $filename, string $label, string $productId, string $position, string $data);

    /**
     * Check whether a file exists in the upload directory.
     *
     * @return mixed
     */
    public function fileExists(string $filename);

    /**
     * List the files linked to a product.
     *
     * @return mixed
     */
    public function fileList(string $productId);

    /**
     * Unlink a file from a product.
     *
     * @return mixed
     */
    public function unlinkFileFromProduct(string $productId, string $filename);

    /**
     * Change the order position of a file linked to a product.
     *
     * @return mixed
     */
    public function updatePosition(string $productId, string $filename, string $position);
}
