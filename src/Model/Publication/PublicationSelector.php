<?php

namespace srag\Plugins\Opencast\Model\Publication;

use ilObjOpenCastAccess;
use ilOpenCastPlugin;
use Opis\Closure\SerializableClosure;
use srag\DIC\OpenCast\DICTrait;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\Model\DTO\DownloadDto;
use srag\Plugins\Opencast\Model\Event\Event;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsageRepository;
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
    use DICTrait;
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
     * PublicationSelector constructor.
     *
     * @param Event $event
     */
    public function __construct(Event $event)
    {
        $this->event = $event;
        $this->publication_usage_repository = new PublicationUsageRepository();
    }


    /**
     * @param stdClass[] $publication_data
     *
     * @throws xoctException
     */
    public function loadFromArray(array $publication_data)
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
        if (!isset($this->player_publications)) {
            $player_usage = $this->publication_usage_repository->getUsage(PublicationUsage::USAGE_PLAYER);
            if (PluginConfig::getConfig(PluginConfig::F_INTERNAL_VIDEO_PLAYER)) {   // force media for internal player
                $player_usage->setMdType(PublicationUsage::MD_TYPE_MEDIA);
            }
            $pubs = $this->getPublicationMetadataForUsage($player_usage);
            $this->player_publications = $pubs;
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
            $pubs = $this->getPublicationMetadataForUsage(
                $this->publication_usage_repository->getUsage(PublicationUsage::USAGE_DOWNLOAD)
            );
            if (empty($pubs)) {
                $pubs = $this->getPublicationMetadataForUsage(
                    $this->publication_usage_repository->getUsage(PublicationUsage::USAGE_DOWNLOAD_FALLBACK)
                );
            }
            $this->download_publications = $pubs;
        }

        return $this->download_publications;
    }

    /**
     * @param bool $with_urls
     * @return DownloadDto[]
     * @throws xoctException
     */
    public function getDownloadDtos(bool $with_urls = true): array
    {
        $download_publications = $this->getDownloadPublications();
        usort($download_publications, function ($pub1, $pub2) {
            /** @var $pub1 Publication|Media|Attachment */
            /** @var $pub2 Publication|Media|Attachment */
            if ($pub1 instanceof Media && $pub2 instanceof Media) {
                if ($pub1->getHeight() == $pub2->getHeight()) {
                    return 0;
                }
                return ($pub1->getHeight() > $pub2->getHeight()) ? -1 : 1;
            }
            return 0;
        });
        return array_map(function ($pub, $i) use ($with_urls) {
            /** @var $pub Publication|Media|Attachment */
            $label = ($pub instanceof Media) ? $pub->getHeight() . 'p' :
                ($pub instanceof Attachment ? $pub->getFlavor() : 'Download ' . $i);
            $label = $label == '1080p' ? ($label . ' (FullHD)') : $label;
            $label = $label == '2160p' ? ($label . ' (UltraHD)') : $label;
            return new DownloadDto(
                $pub->getId(),
                $label,
                $with_urls ?
                    (PluginConfig::getConfig(PluginConfig::F_SIGN_DOWNLOAD_LINKS) ?
                        xoctSecureLink::signDownload($pub->getUrl()) : $pub->getUrl())
                    : ''
            );
        }, $download_publications, array_keys($download_publications));
    }


    /**
     * @return Publication[]|Media[]|Attachment[]
     * @throws xoctException
     */
    public function getPreviewPublications(): array
    {
        if (!isset($this->preview_publications)) {
            $pubs = $this->getPublicationMetadataForUsage($this->publication_usage_repository->getUsage(PublicationUsage::USAGE_PREVIEW));
            $this->preview_publications = $pubs;
        }

        return $this->preview_publications;
    }


    /**
     * @return Attachment[]
     * @throws xoctException
     */
    public function getSegmentPublications(): array
    {
        if (!isset($this->segment_publications)) {
            $pubs = $this->getPublicationMetadataForUsage($this->publication_usage_repository->getUsage(PublicationUsage::USAGE_SEGMENTS));
            $this->segment_publications = $pubs;
        }

        return $this->segment_publications;
    }


    /**
     * @return null|string
     */
    public function getCuttingLink()
    {
        if (!isset($this->cutting_url)) {
            $url = str_replace('{event_id}', $this->event->getIdentifier(), PluginConfig::getConfig(PluginConfig::F_EDITOR_LINK));
            if (!$url) {
                $xoctPublication = $this->getFirstPublicationMetadataForUsage(
                    $this->publication_usage_repository->getUsage(PublicationUsage::USAGE_CUTTING)
                );
                $url = is_null($xoctPublication) ? '' : $xoctPublication->getUrl();
            }
            if (!$url) {
                $base = rtrim(PluginConfig::getConfig(PluginConfig::F_API_BASE), "/");
                $base = str_replace('/api', '', $base);
                $url = $base . '/admin-ng/index.html#!/events/events/' . $this->event->getIdentifier() . '/tools/editor';
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
        if (!isset($this->player_url)) {
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
        return $livePublicationUsage ? $this->getFirstPublicationMetadataForUsage(
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
        if (!isset($this->annotation_url)) {
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
                $xoctUser = xoctUser::getInstance(self::dic()->user());
                // Get Media URL
                $media_objects = $annotation_publication instanceof Publication ? $annotation_publication->getMedia() : [$annotation_publication];
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
        if (!isset($this->unprotected_link)) {
            $publication = $this->getFirstPublicationMetadataForUsage($this->publication_usage_repository->getUsage(PublicationUsage::USAGE_UNPROTECTED_LINK));
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
            $xoctPublication = $this->getFirstPublicationMetadataForUsage(
                $this->publication_usage_repository->getUsage($usage)
            );
            if (is_null($xoctPublication)) {
                continue;
            }
            $url = $xoctPublication->getUrl();
            if (PluginConfig::getConfig(PluginConfig::F_SIGN_THUMBNAIL_LINKS)) {
                $this->thumbnail_url = xoctSecureLink::signThumbnail($url);
            } else {
                $this->thumbnail_url = $url;
            }
            break;
        }

        if (!$this->thumbnail_url) {
            $this->thumbnail_url = self::NO_PREVIEW;
        }

        return $this->thumbnail_url;
    }


    /**
     * @param $PublicationUsage
     *
     * @return Publication[]|Media[]|Attachment[]
     * @throws xoctException
     */
    public function getPublicationMetadataForUsage($PublicationUsage): array
    {
        if (!$PublicationUsage instanceof PublicationUsage) {
            return [];
        }
        /**
         * @var $PublicationUsage       PublicationUsage
         * @var $attachment             Attachment
         * @var $medium                 Media
         */
        $media = [];
        $attachments = [];
        foreach ($this->getPublications() as $publication) {
            if ($publication->getChannel() == $PublicationUsage->getChannel()) {
                $media = array_merge($media, $publication->getMedia());
                $attachments = array_merge($attachments, $publication->getAttachments());
            }
        }
        $return = [];
        switch ($PublicationUsage->getMdType()) {
            case PublicationUsage::MD_TYPE_ATTACHMENT:
                foreach ($attachments as $attachment) {
                    switch ($PublicationUsage->getSearchKey()) {
                        case PublicationUsage::SEARCH_KEY_FLAVOR:
                            if ($this->checkFlavor($attachment->getFlavor(), $PublicationUsage->getFlavor())) {
                                $return[] = $attachment;
                            }
                            break;
                        case PublicationUsage::SEARCH_KEY_TAG:
                            if (in_array($PublicationUsage->getTag(), $attachment->getTags())) {
                                $return[] = $attachment;
                            }
                            break;
                    }
                }
                break;
            case PublicationUsage::MD_TYPE_MEDIA:
                foreach ($media as $medium) {
                    switch ($PublicationUsage->getSearchKey()) {
                        case PublicationUsage::SEARCH_KEY_FLAVOR:
                            if ($this->checkFlavor($medium->getFlavor(), $PublicationUsage->getFlavor())) {
                                $return[] = $medium;
                            }
                            break;
                        case PublicationUsage::SEARCH_KEY_TAG:
                            if (in_array($PublicationUsage->getTag(), $medium->getTags())) {
                                $return[] = $medium;
                            }
                            break;
                    }
                }
                break;
            case PublicationUsage::MD_TYPE_PUBLICATION_ITSELF:
                foreach ($this->getPublications() as $publication) {
                    if ($publication->getChannel() == $PublicationUsage->getChannel()) {
                        $return[] = $publication;
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


    /**
     * @param string $haystack
     * @param string $needle
     *
     * @return bool
     */
    protected function startsWith(string $haystack, string $needle): bool
    {
        $length = strlen($needle);

        return (substr($haystack, 0, $length) === $needle);
    }


    /**
     * @param string $haystack
     * @param string $needle
     *
     * @return bool
     */
    protected function endsWith(string $haystack, string $needle): bool
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }


    /**
     * @param string $haystack
     * @param string $needle
     *
     * @return bool
     */
    protected function checkFlavor(string $haystack, string $needle): bool
    {
        return ($haystack == $needle)
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

    public function setReference(SerializableClosure $reference)
    {
        $this->reference = $reference;
    }
}
