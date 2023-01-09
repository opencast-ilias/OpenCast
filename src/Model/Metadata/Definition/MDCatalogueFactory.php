<?php

namespace srag\Plugins\Opencast\Model\Metadata\Definition;

class MDCatalogueFactory
{
    /**
     * @var MDCatalogue
     */
    private $event_catalogue;
    /**
     * @var MDCatalogue
     */
    private $series_catalogue;

    final public function __construct()
    {
    }

    public function event(): MDCatalogue
    {
        return $this->event_catalogue ?? $this->event_catalogue = new MDCatalogue([
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
        ]);
    }

    public function series(): MDCatalogue
    {
        return $this->series_catalogue ?? $this->series_catalogue =  new MDCatalogue([
                new MDFieldDefinition(MDFieldDefinition::F_TITLE, MDDataType::text(), false, true),
                new MDFieldDefinition(MDFieldDefinition::F_DESCRIPTION, MDDataType::text_long(), false, false),
                new MDFieldDefinition(MDFieldDefinition::F_RIGHTS_HOLDER, MDDataType::text(), false, false),
                new MDFieldDefinition(MDFieldDefinition::F_CREATED_BY, MDDataType::text(), true, false),
                new MDFieldDefinition(MDFieldDefinition::F_CREATOR, MDDataType::text_array(), false, false),
                new MDFieldDefinition(MDFieldDefinition::F_CONTRIBUTOR, MDDataType::text_array(), false, false),
                new MDFieldDefinition(MDFieldDefinition::F_PUBLISHER, MDDataType::text_array(), true, false),
                new MDFieldDefinition(MDFieldDefinition::F_IDENTIFIER, MDDataType::text(), true, false),
                new MDFieldDefinition(MDFieldDefinition::F_LICENSE, MDDataType::text_selection(), false, false),
            ]);
    }
}
