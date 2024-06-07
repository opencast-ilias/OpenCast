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
use xoctLog;


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

        $frame_list_raw = $this->buildSegments($this->event);
        if ((bool) PluginConfig::getConfig(PluginConfig::F_PAELLA_OCR_TEXT_ENABLE) &&
            ($frame_list_raw = $this->addMpeg7Metadata($this->event, $frame_list_raw))) {
            $data['transcriptions'] = array_values($frame_list_raw);
        }
        $data['frameList'] = array_values($frame_list_raw);

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

        $source_type_master_mapping = [];
        foreach ($media as $medium) {
            $duration = $duration ?: $medium->getDuration();
            $source_type = self::$mimetype_mapping[$medium->getMediatype()];
            if (!isset($sources[$medium->getRole()][$source_type]) || !is_array($sources[$medium->getRole()][$source_type])) {
                $sources[$medium->getRole()][$source_type] = [];
            }
            $is_master_playlist = $medium->isMasterPlaylist();
            if ($is_master_playlist) {
                $source_type_master_mapping[$source_type] = true;
                $sources[$medium->getRole()][$source_type] = [];
            }

            if ($is_master_playlist || empty($source_type_master_mapping[$source_type])) {
                $sources[$medium->getRole()][$source_type][] = $this->buildSource($medium, $duration);
            }
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
        $frame_list_raw = [];
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
            $frame_list_raw = array_map(function (array $segment) {
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
            }, $segments);
        }

        // Clear the ref id from key, to have the timepoint only.
        foreach ($frame_list_raw as $ref => $segment_data) {
            $time_flag = ';time=';
            $time_pos = strpos($ref, $time_flag);
            if ($time_pos !== false) {
                $filtered_key_value = substr($ref, ($time_pos + strlen($time_flag)), 18);
                $frame_list_raw[$filtered_key_value] = $segment_data;
                unset($frame_list_raw[$ref]);
            }
        }

        return $frame_list_raw;
    }

    /**
     * Extract the segments texts from the MPEG7 Catalog XML file loaded from "mpeg7_catalog" publication.
     *
     * @param Event $event the actual event
     * @param array $frame_list_raw the raw frame list array that contains the timepoints as for the keys.
     *
     * @return array the raw frame list array with added text segments.
     * @throws xoctException
     */
    protected function addMpeg7Metadata(Event $event, array $frame_list_raw): array
    {
        $mpeg7_catalog_dom_xml = $this->loadMpeg7CatalogXML($event->publications()->getMpeg7CatalogUrl());
        if (empty($mpeg7_catalog_dom_xml)) {
            return [];
        }
        try {
            $mc_nodes = $mpeg7_catalog_dom_xml->getElementsByTagName('MultimediaContent');
            foreach ($mc_nodes as $mc_node) {
                if ($mc_node->hasChildNodes()) {
                    foreach ($mc_node->childNodes as $child_node) {
                        if (!($child_node->nodeName == 'Video') && !($child_node->nodeName == 'AudioVisual')) {
                            continue;
                        }

                        $td_nodes = $child_node->getElementsByTagName('TemporalDecomposition');
                        $td_node = $td_nodes->length ? $td_nodes->item(0) : null;
                        if (!empty($td_node) && $td_node->hasChildNodes()) {
                            $vs_nodes = $td_node->getElementsByTagName('VideoSegment');
                            if (empty($vs_nodes) || $vs_nodes->length == 0) {
                                continue;
                            }
                            foreach ($vs_nodes as $vs_node) {
                                if (!$vs_node->hasChildNodes()) {
                                    continue;
                                }
                                $mt_nodes = $vs_node->getElementsByTagName('MediaTime');
                                $mt_node = $mt_nodes->length ? $mt_nodes->item(0) : null;
                                if (!empty($mt_node) && $mt_node->hasChildNodes()) {
                                    $mrtp_nodes = $mt_node->getElementsByTagName('MediaRelTimePoint');
                                    $mrtp_node = $mrtp_nodes->length ? $mrtp_nodes->item(0) : null;
                                    if (!empty($mrtp_node)) {
                                        $media_rel_time_point_value = $mrtp_node->nodeValue ?? '';
                                        $segment_text = '';

                                        $std_nodes = $vs_node->getElementsByTagName('SpatioTemporalDecomposition');
                                        $std_node = $std_nodes->length ? $std_nodes->item(0) : null;
                                        if (!empty($std_node)) {
                                            $vt_nodes = $std_node->getElementsByTagName('VideoText');
                                            if (!empty($vt_nodes) && $vt_nodes->length) {
                                                foreach ($vt_nodes as $vt_node) {
                                                    $text_nodes = $vt_node->getElementsByTagName('Text');
                                                    if (!empty($text_nodes) && $text_nodes->length) {
                                                        foreach ($text_nodes as $text_node) {
                                                            if (!empty($segment_text)) {
                                                                $segment_text .= ' ';
                                                            }
                                                            $segment_text .= $text_node->nodeValue ?? '';
                                                        }
                                                    }
                                                }
                                            }
                                        }

                                        // Annotations.
                                        $ta_nodes = $vs_node->getElementsByTagName('TextAnnotation');
                                        if (!empty($ta_nodes) && $ta_nodes->length) {
                                            foreach ($ta_nodes as $ta_node) {
                                                // Keyword annotations
                                                $ka_nodes = $ta_node->getElementsByTagName('KeywordAnnotation');
                                                if (!empty($ka_nodes) && $ka_nodes->length) {
                                                    foreach ($ka_nodes as $ka_node) {
                                                        if (!empty($segment_text)) {
                                                            $segment_text .= ' ';
                                                        }
                                                        $segment_text .= $ka_node->nodeValue ?? '';
                                                    }
                                                }

                                                // Free Text Annotations.
                                                $fta_nodes = $ta_node->getElementsByTagName('FreeTextAnnotation');
                                                if (!empty($fta_nodes) && $fta_nodes->length) {
                                                    foreach ($fta_nodes as $fta_node) {
                                                        if (!empty($segment_text)) {
                                                            $segment_text .= ' ';
                                                        }
                                                        $segment_text .= $fta_node->nodeValue ?? '';
                                                    }
                                                }
                                            }
                                        }

                                        // Now we add the text item into the array of respective frame list segment.
                                        if (isset($frame_list_raw[$media_rel_time_point_value])) {
                                            $frame_list_raw[$media_rel_time_point_value]['text'] = $segment_text;
                                        } else {
                                            // This should never happen,
                                            // but we provide an empty frame with "NO PREVIEW" thumb and the text, if it does!
                                            $time = substr($media_rel_time_point_value, 1, 8);
                                            $time = new DateTime("1970-01-01 $time", new DateTimeZone("UTC"));
                                            $time = $time->getTimestamp();
                                            $frame_list_raw[$media_rel_time_point_value] = [
                                                "id" => "frame_" . $time,
                                                "mimetype" => null,
                                                "time" => $time,
                                                "url" => null,
                                                "thumb" => $event->publications()::NO_PREVIEW
                                            ];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } catch (xoctException $e) {
            xoctLog::getInstance()->writeWarning('adding text segments failed: ' . $e->getMessage());
        }
        return $frame_list_raw;
    }

    /**
     * Loads the XML MPEG7 Catalog from publication url.
     *
     * @param string $mpeg7_catalog_url the url driven from "mpeg7_catalog" publication
     *
     * @return \DOMDocument|null the DOMDocument object or null
     * @throws xoctException
     */
    protected function loadMpeg7CatalogXML(string $mpeg7_catalog_url): ?\DOMDocument
    {
        $mpeg7_catalog_dom_xml = null;
        if (empty($mpeg7_catalog_url)) {
            return $mpeg7_catalog_dom_xml;
        }
        try {
            $mpeg7_catalog_dom_xml = new \DOMDocument('1.0', 'UTF-8');
            $mpeg7_catalog_dom_xml->loadXML(file_get_contents($mpeg7_catalog_url));
        } catch (xoctException $e) {
            xoctLog::getInstance()->writeWarning('loading mpeg7 catalog failed: ' . $e->getMessage());
            $mpeg7_catalog_dom_xml = null;
        }

        return $mpeg7_catalog_dom_xml;
    }
}
