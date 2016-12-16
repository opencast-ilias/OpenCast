<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */
require_once 'Services/Component/classes/class.ilComponent.php';
require_once('./include/inc.ilias_version.php');
/**
 * Class xoct
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xoct {

	const ILIAS_50 = 50;
	const ILIAS_51 = 51;
	const ILIAS_52 = 52;
	const MIN_ILIAS_VERSION = self::ILIAS_50;

	/**
	 * @return int
	 */
	public static function getILIASVersion() {
		if (ilComponent::isVersionGreaterString(ILIAS_VERSION_NUMERIC, '5.1.999')) {
			return self::ILIAS_52;
		}
		if (ilComponent::isVersionGreaterString(ILIAS_VERSION_NUMERIC, '5.0.999')) {
			return self::ILIAS_51;
		}
		if (ilComponent::isVersionGreaterString(ILIAS_VERSION_NUMERIC, '4.9.999')) {
			return self::ILIAS_50;
		}

		return 0;
	}

	/**
	 * @return bool
	 */
	public static function is50() {
		return self::getILIASVersion() >= self::ILIAS_50;
	}

	/**
	 * @return bool
	 */
	public static function is51() {
		return self::getILIASVersion() >= self::ILIAS_51;
	}

	/**
	 * @return bool
	 */
	public static function is52() {
		return self::getILIASVersion() >= self::ILIAS_52;
	}

}