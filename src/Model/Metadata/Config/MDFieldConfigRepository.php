<?php

namespace srag\Plugins\Opencast\Model\Metadata\Config;

class MDFieldConfigRepository
{

    /**
     * @return MDFieldConfigAR[]
     */
    public function getAll() : array
    {
        return MDFieldConfigAR::get();
    }


}