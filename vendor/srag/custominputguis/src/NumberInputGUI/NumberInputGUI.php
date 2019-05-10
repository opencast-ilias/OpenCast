<?php

namespace srag\CustomInputGUIs\OpenCast\NumberInputGUI;

use ilNumberInputGUI;
use ilTableFilterItem;
use srag\DIC\OpenCast\DICTrait;

/**
 * Class NumberInputGUI
 *
 * @package srag\CustomInputGUIs\OpenCast\NumberInputGUI
 *
 * @author  studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 */
class NumberInputGUI extends ilNumberInputGUI implements ilTableFilterItem {

	use DICTrait;


	/**
	 * @inheritdoc
	 */
	public function getTableFilterHTML()/*: string*/ {
		return $this->render();
	}
}
