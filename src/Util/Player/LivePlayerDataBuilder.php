<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Util\Player;

use srag\Plugins\Opencast\API\OpencastAPI;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\Model\User\xoctUser;
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
        global $DIC;
        $user = xoctUser::getInstance($DIC->user());
        $jwt_iframe_urls = [];
        $fallback_tracks = [];
        if ($this->api->isJWTActivated()) {
            // TODO: Major issue in Opencast as it blocks the calls to search endpoints without JWT,
            // however, it returns empty result even with JWT.
            // This issue should be followed from this comment: https://github.com/opencast/opencast/pull/7249#issuecomment-3665403365
            // As a workaround, we fetch the episode data with admin claims to extract the live publication url, which is required for the JWT Iframe player to work. This is not ideal but seems to be the only way until the issue in Opencast is resolved. Once that issue is resolved, we can simply call the search endpoint.
            $oc_claim = new OcJwtClaim();
            $oc_claim->setUserInfoClaims(
                'unknown-jwt-user',
                'unknown-jwt-user',
                'no-mail@jwt.invalid'
            );
            $user_basic_access_roles = $user->getBasicAccessRoles();
            $user_role_name = $user->getUserRoleName();
            $oc_claim->setRoles(array_unique(array_merge($user_basic_access_roles, [$user_role_name, 'ROLE_ADMIN', 'ROLE_ANONYMOUS', 'ROLE_USER_UNKNOWN_JWT_USER', 'ROLE_JWT_USER'])));
            $event_acl = [
                $this->event->getIdentifier() => 'read',
            ];
            $oc_claim->setEventAcls($event_acl);
            $episode_data = $this->api->routes()->search->withClaims($oc_claim)->getEpisodes(
                [
                    'id' => $this->event->getIdentifier()
                ],
                OpencastAPI::RETURN_ARRAY
            );

            $live_publication = $this->event->publications()->getLivePublication();
            $jwt_iframe_urls[] = $this->api->makeJwtIframeSourceUrl(
                $live_publication->getUrl(),
                $this->event->getIdentifier()
            );

            // A fallback to publication, in case mediapackage fails.
            $media = $live_publication->getMedia();
            $fallback_tracks = [];
            if (empty($episode_data['result']) && !empty($media)) {
                foreach ($media as $medium) {
                    $medium_arr = $medium->getAsArray();
                    $medium_arr['mimetype'] = $medium_arr['mediatype'];
                    $fallback_tracks[] = $medium_arr;
                }
                if (count($fallback_tracks) === 1) {
                    $fallback_tracks = reset($fallback_tracks);
                }
                $episode_data['result']['mediapackage']['media']['track'] = $fallback_tracks;
            }
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
        $urls = [];

        if (isset($media_package['media']['track'][0])) {  // multi stream
            foreach ($media_package['media']['track'] as $track) {
                $role = str_contains((string) $track['type'], self::ROLE_MASTER) ? self::ROLE_MASTER : self::ROLE_SLAVE;
                $url = $track['url'];
                $urls[] = $url;
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
            $url = $track['url'];
            $urls[] = $url;
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
            "jwt" => [
                "jwt_iframe_urls" => $jwt_iframe_urls,
                "urls" => $urls,
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
        return true; // It appears that live streams can be played via /play.
    }
}
