<?php

namespace ILIAS\UI\Implementation\Component\Input\Field;

use ILIAS\UI\Component\Component;
use ILIAS\UI\Implementation\Render\ilTemplateWrapper;
use ILIAS\UI\Implementation\Render\ResourceRegistry;
use ILIAS\UI\Implementation\Render\Template;
use ILIAS\UI\Renderer as RendererInterface;
use stdClass;
use ilTemplate;
use ilTemplateException;

class ChunkedFileRenderer extends Renderer
{
    const TEMPLATES = './Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/default/';
    const MB_IN_B = 1000 * 1000;
    const MIB_F = 1.024;
    
    /**
     * @var \ilOpenCastPlugin
     */
    protected $plugin;
    
    public function setPluginInstance(\ilOpenCastPlugin $plugin) : void
    {
        $this->plugin = $plugin;
    }
    
    /**
     * @param ResourceRegistry $registry
     * @return void|null
     */
    public function registerResources(ResourceRegistry $registry)
    {
        $registry->register(self::TEMPLATES . 'chunked_file.js');
    }
    
    /**
     * @param Component         $component
     * @param RendererInterface $default_renderer
     * @return string
     * @throws ilTemplateException
     */
    public function render(Component $component, RendererInterface $default_renderer) : string
    {
        global $DIC;
        $tpl = new ilTemplateWrapper(
            $DIC->ui()->mainTemplate(),
            new ilTemplate(
                self::TEMPLATES . "tpl.chunked_file.html",
                true,
                true
            )
        );
    
        // Support ILIAS 6 and 7
        if (method_exists($this, 'applyName')) {
            $this->applyName($component, $tpl);
        }
        
        $settings = new \stdClass();
        $handler = $component->getUploadHandler();
        $settings->upload_url = $handler->getUploadURL();
        $settings->removal_url = $handler->getFileRemovalURL();
        $settings->info_url = $handler->getExistingFileInfoURL();
        $settings->file_identifier_key = $handler->getFileIdentifierParameterName();
        $settings->accepted_files = implode(',', $component->getAcceptedMimeTypes());
        $settings->existing_file_ids = $component->getValue();
        $settings->existing_files = $handler->getInfoForExistingFiles($component->getValue() ?? []);
        
        $upload_limit = \ilUtil::getUploadSizeLimitBytes();
        $settings->chunked_upload = $handler->supportsChunkedUploads();
        $settings->chunk_size = $upload_limit / 2;
        if (!$settings->chunked_upload) {
            $max_file_size = $component->getMaxFileFize() === -1
                ? $upload_limit
                : $component->getMaxFileFize();
            $settings->max_file_size = min($max_file_size, $upload_limit) / self::MB_IN_B * self::MIB_F ; // dropzone.js expects MiB
        } else {
            $settings->max_file_size = $component->getMaxFileFize() / self::MB_IN_B * self::MIB_F; // dropzone.js expects MiB
        }
        
        $settings->max_file_size_text = sprintf(
            $this->txt('ui_file_input_invalid_size'),
            (string) round($settings->max_file_size, 3)
        );
        
        /**
         * @var $component F\File
         */
        $component = $component->withAdditionalOnLoadCode(
            function ($id) use ($settings) {
                $settings = json_encode($settings);
                return "$(document).ready(function() {
                    il.UI.Input.chunkedFile.init('$id', '{$settings}');
                });";
            }
        );
        
        $id = $this->bindJavaScript($component) ?? $this->createId();
        $tpl->setVariable("ID", $id);
        
        $tpl->setVariable(
            'BUTTON',
            $default_renderer->render(
                $this->getUIFactory()->button()->shy(
                    $this->txt('select_files_from_computer'),
                    "#"
                )
            )
        );
        
        $component = $component->withByline(
            $component->getByline() .
            '<br>' .
            $this->txt('file_notice') . ' ' . ($settings->max_file_size / self::MIB_F) . ' MB'
        );
    
        if ($component->isDisabled()) {
            $tpl->setVariable("DISABLED", 'disabled="disabled"');
        }
        
        // Support ILIAS 6 and 7
        if (method_exists($this, 'wrapInFormContext')) {
            return $this->wrapInFormContext($component, $tpl->get(), $id);
        } else {
            return $this->renderInputFieldWithContext($default_renderer, $tpl, $component);
        }
    }
    
    protected function getComponentInterfaceName() : array
    {
        return [
            ChunkedFile::class,
        ];
    }
    
}
