<?php

namespace srag\Plugins\Opencast\Model\WorkflowParameter;

use stdClass;

class WorkflowParameterParser
{
    public function configurationFromFormData(array $data): stdClass
    {
        $configuration = new stdClass();
        foreach ($data as $key => $value) {
            if (strpos($key, 'wp_') === 0) {
                $key = substr($key, 3);
                $configuration->$key = $value ? 'true' : 'false';
            }
        }
        return $configuration;
    }
}
