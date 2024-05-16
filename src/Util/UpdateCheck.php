<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Util;

class UpdateCheck
{
    private $path_to_plugin_php = __DIR__ . '/../../plugin.php';
    private $db;
    private $last_update_version = '';
    private $version_check_string = '';
    private $version_check_string_db = '';

    public function __construct(\ilDBInterface $db)
    {
        $this->db = $db;
        if (!is_readable($this->path_to_plugin_php)) {
            throw new \Exception('Could not find plugin.php');
        }
        include $this->path_to_plugin_php; // read the infos from plugin.php
        /** @noinspection IssetArgumentExistenceInspection */
        if (isset($version_check)) {
            $this->version_check_string = $version_check;
        }

        // read old plugin version from db
        $res = $this->db->queryF(
            'SELECT last_update_version FROM il_plugin WHERE plugin_id = %s',
            ['text'],
            ['xoct']
        )->fetchObject();
        $this->last_update_version = $res->last_update_version ?? '0.0.0';

        // version check string from db
        try {
            $res = $this->db->queryF(
                'SELECT `value` FROM xoct_config WHERE `name` = %s',
                ['text'],
                ['version_check']
            )->fetchObject();
        } catch (\Throwable $t) {
            $res = null; // unable to read from config, maybe a new installation of the plugin
        }

        $this->version_check_string_db = $res->value ?? '';
    }

    public function isUpdatePossible(): bool
    {
        return true; // we no longer check for compatibility, since there is no fork of the plugin anymore.
        // Older versions
        if (version_compare($this->last_update_version, '4.0.2', '<=')) {
            return true;
        }
        if ($this->version_check_string_db === '') {
            return false;
        }

        return $this->version_check_string === $this->version_check_string_db;
    }

    public function isNewInstallation(): bool
    {
        return false; // todo: check if this still possible
        return $this->last_update_version === '0.0.0';
    }
}
