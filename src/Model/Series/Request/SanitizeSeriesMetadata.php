<?php

namespace srag\Plugins\Opencast\Model\Series\Request;

trait SanitizeSeriesMetadata
{
    public function saniziteMetadataFields(array $metadata_fields)
    {
        $metadata = [];
        // array_map is not suitable here, since it return an array with indices which fail on the API
        foreach ($metadata_fields as $metadata_field) {
            if ($metadata_field->getValue() === null) {
                // if there are no values, the API expects an empty string or an empty array
                switch (true) {
                    case ($metadata_field->getType()->isValidValue('')):
                        $metadata_field->setValue('');
                        break;
                    case ($metadata_field->getType()->isValidValue([])):
                        $metadata_field->setValue([]);
                        break;
                }
            }
            $metadata[] = $metadata_field->jsonSerialize();
        }

        return [
            'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR)
        ];
    }
}
