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
    public function render(Component $input, RendererInterface $default_renderer): string
    {
        global $DIC;
        $file_preview_template = new ilTemplateWrapper(
            $DIC->ui()->mainTemplate(),
            new ilTemplate(
                self::TEMPLATES . "tpl.chunked_file.html",
                true,
                true
            )
        );
        $template = new ilTemplateWrapper(
            $DIC->ui()->mainTemplate(),
            new ilTemplate(
                self::TEMPLATES . "tpl.chunked_file.html",
                true,
                true
            )
        );

        $file_preview_template = $this->renderFilePreview(
            $input,
            $input->getTemplateForDynamicInputs(),
            $default_renderer,
            null,
            $file_preview_template
        );

        $input = $this->initClientsideFileInput($input);
        $input = $this->initClientsideRenderer($input, $file_preview_template->get('block_file_preview'));

        // display the action button (to choose files).
        $template->setVariable('ACTION_BUTTON', $default_renderer->render(
            $this->getUIFactory()->button()->shy(
                $this->txt('select_files_from_computer'),
                '#'
            )
        ));

        $js_id = $this->bindJSandApplyId($input, $template);
        return $this->wrapInFormContext(
            $input,
            $template->get(),
            $js_id,
            "",
            false
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

    protected function getComponentInterfaceName(): array
    {
        return [
            ChunkedFile::class,
        ];
    }
}
