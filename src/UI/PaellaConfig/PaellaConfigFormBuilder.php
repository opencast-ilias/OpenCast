<?php

namespace srag\Plugins\Opencast\UI\PaellaConfig;

use ILIAS\UI\Component\Input\Container\Form\Standard;
use ILIAS\UI\Component\Input\Field\UploadHandler;
use ILIAS\UI\Implementation\Component\Input\Field\Input;
use ilPlugin;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\Util\FileTransfer\PaellaConfigStorageService;
use ILIAS\UI\Factory;

class PaellaConfigFormBuilder
{

    // Paella Player Path
    const F_PAELLA_PLAYER_OPTION = 'paella_player_option';
    const F_PAELLA_PLAYER_DEFAULT = 'pp_default';
    const F_PAELLA_PLAYER_FILE = 'pp_file';
    const F_PAELLA_PLAYER_LINK = 'pp_link';
    // Paella Player live Path
    const F_PAELLA_PLAYER_LIVE_OPTION = 'paella_player_live_option';
    const F_PAELLA_PLAYER_LIVE_DEFAULT = 'pp_default';
    const F_PAELLA_PLAYER_LIVE_FILE = 'pp_live_file';
    const F_PAELLA_PLAYER_LIVE_LINK = 'pp_live_link';

    /**
     * @var ilPlugin
     */
    private $plugin;
    /**
     * @var UploadHandler
     */
    private $fileUploadHandler;
    /**
     * @var PaellaConfigStorageService
     */
    private $paellaStorageService;
    /**
     * @var Factory
     */
    private $ui_factory;

    public function __construct(ilPlugin $plugin, UploadHandler $fileUploadHandler, PaellaConfigStorageService $paellaStorageService, Factory $ui_factory)
    {
        $this->plugin = $plugin;
        $this->fileUploadHandler = $fileUploadHandler;
        $this->paellaStorageService = $paellaStorageService;
        $this->ui_factory = $ui_factory;
    }

    public function buildForm(string $form_action): Standard
    {
        $inputs[self::F_PAELLA_PLAYER_OPTION] = $this->getPaellaPlayerPathInput(false,
            PluginConfig::getConfig(PluginConfig::F_PAELLA_OPTION),
            PluginConfig::getConfig(PluginConfig::F_PAELLA_FILE_ID) ?? '',
            PluginConfig::getConfig(PluginConfig::F_PAELLA_URL) ?? '');
        $inputs[self::F_PAELLA_PLAYER_LIVE_OPTION] = $this->getPaellaPlayerPathInput(true,
            PluginConfig::getConfig(PluginConfig::F_PAELLA_OPTION_LIVE),
            PluginConfig::getConfig(PluginConfig::F_PAELLA_FILE_ID_LIVE) ?? '',
            PluginConfig::getConfig(PluginConfig::F_PAELLA_URL_LIVE) ?? '');
        return $this->ui_factory->input()->container()->form()->standard(
            $form_action,
            $inputs
        );
    }

    private function getPaellaPlayerPathInput(bool $live, string $option, string $path, string $url): Input
    {
        $f = $this->ui_factory->input()->field();
        $live_s = $live ? '_live' : '';
        return $f->switchableGroup([
            PluginConfig::PAELLA_OPTION_DEFAULT => $f->group([], $this->plugin->txt(self::F_PAELLA_PLAYER_DEFAULT)),
            PluginConfig::PAELLA_OPTION_FILE => $f->group([
                'file' => $f->file($this->fileUploadHandler, $this->plugin->txt('file')) // todo: set required when this is fixed: https://mantis.ilias.de/view.php?id=31645
                ->withAcceptedMimeTypes(['application/json'])
                    ->withByline($this->buildInlineDownload($path))
                    ->withValue(($path && $this->paellaStorageService->exists($path)) ? [$path] : null)
            ], $this->plugin->txt('pp_file')),
            PluginConfig::PAELLA_OPTION_URL => $f->group([
                'url' => $f->text($this->plugin->txt('link'))
                    ->withByline($this->plugin->txt('pp_link_info'))
                    ->withRequired(true)
                    ->withValue($url)
            ], $this->plugin->txt('pp_url'))
        ], $this->txt(self::F_PAELLA_PLAYER_OPTION . $live_s))
            ->withValue($option)
            ->withRequired(true);
    }

    private function buildInlineDownload(string $file_id): string
    {
        if (!$file_id || !$this->paellaStorageService->exists($file_id)) {
            return '';
        }
        $fileAsBase64 = $this->paellaStorageService->getFileAsBase64($file_id);
        $fileInfo = $this->paellaStorageService->getFileInfo($file_id);
        return '<a href="data:text/vtt;base64,'
            . $fileAsBase64
            . '" target="blank" download="' . $fileInfo['name'] . '">Download</a>';
    }

    private function txt(string $string) : string
    {
        return $this->plugin->txt('config_' . $string);
    }


}