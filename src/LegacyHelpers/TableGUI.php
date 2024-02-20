<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\LegacyHelpers;

use srag\Plugins\Opencast\Util\Locale\LocaleTrait;

/**
 * @author     Fabian Schmid <fabian@sr.solutions>
 * @deprecated Do not use this trait anymore, it's only to make old tables run which used the srag/custominputguis library
 */
trait TableGUI
{
    use LocaleTrait;

    /**
     * @var array
     *
     * @deprecated
     */
    protected $filter_fields = [];
    /**
     * @var ilFormPropertyGUI[]
     *
     * @deprecated
     */
    private $filter_cache = [];

    /**
     * @inheritDoc
     *
     * @return array
     *
     * @deprecated
     */
    final public function getSelectableColumns(): array
    {
        return array_map(function (array $column): array {
            if (!isset($column["txt"])) {
                $column["txt"] = $this->txt($column["id"]);
            }

            return $column;
        }, $this->getSelectableColumns2());
    }

    /**
     * @param string      $key
     * @param string|null $default
     *
     * @return string
     *
     * @deprecated
     */
    public function txt(string $key, /*?*/ string $default = null): string
    {
        return $this->getLocaleString($key, '', $default);
    }

    /**
     * @inheritDoc
     *
     * @param array|object $row
     *
     * @deprecated
     */
    #[\ReturnTypeWillChange]
    protected function fillRow(/*array*/ $row): void
    {
        $this->tpl->setCurrentBlock("column");

        foreach ($this->getSelectableColumns() as $column) {
            if ($this->isColumnSelected($column["id"])) {
                $column = $this->getColumnValue($column["id"], $row);

                if (!empty($column)) {
                    $this->tpl->setVariable("COLUMN", $column);
                } else {
                    $this->tpl->setVariable("COLUMN", " ");
                }

                $this->tpl->parseCurrentBlock();
            }
        }
    }

    /**
     * @deperecated
     */
    protected function getColumnValue(
        string $column,
        $row,
        int $format = TableGUIConstants::DEFAULT_FORMAT
    ): string {
        return $row[$column] ?? '';
    }

    /**
     * @return array
     *
     * @deprecated
     */
    abstract protected function getSelectableColumns2(): array;

    /**
     * @param string $field_id
     *
     * @return bool
     *
     * @deprecated
     */
    final protected function hasSessionValue(string $field_id): bool
    {
        // Not set (null) on first visit, false on reset filter, string if is set
        return (isset($_SESSION["form_" . $this->getId()][$field_id]) && $_SESSION["form_" . $this->getId(
            )][$field_id] !== false);
    }

    /**
     * @deprecated
     */
    protected function initAction(): void
    {
        $this->setFormAction($this->ctrl->getFormAction($this->parent_obj));
    }

    /**
     * @deprecated
     */
    protected function initColumns(): void
    {
        foreach ($this->getSelectableColumns() as $column) {
            if ($this->isColumnSelected($column["id"])) {
                $this->addColumn($column["txt"], ($column["sort"] ? $column["id"] : null));
            }
        }
    }

    /**
     * @deprecated
     */
    protected function initCommands(): void
    {
    }

    /**
     * @deprecated
     */
    abstract protected function initData(): void;

    /**
     * @deprecated
     */
    protected function initExport(): void
    {
    }

    /**
     * @deprecated
     */
    abstract protected function initId(): void;

    /**
     * @deprecated
     */
    abstract protected function initTitle(): void;

    abstract protected function getRowTemplate(): string;

    /**
     * @deprecated
     */
    private function initRowTemplate(): void
    {
        $this->setRowTemplate($this->getRowTemplate());
    }

    /**
     * @deprecated
     */
    private function initTable(): void
    {
        $this->initAction();

        $this->initTitle();

        $this->initData();

        $this->initColumns();

        $this->initExport();

        $this->initRowTemplate();

        $this->initCommands();
    }
}
