<?php

namespace srag\Plugins\Opencast\Util\Player;

use srag\Plugins\Opencast\Model\Config\PluginConfig;
use xoctOpencastApi;

/**
 * Class LivePlayerDataBuilder
 * @package srag\Plugins\Opencast\Util\Player
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class LivePlayerDataBuilder extends PlayerDataBuilder
{
    public function buildStreamingData(): array
    {
        $episode_data = xoctOpencastApi::getApi()->search->getEpisodes(
            [
                'id' => $this->event->getIdentifier()
            ],
            xoctOpencastApi::RETURN_ARRAY
        );

        $media_package = $episode_data['search-results']['result']['mediapackage'];

        $streams = [];
        if (isset($media_package['media']['track'][0])) {  // multi stream
            foreach ($media_package['media']['track'] as $track) {
                $role = strpos($track['type'], self::ROLE_MASTER) !== false ? self::ROLE_MASTER : self::ROLE_SLAVE;
                $streams[$role] = [
                    "content" => $role,
                    "sources" => [
                        "hls" => [
                            [
                                "src" => $track['url'],
                                "mimetype" => $track['mimetype']
                                //"isLiveStream" => true
                                //removed isLivestream so that the playback-bar and forward/backward-buttons show up in livestreams.
                                //Otherwise the paellaplayer will not show these buttons in livestreams.
                            ]
                        ]
                    ]
                ];
            }
        } else {    // single stream
            $track = $media_package['media']['track'];
            $streams[] = [
                "content" => self::ROLE_MASTER,
                "sources" => [
                    "hls" => [
                        [
                            "src" => $track['url'],
                            "mimetype" => $track['mimetype']
                            //"isLiveStream" => true
                            //removed isLivestream so that the playback-bar and forward/backward-buttons show up in livestreams.
                            //Otherwise the paellaplayer will not show these buttons in livestreams.
                        ]
                    ]
                ]
            ];
        }

        return [
            "streams" => array_values($streams),
            "metadata" => [
                "title" => $this->event->getTitle(),
            ],
        ];
    }
}
