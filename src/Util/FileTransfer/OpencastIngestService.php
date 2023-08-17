<?php

namespace srag\Plugins\Opencast\Util\FileTransfer;

use srag\Plugins\Opencast\Model\Event\Request\UploadEventRequest;
use srag\Plugins\Opencast\Util\Transformator\MetadataToXML;
use xoctException;
use srag\Plugins\Opencast\API\OpencastAPI;

class OpencastIngestService
{
    /**
     * @var UploadStorageService
     */
    private $uploadStorageService;

    public function __construct(UploadStorageService $uploadStorageService)
    {
        $this->uploadStorageService = $uploadStorageService;
    }

    /**
     * @throws xoctException
     */
    public function ingest(UploadEventRequest $uploadEventRequest): void
    {
        // We need to activate OpencastAPI Ingest.
        OpencastAPI::activateIngest(true);
        $payload = $uploadEventRequest->getPayload();

        // create media package
        $media_package = OpencastAPI::getApi()->ingest->createMediaPackage();

        // Metadata
        $media_package = OpencastAPI::getApi()->ingest->addDCCatalog(
            $media_package,
            (new MetadataToXML($payload->getMetadata()))->getXML(),
            'dublincore/episode'
        );

        // ACLs (as attachment)
        $media_package = OpencastAPI::getApi()->ingest->addAttachment(
            $media_package,
            'security/xacml+episode',
            $this->uploadStorageService->buildACLUploadFile($payload->getAcl())->getFileStream()
        );

        // track
        $media_package = OpencastAPI::getApi()->ingest->addTrack(
            $media_package,
            'presentation/source',
            $payload->getPresentation()->getFileStream()
        );

        // ingest
        $media_package = OpencastAPI::getApi()->ingest->ingest(
            $media_package,
            $payload->getProcessing()->getWorkflow()
        );

        // When we are done, we deactivate the ingest to keep everything clean.
        OpencastAPI::activateIngest(false);
    }
}
