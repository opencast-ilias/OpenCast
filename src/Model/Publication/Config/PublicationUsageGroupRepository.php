<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Publication\Config;

use srag\Plugins\Opencast\Util\Locale\LocaleTrait;

/**
 * Class PublicationUsageGroupRepository
 *
 * @author Farbod Zamani Boroujeni <zamani@elan-ev.de>
 */
class PublicationUsageGroupRepository
{
    use LocaleTrait;

    /**
     * Returns the display name of the group, looking for a localised record first.
     */
    public function getDisplayName(int $group_id): string
    {
        $group = PublicationUsageGroup::find($group_id);
        $display_name = $group === null ? '' : (string) $group->getDisplayName();

        if ($display_name !== '') {
            $display_name = $this->getLocaleString(
                strtolower($display_name),
                PublicationUsageGroup::DISPLAY_NAME_LANG_MODULE,
                $display_name
            );
        }

        if (trim($display_name) === '') {
            $display_name = $this->getLocaleString('default', PublicationUsageGroup::DISPLAY_NAME_LANG_MODULE);
        }

        return trim($display_name);
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
            return PublicationUsageGroup::orderBy(PublicationUsageGroup::SORT_BY)->getArray();
        }

        return PublicationUsageGroup::where(['id' => $sub_ids], 'IN')->orderBy(
            PublicationUsageGroup::SORT_BY
        )->getArray();
    }
}
