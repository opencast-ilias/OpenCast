<?php

namespace srag\DIC\OpenCast\DIC;

use srag\DIC\OpenCast\Database\DatabaseDetector;
use srag\DIC\OpenCast\Database\DatabaseInterface;

/**
 * Class AbstractDIC
 *
 * @package srag\DIC\OpenCast\DIC
 *
 * @author  studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 */
abstract class AbstractDIC implements DICInterface {

	/**
	 * AbstractDIC constructor
	 */
	protected function __construct() {

	}


	/**
	 * @inheritdoc
	 */
	public function database() {
		return DatabaseDetector::getInstance($this->databaseCore());
	}
}
