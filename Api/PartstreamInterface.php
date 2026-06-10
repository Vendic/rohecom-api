<?php

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

declare(strict_types=1);

namespace Vendic\RoheconApi\Api;

/**
 * @api
 */
interface PartstreamInterface
{
    /**
     * @return array
     */
    public function partstreamInfo(): array;
}
