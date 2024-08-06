<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\WorkflowParameter;

use stdClass;

class WorkflowParameterParser
{
    public function configurationFromFormData(array $data): stdClass
    {
        $configuration = new stdClass();
        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'wp_')) {
                $key = substr($key, 3);
                $configuration->$key = $value ? 'true' : 'false';
            }
        }
        return $configuration;
    }
}
