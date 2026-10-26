<?php

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

declare(strict_types=1);

namespace Vendic\RohecomApi\Model;

use InvalidArgumentException;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Vendic\RohecomApi\Api\AttributeInterface;

class Attribute implements AttributeInterface
{
    private const string OPTION_VALUE_TABLE = 'eav_attribute_option_value';
    private const string OPTION_TABLE = 'eav_attribute_option';

    public function __construct(
        private readonly ProductAttributeRepositoryInterface $attributeRepository,
        private readonly EavConfig $eavConfig,
        private readonly ResourceConnection $resourceConnection,
        private readonly Json $jsonSerializer
    ) {
    }

    /**
     * @inheritDoc
     *
     * @throws NoSuchEntityException
     */
    public function attributeOptionsList(string $aid): array
    {
        $options = $this->attributeRepository->get($aid)->getOptions() ?? [];

        $data = [];
        foreach ($options as $option) {
            if ($option->getValue() !== '') {
                $data[] = [
                    'option_id' => $option->getValue(),
                    'label' => $option->getLabel(),
                    'position' => $option->getSortOrder(),
                    'store_id' => 0,
                ];
            }
        }

        return $data;
    }

    /**
     * @inheritDoc
     *
     * @throws LocalizedException
     */
    public function updateAttributeLabel(int $aid, int $optionId, string $jsonValues, int $position): array
    {
        $connection = $this->resourceConnection->getConnection();

        foreach ($this->decodeStoreLabels($jsonValues) as $storeId => $label) {
            $connection->update(
                $this->resourceConnection->getTableName(self::OPTION_VALUE_TABLE),
                ['value' => (string)$label],
                [
                    'store_id = ?' => (int)$storeId,
                    'option_id = ?' => $optionId,
                ]
            );
        }

        $connection->update(
            $this->resourceConnection->getTableName(self::OPTION_TABLE),
            ['sort_order' => $position],
            [
                'attribute_id = ?' => $aid,
                'option_id = ?' => $optionId,
            ]
        );

        return [
            'data_item' => [
                'status_code' => 1,
                'status_message' => 'succesfully updated attribute option',
            ],
        ];
    }

    /**
     * @inheritDoc
     *
     * @throws NoSuchEntityException
     */
    public function getAttributeFrontendLabels(int $attributeId): array
    {
        $attributeCode = (string)$this->eavConfig
            ->getAttribute(ProductAttributeInterface::ENTITY_TYPE_CODE, $attributeId)
            ->getAttributeCode();

        $frontendLabels = $this->attributeRepository->get($attributeCode)->getFrontendLabels();

        if ($frontendLabels === []) {
            throw new NoSuchEntityException(__('Attribute does not exist'));
        }

        $result = [];
        foreach ($frontendLabels as $frontendLabel) {
            $result[] = [
                'store_id' => (string)$frontendLabel->getStoreId(),
                'value' => $frontendLabel->getLabel(),
            ];
        }

        return $result;
    }

    /**
     * @throws LocalizedException
     */
    private function decodeStoreLabels(string $jsonValues): array
    {
        try {
            $values = $this->jsonSerializer->unserialize($jsonValues);
        } catch (InvalidArgumentException) {
            throw new LocalizedException(__('Invalid jsonValues payload'));
        }

        if (!is_array($values)) {
            throw new LocalizedException(__('Invalid jsonValues payload'));
        }

        return $values;
    }
}
