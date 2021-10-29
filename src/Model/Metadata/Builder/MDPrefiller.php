<?php

namespace srag\Plugins\Opencast\Model\Metadata\Builder;

use srag\Plugins\Opencast\Model\Metadata\Config\MDPrefillOption;

class MDPrefiller
{
    public function getPrefillValue(MDPrefillOption $prefill_type) : ?string
    {
        switch ($prefill_type->getValue()) {
            case MDPrefillOption::T_NONE:
            default:
                return null;
        }
    }
}