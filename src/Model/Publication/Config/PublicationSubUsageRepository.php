<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Publication\Config;

use srag\Plugins\Opencast\Util\Locale\LocaleTrait;

/**
 * Class PublicationSubUsageRepository
 *
 * @author Farbod Zamani Boroujeni <zamani@elan-ev.de>
 */
class PublicationSubUsageRepository
{
    use LocaleTrait;

    /**
     * Returns the display name of the sub-usage but by looking for a record also in localization.
     * @param int $sub_id
     * @return string
     */
    public function getDisplayName(int $sub_id): string
    {
        $display_name = '';
        $sub_usage = PublicationSubUsage::find($sub_id);
        if (!empty($sub_usage) && !empty($sub_usage->getDisplayName())) {
            $display_name = $sub_usage->getDisplayName();
            $display_name = $this->getLocaleString(
                strtolower($display_name),
                PublicationSubUsage::DISPLAY_NAME_LANG_MODULE,
                $display_name
            );
        }
        return trim($display_name);
    }

    /**
     * Generates the title of th sub-usage by calculating the number of subs for the specified usage.
     * @param string $parent_usage_id
     * @param string $title_text
     * @return string
     */
    public function generateTitle(string $parent_usage_id, string $title_text): string
    {
        $count_subs = PublicationSubUsage::where(['parent_usage_id' => $parent_usage_id])->count();
        return $title_text . " (" . $this->getLocaleString('publication_usage_sub') . "-" . ($count_subs + 1) . ")";
    }

    /**
     * Return a list of sub usages based on the parent usage id.
     * @param string $parent_usage_id
     * @return array
     */
    public function getSubUsages($parent_usage_id): array
    {
        return PublicationSubUsage::where(['parent_usage_id' => $parent_usage_id])->get();
    }

    /**
     * Return a list of sub usages based on the parent usage id.
     * @param string $parent_usage_id
     * @return array
     */
    public function convertSubsToUsage($parent_usage_id): array
    {
        $subs = $this->getSubUsages($parent_usage_id);
        $usages = [];
        foreach ($subs as $sub) {
            if ($usage = $this->convertSingleSubToUsage($sub->getId())) {
                $usages[] = $usage;
            }
        }
        return $usages;
    }

    /**
     * Return a single usage converted from sub usage
     * INFO: the return object of type PublicationUsage has two additional parameters to be consumed during the process:
     *  - is_sub
     *  - sub_id
     * @param int $sub_id
     * @return PublicationUsage|null
     */
    public function convertSingleSubToUsage($sub_id): ?PublicationUsage
    {
        $sub = PublicationSubUsage::find($sub_id);
        if (!empty($sub) && !empty($sub->getParentUsageId())) {
            $usage = new PublicationUsage();
            $usage->setUsageId($sub->getParentUsageId());
            $usage->setTitle($sub->getTitle());
            $usage->setDisplayName($sub->getDisplayName());
            $usage->setDescription($sub->getDescription());
            $usage->setGroupId($sub->getGroupId());
            $usage->setChannel($sub->getChannel());
            $usage->setMdType($sub->getMdType());
            $usage->setSearchKey($sub->getSearchKey());
            $usage->setFlavor($sub->getFlavor());
            $usage->setTag($sub->getTag());
            $usage->setAllowMultiple($sub->isAllowMultiple());
            $usage->setMediaType($sub->getMediaType());
            $usage->setOverwriteDownloadPerm($sub->overwriteDownloadPerm());
            $usage->setExternalDownloadSource($sub->isExternalDownloadSource());
            // Add extra tracking information.
            $usage->setAsSub(true);
            $usage->setSubId($sub->getId());
            return $usage;
        }
        return null;
    }
}
