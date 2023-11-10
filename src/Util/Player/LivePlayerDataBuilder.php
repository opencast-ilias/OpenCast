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
        $episode_data = $this->api->routes()->search->getEpisodes(
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
                        "hlsLive" => [
                            [
                                "src" => $track['url'],
                                "mimetype" => $track['mimetype']
                            ]
                        ]
                    ]
                ];
                if (isset($track['video']['resolution'])) {
                    $streams[$role]['sources']['hlsLive'][0]['res'] = $this->getConsumableResolution($track['video']['resolution']);
                }
            }
        } else {    // single stream
            $track = $media_package['media']['track'];
            $streams[] = [
                "content" => self::ROLE_MASTER,
                "sources" => [
                    "hlsLive" => [
                        [
                            "src" => $track['url'],
                            "mimetype" => $track['mimetype']
                        ]
                    ]
                ]
            ];
            if (isset($track['video']['resolution'])) {
                $streams[0]['sources']['hlsLive'][0]['res'] = $this->getConsumableResolution($track['video']['resolution']);
            }
        }

        return [
            "streams" => array_values($streams),
            "metadata" => [
                "title" => $this->event->getTitle(),
                "preview" => ILIAS_HTTP_PATH . ltrim($this->event->publications()->getThumbnailUrl(), '.'),
            ],
        ];
    }

    private function getConsumableResolution($resolution) {
        $video_res = [
            "w" => '1920',
            "h" => '1080'
        ];
        $resolution_arr = explode('x', $resolution);
        if (count($resolution_arr) == 2) {
            $video_res['w'] = $resolution_arr[0];
            $video_res['h'] = $resolution_arr[1];
        }
        return $video_res;
    }
}
