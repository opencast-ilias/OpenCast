<?php

namespace srag\Plugins\Opencast\Model\Config\PublicationUsage;

use ilObjOpenCastAccess;
use ilObjOpenCastGUI;
use ilOpenCastPlugin;
use ilRepositoryGUI;
use srag\DIC\OpenCast\DICTrait;
use stdClass;
use xoctAttachment;
use xoctConf;
use xoctEvent;
use xoctEventGUI;
use xoctException;
use xoctMedia;
use xoctPlayerGUI;
use xoctPublication;
use xoctPublicationUsageFormGUI;
use xoctRequest;
use xoctSecureLink;
use xoctUser;
use xoctPublicationMetadata;
use srag\Plugins\Opencast\Model\DTO\DownloadDto;

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
    const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;
    const NO_PREVIEW = './Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/images/no_preview.png';
    const THUMBNAIL_SCHEDULED = './Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/images/thumbnail_scheduled.png';
    const THUMBNAIL_SCHEDULED_LIVE = './Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/images/thumbnail_scheduled_live.png';
    const THUMBNAIL_LIVE_RUNNING = './Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/images/thumbnail_live_running.png';
    /**
     * @var self[]
     */
    protected static $instances = [];
    /**
     * @var bool
     */
    protected $loaded = false;
    /**
     * @var xoctEvent
     */
    protected $event;
    /**
     * @var xoctPublication[]
     */
    protected $publications = [];
    /**
     * @var PublicationUsageRepository
     */
    protected $publication_usage_repository;
    /**
     * @var xoctPublication[]|xoctMedia[]|xoctAttachment[]
     */
    protected $player_publications;
    /**
     * @var xoctPublication[]|xoctMedia[]|xoctAttachment[]
     */
    protected $download_publications;
    /**
     * @var xoctPublication[]|xoctMedia[]|xoctAttachment[]
     */
    protected $preview_publications;
    /**
     * @var xoctPublication[]|xoctMedia[]|xoctAttachment[]
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
     * PublicationSelector constructor.
     *
     * @param xoctEvent $event
     */
    public function __construct(xoctEvent $event)
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
        $publications = array();
        foreach ($publication_data as $p_array) {
            $md = new xoctPublication();
            if ($p_array instanceof stdClass) {
                $md->loadFromStdClass($p_array);
            } else {
                $md->loadFromArray($p_array);
            }
            $publications[] = $md;
        }

        $this->publications = $publications;
        $this->loaded = true;
    }


    /**
     * @throws xoctException
     */
    protected function loadPublications()
    {
        $data = json_decode(xoctRequest::root()->events($this->event->getIdentifier())->publications()->get());

        $publications = array();
        foreach ($data as $d) {
            $p = new xoctPublication();
            $p->loadFromStdClass($d);
            $publications[] = $p;
        }
        $this->publications = $publications;
        $this->loaded = true;
    }


    /**
     * @return xoctPublication[]|xoctMedia[]|xoctAttachment[]
     * @throws xoctException
     */
    public function getPlayerPublications() : array
    {
        if (!isset($this->player_publications)) {
            $player_usage = $this->publication_usage_repository->getUsage(PublicationUsage::USAGE_PLAYER);
            if (xoctConf::getConfig(xoctConf::F_INTERNAL_VIDEO_PLAYER)) {   // force media for internal player
                $player_usage->setMdType(PublicationUsage::MD_TYPE_MEDIA);
            }
            $pubs = $this->getPublicationMetadataForUsage($player_usage);
            $this->player_publications = $pubs;
        }

        return $this->player_publications;
    }


    /**
     * @return xoctPublication[]|xoctMedia[]|xoctAttachment[]
     * @throws xoctException
     */
    public function getDownloadPublications() : array
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
    public function getDownloadDtos(bool $with_urls = true) : array {
        $download_publications = $this->getDownloadPublications();
        usort($download_publications, function ($pub1, $pub2) {
            /** @var $pub1 xoctPublication|xoctMedia|xoctAttachment */
            /** @var $pub2 xoctPublication|xoctMedia|xoctAttachment */
            if ($pub1 instanceof xoctMedia && $pub2 instanceof xoctMedia) {
                if ($pub1->getHeight() == $pub2->getHeight()) {
                    return 0;
                }
                return ($pub1->getHeight() > $pub2->getHeight()) ? -1 : 1;
            }
            return 0;
        });
        return array_map(function($pub, $i) use ($with_urls) {
            /** @var $pub xoctPublication|xoctMedia|xoctAttachment */
            $label = ($pub instanceof xoctMedia) ? $pub->getHeight() . 'p' :
                ($pub instanceof xoctAttachment ? $pub->getFlavor() : 'Download ' . $i);
            $label = $label == '1080p' ? ($label . ' (FullHD)') : $label;
            $label = $label == '2160p' ? ($label . ' (UltraHD)') : $label;
            return new DownloadDto(
                $pub->getId(),
                $label,
                $with_urls ?
                    (xoctConf::getConfig(xoctConf::F_SIGN_DOWNLOAD_LINKS) ?
                        xoctSecureLink::signDownload($pub->getUrl()) : $pub->getUrl())
                    : ''
            );
        }, $download_publications, array_keys($download_publications));
    }


    /**
     * @return xoctPublication[]|xoctMedia[]|xoctAttachment[]
     * @throws xoctException
     */
    public function getPreviewPublications() : array
    {
        if (!isset($this->preview_publications)) {
            $pubs = $this->getPublicationMetadataForUsage($this->publication_usage_repository->getUsage(PublicationUsage::USAGE_PREVIEW));
            $this->preview_publications = $pubs;
        }

        return $this->preview_publications;
    }


    /**
     * @return xoctAttachment[]
     * @throws xoctException
     */
    public function getSegmentPublications() : array
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
            $url = str_replace('{event_id}', $this->event->getIdentifier(), xoctConf::getConfig(xoctConf::F_EDITOR_LINK));
            if (!$url) {
                $xoctPublication = $this->getFirstPublicationMetadataForUsage(
                    $this->publication_usage_repository->getUsage(PublicationUsage::USAGE_CUTTING)
                );
                $url = is_null($xoctPublication) ? '' : $xoctPublication->getUrl();
            }
            if (!$url) {
                $base = rtrim(xoctConf::getConfig(xoctConf::F_API_BASE), "/");
                $base = str_replace('/api', '', $base);
                $url = $base . '/admin-ng/index.html#!/events/events/' . $this->event->getIdentifier() . '/tools/editor';
            }

            $this->cutting_url = $url;
        }

        return $this->cutting_url;
    }

    /**
     * @return xoctPublication
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

            if (xoctConf::getConfig(xoctConf::F_SIGN_PLAYER_LINKS)) {
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
            if (xoctConf::getConfig(xoctConf::F_SIGN_ANNOTATION_LINKS)) {
                $this->annotation_url = xoctSecureLink::signAnnotation($url);
            } else {
                $this->annotation_url = $url;
            }

            if ($ref_id > 0 && xoctConf::getConfig(xoctConf::F_ANNOTATION_TOKEN_SEC)) {
                $xoctUser = xoctUser::getInstance(self::dic()->user());
                // Get Media URL
                $media_object = $annotation_publication instanceof xoctPublication ? $annotation_publication->getMedia() : [$annotation_publication];
                $media_object = array_shift($media_object);
                $media_url = $media_object->getUrl();

                if (xoctConf::getConfig(xoctConf::F_SIGN_PLAYER_LINKS)) {
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
        if (in_array(
            $this->event->getProcessingState(),
            [xoctEvent::STATE_SCHEDULED, xoctEvent::STATE_SCHEDULED_OFFLINE, xoctEvent::STATE_RECORDING]
        )
        ) {
            $this->thumbnail_url = self::THUMBNAIL_SCHEDULED;

            return $this->thumbnail_url;
        }

        if (in_array(
            $this->event->getProcessingState(),
            [xoctEvent::STATE_LIVE_SCHEDULED, xoctEvent::STATE_LIVE_OFFLINE]
        )
        ) {
            $this->thumbnail_url = self::THUMBNAIL_SCHEDULED_LIVE;

            return $this->thumbnail_url;
        }

        if ($this->event->getProcessingState() == xoctEvent::STATE_LIVE_RUNNING) {
            $this->thumbnail_url = self::THUMBNAIL_LIVE_RUNNING;

            return $this->thumbnail_url;
        }

        $possible_publications = array(
            PublicationUsage::USAGE_THUMBNAIL,
            PublicationUsage::USAGE_THUMBNAIL_FALLBACK,
            PublicationUsage::USAGE_THUMBNAIL_FALLBACK_2,
        );

        $i = 0;
        while (!$this->thumbnail_url && $i < count($possible_publications)) {
            $xoctPublication = $this->getFirstPublicationMetadataForUsage(
                $this->publication_usage_repository->getUsage($possible_publications[$i])
            );
            if (is_null($xoctPublication)) {
                continue;
            }
            $url = $xoctPublication->getUrl();
            if (xoctConf::getConfig(xoctConf::F_SIGN_THUMBNAIL_LINKS)) {
                $this->thumbnail_url = xoctSecureLink::signThumbnail($url);
            } else {
                $this->thumbnail_url = $url;
            }
            $i++;
        }
        if (!$this->thumbnail_url) {
            $this->thumbnail_url = self::NO_PREVIEW;
        }

        return $this->thumbnail_url;
    }


    /**
     * @param $PublicationUsage
     *
     * @return xoctPublication[]|xoctMedia[]|xoctAttachment[]
     * @throws xoctException
     */
    public function getPublicationMetadataForUsage($PublicationUsage) : array
    {
        if (!$PublicationUsage instanceof PublicationUsage) {
            return [];
        }
        /**
         * @var $PublicationUsage       PublicationUsage
         * @var $attachment             xoctAttachment
         * @var $medium                 xoctMedia
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
                return [new xoctPublication()];
        }

        return array_filter($return);
    }

    /**
     * @param $xoctPublicationUsage
     * @return mixed|xoctPublication
     * @throws xoctException
     */
    public function getFirstPublicationMetadataForUsage($xoctPublicationUsage)
    {
        $metadata = $this->getPublicationMetadataForUsage($xoctPublicationUsage);

        return count($metadata) ? array_shift($metadata) : new xoctPublication();
    }


    /**
     * @param string $haystack
     * @param string $needle
     *
     * @return bool
     */
    protected function startsWith(string $haystack, string $needle) : bool
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
    protected function endsWith(string $haystack, string $needle) : bool
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
    protected function checkFlavor(string $haystack, string $needle) : bool
    {
        return ($haystack == $needle)
            || ($this->startsWith($needle, '/') && $this->endsWith($haystack, $needle))
            || ($this->endsWith($needle, '/') && $this->startsWith($haystack, $needle));
    }

    /**
     * @return xoctPublication[]
     * @throws xoctException
     */
    public function getPublications() : array
    {
        if (!$this->loaded) {
            $this->loadPublications();
        }
        return $this->publications;
    }
}
