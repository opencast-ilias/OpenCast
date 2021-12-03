<?php

namespace srag\Plugins\Opencast\Model\Metadata\Config;

interface MDFieldConfigRepository
{

    /**
     * @return MDFieldConfigAR[]
     */
    public function getAll() : array;
    /**
     * @return MDFieldConfigAR[]
     */
    public function getAllEditable() : array;

    /**
     * @return array
     */
    public function getArray() : array;

    public function findByFieldId(string $field_id) : ?MDFieldConfigAR;

    public function storeFromArray(array $data) : MDFieldConfigAR;


}