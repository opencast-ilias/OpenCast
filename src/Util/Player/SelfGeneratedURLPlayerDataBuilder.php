<?php

namespace srag\Plugins\Opencast\Util\Player;

use xoctMedia;
use xoctException;
use xoctConf;
use xoctSecureLink;

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

    protected function buildStreams(array $media) : array
    {
        $event_id = $this->event->getIdentifier();
        $duration = 0;

        $streams = [];
        foreach ($media as $medium) {
            $duration = $duration ?: $medium->getDuration();
            list($hls_url, $dash_url) = $this->buildStreamingUrls($medium, $event_id, $duration);

            $role = $medium->getRole() !== xoctMedia::ROLE_PRESENTATION ? self::ROLE_MASTER : self::ROLE_SLAVE;
            $streams[$role] = [
                "type" => xoctMedia::MEDIA_TYPE_VIDEO,
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

        return array($duration, $streams);
    }

    /**
     * @param xoctMedia $medium
     * @param string    $event_id
     * @param int       $duration
     * @return string[]
     * @throws xoctException
     */
    protected function buildStreamingUrls(xoctMedia $medium, string $event_id, int $duration) : array
    {
        $smil_url_identifier = ($medium->getRole() !== xoctMedia::ROLE_PRESENTATION ? "_presenter" : "_presentation");
        $streaming_server_url = xoctConf::getConfig(xoctConf::F_STREAMING_URL);
        $hls_url = $streaming_server_url . "/smil:engage-player_" . $event_id . $smil_url_identifier . ".smil/playlist.m3u8";
        $dash_url = $streaming_server_url . "/smil:engage-player_" . $event_id . $smil_url_identifier . ".smil/manifest_mpm4sav_mvlist.mpd";

        if (xoctConf::getConfig(xoctConf::F_SIGN_PLAYER_LINKS)) {
            $hls_url = xoctSecureLink::signPlayer($hls_url, $duration);
            $dash_url = xoctSecureLink::signPlayer($dash_url, $duration);
        }
        return array($hls_url, $dash_url);
    }
}
