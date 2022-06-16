<?php

namespace srag\Plugins\Opencast\Util\Player;

use srag\Plugins\Opencast\Model\Config\PluginConfig;
use xoctRequest;

/**
 * Class LivePlayerDataBuilder
 * @package srag\Plugins\Opencast\Util\Player
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class LivePlayerDataBuilder extends PlayerDataBuilder
{
    public function buildStreamingData(): array
    {
        $episode_json = xoctRequest::root()->episodeJson($this->event->getIdentifier())->get(
            [],
            '',
            PluginConfig::getConfig(PluginConfig::F_PRESENTATION_NODE)
        );

        $episode_data = json_decode($episode_json, true);
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
                                "mimetype" => $track['mimetype'],
                                "isLiveStream" => true
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
                            "mimetype" => $track['mimetype'],
                            "isLiveStream" => true
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
