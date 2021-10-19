<?php

namespace srag\Plugins\Opencast\Model\Metadata\Config\Event;

use srag\Plugins\Opencast\Model\Metadata\Config\MDFieldConfigAR;
use srag\Plugins\Opencast\Model\Metadata\Config\MDFieldConfigRepository;
use srag\Plugins\Opencast\Model\Metadata\Config\MDPrefillOption;
use xoctException;

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

    /**
     * @throws xoctException
     */
    public function findByFieldId($field_id): MDFieldConfigAR
    {
        $ar = MDFieldConfigEventAR::where(['field_id' => $field_id])->first();
        if (is_null($ar)) {
            throw new xoctException(xoctException::INTERNAL_ERROR, "could not find md field with id $field_id");
        }
        return $ar;
    }

    public function createFromArray($data) : MDFieldConfigAR
    {
        $ar = new MDFieldConfigEventAR();
        $ar->setFieldId($data['field_id']);
        $ar->setTitle($data['title']);
        $ar->setVisibleForRoles($data['visible_for_roles']);
        $ar->setPrefill(new MDPrefillOption($data['prefill']));
        $ar->setReadOnly($data['read_only']);
        $ar->setRequired($data['required']);
        $ar->create();
        return $ar;
    }
}