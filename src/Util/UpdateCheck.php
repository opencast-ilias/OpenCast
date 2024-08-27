<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Util;

class UpdateCheck
{
    private string $last_update_version = '';

    public function __construct(\ilDBInterface $db)
    {
        // read old plugin version from db
        $res = $db->queryF(
            'SELECT last_update_version FROM il_plugin WHERE plugin_id = %s',
            ['text'],
            ['xoct']
        )->fetchObject();
        $this->last_update_version = $res->last_update_version ?? '0.0.0';
    }

    public function isNewInstallation(): bool
    {
        return $this->last_update_version === '0.0.0';
    }
}
