<?php

namespace srag\Plugins\Opencast\Model\Publication\Config;

use ilOpenCastPlugin;
use srag\DIC\OpenCast\DICTrait;
/**
 * Class PublicationUsageRepository
 *
 * @package srag\Plugins\Opencast\Model\Config\PublicationUsage
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class PublicationUsageRepository
{
    use DICTrait;
    public const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;
    /**
     * @param string $usage
     * @return bool
     */
    public function exists(string $usage): bool
    {
        return !is_null(PublicationUsage::find($usage));
    }

    /**
     * @param string $usage
     *
     * @return PublicationUsage|null
     */
    public function getUsage(string $usage)
    {
        return PublicationUsage::find($usage) ?: PublicationUsageDefault::getDefaultUsage($usage);
    }


    /**
     * @return array
     */
    public function getMissingUsageIds(): array
    {
        $missing = array_diff(PublicationUsage::$usage_ids, $this->getArray(null, 'usage_id'));

        return $missing;
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
     *
     * @return array
     */
    public function getArray($key = null, $values = null): array
    {
        return PublicationUsage::getArray($key, $values);
    }


    /**
     * @param string $usage_id
     */
    public function delete(string $usage_id)
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


    /**
     * @param string $usage
     * @param string $title
     * @param string $display_name
     * @param string $description
     * @param string $group_id
     * @param string $channel
     * @param int    $md_type
     * @param string $search_key
     * @param string $flavor
     * @param string $tag
     * @param bool   $allow_multiple
     * @param string $mimetype
     * @param bool $ignore_object_settings
     */
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
        bool $ignore_object_settings = false
    ) {
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
            $translated_display_name = self::plugin()->translate(strtolower($display_name), PublicationUsage::DISPLAY_NAME_LANG_MODULE);
            if (strpos($translated_display_name, 'MISSING') === false) {
                $display_name = $translated_display_name;
            }
        }
        return trim($display_name);
    }
}
