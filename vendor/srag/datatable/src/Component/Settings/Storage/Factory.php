<?php

namespace srag\DataTableUI\OpencastObject\Component\Settings\Storage;

/**
 * Interface Factory
 *
 * @package srag\DataTableUI\OpencastObject\Component\Settings\Storage
 */
interface Factory
{

    /**
     * @return SettingsStorage
     */
    public function default() : SettingsStorage;
}
