<?php

namespace srag\Plugins\Opencast\Util\Upload;

use srag\Plugins\Opencast\Model\API\Event\UploadEventRequest;
use srag\Plugins\Opencast\Util\Transformator\MetadataToXML;
use xoctException;
use xoctRequest;

class OpencastIngestService
{
    /**
     * @var UploadStorageService
     */
    private $uploadStorageService;

    /**
     * @param UploadStorageService $uploadStorageService
     */
    public function __construct(UploadStorageService $uploadStorageService)
    {
        $this->uploadStorageService = $uploadStorageService;
    }

    /**
     * @throws xoctException
     */
    public function ingest(UploadEventRequest $uploadEventRequest) : void
    {
        $payload = $uploadEventRequest->getPayload();
        $ingest_node_url = $this->getIngestNodeURL();

        // create media package
        $media_package = xoctRequest::root()->ingest()->createMediaPackage()->get([], '', $ingest_node_url);

        // Metadata
        $media_package = xoctRequest::root()->ingest()->addDCCatalog()->post([
            'dublinCore' => (new MetadataToXML($payload->getMetadata()))->getXML(),
            'mediaPackage' => $media_package,
            'flavor' => 'dublincore/episode'
        ], [], '', $ingest_node_url);

        // ACLs (as attachment)
        $media_package = xoctRequest::root()->ingest()->addAttachment()->postFiles([
            'mediaPackage' => $media_package,
            'flavor' => 'security/xacml+episode'
        ], [$this->uploadStorageService->buildACLUploadFile($payload->getAcl())], [], '', $ingest_node_url);

        // track
        $media_package = xoctRequest::root()->ingest()->addTrack()->postFiles([
            'mediaPackage' => $media_package,
            'flavor' => 'presentation/source'
        ], [$payload->getPresentation()], [], '', $ingest_node_url);

        // ingest
        $post_params = [
            'mediaPackage' => $media_package,
            'workflowDefinitionId' => $payload->getProcessing()->getWorkflow()
        ];
        $post_params = array_merge($post_params, $payload->getProcessing()->getConfiguration());
        xoctRequest::root()->ingest()->ingest()->post($post_params, [], '', $ingest_node_url);
    }

    /**
     * @return string
     * @throws xoctException
     */
    private function getIngestNodeURL(): string
    {
        $nodes = json_decode(xoctRequest::root()->services()->available('org.opencastproject.ingest')->get(), true);
        if (!is_array($nodes)
            || !isset($nodes['services'])
            || !isset($nodes['services']['service'])
            || empty($nodes['services']['service'])
        ) {
            throw new xoctException(xoctException::API_CALL_STATUS_500, 'no available ingest nodes found');
        }
        $available_hosts = [];
        $services = $nodes['services']['service'];
        $services = isset($services['type']) ? [$services] : $services; // only one service?
        foreach ($services as $node) {
            if ($node['active'] && $node['host']) {
                $available_hosts[] = $node['host'];
            }
        }
        if (count($available_hosts) === 0) {
            throw new xoctException(xoctException::API_CALL_STATUS_500, 'no available ingest nodes found');
        }
        return array_rand(array_flip($available_hosts));
    }
}