<?php

namespace srag\Plugins\Opencast\Model\Publication\Config;

/**
 * Class PublicationUsageGroupRepository
 *
 * @author Farbod Zamani Boroujeni <zamani@elan-ev.de>
 */
class PublicationUsageGroupRepository
{
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
