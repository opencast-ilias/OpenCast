<?php

namespace srag\Plugins\Opencast\Util\Player;

use srag\Plugins\Opencast\API\OpencastAPI;

/**
 * Class LivePlayerDataBuilder
 * @package srag\Plugins\Opencast\Util\Player
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class LivePlayerDataBuilder extends PlayerDataBuilder
{
    /**
     * @return array{streams: array<int, array{content: string, sources: array{hls: array<int, array{src: mixed, mimetype: mixed}>}}>, metadata: array{title: string}}
     */
    public function buildStreamingData(): array
    {
        $episode_data = OpencastAPI::getApi()->search->getEpisodes(
            [
                'id' => $this->event->getIdentifier()
            ],
            OpencastAPI::RETURN_ARRAY
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
