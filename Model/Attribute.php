<?php

/**
 * @copyright   Copyright (c) Vendic B.V https://vendic.nl/
 */

declare(strict_types=1);

namespace Vendic\RoheconApi\Model;

use InvalidArgumentException;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Eav\Api\AttributeOptionUpdateInterface;
use Magento\Eav\Api\Data\AttributeOptionInterfaceFactory;
use Magento\Eav\Api\Data\AttributeOptionLabelInterface;
use Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Vendic\RoheconApi\Api\AttributeInterface;

class Attribute implements AttributeInterface
{
    public function __construct(
        private readonly ProductAttributeRepositoryInterface $attributeRepository,
        private readonly EavConfig $eavConfig,
        private readonly AttributeOptionUpdateInterface $attributeOptionUpdate,
        private readonly AttributeOptionInterfaceFactory $attributeOptionFactory,
        private readonly AttributeOptionLabelInterfaceFactory $attributeOptionLabelFactory,
        private readonly Json $jsonSerializer
    ) {
    }

    /**
     * @inheritDoc
     */
    public function attributeOptionsList(string $aid): array
    {
        try {
            $options = $this->attributeRepository->get($aid)->getOptions() ?? [];
        } catch (NoSuchEntityException) {
            return $this->prepareData([
                'status_code' => 404,
                'status_message' => (string)__('Attribute does not exist'),
            ]);
        }

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
     */
    public function updateAttributeLabel(int $aid, int $optionId, string $jsonValues, int $position): array
    {
        try {
            $labels = $this->decodeStoreLabels($jsonValues);
            $attribute = $this->eavConfig->getAttribute(ProductAttributeInterface::ENTITY_TYPE_CODE, $aid);

            $option = $this->attributeOptionFactory->create();
            $option->setSortOrder($position);
            $option->setLabel($this->resolveDefaultLabel($attribute, $labels, $optionId));
            $option->setStoreLabels($this->buildStoreLabels($labels));

            $this->attributeOptionUpdate->update(
                ProductAttributeInterface::ENTITY_TYPE_CODE,
                (string)$attribute->getAttributeCode(),
                $optionId,
                $option
            );
        } catch (NoSuchEntityException) {
            return $this->prepareData([
                'status_code' => 404,
                'status_message' => (string)__('Attribute does not exist'),
            ]);
        } catch (LocalizedException $e) {
            return $this->prepareData([
                'status_code' => 400,
                'status_message' => $e->getMessage(),
            ]);
        }

        return $this->prepareData([
            'status_code' => 1,
            'status_message' => 'succesfully updated attribute option',
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getAttributeFrontendLabels(int $attributeId): array
    {
        try {
            $attributeCode = (string)$this->eavConfig
                ->getAttribute(ProductAttributeInterface::ENTITY_TYPE_CODE, $attributeId)
                ->getAttributeCode();

            $frontendLabels = $this->attributeRepository->get($attributeCode)->getFrontendLabels();
        } catch (NoSuchEntityException | LocalizedException) {
            return $this->prepareData([
                'status_code' => 404,
                'status_message' => (string)__('Attribute does not exist'),
            ]);
        }

        if ($frontendLabels === []) {
            return $this->prepareData([
                'status_code' => 404,
                'status_message' => (string)__('Attribute does not exist'),
            ]);
        }

        $result = [];
        foreach ($frontendLabels as $frontendLabel) {
            $result[] = [
                'store_id' => $frontendLabel->getStoreId(),
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

    /**
     * @throws LocalizedException
     */
    private function resolveDefaultLabel(AbstractAttribute $attribute, array $labels, int $optionId): string
    {
        if (isset($labels[0])) {
            return (string)$labels[0];
        }

        $currentLabel = $attribute->getSource()->getOptionText($optionId);

        return is_string($currentLabel) ? $currentLabel : '';
    }

    /**
     * @return AttributeOptionLabelInterface[]
     */
    private function buildStoreLabels(array $labels): array
    {
        $storeLabels = [];

        foreach ($labels as $storeId => $label) {
            if ((int)$storeId === 0) {
                continue;
            }

            $storeLabel = $this->attributeOptionLabelFactory->create();
            $storeLabel->setStoreId((int)$storeId);
            $storeLabel->setLabel((string)$label);
            $storeLabels[] = $storeLabel;
        }

        return $storeLabels;
    }

    private function prepareData(array $payload): array
    {
        return ['data_item' => $payload];
    }
}
