<?php

/**
 * Class xoctUserSettings
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctUserSettings {

	const S_VIEW_TYPE = 'view_type';
	const S_TILE_LIMIT = 'tile_limit';

	const VIEW_TYPE_LIST = 0;
	const VIEW_TYPE_TILES = 1;
	const DEFAULT_VIEW_TYPE = self::VIEW_TYPE_LIST;
	const DEFAULT_TILE_LIMIT = 12;


	/**
	 * @param $user_id
	 * @param $ref_id
	 * @param $view_type
	 */
	public static function changeViewType($user_id, $ref_id, $view_type) {
		$xoctUserSetting = xoctUserSetting::where(['ref_id' => $ref_id, 'user_id' => $user_id, 'name' => self::S_VIEW_TYPE])->first();
		$xoctUserSetting = $xoctUserSetting ?: new xoctUserSetting();
		$xoctUserSetting->setUserId($user_id)
			->setRefId($ref_id)
			->setValue($view_type)
			->setName(self::S_VIEW_TYPE)
			->store();
	}

	/**
	 * @param $user_id
	 * @param $ref_id
	 * @return int
	 */
	public static function getViewTypeForUser($user_id, $ref_id) {
		if (!xoct::isIlias54()) {
			return self::VIEW_TYPE_LIST;
		}
		/** @var xoctUserSetting $xoctUserSetting */
		$xoctUserSetting = xoctUserSetting::where(['user_id' => $user_id, 'ref_id' => $ref_id, 'name' => self::S_VIEW_TYPE])->first();
		/** @var xoctOpenCast $xoctOpenCast */
		$xoctOpenCast = xoctOpenCast::find(ilObjOpenCast::_lookupObjectId($ref_id));
		if (!$xoctOpenCast->isViewChangeable() || !$xoctUserSetting) {
			return $xoctOpenCast->getDefaultView();
		}

		return $xoctUserSetting->getValue() ?: self::DEFAULT_VIEW_TYPE;
	}

	/**
	 * @param $user_id
	 * @param $ref_id
	 * @param $limit
	 */
	public static function changeTileLimit($user_id, $ref_id, $limit) {
		$xoctUserSetting = xoctUserSetting::where(['ref_id' => $ref_id, 'user_id' => $user_id, 'name' => self::S_TILE_LIMIT])->first();
		$xoctUserSetting = $xoctUserSetting ?: new xoctUserSetting();
		$xoctUserSetting->setUserId($user_id)
			->setRefId($ref_id)
			->setValue($limit)
			->setName(self::S_TILE_LIMIT)
			->store();
	}

	/**
	 * @param $user_id
	 * @param $ref_id
	 * @return int
	 */
	public static function getTileLimitForUser($user_id, $ref_id) {
		/** @var xoctUserSetting $xoctUserSetting */
		$xoctUserSetting = xoctUserSetting::where(['user_id' => $user_id, 'ref_id' => $ref_id, 'name' => self::S_TILE_LIMIT])->first();
		return (int) $xoctUserSetting->getValue() ?: self::DEFAULT_TILE_LIMIT;
	}
}