<?php

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

declare(strict_types=1);

namespace Vendic\RoheconApi\Model;

use Vendic\RoheconApi\Api\WelcomeInterface;

class Welcome implements WelcomeInterface
{
    /**
     * @inheritDoc
     */
    public function welcome(): array
    {
        return [
            'data_item' => [
                'status_code' => 1,
                'status_message' => (string)__('Welkom, dit is de Rohecom Magento 2 API'),
            ],
        ];
    }
}
