<?php

namespace srag\Plugins\Opencast\Model\Metadata\Config;

interface MDFieldConfigRepository
{

    /**
     * @return MDFieldConfigAR[]
     */
    public function getAll() : array;
    /**
     * Important: this returns all fields that are defined as read_only by the Opencast Metadata Catalogue - NOT ONLY by the
     * metadata field configuration in the plugin. This is an important distinction, since fields that are read_only in
     * the plugin but NOT read_only in Opencast might still be prefilled, e.g. with the course title or current username.
     *
     * @return array|MDFieldConfigAR[]
     */
    public function getAllForForm() : array;

    /**
     * @return array
     */
    public function getArray() : array;

    public function findByFieldId(string $field_id) : ?MDFieldConfigAR;

    public function storeFromArray(array $data) : MDFieldConfigAR;


}