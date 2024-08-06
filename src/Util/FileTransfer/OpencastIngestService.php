<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Util\FileTransfer;

use srag\Plugins\Opencast\Model\Event\Request\UploadEventRequest;
use srag\Plugins\Opencast\Util\Transformator\MetadataToXML;
use xoctException;
use srag\Plugins\Opencast\API\API;
use ilFFmpeg;
use ilShellUtil;
use srag\Plugins\Opencast\Container\Init;

class OpencastIngestService
{
    /**
     * @var API
     */
    protected $api;

    public function __construct(private readonly UploadStorageService $uploadStorageService)
    {
        $opencastContainer = Init::init();
        $this->api = $opencastContainer[API::class];
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
                // Perform conversion of supported subtitles files to WebVTT format.
                $file_stream = $subtitle_uploadfile->getFileStream();
                if (ilFFmpeg::enabled() && $subtitle_uploadfile->getMimeType() != 'text/vtt') {
                    $path = $subtitle_uploadfile->getPath();
                    $extension = pathinfo((string) $path, PATHINFO_EXTENSION);
                    $new_vtt_path = str_replace(".$extension", '.vtt', $path);
                    $escaped_path = ilShellUtil::escapeShellArg($path);
                    $escaped_new_vtt_path = ilShellUtil::escapeShellArg($new_vtt_path);
                    $ffmpeg_cmd = "-i {$path} -c:s webvtt {$new_vtt_path}";
                    $escaped_cmd = ilShellUtil::escapeShellCmd($ffmpeg_cmd);
                    ilFFmpeg::exec($escaped_cmd);
                    if (file_exists($new_vtt_path)) {
                        $file_stream = fopen($new_vtt_path, 'rb');
                        unlink($new_vtt_path);
                    }
                }
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
                    $file_stream,
                    implode(',', $tags)
                );
            }
        }
        // If thumbnail exists, we add it into attachments
        if ($payload->hasThumbnail()) {
            $media_package = $this->api->routes()->ingest->addAttachment(
                $media_package,
                'presentation/preview', // NOTE: This is aligned with the workflow, change it if you use other flavors.
                $payload->getThumbnail()->getFileStream(),
                'player' // NOTE: This is aligned with the workflow, change it if you use other tags.
            );
        }

        // track
        $media_package = $this->api->routes()->ingest->addTrack(
            $media_package,
            'presentation/source',
            $payload->getPresentation()->getFileStream()
        );

        // Get workflow configuration params ready, make sure it is array!
        $workflow_configuration = (array) $payload->getProcessing()->getConfiguration();
        // ingest
        $media_package = $this->api->routes()->ingest->ingest(
            $media_package,
            $payload->getProcessing()->getWorkflow(),
            '',
            $workflow_configuration
        );

        // When we are done, we deactivate the ingest to keep everything clean.
        $this->api->activateIngest(false);
    }
}
