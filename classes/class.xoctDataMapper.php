<?php

/**
 * Class xoctDataMapper
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctDataMapper {

	/**
	 * @param xoctOpenCast $xoctOpenCast
	 * @return bool
	 */
	public static function xoctOpenCastupdated(xoctOpenCast $xoctOpenCast) {
		/**
		 * @var $ilObjOpenCast ilObjOpenCast
		 */
		$ilObjOpenCast = ilObjectFactory::getInstanceByObjId($xoctOpenCast->getObjId());

		$ilObjOpenCast->setTitle($xoctOpenCast->getSeries()->getTitle());
		$ilObjOpenCast->setDescription($xoctOpenCast->getSeries()->getDescription());

		$ilObjOpenCast->update();

		return true;
	}
}
