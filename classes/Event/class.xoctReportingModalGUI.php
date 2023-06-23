<?php

use srag\DIC\OpenCast\DICTrait;
use srag\Plugins\Opencast\Model\Config\PluginConfig;

/**
 * Class xoctReportingFormGUI
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctReportingModalGUI extends ilModalGUI
{
    public const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;

    public const REPORTING_TYPE_DATE = 1;
    public const REPORTING_TYPE_QUALITY = 2;

    /**
     * @var xoctEventGUI
     */
    protected $parent_gui;
    /**
     * @var \ilCtrlInterface
     */
    private $ctrl;

    /**
     * xoctReportingFormGUI constructor.
     * @noinspection MagicMethodsValidityInspection
     */
    public function __construct($parent_gui, $type)
    {
        global $DIC;
        $main_tpl = $DIC->ui()->mainTemplate();
        $this->ctrl = $DIC->ctrl();
        $this->parent_gui = $parent_gui;

        $this->setType(ilModalGUI::TYPE_LARGE);
        $main_tpl->addCss(self::plugin()->getPluginObject()->getDirectory() . '/templates/default/reporting_modal.css');

        $send_button = ilSubmitButton::getInstance();
        $send_button->setCaption('send');

        $this->addButton($send_button);

        $cancel_button = ilButton::getInstance();
        $cancel_button->setCaption('cancel');
        $type_title = $type == self::REPORTING_TYPE_DATE ? 'date' : 'quality';
        $cancel_button->setOnClick("$('#xoct_report_{$type_title}_modal').modal('hide');event.preventDefault();");
        $this->addButton($cancel_button);

        switch ($type) {
            case self::REPORTING_TYPE_DATE:
                $this->setId('xoct_report_date_modal');
                $this->setHeading(self::plugin()->translate('event_report_date_modification'));
                $this->setBody(nl2br(PluginConfig::getConfig(PluginConfig::F_REPORT_DATE_TEXT)));
                $send_button->setCommand(xoctEventGUI::CMD_REPORT_DATE);
                break;
            case self::REPORTING_TYPE_QUALITY:
                $this->setId('xoct_report_quality_modal');
                $this->setHeading(self::plugin()->translate('event_report_quality_problem'));
                $this->setBody(nl2br(PluginConfig::getConfig(PluginConfig::F_REPORT_QUALITY_TEXT)));
                $send_button->setCommand(xoctEventGUI::CMD_REPORT_QUALITY);
                break;
        }
    }

    /**
     * @return ilModalGUI|void
     * @throws ilException
     */
    public static function getInstance()
    {
        throw new ilException('Do not use this method, please use the constructor instead.');
    }

    /**
     * @return string
     * @throws \srag\DIC\OpenCast\Exception\DICException
     * @throws ilTemplateException
     */
    public function getHTML()
    {
        // only the following two lines differ from the parent method
        $tpl = new ilTemplate(
            "tpl.reporting_modal.html",
            true,
            true,
            self::plugin()->getPluginObject()->getDirectory()
        );
        $tpl->setVariable('FORM_ACTION', $this->ctrl->getFormAction($this->parent_gui));

        if (count($this->getButtons()) > 0) {
            foreach ($this->getButtons() as $b) {
                $tpl->setCurrentBlock("button");
                $tpl->setVariable("BUTTON", $b->render());
                $tpl->parseCurrentBlock();
            }
            $tpl->setCurrentBlock("footer");
            $tpl->parseCurrentBlock();
        }

        $tpl->setVariable("HEADING", $this->getHeading());

        $tpl->setVariable("MOD_ID", $this->getId());
        $tpl->setVariable("BODY", $this->getBody());

        switch ($this->getType()) {
            case self::TYPE_LARGE:
                $tpl->setVariable("CLASS", "modal-lg");
                break;

            case self::TYPE_SMALL:
                $tpl->setVariable("CLASS", "modal-sm");
                break;
        }

        return $tpl->get();
    }
}
