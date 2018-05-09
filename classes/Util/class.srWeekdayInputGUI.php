<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class srWeekdayInputGUI
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class srWeekdayInputGUI extends ilFormPropertyGUI {

	const TYPE = 'weekday';
	/**
	 * @var array
	 */
	protected $value = array();
	/**
	 * @var ilLanguage
	 */
	protected $lng;
	/**
	 * @var ilOpenCastPlugin
	 */
	protected $pl;


	public function __construct($a_title, $a_postvar) {
		global $DIC;
		$lng = $DIC['lng'];
		$this->lng = $lng;
		$this->pl = ilOpenCastPlugin::getInstance();
		parent::__construct($a_title, $a_postvar);
		$this->setType(self::TYPE);
	}


	/**
	 * Set Value.
	 *
	 * @param    array $a_value Value
	 */
	function setValue($a_value) {
		$this->value = $a_value;
	}


	/**
	 * Get Value.
	 *
	 * @return    array    Value
	 */
	function getValue() {
		return $this->value;
	}


	/**
	 * Set value by array
	 *
	 * @param $a_values
	 *
	 * @internal param object $a_item Item
	 */
	function setValueByArray($a_values) {
		$this->setValue($a_values[$this->getPostVar()] ? $a_values[$this->getPostVar()] : array());
	}


	function checkInput() {
		return ($_POST[$this->getPostVar()] == NULL) || (count($_POST[$this->getPostVar()]) <= 7);
	}


	/**
	 * Insert property html
	 *
	 * @return    int    Size
	 */
	function insert(&$a_tpl) {
		$html = $this->render();

		$a_tpl->setCurrentBlock("prop_generic");
		$a_tpl->setVariable("PROP_GENERIC", $html);
		$a_tpl->parseCurrentBlock();
	}


	protected function render() {
		$tpl = $this->pl->getTemplate("default/tpl.weekday_input.html");

		$days = array( 1 => 'MO', 2 => 'TU', 3 => 'WE', 4 => 'TH', 5 => 'FR', 6 => 'SA', 7 => 'SU' );

		for ($i = 1; $i < 8; $i ++) {
			$tpl->setCurrentBlock('byday_simple');

			if (in_array($days[$i], $this->getValue())) {
				$tpl->setVariable('BYDAY_WEEKLY_CHECKED', 'checked="checked"');
			}
			$tpl->setVariable('TXT_ON', $this->lng->txt('cal_on'));
			$tpl->setVariable('BYDAY_WEEKLY_VAL', $days[$i]);
			$tpl->setVariable('TXT_DAY_SHORT', ilCalendarUtil::_numericDayToString($i, false));
			$tpl->setVariable('POSTVAR', $this->getPostVar());
			$tpl->parseCurrentBlock();
		}

		return $tpl->get();
	}


	/**
	 * Get HTML for table filter
	 */
	function getTableFilterHTML() {
		$html = $this->render();

		return $html;
	}
}