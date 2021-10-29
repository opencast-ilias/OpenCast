<?php

namespace srag\Plugins\Opencast\Model\Metadata\Config\Event;

use srag\Plugins\Opencast\Model\Metadata\Config\MDFieldConfigAR;
use srag\Plugins\Opencast\Model\Metadata\Config\MDFieldConfigRepository;
use srag\Plugins\Opencast\Model\Metadata\Config\MDPrefillOption;

class MDFieldConfigEventRepository implements MDFieldConfigRepository
{
    /**
     * @return MDFieldConfigEventAR[]
     */
    public function getAll() : array
    {
        return MDFieldConfigEventAR::get();
    }

    public function getArray() : array
    {
        return MDFieldConfigEventAR::getArray();
    }

    public function findByFieldId(string $field_id): ?MDFieldConfigAR
    {
        $ar = MDFieldConfigEventAR::where(['field_id' => $field_id])->first();
        return $ar;
    }

    public function storeFromArray(array $data) : MDFieldConfigAR
    {
        $ar = MDFieldConfigEventAR::where(['field_id' => $data['field_id']])->first();
        if (is_null($ar)) {
            $ar = new MDFieldConfigEventAR();
        }
        $ar->setFieldId($data['field_id']);
        $ar->setTitle($data['title']);
        $ar->setVisibleForRoles($data['visible_for_roles']);
        $ar->setPrefill(new MDPrefillOption($data['prefill']));
        $ar->setReadOnly($data['read_only']);
        $ar->setRequired($data['required']);
        $ar->store();
        return $ar;
    }
}