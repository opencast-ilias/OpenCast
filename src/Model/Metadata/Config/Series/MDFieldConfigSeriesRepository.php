<?php

namespace srag\Plugins\Opencast\Model\Metadata\Config\Series;

use arException;
use srag\Plugins\Opencast\Model\Metadata\Config\MDFieldConfigAR;
use srag\Plugins\Opencast\Model\Metadata\Config\MDFieldConfigRepository;
use srag\Plugins\Opencast\Model\Metadata\Config\MDPrefillOption;
use xoctException;

class MDFieldConfigSeriesRepository implements MDFieldConfigRepository
{
    /**
     * @return MDFieldConfigSeriesAR[]
     */
    public function getAll() : array
    {
        return MDFieldConfigSeriesAR::get();
    }

    public function getArray() : array
    {
        return MDFieldConfigSeriesAR::getArray();
    }

    /**
     * @param $field_id
     * @return MDFieldConfigAR
     * @throws xoctException
     */
    public function findByFieldId($field_id): MDFieldConfigAR
    {
        $ar = MDFieldConfigSeriesAR::where(['field_id' => $field_id])->first();
        if (is_null($ar)) {
            throw new xoctException(xoctException::INTERNAL_ERROR, "could not find md field with id $field_id");
        }
        return $ar;
    }

    public function createFromArray($data) : MDFieldConfigAR
    {
        $ar = new MDFieldConfigSeriesAR();
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