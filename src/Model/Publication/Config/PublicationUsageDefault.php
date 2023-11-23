<?php

namespace srag\Plugins\Opencast\Model\Publication\Config;

use srag\Plugins\Opencast\Model\Config\PluginConfig;

/**
 * Class PublicationUsageDefault
 * @package srag\Plugins\Opencast\Model\Config\PublicationUsage
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class PublicationUsageDefault extends PublicationUsage
{
    /**
     * @var array
     */
    protected static $default_values;

    protected static function initDefaultValues()
    {
        if (is_null(self::$default_values)) {
            $internal_player = PluginConfig::getConfig(PluginConfig::F_INTERNAL_VIDEO_PLAYER);
            self::$default_values = [
                PublicationUsage::USAGE_PLAYER => [
                    'channel' => ($internal_player ? 'api' : 'engage-player'),
                    'md_type' => ($internal_player ? PublicationUsage::MD_TYPE_MEDIA : PublicationUsage::MD_TYPE_PUBLICATION_ITSELF),
                    'search_key' => 'tag',
                    'flavor' => '',
                    'tag' => 'engage-streaming'
                ],
                PublicationUsage::USAGE_THUMBNAIL => [
                    'channel' => 'api',
                    'md_type' => PublicationUsage::MD_TYPE_ATTACHMENT,
                    'search_key' => 'flavor',
                    'flavor' => 'presentation/player+preview',
                    'tag' => ''
                ],
                PublicationUsage::USAGE_THUMBNAIL_FALLBACK => [
                    'channel' => 'api',
                    'md_type' => PublicationUsage::MD_TYPE_ATTACHMENT,
                    'search_key' => 'flavor',
                    'flavor' => 'presenter/player+preview',
                    'tag' => ''
                ],
                PublicationUsage::USAGE_THUMBNAIL_FALLBACK_2 => [
                    'channel' => 'api',
                    'md_type' => PublicationUsage::MD_TYPE_ATTACHMENT,
                    'search_key' => 'flavor',
                    'flavor' => 'presentation/search+preview',
                    'tag' => ''
                ],
            ];

            $pub_repository = new PublicationUsageRepository();
            $player_channel = $pub_repository->getUsage(PublicationUsage::USAGE_PLAYER)->getChannel();
            self::$default_values[PublicationUsage::USAGE_PREVIEW] = [
                'channel' => $player_channel,
                'md_type' => PublicationUsage::MD_TYPE_ATTACHMENT,
                'search_key' => 'flavor',
                'flavor' => '/player+preview',
                'tag' => ''
            ];
            self::$default_values[PublicationUsage::USAGE_SEGMENTS] = [
                'channel' => $player_channel,
                'md_type' => PublicationUsage::MD_TYPE_ATTACHMENT,
                'search_key' => 'flavor',
                'flavor' => '/segment+preview',
                'tag' => ''
            ];
        }
    }

    /**
     * @return PublicationUsage|null
     */
    public static function getDefaultUsage(string $usage)
    {
        self::initDefaultValues();
        $defaults = self::$default_values[$usage];
        if (is_null($defaults)) {
            return null;
        }
        $publication_usage = new PublicationUsage();
        $publication_usage->setUsageId($usage);
        $publication_usage->setChannel($defaults['channel']);
        $publication_usage->setMdType($defaults['md_type']);
        $publication_usage->setSearchKey($defaults['search_key']);
        $publication_usage->setFlavor($defaults['flavor']);
        $publication_usage->setTag($defaults['tag']);
        return $publication_usage;
    }
}
