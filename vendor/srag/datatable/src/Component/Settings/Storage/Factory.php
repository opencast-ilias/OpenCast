<?php

namespace srag\DataTableUI\OpenCast\Component\Settings\Storage;

/**
 * Interface Factory
 *
 * @package srag\DataTableUI\OpenCast\Component\Settings\Storage
 */
interface Factory
{

    /**
     * @return SettingsStorage
     */
    public function default() : SettingsStorage;
}
