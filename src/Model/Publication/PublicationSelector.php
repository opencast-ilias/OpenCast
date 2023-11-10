<?php

namespace srag\Plugins\Opencast\Model\Publication;

use ilObjOpenCastAccess;
use ilOpenCastPlugin;
use Opis\Closure\SerializableClosure;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\Model\DTO\DownloadDto;
use srag\Plugins\Opencast\Model\Event\Event;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsageRepository;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationSubUsage;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationSubUsageRepository;
use srag\Plugins\Opencast\Model\User\xoctUser;
use stdClass;
use xoctException;
use xoctSecureLink;

/**
 * Class PublicationSelector
 *
 * @package srag\Plugins\Opencast\Model\Config\PublicationUsage
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class PublicationSelector
{
    public const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;
    public const NO_PREVIEW = './Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/images/no_preview.png';
    public const THUMBNAIL_SCHEDULED = './Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/images/thumbnail_scheduled.png';
    public const THUMBNAIL_SCHEDULED_LIVE = './Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/images/thumbnail_scheduled_live.png';
    public const THUMBNAIL_LIVE_RUNNING = './Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/images/thumbnail_live_running.png';
    /**
     * @var self[]
     */
    protected static $instances = [];
    /**
     * @var bool
     */
    protected $loaded = false;
    /**
     * @var Event
     */
    protected $event;
    /**
     * @var Publication[]
     */
    protected $publications;
    /**
     * @var PublicationUsageRepository
     */
    protected $publication_usage_repository;
    /**
     * @var PublicationSubUsageRepository
     */
    protected $publication_sub_usage_repository;
    /**
     * @var Publication[]|Media[]|Attachment[]
     */
    protected $player_publications;
    /**
     * @var Publication[]|Media[]|Attachment[]
     */
    protected $download_publications;
    /**
     * @var Publication[]|Media[]|Attachment[]
     */
    protected $preview_publications;
    /**
     * @var Publication[]|Media[]|Attachment[]
     */
    protected $segment_publications;
    /**
     * @var string
     */
    protected $cutting_url;
    /**
     * @var string
     */
    protected $player_url;
    /**
     * @var string
     */
    protected $annotation_url;
    /**
     * @var string
     */
    protected $thumbnail_url;
    /**
     * @var string
     */
    protected $unprotected_link;
    /**
     * @var SerializableClosure
     */
    private $reference;
    private static $thumbnail_publication_usages = [
        PublicationUsage::USAGE_THUMBNAIL,
        PublicationUsage::USAGE_THUMBNAIL_FALLBACK,
        PublicationUsage::USAGE_THUMBNAIL_FALLBACK_2,
    ];
    /**
     * @var Publication[]|Media[]|Attachment[]
     */
    protected $caption_publications = [];
    /**
     * @var \ilObjUser
     */
    private $user;

    /**
     * PublicationSelector constructor.
     */
    public function __construct(Event $event)
    {
        global $DIC;
        $this->user = $DIC->user();
        $this->event = $event;
        $this->publication_usage_repository = new PublicationUsageRepository();
        $this->publication_sub_usage_repository = new PublicationSubUsageRepository();
    }

    /**
     * @param stdClass[] $publication_data
     *
     * @throws xoctException
     */
    public function loadFromArray(array $publication_data): void
    {
        $publications = [];
        foreach ($publication_data as $p_array) {
            $md = new Publication();
            if ($p_array instanceof stdClass) {
                $md->loadFromStdClass($p_array);
            } else {
                $md->loadFromArray($p_array);
            }
            $publications[] = $md;
        }

        $this->publications = $publications;
    }

    /**
     * @return Publication[]|Media[]|Attachment[]
     * @throws xoctException
     */
    public function getPlayerPublications(): array
    {
        if ($this->player_publications === null) {
            $player_usage = $this->publication_usage_repository->getUsage(PublicationUsage::USAGE_PLAYER);
            if (PluginConfig::getConfig(PluginConfig::F_INTERNAL_VIDEO_PLAYER)) {   // force media for internal player
                $player_usage->setMdType(PublicationUsage::MD_TYPE_MEDIA);
            }
            $this->player_publications = $this->getPublicationMetadataForUsage($player_usage);
        }

        return $this->player_publications;
    }

    /**
     * @return Publication[]|Media[]|Attachment[]
     * @throws xoctException
     */
    public function getDownloadPublications(): array
    {
        if (!isset($this->download_publications)) {
            $pubs = [];
            $download_pub_usage = $this->publication_usage_repository->getUsage(PublicationUsage::USAGE_DOWNLOAD);
            $download_pub_sub_usages = $this->publication_sub_usage_repository->convertSubsToUsage(PublicationUsage::USAGE_DOWNLOAD);
            $download_usages = array_merge([$download_pub_usage], $download_pub_sub_usages);
            foreach ($download_usages as $download_usage) {
                $usage_pubs = $this->getPublicationMetadataForUsage($download_usage);
                if (!empty($usage_pubs)) {
                    $pubs = array_merge($usage_pubs, $pubs);
                }
            }
            if (empty($pubs)) {
                $download_fallback_usage = $this->publication_usage_repository->getUsage(PublicationUsage::USAGE_DOWNLOAD_FALLBACK);
                $download_usages = array_merge([$download_fallback_usage], $download_usages);
                $pubs = $this->getPublicationMetadataForUsage($download_fallback_usage);
            }
            // adding external download source option to the publications.
            $pubs_mapped = array_map(function ($pub) use ($download_usages) {
                $ext_dl_source = false;
                $usage_id = $pub->usage_id;
                $usage_type = $pub->usage_type;
                $usage_filtered = array_filter($download_usages, function ($usage) use ($usage_id, $usage_type) {
                    if ($usage_type == 'sub') {
                        return $usage->isSub() && $usage->getSubId() == $usage_id;
                    } else {
                        return !$usage->isSub() && $usage->getUsageId() == $usage_id;
                    }
                });
                if (!empty($usage_filtered)) {
                    $ext_dl_source = reset($usage_filtered)->isExternalDownloadSource();
                }
                $pub->ext_dl_source = $ext_dl_source;
                return $pub;
            }, $pubs);
            $this->download_publications = $pubs_mapped;
        }

        return $this->download_publications;
    }

    /**
     * @param bool $with_urls
     * @return array $categorized_dtos dtos array with format:
     *      $categorized_dtos[{USAGE TYPE: org/sub}][{usage id (string) or usage sub id (int)}][$downloadDto]
     * @throws xoctException
     */
    public function getDownloadDtos(bool $with_urls = true): array
    {
        $download_publications = $this->getDownloadPublications();
        usort($download_publications, function ($pub1, $pub2): int {
            /** @var $pub1 Publication|Media|Attachment */
            /** @var $pub2 Publication|Media|Attachment */
            if ($pub1 instanceof Media && $pub2 instanceof Media) {
                return $pub2->getHeight() <=> $pub1->getHeight();
            }
            return 0;
        });

        $categorized_dtos = [];
        foreach ($download_publications as $index => $pub) {
            $i = ($index + 1);
            $label = ($pub instanceof Media) ? (!empty($pub->getHeight()) ? $pub->getHeight() . 'p' : 'Download ' . $i) :
                ($pub instanceof Attachment ? $pub->getFlavor() : 'Download ' . $i);
            $label = $label == '1080p' ? ($label . ' (FullHD)') : $label;
            $label = $label == '2160p' ? ($label . ' (UltraHD)') : $label;
            $downloadDto = new DownloadDto(
                $pub->getId(),
                trim($label),
                $with_urls ?
                    (PluginConfig::getConfig(PluginConfig::F_SIGN_DOWNLOAD_LINKS) ?
                        xoctSecureLink::signDownload($pub->getUrl()) : $pub->getUrl())
                    : ''
            );
            if ($pub->usage_type === PublicationUsage::USAGE_TYPE_SUB) {
                $categorized_dtos[PublicationUsage::USAGE_TYPE_SUB][$pub->usage_id][] = $downloadDto;
            } else {
                $categorized_dtos[PublicationUsage::USAGE_TYPE_ORG][$pub->usage_id][] = $downloadDto;
            }
        }

        return $categorized_dtos;
    }

    /**
     * @return Publication[]|Media[]|Attachment[]
     * @throws xoctException
     */
    public function getPreviewPublications(): array
    {
        if ($this->preview_publications === null) {
            $this->preview_publications = $this->getPublicationMetadataForUsage(
                $this->publication_usage_repository->getUsage(PublicationUsage::USAGE_PREVIEW)
            );
        }

        return $this->preview_publications;
    }

    /**
     * @return Attachment[]
     * @throws xoctException
     */
    public function getSegmentPublications(): array
    {
        if ($this->segment_publications === null) {
            $this->segment_publications = $this->getPublicationMetadataForUsage(
                $this->publication_usage_repository->getUsage(PublicationUsage::USAGE_SEGMENTS)
            );
        }

        return $this->segment_publications;
    }

    /**
     * @return null|string
     */
    public function getCuttingLink()
    {
        if ($this->cutting_url === null) {
            $url = str_replace(
                '{event_id}',
                $this->event->getIdentifier(),
                PluginConfig::getConfig(PluginConfig::F_EDITOR_LINK)
            );
            if (!$url) {
                $xoctPublication = $this->getFirstPublicationMetadataForUsage(
                    $this->publication_usage_repository->getUsage(PublicationUsage::USAGE_CUTTING)
                );
                $url = is_null($xoctPublication) ? '' : $xoctPublication->getUrl();
            }
            if (!$url) {
                $base = rtrim(PluginConfig::getConfig(PluginConfig::F_API_BASE), "/");
                $base = str_replace('/api', '', $base);
                $url = $base . '/admin-ng/index.html#!/events/events/' . $this->event->getIdentifier(
                    ) . '/tools/editor';
            }

            $this->cutting_url = $url;
        }

        return $this->cutting_url;
    }

    /**
     * @return Publication
     * @throws xoctException
     */
    public function getPlayerPublication()
    {
        return $this->getFirstPublicationMetadataForUsage(
            $this->publication_usage_repository->getUsage(PublicationUsage::USAGE_PLAYER)
        );
    }

    /**
     * @return null|string
     * @throws xoctException
     */
    public function getPlayerLink()
    {
        if ($this->player_url === null) {
            $url = $this->getPlayerPublication()->getUrl();

            if (PluginConfig::getConfig(PluginConfig::F_SIGN_PLAYER_LINKS)) {
                $this->player_url = xoctSecureLink::signPlayer($url);
            } else {
                $this->player_url = $url;
            }
        }

        return $this->player_url;
    }

    public function getAnnotationPublication()
    {
        return $this->getFirstPublicationMetadataForUsage(
            $this->publication_usage_repository->getUsage(PublicationUsage::USAGE_ANNOTATE)
        );
    }

    public function getLivePublication()
    {
        $livePublicationUsage = $this->publication_usage_repository->getUsage(PublicationUsage::USAGE_LIVE_EVENT);
        return $livePublicationUsage instanceof \srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage ? $this->getFirstPublicationMetadataForUsage(
            $livePublicationUsage
        ) : null;
    }

    /**
     * @param int $ref_id set the ref id if the link should be secured via hash mechanism
     *
     * @return null|string
     * @throws xoctException
     */
    public function getAnnotationLink(int $ref_id = 0)
    {
        $media_object = null;
        $media_url = null;
        if ($this->annotation_url === null) {
            $annotation_publication = $this->getAnnotationPublication();
            if (is_null($annotation_publication)) {
                $this->annotation_url = '';
                return '';
            }

            $url = $annotation_publication->getUrl();
            if (PluginConfig::getConfig(PluginConfig::F_SIGN_ANNOTATION_LINKS)) {
                $this->annotation_url = xoctSecureLink::signAnnotation($url);
            } else {
                $this->annotation_url = $url;
            }

            if ($ref_id > 0 && PluginConfig::getConfig(PluginConfig::F_ANNOTATION_TOKEN_SEC)) {
                $xoctUser = xoctUser::getInstance($this->user);
                // Get Media URL
                $media_objects = $annotation_publication instanceof Publication ? $annotation_publication->getMedia(
                ) : [$annotation_publication];
                //TODO: Get all urls for all mediatypes and compress them to send by URL
                foreach ($media_objects as $media_object) {
                    if ($media_object->getMediatype() == "application/x-mpegURL") {
                        $media_url = $media_object->getUrl();
                    }
                }

                if (PluginConfig::getConfig(PluginConfig::F_SIGN_PLAYER_LINKS)) {
                    // Get duration from metadata
                    $duration = $media_object->duration;

                    // Sign the url and parse variables
                    $media_url_signed = xoctSecureLink::signPlayer($media_url, $duration);
                    $media_url_query = parse_url($media_url_signed, PHP_URL_QUERY);
                    $media_url = $media_url . '&' . $media_url_query;
                }

                // Get user and course ref id
                $user_id = $xoctUser->getIdentifier();
                $is_admin = (int) ilObjOpenCastAccess::hasWriteAccess($ref_id);

                // Create the hash
                $hash_input = $user_id . $ref_id . $is_admin;
                $auth_hash = hash("md5", $hash_input);

                $this->annotation_url = $this->annotation_url . '&mediaURL=' . $media_url . '&refid=' . $ref_id . '&auth=' . $auth_hash;
            }
        }

        return $this->annotation_url;
    }

    /**
     * @return string|null
     */
    public function getUnprotectedLink()
    {
        if ($this->unprotected_link === null) {
            $publication = $this->getFirstPublicationMetadataForUsage(
                $this->publication_usage_repository->getUsage(PublicationUsage::USAGE_UNPROTECTED_LINK)
            );
            $this->unprotected_link = is_null($publication) ? null : $publication->getUrl();
        }
        return $this->unprotected_link;
    }

    /**
     * @return string
     * @throws xoctException
     */
    public function getThumbnailUrl()
    {
        switch ($this->event->getProcessingState()) {
            case Event::STATE_SCHEDULED:
            case Event::STATE_SCHEDULED_OFFLINE:
            case Event::STATE_RECORDING:
                $this->thumbnail_url = self::THUMBNAIL_SCHEDULED;
                return $this->thumbnail_url;
            case Event::STATE_LIVE_SCHEDULED:
            case Event::STATE_LIVE_OFFLINE:
                $this->thumbnail_url = self::THUMBNAIL_SCHEDULED_LIVE;
                return $this->thumbnail_url;
            case Event::STATE_LIVE_RUNNING:
                $this->thumbnail_url = self::THUMBNAIL_LIVE_RUNNING;
                return $this->thumbnail_url;
        }

        foreach (self::$thumbnail_publication_usages as $usage) {
            $publication = $this->getFirstPublicationMetadataForUsage(
                $this->publication_usage_repository->getUsage($usage)
            );
            if ($publication === null) {
                continue;
            }
            $url = $publication->getUrl();
            if (PluginConfig::getConfig(PluginConfig::F_SIGN_THUMBNAIL_LINKS)) {
                $this->thumbnail_url = xoctSecureLink::signThumbnail($url);
            } else {
                $this->thumbnail_url = $url;
            }
            break;
        }

        if (empty($this->thumbnail_url)) {
            $this->thumbnail_url = self::NO_PREVIEW;
        }

        return $this->thumbnail_url;
    }

    /**
     * @param $publication_usage
     *
     * @return Publication[]|Media[]|Attachment[]
     * @throws xoctException
     */
    public function getPublicationMetadataForUsage($publication_usage): array
    {
        if (!$publication_usage instanceof PublicationUsage) {
            return [];
        }
        $usage_type = PublicationUsage::USAGE_TYPE_ORG;
        $usage_id = $publication_usage->getUsageId();
        if ($publication_usage->isSub()) {
            $usage_type = PublicationUsage::USAGE_TYPE_SUB;
            $usage_id = $publication_usage->getSubId();
        }
        /**
         * @var $publication_usage       PublicationUsage
         * @var $attachment             Attachment
         * @var $medium                 Media
         */
        $media = [];
        $attachments = [];
        foreach ($this->getPublications() as $publication) {
            if ($publication->getChannel() === $publication_usage->getChannel()) {
                $media += $publication->getMedia();
                $attachments += $publication->getAttachments();
            }
        }
        // Adding the usage and sub usage flags to both attachments and media.
        $attachments = array_map(function($attachment) use ($usage_type, $usage_id) {
            $attachment->usage_type = $usage_type;
            $attachment->usage_id = $usage_id;
            return $attachment;
        }, $attachments);
        $media = array_map(function($medium) use ($usage_type, $usage_id) {
            $medium->usage_type = $usage_type;
            $medium->usage_id = $usage_id;
            return $medium;
        }, $media);
        $return = [];
        switch ($publication_usage->getMdType()) {
            case PublicationUsage::MD_TYPE_ATTACHMENT:
                foreach ($attachments as $attachment) {
                    switch ($publication_usage->getSearchKey()) {
                        case PublicationUsage::SEARCH_KEY_FLAVOR:
                            if ($this->checkFlavor($attachment->getFlavor(), $publication_usage->getFlavor())) {
                                $result = $this->checkMediaTypes($attachment, $publication_usage);
                                if (!empty($result)) {
                                    $return[] = clone $result;
                                }
                            }
                            break;
                        case PublicationUsage::SEARCH_KEY_TAG:
                            if (in_array($publication_usage->getTag(), $attachment->getTags())) {
                                $result = $this->checkMediaTypes($attachment, $publication_usage);
                                if (!empty($result)) {
                                    $return[] = clone $result;
                                }
                            }
                            break;
                    }
                }
                break;
            case PublicationUsage::MD_TYPE_MEDIA:
                foreach ($media as $medium) {
                    switch ($publication_usage->getSearchKey()) {
                        case PublicationUsage::SEARCH_KEY_FLAVOR:
                            if ($this->checkFlavor($medium->getFlavor(), $publication_usage->getFlavor())) {
                                $result = $this->checkMediaTypes($medium, $publication_usage);
                                if (!empty($result)) {
                                    $return[] = clone $result;
                                }
                            }
                            break;
                        case PublicationUsage::SEARCH_KEY_TAG:
                            if (in_array($publication_usage->getTag(), $medium->getTags())) {
                                $result = $this->checkMediaTypes($medium, $publication_usage);
                                if (!empty($result)) {
                                    $return[] = clone $result;
                                }
                            }
                            break;
                    }
                }
                break;
            case PublicationUsage::MD_TYPE_PUBLICATION_ITSELF:
                foreach ($this->getPublications() as $publication) {
                    if ($publication->getChannel() == $publication_usage->getChannel()) {
                        $publication->usage_type = $usage_type;
                        $publication->usage_id = $usage_id;
                        $return[] = clone $publication;
                    }
                }
                break;
            default:
                return [new Publication()];
        }

        return array_filter($return);
    }

    /**
     * @param $xoctPublicationUsage
     * @return Attachment|Media|Publication|null
     * @throws xoctException
     */
    public function getFirstPublicationMetadataForUsage($xoctPublicationUsage)
    {
        $metadata = $this->getPublicationMetadataForUsage($xoctPublicationUsage);

        return count($metadata) ? array_shift($metadata) : null;
    }

    protected function startsWith(string $haystack, string $needle): bool
    {
        $length = strlen($needle);

        return (substr($haystack, 0, $length) === $needle);
    }

    protected function endsWith(string $haystack, string $needle): bool
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }

    protected function checkFlavor(string $haystack, string $needle): bool
    {
        return ($haystack === $needle)
            || ($this->startsWith($needle, '/') && $this->endsWith($haystack, $needle))
            || ($this->endsWith($needle, '/') && $this->startsWith($haystack, $needle));
    }

    /**
     * @return Publication[]
     */
    public function getPublications(): array
    {
        if (is_null($this->publications)) {
            $reference = $this->reference->getClosure();
            $this->publications = $reference();
        }
        return $this->publications;
    }

    public function setReference(SerializableClosure $reference): void
    {
        $this->reference = $reference;
    }

    /**
     * Returns the publication if the media type matches the usage media type, null otherwise.
     * @param publicationMetadata $publicationType
     * @param PublicationUsage $publication_usage
     *
     * @return publicationMetadata|null
     */
    private function checkMediaTypes(
        publicationMetadata $publicationType,
        PublicationUsage $publication_usage): ?publicationMetadata
    {
        $media_types = $publication_usage->getArrayMediaTypes();
        if (empty($media_types)) {
            return $publicationType;
        }
        if (in_array($publicationType->getMediatype(), $media_types)) {
            return $publicationType;
        }
        return null;
    }

    /**
     * @return Publication[]|Media[]|Attachment[]
     * @throws xoctException
     */
    public function getCaptionPublications(): array
    {
        if (empty($this->caption_publications)) {
            $captions = $this->getPublicationMetadataForUsage(
                $this->publication_usage_repository->getUsage(PublicationUsage::USAGE_CAPTIONS)
            );
            $captions_fallback = $this->getPublicationMetadataForUsage($this->publication_usage_repository->getUsage(PublicationUsage::USAGE_CAPTIONS_FALLBACK));
            $this->caption_publications = array_merge($captions, $captions_fallback);
        }

        return $this->caption_publications;
    }
}
