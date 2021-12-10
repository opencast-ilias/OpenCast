<?php

use srag\Plugins\Opencast\Model\Object\ObjectSettings;

/**
 * Class xoctDataMapper
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctDataMapper {

	/**
	 * @param ObjectSettings $objectSettings
	 * @return bool
	 */
	public static function objectSettingsUpdated(ObjectSettings $objectSettings) {
		if ($objectSettings->getObjId()) {
			/**
			 * @var $ilObjOpenCast ilObjOpenCast
			 */
			$ilObjOpenCast = ilObjectFactory::getInstanceByObjId($objectSettings->getObjId());
			$ilObjOpenCast->setTitle($objectSettings->getSeries()->getTitle());
			$ilObjOpenCast->setDescription($objectSettings->getSeries()->getDescription());
			$ilObjOpenCast->update();

			return true;
		}
		
		return false;
	}
}
