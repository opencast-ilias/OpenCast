<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Metadata\Definition;

use xoctException;

class MDCatalogue
{
    /**
     * @param MDFieldDefinition[] $field_definitions
     */
    public function __construct(private readonly array $field_definitions)
    {
    }

    /**
     * @return MDFieldDefinition[]
     */
    public function getFieldDefinitions(): array
    {
        return $this->field_definitions;
    }

    /**
     * @throws xoctException
     */
    public function getFieldById(string $id): MDFieldDefinition
    {
        $field = array_filter($this->field_definitions, fn(MDFieldDefinition $field): bool => $field->getId() === $id);
        if ($field === []) {
            throw new xoctException(
                xoctException::INTERNAL_ERROR,
                'could not find metadata field with id ' . $id
            );
        }
        return array_values($field)[0];
    }
}
