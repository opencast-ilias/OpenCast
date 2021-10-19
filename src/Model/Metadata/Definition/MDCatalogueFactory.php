<?php

namespace srag\Plugins\Opencast\Model\Metadata\Definition;

class MDCatalogueFactory
{
    public static function event(): MDCatalogue
    {
        static $catalogue;
        if (!$catalogue) {
            $catalogue = new MDCatalogue([
                new MDFieldDefinition('title', MDDataType::text(), false, true),
                new MDFieldDefinition('subjects', MDDataType::text_array(), false, false),
                new MDFieldDefinition('description', MDDataType::text_long(), false, false),
                new MDFieldDefinition('language', MDDataType::text(), false, false),
                new MDFieldDefinition('rightsHolder', MDDataType::text(), false, false),
                new MDFieldDefinition('license', MDDataType::text(), false, false),
                new MDFieldDefinition('isPartOf', MDDataType::text(), false, false),
                new MDFieldDefinition('creator', MDDataType::text_array(), false, false),
                new MDFieldDefinition('startDate', MDDataType::date(), false, false),
                new MDFieldDefinition('duration', MDDataType::text(), false, false),
                new MDFieldDefinition('location', MDDataType::text(), false, false),
                new MDFieldDefinition('source', MDDataType::text(), false, false),
                new MDFieldDefinition('created', MDDataType::date(), true, false),
                new MDFieldDefinition('publisher', MDDataType::text(), true, false),
                new MDFieldDefinition('identifier', MDDataType::text(), true, false),
            ]);
        }
        return $catalogue;
    }

    public static function series(): MDCatalogue
    {
        static $catalogue;
        if (!$catalogue) {
            return new MDCatalogue([
                new MDFieldDefinition('title', MDDataType::text(), false, true),
                new MDFieldDefinition('subjects', MDDataType::text_array(), false, false),
                new MDFieldDefinition('description', MDDataType::text_long(), false, false),
                new MDFieldDefinition('language', MDDataType::text(), false, false),
                new MDFieldDefinition('rightsHolder', MDDataType::text(), false, false),
                new MDFieldDefinition('license', MDDataType::text(), false, false),
                new MDFieldDefinition('created_by', MDDataType::text(), false, false),
                new MDFieldDefinition('creator', MDDataType::text_array(), false, false),
                new MDFieldDefinition('contributor', MDDataType::text_array(), false, false),
                new MDFieldDefinition('publisher', MDDataType::text(), true, false),
                new MDFieldDefinition('identifier', MDDataType::text(), true, false),
            ]);
        }
        return $catalogue;
    }
}