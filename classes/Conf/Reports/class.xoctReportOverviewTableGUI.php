<?php

declare(strict_types=1);

use srag\CustomInputGUIs\OpenCast\PropertyFormGUI\PropertyFormGUI;
use srag\CustomInputGUIs\OpenCast\TableGUI\TableGUI;
use srag\Plugins\Opencast\Model\Report\Report;
use srag\Plugins\Opencast\Util\Locale\LocaleTrait;
use srag\Plugins\Opencast\LegacyHelpers\TableGUIConstants;

/**
 * Class xoctReportOverviewTableGUI
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctReportOverviewTableGUI extends ilTable2GUI
{
    use LocaleTrait;
    use \srag\Plugins\Opencast\LegacyHelpers\TableGUI;
    public const ROW_TEMPLATE = "tpl.report_table_row.html";
    private ilOpenCastPlugin $plugin;

    /**
     * xoctReportOverviewTableGUI constructor.
     * @param $parent xoctReportOverviewGUI
     * @param $parent_cmd
     */
    public function __construct($parent, string $parent_cmd)
    {
        global $DIC, $opencastContainer;
        $this->plugin = $opencastContainer[ilOpenCastPlugin::class];
        $this->setSelectAllCheckbox('id[]');
        parent::__construct($parent, $parent_cmd);
        $this->setFormAction($DIC->ctrl()->getFormAction($parent));
        $this->addMultiCommand(xoctGUI::CMD_DELETE, $this->getLocaleString(xoctGUI::CMD_DELETE, 'common'));
        $this->initRowTemplate();
        $this->initTitle();
        $this->initData();
    }

    protected function getRowTemplate(): string
    {
        return $this->plugin->getDirectory() . '/templates/default/' . self::ROW_TEMPLATE;
    }



    protected function getSelectableColumns2(): array
    {
        return [];
    }


    protected function initColumns(): void
    {
        $this->addColumn('', '', '', true);
        $this->addColumn($this->getLocaleString('message'));
        $this->addColumn($this->getLocaleString('sender'));
        $this->addColumn($this->getLocaleString('date'), 'created_at');
    }

    protected function initData(): void
    {
        $filter_date_from = null;
        $filter_date_to = null;
        $filter_values = [];
        $filter_sender = $filter_values['sender'] ?? '';
        /** @var ilDate $ilDate */
        if ($ilDate = $filter_values['date_from'] ?? null) {
            $filter_date_from = $ilDate->get(IL_CAL_DATE, 'Y-m-d H:i:s');
        }
        if ($ilDate = $filter_values['date_to'] ?? null) {
            $filter_date_to = $ilDate->get(IL_CAL_DATE, 'Y-m-d H:i:s');
        }

        if ($filter_date_from && $filter_date_to) {
            $data = Report::where(['created_at' => $filter_date_from], ['created_at' => '>='])->where(
                ['created_at' => $filter_date_to],
                ['created_at' => '<=']
            )->getArray();
        } elseif ($filter_date_from) {
            $data = Report::where(['created_at' => $filter_date_from], ['created_at' => '>='])->getArray();
        } elseif ($filter_date_to) {
            $data = Report::where(['created_at' => $filter_date_to], ['created_at' => '<='])->getArray();
        } else {
            $data = Report::getArray();
        }

        $filtered = [];
        foreach ($data as $key => $value) {
            $value['sender'] = ilObjUser::_lookupLogin((int)$value['user_id']) . ', ' . ilObjUser::_lookupEmail(
                    (int)$value['user_id']
            );
            if ($filter_sender && (stripos($value['sender'], strtolower($filter_sender)) === false)) {
                unset($data[$key]);
            } else {
                $filtered[] = $value;
            }
        }

        $this->setData($filtered);
    }


    protected function initFilterFields(): void
    {
        $this->filter_fields = [
            "sender" => [
                PropertyFormGUI::PROPERTY_CLASS => ilTextInputGUI::class
            ],
            "date_from" => [
                PropertyFormGUI::PROPERTY_CLASS => ilDateTimeInputGUI::class
            ],
            "date_to" => [
                PropertyFormGUI::PROPERTY_CLASS => ilDateTimeInputGUI::class
            ],
        ];
    }


    protected function initId(): void
    {
        $this->setId('xoct_reports');
    }

    protected function initTitle(): void
    {
    }


    protected function fillRow(array $row): void
    {
        $this->tpl->setVariable('ID', $row['id']);
        $ilAccordionGUI = new ilAccordionGUI();
        $ilAccordionGUI->addItem($row['subject'], $row['message']);
        $this->tpl->setVariable('SENDER', $row['sender']);
        $this->tpl->setVariable('MESSAGE', $ilAccordionGUI->getHTML());
        $this->tpl->setVariable('DATE', date('d.m.Y H:i:s', strtotime($row['created_at'])));
    }
}
