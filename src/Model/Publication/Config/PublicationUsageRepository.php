<?php

namespace srag\Plugins\Opencast\Model\Publication\Config;

use srag\Plugins\Opencast\LegacyHelpers\TranslatorTrait;

/**
 * Class PublicationUsageRepository
 *
 * @package srag\Plugins\Opencast\Model\Config\PublicationUsage
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class PublicationUsageRepository
{
    use TranslatorTrait;
    public function exists(string $usage): bool
    {
        return !is_null(PublicationUsage::find($usage));
    }

    /**
     * @return PublicationUsage|null
     */
    public function getUsage(string $usage)
    {
        return PublicationUsage::find($usage) ?: PublicationUsageDefault::getDefaultUsage($usage);
    }

    public function getMissingUsageIds(): array
    {
        return array_diff(PublicationUsage::$usage_ids, $this->getArray(null, 'usage_id'));
    }

    /**
     * @return array
     */
    public function getSubAllowedUsageIds(): array
    {
        $sub_allowed_configured = array_intersect(PublicationUsage::$sub_allowed_usage_ids, $this->getArray(null, 'usage_id'));
        return $sub_allowed_configured;
    }


    /**
     * @param null $key
     * @param null $values
     */
    public function getArray($key = null, $values = null): array
    {
        return PublicationUsage::getArray($key, $values);
    }

    public function delete(string $usage_id): void
    {
        $usage = $this->getUsage($usage_id);
        if (!is_null($usage)) {
            $usage->delete();
            // Also deleting the sub-usages.
            foreach (PublicationSubUsage::where(['parent_usage_id' => $usage_id])->get() as $psu) {
                $psu->delete();
            }
        }
    }

    public function store(
        string $usage,
        string $title,
        string $display_name = '',
        string $description,
        string $group_id = null,
        string $channel,
        int $md_type,
        string $search_key = '',
        string $flavor = '',
        string $tag = '',
        bool $allow_multiple = false,
        string $mediatype = '',
        bool $ignore_object_settings = false,
        bool $ext_dl_source = false
    ): void {
        /** @var PublicationUsage $usage */
        $usage = PublicationUsage::findOrGetInstance($usage);
        $usage->setTitle($title);
        $usage->setDisplayName($display_name);
        $usage->setDescription($description);
        $usage->setGroupId($group_id);
        $usage->setChannel($channel);
        $usage->setMdType($md_type);
        $usage->setSearchKey($search_key);
        $usage->setFlavor($flavor);
        $usage->setTag($tag);
        $usage->setAllowMultiple($allow_multiple);
        $usage->setMediaType($mediatype);
        $usage->setIgnoreObjectSettings($ignore_object_settings);
        $usage->setExternalDownloadSource($ext_dl_source);
        $usage->store();
    }

    /**
     * Returns the display name of the group but by looking for a record also in localization.
     * @param string $usage
     * @return string
     */
    public function getDisplayName(string $usage): string
    {
        $display_name = '';
        $usage = $this->getUsage($usage);
        if (!empty($usage->getDisplayName())) {
            $display_name = $usage->getDisplayName();
            $translated_display_name = $this->translate(strtolower($display_name), PublicationUsage::DISPLAY_NAME_LANG_MODULE);
            if (strpos($translated_display_name, 'MISSING') === false) {
                $display_name = $translated_display_name;
            }
        }
        return trim($display_name);
    }
}
