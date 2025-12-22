<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Util\Player;

use srag\Plugins\Opencast\API\OpencastAPI;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use OpencastApi\Auth\JWT\OcJwtClaim;
use OpencastApi\Util\OcUtils;

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
        // TODO: Major issue in Opencast as it blocks the calls to search endpoints without JWT,
        // however, it returns empty result even with JWT.
        // This issue should be followed from this comment: https://github.com/opencast/opencast/pull/7249#issuecomment-3665403365
        if ($this->api->isJWTActivated()) {
            $oc_claim = new OcJwtClaim();
            $oc_claim->setUserInfoClaims('admin');
            $oc_claim->setRoles(['ROLE_ADMIN', 'ROLE_ANONYMOUS']);
            $episode_data = $this->api->routes()->search->withClaims($oc_claim)->getEpisodes(
                [
                    'id' => 'acca3fc3-8e83-4d8e-a234-c892b3472d80'
                ],
                OpencastAPI::RETURN_ARRAY
            );

            // For the of testing:
            return [
                "jwt_iframe_urls" => []
            ];
        } else {
            $episode_data = $this->api->routes()->search->getEpisodes(
                [
                    'id' => $this->event->getIdentifier()
                ],
                OpencastAPI::RETURN_ARRAY
            );
        }

        // Extracting mediapackage from the search endpoint response using OcUtils class from OpencastApi!
        $media_package = OcUtils::findValueByKey($episode_data, 'mediapackage');

        $source_format = PluginConfig::getConfig(PluginConfig::F_LIVESTREAM_BUFFERED) ? 'hls' : 'hlsLive';
        $streams = [];

        // Take care of duration of JWT exp.
        $duration = (int) $this->event->getScheduling()->getEnd()->getTimestamp() - time();
        $duration_in_seconds = 0;
        if ($duration > 0) {
            $duration_in_seconds = (int) ($duration / 1000);
            // We add 5 minutes buffer time, just in case!
            $duration_in_seconds += (60 * 5);
        }

        if (isset($media_package['media']['track'][0])) {  // multi stream
            foreach ($media_package['media']['track'] as $track) {
                $role = str_contains((string) $track['type'], self::ROLE_MASTER) ? self::ROLE_MASTER : self::ROLE_SLAVE;
                $url = $this->api->attachJwtIntoStaticFileUrlForEvent(
                    $track['url'],
                    $this->event->getIdentifier(),
                    ['read'],
                    $duration_in_seconds
                );
                $streams[$role] = [
                    "content" => $role,
                    "sources" => [
                        $source_format => [
                            [
                                "src" => $url,
                                "mimetype" => $track['mimetype']
                            ]
                        ]
                    ]
                ];
                if (isset($track['video']['resolution'])) {
                    $streams[$role]['sources'][$source_format][0]['res'] = $this->getConsumableResolution(
                        $track['video']['resolution']
                    );
                }
            }
        } else {    // single stream
            $track = $media_package['media']['track'];
            $url = $this->api->attachJwtIntoStaticFileUrlForEvent(
                $track['url'],
                $this->event->getIdentifier(),
                ['read'],
                $duration_in_seconds
            );
            $streams[] = [
                "content" => self::ROLE_MASTER,
                "sources" => [
                    $source_format => [
                        [
                            "src" => $url,
                            "mimetype" => $track['mimetype']
                        ]
                    ]
                ]
            ];
            if (isset($track['video']['resolution'])) {
                $streams[0]['sources'][$source_format][0]['res'] = $this->getConsumableResolution(
                    $track['video']['resolution']
                );
            }
        }

        return [
            "streams" => array_values($streams),
            "metadata" => [
                "title" => $this->event->getTitle(),
                "preview" => ILIAS_HTTP_PATH . ltrim($this->event->publications()->getThumbnailUrl(), '.'),
                "videoid" => $this->event->getIdentifier() ?? '',
                "seriesid" => $this->event->getSeriesIdentifier() ?? ''
            ],
        ];
    }

    private function getConsumableResolution($resolution): array
    {
        $video_res = [
            "w" => '1920',
            "h" => '1080'
        ];
        $resolution_arr = explode('x', (string) $resolution);
        if (count($resolution_arr) == 2) {
            $video_res['w'] = $resolution_arr[0];
            $video_res['h'] = $resolution_arr[1];
        }
        return $video_res;
    }

    /**
     * @inheritdoc
     */
    public function shouldPlayInJWTIframe(): bool
    {
        // Apparently live streams do not work with the iframe JWT player, so we return false here.
        // For Live Streams we generate a JWT long enough for the stream duration and inject it into the url,
        // so that normal player can use it.
        return false;
    }
}
