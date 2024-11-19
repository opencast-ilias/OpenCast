<?php

declare(strict_types=1);

/**
 * Class ilOpenCastDBUpdateSteps
 *
 * @author Farbod Zamani Boroujeni <zamani@elan-ev.de>
 */
class ilOpenCastDBUpdateSteps implements \ilDatabaseUpdateSteps
{
    protected \ilDBInterface $db;

    public function prepare(\ilDBInterface $db): void
    {
        $this->db = $db;
    }

    public function step_1(): void
    {
        // Change the deafult value of streaming_only to "-1" in xoct_data table.
        if ($this->db->tableColumnExists('xoct_data', 'streaming_only')) {
            $this->db->modifyTableColumn(
                'xoct_data',
                'streaming_only',
                [
                    'type' => 'integer',
                    'length' => 1,
                    'notnull' => false,
                    'default' => -1
                ]
            );
        }
    }

    public function step_2(): void
    {
        $old_column_name = 'ignore_object_setting';
        $new_column_name = 'overwrite_download_perm';

        // Rename the column in "xoct_publication_usage" table
        $table_name = 'xoct_publication_usage';
        if ($this->db->tableColumnExists($table_name, $old_column_name) &&
            !$this->db->tableColumnExists($table_name, $new_column_name)) {
            $this->db->renameTableColumn($table_name, $old_column_name, $new_column_name);
        }

        // Rename the column in "xoct_pub_sub_usage" table
        $table_name = 'xoct_pub_sub_usage';
        if (!$this->db->tableColumnExists($table_name, $old_column_name)) {
            return;
        }
        if ($this->db->tableColumnExists($table_name, $new_column_name)) {
            return;
        }
        $this->db->renameTableColumn($table_name, $old_column_name, $new_column_name);
    }

    public function step_3(): void
    {
        // check for missing mandatory "title" MD for series
        $r = $this->db->query("SELECT id FROM xoct_md_field_series WHERE field_id = 'title'");
        if ($r->rowCount() > 0) {
            return;
        }

        $next_id = $this->db->nextId('xoct_md_field_series');

        $this->db->insert(
            'xoct_md_field_series',
            [
                'id' => ['integer', $next_id],
                'field_id' => ['text', 'title'],
                'title_de' => ['text', 'Titel'],
                'title_en' => ['text', 'Title'],
                'visible_for_permissions' => ['text', 'all'],
                'required' => ['integer', 1],
                'read_only' => ['integer', 0],
                'prefill' => ['text', ''],
                'sort' => ['integer', 1],
                'values' => ['text', '']
            ]
        );
    }

    public function step_4(): void
    {
        // check for missing mandatory "title" MD for event
        $r = $this->db->query("SELECT id FROM xoct_md_field_event WHERE field_id = 'title'");
        if ($r->rowCount() > 0) {
            return;
        }

        $next_id = $this->db->nextId('xoct_md_field_event');

        $this->db->insert(
            'xoct_md_field_event',
            [
                'id' => ['integer', $next_id],
                'field_id' => ['text', 'title'],
                'title_de' => ['text', 'Titel'],
                'title_en' => ['text', 'Title'],
                'visible_for_permissions' => ['text', 'all'],
                'required' => ['integer', 1],
                'read_only' => ['integer', 0],
                'prefill' => ['text', ''],
                'sort' => ['integer', 1],
                'values' => ['text', '']
            ]
        );
    }
}
