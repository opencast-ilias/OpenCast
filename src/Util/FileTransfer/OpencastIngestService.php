<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Util\FileTransfer;

use srag\Plugins\Opencast\Model\Event\Request\UploadEventRequest;
use srag\Plugins\Opencast\Util\Transformator\MetadataToXML;
use xoctException;
use srag\Plugins\Opencast\API\API;

class OpencastIngestService
{
    /**
     * @var UploadStorageService
     */
    private $uploadStorageService;
    /**
     * @var API
     */
    protected $api;

    public function __construct(UploadStorageService $uploadStorageService)
    {
        global $opencastContainer;
        $this->api = $opencastContainer[API::class];
        $this->uploadStorageService = $uploadStorageService;
    }

    /**
     * @throws xoctException
     */
    public function ingest(UploadEventRequest $uploadEventRequest): void
    {
        // We need to activate OpencastAPI Ingest.
        $this->api->activateIngest(true);
        $payload = $uploadEventRequest->getPayload();

        // create media package
        $media_package = $this->api->routes()->ingest->createMediaPackage();

        // Metadata
        $media_package = $this->api->routes()->ingest->addDCCatalog(
            $media_package,
            (new MetadataToXML($payload->getMetadata()))->getXML(),
            'dublincore/episode'
        );

        // ACLs (as attachment)
        $media_package = $this->api->routes()->ingest->addAttachment(
            $media_package,
            'security/xacml+episode',
            $this->uploadStorageService->buildACLUploadFile($payload->getAcl())->getFileStream()
        );

        // Subtitles using addTrack ingest method.
        if ($payload->hasSubtitles()) {
            foreach ($payload->getSubtitles() as $lang_code => $subtitle_uploadfile) {
                // Important tags to set for subtitles.
                $tags = [
                    'subtitle',
                    'type:subtitle',
                    'generator-type:manual',
                    "lang:$lang_code",
                ];
                $media_package = $this->api->routes()->ingest->addTrack(
                    $media_package,
                    'captions/source', // Important flavor to set for subtitles.
                    $subtitle_uploadfile->getFileStream(),
                    implode(',', $tags)
                );
            }
        }

        // track
        $media_package = $this->api->routes()->ingest->addTrack(
            $media_package,
            'presentation/source',
            $payload->getPresentation()->getFileStream()
        );

        // ingest
        $media_package = $this->api->routes()->ingest->ingest(
            $media_package,
            $payload->getProcessing()->getWorkflow()
        );

        // When we are done, we deactivate the ingest to keep everything clean.
        $this->api->activateIngest(false);
    }
}
