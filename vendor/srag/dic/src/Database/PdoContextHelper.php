<?php

namespace srag\DIC\OpenCast\Database;

use ilDBPdo;
use ilDBPdoInterface;
use PDO;
use srag\DIC\OpenCast\Exception\DICException;

/**
 * Class PdoContextHelper
 *
 * @package srag\DIC\OpenCast\Database
 *
 * @author  studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 *
 * @internal
 */
final class PdoContextHelper extends ilDBPdo {

	/**
	 * @param ilDBPdoInterface $db
	 *
	 * @return PDO
	 *
	 * @throws DICException PdoContextHelper only supports ilDBPdo!
	 *
	 * @internal
	 */
	public static function getPdo(ilDBPdoInterface $db) {
		if (!($db instanceof ilDBPdo)) {
			throw new DICException("PdoContextHelper only supports ilDBPdo!");
		}

		return $db->pdo;
	}


	/**
	 * PdoContextHelper constructor
	 */
	private function __construct() {

	}


	/**
	 * @inheritdoc
	 */
	public function initHelpers() {

	}
}
