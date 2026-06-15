<?php

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

declare(strict_types=1);

namespace Vendic\RoheconApi\Api;

/**
 * @api
 */
interface AttributeInterface
{
    /**
     * @param string $aid
     *
     * @return array
     */
    public function attributeOptionsList(string $aid): array;

    /**
     * @param int $aid
     * @param int $optionId
     * @param string $jsonValues
     * @param int $position
     *
     * @return array
     */
    public function updateAttributeLabel(int $aid, int $optionId, string $jsonValues, int $position): array;

    /**
     * @param int $attributeId
     *
     * @return array
     */
    public function getAttributeFrontendLabels(int $attributeId): array;
}
