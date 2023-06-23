<?php

namespace srag\Plugins\Opencast\Util\Player;

use DateTime;
use DateTimeZone;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\Model\Event\Event;
use srag\Plugins\Opencast\Model\Metadata\Metadata;
use srag\Plugins\Opencast\Model\Publication\Attachment;
use srag\Plugins\Opencast\Model\Publication\Media;
use srag\Plugins\Opencast\Model\Publication\publicationMetadata;
use xoctException;
use xoctSecureLink;

/**
 * Class DefaultPlayerDataBuilder
 * @package srag\Plugins\Opencast\Util\Player
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class StandardPlayerDataBuilder extends PlayerDataBuilder
{
    private static $mimetype_mapping = [
        'application/x-mpegURL' => 'hls',
        'application/dash+xml' => 'dash',
        'video/mp4' => 'mp4'
    ];

    private static $role_mapping = [
        publicationMetadata::ROLE_PRESENTER => self::ROLE_MASTER,
        publicationMetadata::ROLE_PRESENTATION => self::ROLE_SLAVE
    ];

    /**
     * @return array
     * @throws xoctException
     */
    public function buildStreamingData(): array
    {
        $media = array_values(array_filter($this->event->publications()->getPlayerPublications(), function (Media $medium) {
            return array_key_exists($medium->getMediatype(), self::$mimetype_mapping);
        }));

        if (empty($media)) {
            throw new xoctException(xoctException::NO_STREAMING_DATA);
        }

        list($duration, $streams) = $this->buildStreams($media);

        $data = [
            "streams" => array_values($streams),
            "metadata" => [
                "title" => $this->event->getTitle(),
                "duration" => $duration,
                "preview" => $this->event->publications()->getThumbnailUrl()
            ]
        ];
        $data['frameList'] = $this->buildSegments($this->event);

        return $data;
    }

    /**
     * @param Media[] $media
     * @return array
     * @throws xoctException
     */
    protected function buildStreams(array $media): array
    {
        $duration = 0;
        $streams = [];
        $sources = [
            publicationMetadata::ROLE_PRESENTER => [],
            publicationMetadata::ROLE_PRESENTATION => []
        ];

        foreach ($media as $medium) {
            $duration = $duration ?: $medium->getDuration();
            $source_type = self::$mimetype_mapping[$medium->getMediatype()];
            if (!is_array($sources[$medium->getRole()][$source_type])) {
                $sources[$medium->getRole()][$source_type] = [];
            }
            $sources[$medium->getRole()][$source_type][] = $this->buildSource($medium, $duration);
        }

        foreach ($sources as $role => $source) {
            if (!empty($source)) {
                $streams[] = [
                    "content" => self::$role_mapping[$role],
                    "sources" => $source
                ];
            }
        }

        return [$duration, $streams];
    }

    /**
     * @param     $medium
     * @param int $duration
     * @return array
     * @throws xoctException
     */
    private function buildSource($medium, int $duration): array
    {
        $url = PluginConfig::getConfig(PluginConfig::F_SIGN_PLAYER_LINKS) ? xoctSecureLink::signPlayer(
            $medium->getUrl(),
            $duration
        ) : $medium->getUrl();
        return [
            "src" => $url,
            "mimetype" => $medium->getMediatype(),
            "res" => [
                "w" => $medium->getWidth(),
                "h" => $medium->getHeight()
            ]
        ];
    }

    /**
     * @param Event $event
     *
     * @return array
     * @throws xoctException
     */
    protected function buildSegments(Event $event): array
    {
        $frameList = [];
        $segments = $event->publications()->getSegmentPublications();
        if (count($segments) > 0) {
            $segments = array_reduce($segments, function (array &$segments, Attachment $segment) {
                if (!isset($segments[$segment->getRef()])) {
                    $segments[$segment->getRef()] = [];
                }
                $segments[$segment->getRef()][$segment->getFlavor()] = $segment;

                return $segments;
            }, []);

            ksort($segments);
            $frameList = array_values(array_map(function (array $segment) {
                if (PluginConfig::getConfig(PluginConfig::F_USE_HIGH_LOW_RES_SEGMENT_PREVIEWS)) {
                    /**
                     * @var Attachment[] $segment
                     */
                    $high = $segment[Metadata::FLAVOR_PRESENTATION_SEGMENT_PREVIEW_HIGHRES];
                    $low = $segment[Metadata::FLAVOR_PRESENTATION_SEGMENT_PREVIEW_LOWRES];
                    if ($high === null || $low === null) {
                        $high = $segment[Metadata::FLAVOR_PRESENTER_SEGMENT_PREVIEW_HIGHRES];
                        $low = $segment[Metadata::FLAVOR_PRESENTER_SEGMENT_PREVIEW_LOWRES];
                    }

                    $time = substr($high->getRef(), strpos($high->getRef(), ";time=") + 7, 8);
                    $time = new DateTime("1970-01-01 $time", new DateTimeZone("UTC"));
                    $time = $time->getTimestamp();

                    $high_url = $high->getUrl();
                    $low_url = $low->getUrl();
                    if (PluginConfig::getConfig(PluginConfig::F_SIGN_THUMBNAIL_LINKS)) {
                        $high_url = xoctSecureLink::signThumbnail($high_url);
                        $low_url = xoctSecureLink::signThumbnail($low_url);
                    }

                    return [
                        "id"       => "frame_" . $time,
                        "mimetype" => $high->getMediatype(),
                        "time"     => $time,
                        "url"      => $high_url,
                        "thumb"    => $low_url
                    ];
                } else {
                    $preview = $segment[Metadata::FLAVOR_PRESENTATION_SEGMENT_PREVIEW];

                    if ($preview === null) {
                        $preview = $segment[Metadata::FLAVOR_PRESENTER_SEGMENT_PREVIEW];
                    }

                    $time = substr($preview->getRef(), strpos($preview->getRef(), ";time=") + 7, 8);
                    $time = new DateTime("1970-01-01 $time", new DateTimeZone("UTC"));
                    $time = $time->getTimestamp();

                    $url = $preview->getUrl();
                    if (PluginConfig::getConfig(PluginConfig::F_SIGN_THUMBNAIL_LINKS)) {
                        $url = xoctSecureLink::signThumbnail($url);
                    }

                    return [
                        "id"       => "frame_" . $time,
                        "mimetype" => $preview->getMediatype(),
                        "time"     => $time,
                        "url"      => $url,
                        "thumb"    => $url
                    ];
                }
            }, $segments));
        }

        return $frameList;
    }
}
