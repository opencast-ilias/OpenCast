<?php

declare(strict_types=1);

namespace ILIAS\UI\Implementation\Component\Input\Field;

use ILIAS\UI\Component\Input\Field\File;
use ILIAS\UI\Component\Component;
use ILIAS\UI\Implementation\Render\ilTemplateWrapper;
use ILIAS\UI\Implementation\Render\ResourceRegistry;
use ILIAS\UI\Renderer as RendererInterface;
use ilTemplate;
use ilTemplateException;
use srag\Plugins\Opencast\LegacyHelpers\UploadSize;

class ChunkedFileRenderer extends Renderer
{
    public const TEMPLATES = './Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/default/';
    public const MB_IN_B = 1000 * 1000;
    public const MIB_F = 1.024;

    /**
     * @var \ilOpenCastPlugin
     */
    protected $plugin;

    public function setPluginInstance(\ilOpenCastPlugin $plugin): void
    {
        $this->plugin = $plugin;
    }

    public function registerResources(ResourceRegistry $registry): void
    {
        $registry->register(self::TEMPLATES . 'chunked_file.js');
    }

    /**
     * @param ChunkedFile $component
     * @throws ilTemplateException
     * @param mixed $default_renderer
     */
    public function render(Component $component, $default_renderer): string
    {
        $component = $component->withByline(
            $component->getByline() . '<br>' . $this->txt('file_notice') . ': ' . $component->getMaxFileSize(
            ) / self::MB_IN_B . ' MB'
        );


        return $this->renderFileField($component, $default_renderer);
    }

    protected function initClientsideFileInput(File $input): File
    {
        /** @var ChunkedFile $input */

        return $input->withAdditionalOnLoadCode(
            function ($id) use ($input): string {
                $current_file_count = count($input->getDynamicInputs());
                $translations = json_encode($input->getTranslations());
                $is_disabled = ($input->isDisabled()) ? 'true' : 'false';
                return "
                    $(document).ready(function () {
                        il.UI.Input.ChunkedFile.init(
                            '$id',
                            '{$input->getUploadHandler()->getUploadURL()}',
                            '{$input->getUploadHandler()->getFileRemovalURL()}',
                            '{$input->getUploadHandler()->getFileIdentifierParameterName()}',
                            $current_file_count,
                            {$input->getMaxFiles()},
                            {$input->getMaxFileSize()},
                            '{$this->prepareDropzoneJsMimeTypes($input->getAcceptedMimeTypes())}',
                            $is_disabled,
                            $translations,
                            '{$input->getUploadHandler()->supportsChunkedUploads()}',
                            {$input->getChunkSizeInBytes()}
                        );
                    });
                ";
            }
        );
    }


    protected function getComponentInterfaceName(): array
    {
        return [
            ChunkedFile::class,
        ];
    }
}
