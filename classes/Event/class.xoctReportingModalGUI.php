<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class xoctReportingFormGUI
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctReportingModalGUI extends ilModalGUI {

	const REPORTING_TYPE_DATE = 1;
	const REPORTING_TYPE_QUALITY = 2;

	/**
	 * @var ilCtrl
	 */
	protected $ctrl;
    /**
     * @var ilTemplate
     */
	protected $tpl;
	/**
	 * @var ilOpenCastPlugin
	 */
	protected $pl;
	/**
	 * @var xoctEventGUI
	 */
	protected $parent_gui;
	/**
	 * xoctReportingFormGUI constructor.
	 */
	public function __construct($parent_gui, $type) {
		global $DIC;
		$ilCtrl = $DIC['ilCtrl'];
		$this->ctrl = $ilCtrl;
        $this->tpl = $DIC['tpl'];
        $this->pl = ilOpenCastPlugin::getInstance();
        $this->parent_gui = $parent_gui;

        $this->setType(ilModalGUI::TYPE_LARGE);
        $this->tpl->addCss($this->pl->getDirectory() . '/templates/default/reporting_modal.css');

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
				$this->setHeading($this->pl->txt('event_report_date_modification'));
				$this->setBody(nl2br(xoctConf::getConfig(xoctConf::F_REPORT_DATE_TEXT)));
				$send_button->setCommand(xoctEventGUI::CMD_REPORT_DATE);
				break;
			case self::REPORTING_TYPE_QUALITY:
				$this->setId('xoct_report_quality_modal');
				$this->setHeading($this->pl->txt('event_report_quality_problem'));
				$this->setBody(nl2br(xoctConf::getConfig(xoctConf::F_REPORT_QUALITY_TEXT)));
				$send_button->setCommand(xoctEventGUI::CMD_REPORT_QUALITY);
				break;
		}
	}


	static function getInstance() {
		throw new ilException('Do not use this method, please use the constructor instead.');
	}


	function getHTML() {
		// only the following two lines differ from the parent method
		$tpl = new ilTemplate("tpl.reporting_modal.html", true, true, $this->pl->getDirectory());
		$tpl->setVariable('FORM_ACTION', $this->ctrl->getFormAction($this->parent_gui));

		if (count($this->getButtons()) > 0)
		{
			foreach ($this->getButtons() as $b)
			{
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

		switch ($this->getType())
		{
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