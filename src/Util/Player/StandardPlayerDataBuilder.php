<?php

namespace srag\Plugins\Opencast\Util\Player;

use srag\Plugins\Opencast\Model\Event\Event;
use xoctMedia;
use xoctException;
use xoctConf;
use xoctSecureLink;
use xoctAttachment;
use srag\Plugins\Opencast\Model\Metadata\Metadata;
use DateTime;
use DateTimeZone;

/**
 * Class DefaultPlayerDataBuilder
 * @package srag\Plugins\Opencast\Util\Player
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class StandardPlayerDataBuilder extends PlayerDataBuilder
{
    /**
     * @return array
     * @throws xoctException
     */
    public function buildStreamingData() : array
    {
        $media = $this->event->publications()->getPlayerPublications();
        $media = array_values(array_filter($media, function (xoctMedia $medium) {
            return strpos($medium->getMediatype(), xoctMedia::MEDIA_TYPE_VIDEO) !== false;
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
     * @param array $media
     * @return array
     * @throws xoctException
     */
    protected function buildStreams(array $media) : array
    {
        $duration = 0;
        $streams = [];
        $presenters = [];
        $presentations = [];

        foreach ($media as $medium) {
            $duration = $duration ?: $medium->getDuration();
            $source = $this->buildSource($medium, $duration);
            if ($medium->getRole() == xoctMedia::ROLE_PRESENTATION) {
                $presentations[$medium->getHeight()] = $source;
            } else {
                $presenters[$medium->getHeight()] = $source;
            }
        }

        if (count($presenters) > 0) {
            $streams[] = [
                "type" => xoctMedia::MEDIA_TYPE_VIDEO,
                "content" => self::ROLE_MASTER,
                "sources" => [
                    "mp4" => array_values($presenters)
                ],
            ];
        }
        
        if (count($presentations) > 0) {
            $streams[] = [
                "type" => xoctMedia::MEDIA_TYPE_VIDEO,
                "content" => self::ROLE_SLAVE,
                "sources" => [
                    "mp4" => array_values($presentations)
                ],
            ];
        }

        return array($duration, $streams);
    }

    /**
     * @param     $medium
     * @param int $duration
     * @return array
     * @throws xoctException
     */
    private function buildSource($medium, int $duration) : array
    {
        $url = xoctConf::getConfig(xoctConf::F_SIGN_PLAYER_LINKS) ? xoctSecureLink::signPlayer($medium->getUrl(),
            $duration) : $medium->getUrl();
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
    protected function buildSegments(Event $event) : array
    {
        $frameList = [];
        $segments = $event->publications()->getSegmentPublications();
        if (count($segments) > 0) {
            $segments = array_reduce($segments, function (array &$segments, xoctAttachment $segment) {
                if (!isset($segments[$segment->getRef()])) {
                    $segments[$segment->getRef()] = [];
                }
                $segments[$segment->getRef()][$segment->getFlavor()] = $segment;

                return $segments;
            }, []);

            ksort($segments);
            $frameList = array_values(array_map(function (array $segment) {

                if (xoctConf::getConfig(xoctConf::F_USE_HIGH_LOW_RES_SEGMENT_PREVIEWS)) {
                    /**
                     * @var xoctAttachment[] $segment
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
                    if (xoctConf::getConfig(xoctConf::F_SIGN_THUMBNAIL_LINKS)) {
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
                    if (xoctConf::getConfig(xoctConf::F_SIGN_THUMBNAIL_LINKS)) {
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
