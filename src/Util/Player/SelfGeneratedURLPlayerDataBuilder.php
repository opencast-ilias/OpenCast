<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Util\Player;

use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\Model\Publication\Media;
use xoctException;
use xoctSecureLink;
use srag\Plugins\Opencast\Model\Publication\PublicationMetadata;

/**
 * Class StreamingPlayerDataBuilder
 *
 * Used when the plugin config "Build streaming urls statically" is active.
 * If Opencast serves the streaming urls in its publications, this is not necessary.
 * But most installations still don't offer that functionality.
 *
 * @package srag\Plugins\Opencast\Util\Player
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class SelfGeneratedURLPlayerDataBuilder extends StandardPlayerDataBuilder
{
    protected function buildStreams(array $media): array
    {
        $event_id = $this->event->getIdentifier();
        $duration = 0;

        $streams = [];
        foreach ($media as $medium) {
            $duration = $duration ?: $medium->getDuration();
            [$hls_url, $dash_url] = $this->buildStreamingUrls($medium, $event_id, $duration);

            $role = $medium->getRole(
            ) !== PublicationMetadata::ROLE_PRESENTATION ? self::ROLE_MASTER : self::ROLE_SLAVE;
            $streams[$role] = [
                "type" => PublicationMetadata::MEDIA_TYPE_VIDEO,
                "content" => $role,
                "sources" => [
                    "hls" => [
                        [
                            "src" => $hls_url,
                            "mimetype" => "application/x-mpegURL"
                        ],
                    ],
                    "dash" => [
                        [
                            "src" => $dash_url,
                            "mimetype" => "application/dash+xml"
                        ]
                    ]
                ],
            ];
        }

        return [$duration, $streams];
    }

    /**
     * @return string[]
     * @throws xoctException
     */
    protected function buildStreamingUrls(Media $medium, string $event_id, int $duration): array
    {
        $smil_url_identifier = ($medium->getRole(
        ) !== PublicationMetadata::ROLE_PRESENTATION ? "_presenter" : "_presentation");
        $streaming_server_url = PluginConfig::getConfig(PluginConfig::F_STREAMING_URL);
        $hls_url = $streaming_server_url . "/smil:engage-player_" . $event_id . $smil_url_identifier . ".smil/playlist.m3u8";
        $dash_url = $streaming_server_url . "/smil:engage-player_" . $event_id . $smil_url_identifier . ".smil/manifest_mpm4sav_mvlist.mpd";

        if (PluginConfig::getConfig(PluginConfig::F_SIGN_PLAYER_LINKS)) {
            $hls_url = xoctSecureLink::signPlayer($hls_url, $duration);
            $dash_url = xoctSecureLink::signPlayer($dash_url, $duration);
        }
        return [$hls_url, $dash_url];
    }
}
