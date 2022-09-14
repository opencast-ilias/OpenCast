<?php

namespace srag\Plugins\Opencast\Model\Metadata\Definition;

class MDCatalogueFactory
{
    public function event(): MDCatalogue
    {
        static $catalogue;
        if (!$catalogue) {
            $catalogue = new MDCatalogue([
                new MDFieldDefinition(MDFieldDefinition::F_TITLE, MDDataType::text(), false, true),
                new MDFieldDefinition(MDFieldDefinition::F_SUBJECTS, MDDataType::text_array(), false, false),
                new MDFieldDefinition(MDFieldDefinition::F_DESCRIPTION, MDDataType::text_long(), false, false),
                new MDFieldDefinition(MDFieldDefinition::F_RIGHTS_HOLDER, MDDataType::text(), false, false),
                new MDFieldDefinition(MDFieldDefinition::F_IS_PART_OF, MDDataType::text(), true, false),
                new MDFieldDefinition(MDFieldDefinition::F_CREATOR, MDDataType::text_array(), false, false),
                new MDFieldDefinition(MDFieldDefinition::F_START_DATE, MDDataType::datetime(), false, false),
                new MDFieldDefinition(MDFieldDefinition::F_DURATION, MDDataType::time(), false, false),
                new MDFieldDefinition(MDFieldDefinition::F_LOCATION, MDDataType::text(), false, false),
                new MDFieldDefinition(MDFieldDefinition::F_SOURCE, MDDataType::text(), false, false),
                new MDFieldDefinition(MDFieldDefinition::F_CREATED, MDDataType::datetime(), true, false),
                new MDFieldDefinition(MDFieldDefinition::F_PUBLISHER, MDDataType::text(), false, false),
                new MDFieldDefinition(MDFieldDefinition::F_IDENTIFIER, MDDataType::text(), true, false),
                new MDFieldDefinition(MDFieldDefinition::F_CONTRIBUTOR, MDDataType::text_array(), false, false),
                // language and license are selection fields in opencast, which is not implemented yet
//                new MDFieldDefinition(MDFieldDefinition::F_LANGUAGE, MDDataType::text(), false, false),
//                new MDFieldDefinition(MDFieldDefinition::F_LICENSE, MDDataType::text(), false, false),
            ]);
        }
        return $catalogue;
    }

    public function series(): MDCatalogue
    {
        static $catalogue;
        if (!$catalogue) {
            return new MDCatalogue([
                new MDFieldDefinition(MDFieldDefinition::F_TITLE, MDDataType::text(), false, true),
                new MDFieldDefinition(MDFieldDefinition::F_DESCRIPTION, MDDataType::text_long(), false, false),
                new MDFieldDefinition(MDFieldDefinition::F_RIGHTS_HOLDER, MDDataType::text(), false, false),
                new MDFieldDefinition(MDFieldDefinition::F_CREATED_BY, MDDataType::text(), true, false),
                new MDFieldDefinition(MDFieldDefinition::F_CREATOR, MDDataType::text_array(), false, false),
                new MDFieldDefinition(MDFieldDefinition::F_CONTRIBUTOR, MDDataType::text_array(), false, false),
                new MDFieldDefinition(MDFieldDefinition::F_PUBLISHER, MDDataType::text_array(), true, false),
                new MDFieldDefinition(MDFieldDefinition::F_IDENTIFIER, MDDataType::text(), true, false),
//                new MDFieldDefinition(MDFieldDefinition::F_SUBJECTS, MDDataType::text_array(), false, false), // subjects don't work currently (opencast bug)
                // language and license are selection fields in opencast, which is not implemented yet
//                new MDFieldDefinition(MDFieldDefinition::F_LICENSE, MDDataType::text(), false, false),
//                new MDFieldDefinition(MDFieldDefinition::F_LANGUAGE, MDDataType::text(), false, false),
            ]);
        }
        return $catalogue;
    }
}
