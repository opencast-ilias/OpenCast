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
     * @param $usage
     *
     * @return PublicationUsage|null
     */
    public function getUsage($usage)
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
     * @param $usage
     */
    public function delete($usage)
    {
        $usage = $this->getUsage($usage);
        if (!is_null($usage)) {
            $usage->delete();
        }
    }
}