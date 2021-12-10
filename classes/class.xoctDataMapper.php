<?php

use srag\Plugins\Opencast\Model\Object\ObjectSettings;

/**
 * Class xoctDataMapper
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctDataMapper {

	/**
	 * @param ObjectSettings $xoctOpenCast
	 * @return bool
	 */
	public static function xoctOpenCastupdated(ObjectSettings $xoctOpenCast) {
		if ($xoctOpenCast->getObjId()) {
			/**
			 * @var $ilObjOpenCast ilObjOpenCast
			 */
			$ilObjOpenCast = ilObjectFactory::getInstanceByObjId($xoctOpenCast->getObjId());
			$ilObjOpenCast->setTitle($xoctOpenCast->getSeries()->getTitle());
			$ilObjOpenCast->setDescription($xoctOpenCast->getSeries()->getDescription());
			$ilObjOpenCast->update();

			return true;
		}
		
		return false;
	}
}
