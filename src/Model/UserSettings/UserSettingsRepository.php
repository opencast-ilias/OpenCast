<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\UserSettings;

use ilObjOpenCast;
use srag\Plugins\Opencast\Model\Object\ObjectSettings;

/**
 * Class userSettingsRepository
 * TODO: change methods to non-static, introduce class to OpencastDIC
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class UserSettingsRepository
{
    public const S_VIEW_TYPE = 'view_type';
    public const S_TILE_LIMIT = 'tile_limit';

    public const VIEW_TYPE_LIST = 0;
    public const VIEW_TYPE_TILES = 1;
    public const DEFAULT_VIEW_TYPE = self::VIEW_TYPE_LIST;
    public const DEFAULT_TILE_LIMIT = 12;


    public static function changeViewType(int $user_id, int $ref_id, int $view_type): void
    {
        $user_setting = UserSetting::where(
            ['ref_id' => $ref_id, 'user_id' => $user_id, 'name' => self::S_VIEW_TYPE]
        )->first();
        $user_setting = $user_setting ?: new UserSetting();
        $user_setting->setUserId($user_id)
                        ->setRefId($ref_id)
                        ->setValue($view_type)
                        ->setName(self::S_VIEW_TYPE)
                        ->store();
    }


    public static function getViewTypeForUser(int $user_id, int $ref_id): int
    {
        /** @var UserSetting $user_setting */
        $user_setting = UserSetting::where(
            ['user_id' => $user_id, 'ref_id' => $ref_id, 'name' => self::S_VIEW_TYPE]
        )->first();
        /** @var ObjectSettings $objectSettings */
        $objectSettings = ObjectSettings::find(ilObjOpenCast::_lookupObjectId($ref_id));
        if (!$objectSettings->isViewChangeable() || !$user_setting) {
            return $objectSettings->getDefaultView();
        }

        return (int ) ($user_setting->getValue() ?: self::DEFAULT_VIEW_TYPE);
    }

    public static function changeTileLimit(int $user_id, int $ref_id, int $limit): void
    {
        $user_setting = UserSetting::where(
            ['ref_id' => $ref_id, 'user_id' => $user_id, 'name' => self::S_TILE_LIMIT]
        )->first();
        $user_setting = $user_setting ?: new UserSetting();
        $user_setting->setUserId($user_id)
                        ->setRefId($ref_id)
                        ->setValue($limit)
                        ->setName(self::S_TILE_LIMIT)
                        ->store();
    }

    public static function getTileLimitForUser(int $user_id, int $ref_id): int
    {
        /** @var UserSetting $user_setting */
        $user_setting = UserSetting::where(
            ['user_id' => $user_id, 'ref_id' => $ref_id, 'name' => self::S_TILE_LIMIT]
        )->first();
        return (int) ($user_setting ? $user_setting->getValue() : self::DEFAULT_TILE_LIMIT);
    }
}
