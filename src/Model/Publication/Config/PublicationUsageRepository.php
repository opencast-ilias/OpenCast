<?php

namespace srag\Plugins\Opencast\Model\Publication\Config;

/**
 * Class PublicationUsageRepository
 *
 * @package srag\Plugins\Opencast\Model\Config\PublicationUsage
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class PublicationUsageRepository
{
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
     * @param null $key
     * @param null $values
     */
    public function getArray($key = null, $values = null): array
    {
        return PublicationUsage::getArray($key, $values);
    }

    public function delete(string $usage): void
    {
        $usage = $this->getUsage($usage);
        if (!is_null($usage)) {
            $usage->delete();
        }
    }

    public function store(
        string $usage,
        string $title,
        string $description,
        string $channel,
        int $md_type,
        string $search_key = '',
        string $flavor = '',
        string $tag = '',
        bool $allow_multiple = false
    ): void {
        /** @var PublicationUsage $usage */
        $usage = PublicationUsage::findOrGetInstance($usage);
        $usage->setTitle($title);
        $usage->setDescription($description);
        $usage->setChannel($channel);
        $usage->setMdType($md_type);
        $usage->setSearchKey($search_key);
        $usage->setFlavor($flavor);
        $usage->setTag($tag);
        $usage->setAllowMultiple($allow_multiple);
        $usage->store();
    }
}
