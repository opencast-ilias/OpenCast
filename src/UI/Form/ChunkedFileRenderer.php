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
        /** @var ChunkedFile $input */
        global $DIC;

        $template = new ilTemplateWrapper(
            $DIC->ui()->mainTemplate(),
            new ilTemplate(
                self::TEMPLATES . "tpl.chunked_file.html",
                true,
                true
            )
        );

        foreach ($input->getDynamicInputs() as $metadata_input) {
            $file_info = $input->getValue() === [] ? null : $input->getUploadHandler()->getInfoResult(
                $input->getValue()[0]
            );
            $template = $this->renderFilePreview(
                $input,
                $metadata_input,
                $default_renderer,
                $file_info,
                $template
            );
        }

        $file_preview_template = new ilTemplateWrapper(
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
        $template->setVariable(
            'ACTION_BUTTON', $default_renderer->render(
            $this->getUIFactory()->button()->shy(
                $this->txt('select_files_from_computer'),
                '#'
            )
        )
        );

        $js_id = $this->bindJSandApplyId($input, $template);

        return $this->wrapInFormContext(
            $input,
            $template->get(),
            $js_id,
            "",
            false
        );
    }

    protected function getComponentInterfaceName(): array
    {
        return [
            ChunkedFile::class,
        ];
    }
}
