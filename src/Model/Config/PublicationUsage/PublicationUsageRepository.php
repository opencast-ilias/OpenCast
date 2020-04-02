<?php

namespace srag\Plugins\Opencast\Model\Config\PublicationUsage;

/**
 * Class PublicationUsageRepository
 *
 * @package srag\Plugins\Opencast\Model\Config\PublicationUsage
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class PublicationUsageRepository
{

    /**
     * @param string $usage
     *
     * @return PublicationUsage|null
     */
    public function getUsage(string $usage)
    {
        return PublicationUsage::find($usage);
    }


    /**
     * @return array
     */
    public function getMissingUsageIds() : array
    {
        $missing = array_diff(PublicationUsage::$usage_ids, $this->getArray(null, 'usage_id'));

        return $missing;
    }


    /**
     * @param null $key
     * @param null $values
     *
     * @return array
     */
    public function getArray($key = null, $values = null) : array
    {
        return PublicationUsage::getArray($key, $values);
    }


    /**
     * @param string $usage
     */
    public function delete(string $usage)
    {
        $usage = $this->getUsage($usage);
        if (!is_null($usage)) {
            $usage->delete();
        }
    }


    /**
     * @param string $usage
     * @param string $title
     * @param string $description
     * @param string $channel
     * @param int    $md_type
     * @param string $search_key
     * @param string $flavor
     * @param string $tag
     * @param bool   $allow_multiple
     */
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
    ) {
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