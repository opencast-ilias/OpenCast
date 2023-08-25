<?php

namespace srag\Plugins\Opencast\Model\Publication\Config;

use srag\Plugins\Opencast\LegacyHelpers\TranslatorTrait;

/**
 * Class PublicationUsageGroupRepository
 *
 * @author Farbod Zamani Boroujeni <zamani@elan-ev.de>
 */
class PublicationUsageGroupRepository
{
    use TranslatorTrait;
    /**
     * Returns the display name of the group but by looking for a record also in localization.
     * @param int $group_id
     * @return string
     */
    public static function getDisplayName(int $group_id): string
    {
        $display_name = '';
        $group = PublicationUsageGroup::find($group_id);
        if (!empty($group) && !empty($group->getDisplayName())) {
            $display_name = $group->getDisplayName();
            $display_name = self::getLocalizedDisplayName($display_name);
        }
        return trim($display_name);
    }

    /**
     * @param string $group_display_name
     * @return string
     */
    public static function getLocalizedDisplayName($group_display_name): string
    {
        $localized_display_name = self::translate(strtolower($group_display_name), PublicationUsageGroup::DISPLAY_NAME_LANG_MODULE);
        if (strpos($localized_display_name, 'MISSING') === false) {
            $group_display_name = $localized_display_name;
        }
        return trim($group_display_name);
    }

    /**
     * @param array $sub_ids
     * @return array
     */
    public static function getSortedArrayList(array $sub_ids = []): array
    {
        $list = [];
        // Return all but sorted!
        if (empty($sub_ids)) {
            $list = PublicationUsageGroup::orderBy(PublicationUsageGroup::SORT_BY)->getArray();
        } else {
            $list = PublicationUsageGroup::where(['id' => $sub_ids], 'IN')->orderBy(PublicationUsageGroup::SORT_BY)->getArray();
        }

        return $list;
    }
}
