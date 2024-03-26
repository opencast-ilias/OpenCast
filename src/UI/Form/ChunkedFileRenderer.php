<?php

declare(strict_types=1);

namespace ILIAS\UI\Implementation\Component\Input\Field;

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
     */
    public function render(Component $component, RendererInterface $default_renderer): string
    {
        $component = $component->withByline(
            $component->getByline() . '<br>' . $this->txt('file_notice') . ': ' . $component->getMaxFileSize(
            ) / self::MB_IN_B . ' MB'
        );


        return $this->renderFileField($component, $default_renderer);
    }


    protected function renderFileField(File $component, RendererInterface $default_renderer) : string
    {
        $tpl = $this->getTemplateInternal("tpl.chunked_file.html", true, true);
        $this->applyName($component, $tpl);

        $settings = new \stdClass();
        $settings->upload_url = $component->getUploadHandler()->getUploadURL();
        $settings->removal_url = $component->getUploadHandler()->getFileRemovalURL();
        $settings->info_url = $component->getUploadHandler()->getExistingFileInfoURL();
        $settings->file_identifier_key = $component->getUploadHandler()->getFileIdentifierParameterName();
        $settings->accepted_files = implode(',', $component->getAcceptedMimeTypes());
        $settings->existing_file_ids = $component->getValue();
        $settings->existing_files = $component->getUploadHandler()->getInfoForExistingFiles($component->getValue() ?? []);
        $settings->max_file_size = $component->getMaxFileFize();
        $settings->max_file_size_text = sprintf(
            $this->txt('ui_file_input_invalid_size'),
            (string) round($settings->max_file_size, 3)
        );
        $settings->chunk_size = $component->getChunkSizeInBytes();
        $settings->chunking = true;

        /**
         * @var $component F\File
         */
        $component = $component->withAdditionalOnLoadCode(
            function ($id) use ($settings) {
                $settings = json_encode($settings);
                return "$(document).ready(function() {
                    il.UI.Input.ChunkedFile.init('$id', '{$settings}');
                });";
            }
        );
        $id = $this->bindJSandApplyId($component, $tpl);

        $tpl->setVariable(
            'BUTTON',
            $default_renderer->render(
                $this->getUIFactory()->button()->shy(
                    $this->txt('select_files_from_computer'),
                    "#"
                )
            )
        );

        $this->maybeDisable($component, $tpl);
        return $this->wrapInFormContext($component, $tpl->get(), $id);
    }


    protected function initClientsideFileInput(\ILIAS\UI\Component\Input\Field\File $input): \ILIAS\UI\Component\Input\Field\File
    {
        /** @var ChunkedFile $input */

        return $input->withAdditionalOnLoadCode(
            function ($id) use ($input) {
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

    /**
     * @description from original Renderer, but with other location, cannot override since the original is final
     */
    final protected function getTemplateInternal(
        string $name,
        bool $purge_unfilled_vars,
        bool $purge_unused_blocks
    ): ilTemplateWrapper {
        global $DIC;
        return new ilTemplateWrapper(
            $DIC->ui()->mainTemplate(),
            new \ilTemplate(self::TEMPLATES . $name, $purge_unfilled_vars, $purge_unused_blocks)
        );
    }
}
