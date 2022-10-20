<?php

namespace srag\Plugins\Opencast\Model\Metadata\Definition;

use xoctException;

class MDCatalogue
{
    /**
     * @var MDFieldDefinition[]
     */
    private $field_definitions;

    /**
     * @param MDFieldDefinition[] $field_definitions
     */
    public function __construct(array $field_definitions)
    {
        $this->field_definitions = $field_definitions;
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
        $field = array_filter($this->field_definitions, function (MDFieldDefinition $field) use ($id) {
            return $field->getId() === $id;
        });
        if (count($field) === 0) {
            throw new xoctException(
                xoctException::INTERNAL_ERROR,
                'could not find metadata field with id ' . $id
            );
        }
        return array_values($field)[0];
    }
}
