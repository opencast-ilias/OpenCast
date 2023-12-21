<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Util\Player;

use DateTime;
use DateTimeZone;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\Model\Event\Event;
use srag\Plugins\Opencast\Model\Metadata\Metadata;
use srag\Plugins\Opencast\Model\Publication\Attachment;
use srag\Plugins\Opencast\Model\Publication\Media;
use srag\Plugins\Opencast\Model\Publication\PublicationMetadata;
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
        PublicationMetadata::ROLE_PRESENTER => self::ROLE_MASTER,
        PublicationMetadata::ROLE_PRESENTATION => self::ROLE_SLAVE
    ];

    /**
     * @return array{streams: mixed[], metadata: array{title: string, duration: mixed, preview: string}, frameList: mixed[]}
     * @throws xoctException
     */
    public function buildStreamingData(): array
    {
        $media = array_values(
            array_filter($this->event->publications()->getPlayerPublications(), function (Media $medium): bool {
                return array_key_exists($medium->getMediatype(), self::$mimetype_mapping);
            })
        );

        if ($media === []) {
            throw new xoctException(xoctException::NO_STREAMING_DATA);
        }

        [$duration, $streams] = $this->buildStreams($media);

        $data = [
            "streams" => array_values($streams),
            "metadata" => [
                "title" => $this->event->getTitle(),
                "duration" => $duration,
                "preview" => $this->event->publications()->getThumbnailUrl()
            ]
        ];
        $data['frameList'] = $this->buildSegments($this->event);

        $captions = $this->event->publications()->getCaptionPublications();
        $data['captions'] = $this->buildCaptions($captions);

        return $data;
    }

    /**
     * @param array $captions a mixture of Media or Attachment
     * @return array
     * @throws xoctException
     */
    protected function buildCaptions(array $captions): array
    {
        $lang = 'unknown';
        $format = '';
        $url = '';
        $paella_captions = [];
        foreach ($captions as $caption) {
            $tag_info_array = [];

            list($flavor, $sub_flavor) = explode('/', $caption->flavor, 2);
            if ($flavor !== 'captions') {
                continue;
            }

            if ($caption instanceof Media) {
                list($mimefiletype, $format) = explode('/', $caption->mediatype, 2);
                foreach ($caption->tags as $tag) {
                    if (strpos($tag, 'lang:') !== false) {
                        $tag_lang = str_replace('lang:', '', $tag);
                        if (!empty($tag_lang)) {
                            $lang = $tag_lang;
                        }
                    }

                    if (strpos($tag, 'generator-type:') !== false) {
                        $tag_generator_type = str_replace('generator-type:', '', $tag);
                        if (!empty($tag_generator_type)) {
                            $tag_info_array['generator_type'] = $tag_generator_type;
                        }
                    }

                    if (strpos($tag, 'generator:') !== false) {
                        $tag_generator = str_replace('generator:', '', $tag);
                        if (!empty($tag_generator)) {
                            $tag_info_array['generator'] = $tag_generator;
                        }
                    }

                    if (strpos($tag, 'type:') !== false) {
                        $tag_type = str_replace('type:', '', $tag);
                        if (!empty($tag_type)) {
                            $tag_info_array['type'] = $tag_type;
                        }
                    }
                }
            } elseif ($caption instanceof Attachment) {
                list($format, $lang) = explode('+', $sub_flavor, 2);
            }

            $text = $this->buildCaptionText($lang, $tag_info_array);

            $paella_captions[] = [
                'lang' => $lang,
                'text' => $text,
                'format' => $format,
                'url' => $caption->url
            ];
        }

        return $paella_captions;
    }

    /**
     * @param string $lang
     * @param array  $tag_info_array
     * @return string
     * @throws xoctException
     */
    private function buildCaptionText(string $lang, array $tag_info_array): string
    {
        $text_array[] = $lang;
        if (array_key_exists('generator_type', $tag_info_array)) {
            $generator_type = ($tag_info_array['generator_type'] ?? '') === 'auto' ? 'Auto' : 'Manual';
            $text_array[] = "($generator_type)";
        }
        if (array_key_exists('type', $tag_info_array)) {
            $type = ucfirst($tag_info_array['type']);
            $text_array[] = "($type)";
        }
        if (array_key_exists('generator', $tag_info_array)) {
            $generator = ucfirst($tag_info_array['generator']);
            $text_array[] = "($generator)";
        }
        return implode(' - ', $text_array);
    }

    /**
     * @param Media[] $media
     * @throws xoctException
     */
    protected function buildStreams(array $media): array
    {
        $duration = 0;
        $streams = [];
        $sources = [
            PublicationMetadata::ROLE_PRESENTER => [],
            PublicationMetadata::ROLE_PRESENTATION => []
        ];

        foreach ($media as $medium) {
            $duration = $duration ?: $medium->getDuration();
            $source_type = self::$mimetype_mapping[$medium->getMediatype()];
            if (!isset($sources[$medium->getRole()][$source_type])) {
                continue;
            }
            if (!is_array($sources[$medium->getRole()][$source_type])) {
                $sources[$medium->getRole()][$source_type] = [];
            }
            $sources[$medium->getRole()][$source_type][] = $this->buildSource($medium, $duration);
        }

        foreach ($sources as $role => $source) {
            if ($source !== []) {
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
     * @return array{src: mixed, mimetype: mixed, res: array{w: mixed, h: mixed}}
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
     * @throws xoctException
     */
    protected function buildSegments(Event $event): array
    {
        $frameList = [];
        $segments = $event->publications()->getSegmentPublications();
        if ($segments !== []) {
            $segments = array_reduce($segments, function (array $segments, Attachment $segment): array {
                if (!isset($segments[$segment->getRef()])) {
                    $segments[$segment->getRef()] = [];
                }
                $segments[$segment->getRef()][$segment->getFlavor()] = $segment;

                return $segments;
            }, []);

            ksort($segments);
            $frameList = array_values(
                array_map(function (array $segment) {
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
                            "id" => "frame_" . $time,
                            "mimetype" => $high->getMediatype(),
                            "time" => $time,
                            "url" => $high_url,
                            "thumb" => $low_url
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
                            "id" => "frame_" . $time,
                            "mimetype" => $preview->getMediatype(),
                            "time" => $time,
                            "url" => $url,
                            "thumb" => $url
                        ];
                    }
                }, $segments)
            );
        }

        return $frameList;
    }
}
