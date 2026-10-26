<?php

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

declare(strict_types=1);

namespace Vendic\RohecomApi\Api;

/**
 * @api
 */
interface WelcomeInterface
{
    /**
     * @return array
     */
    public function welcome(): array;
}
