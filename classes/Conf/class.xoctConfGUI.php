<?php

declare(strict_types=1);

use ILIAS\UI\Component\Input\Field\UploadHandler;
use ILIAS\UI\Renderer;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\UI\PaellaConfig\PaellaConfigFormBuilder;
use srag\Plugins\Opencast\LegacyHelpers\TranslatorTrait;
use ILIAS\DI\HTTPServices;
use srag\Plugins\Opencast\Util\Locale\LocaleTrait;
use srag\Plugins\Opencast\UI\ThumbnailConfig\ThumbnailConfigFormBuilder;

/**
 * Class xoctConfGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy xoctConfGUI : xoctMainGUI
 */
class xoctConfGUI extends xoctGUI
{
    use TranslatorTrait;
    use LocaleTrait;

    public const CMD_PLAYER = 'player';
    public const CMD_UPDATE_PLAYER = 'updatePlayer';

    public const CMD_THUMBNAIL = 'thumbnail';
    public const CMD_UPDATE_THUMBNAIL = 'updateThumbnail';

    /**
     * @var Renderer
     */
    private $renderer;
    /**
     * @var UploadHandler
     */
    private $fileUploadHandler;
    /**
     * @var PaellaConfigFormBuilder
     */
    private $paellConfigFormBuilder;
    /**
     * @var \ilTabsGUI
     */
    private $tabs;
    /**
     * @var ThumbnailConfigFormBuilder
     */
    private $thumbnailConfigFormBuilder;

    public function __construct(
        Renderer $renderer,
        UploadHandler $fileUploadHandler,
        PaellaConfigFormBuilder $paellConfigFormBuilder,
        ThumbnailConfigFormBuilder $thumbnailConfigFormBuilder
    ) {
        global $DIC;
        parent::__construct();
        $this->tabs = $DIC->tabs();
        $this->renderer = $renderer;
        $this->fileUploadHandler = $fileUploadHandler;
        $this->paellConfigFormBuilder = $paellConfigFormBuilder;
        $this->thumbnailConfigFormBuilder = $thumbnailConfigFormBuilder;
    }

    public function executeCommand(): void
    {
        $nextClass = $this->ctrl->getNextClass();

        switch ($nextClass) {
            case strtolower(xoctFileUploadHandlerGUI::class):
                if (!ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_ADD_EVENT)) {
                    $this->main_tpl->setOnScreenMessage('failure', $this->getLocaleString("msg_no_access"), true);
                    $this->cancel();
                }
                $this->ctrl->forwardCommand($this->fileUploadHandler);
                break;
            default:
                $cmd = $this->ctrl->getCmd(self::CMD_STANDARD);
                $this->performCommand($cmd);
                break;
        }
    }


    /**
     *
     */
    protected function index(): void
    {
        $this->ctrl->saveParameter($this, 'subtab_active');
        $subtab_active = $this->http->request()->getQueryParams()['subtab_active'] ?? xoctMainGUI::SUBTAB_API;
        $this->tabs->setSubTabActive($subtab_active);
        $xoctConfFormGUI = new xoctConfFormGUI($this, $subtab_active);
        $xoctConfFormGUI->fillForm();
        $this->main_tpl->setContent($xoctConfFormGUI->getHTML());
    }

    /**
     * Subtab Player has an own method, since it is rendered with the UI service and not with xoctConfFormGUI
     * @return void
     */
    protected function player(): void
    {
        $this->ctrl->saveParameter($this, 'subtab_active');
        $subtab_active = $this->http->request()->getQueryParams()['subtab_active'] ?? xoctMainGUI::SUBTAB_API;
        $this->tabs->setSubTabActive($subtab_active);
        $form = $this->paellConfigFormBuilder->buildForm($this->ctrl->getFormAction($this, self::CMD_UPDATE_PLAYER));
        $this->main_tpl->setContent($this->renderer->render($form));
    }

    protected function updatePlayer(): void
    {
        $this->ctrl->saveParameter($this, 'subtab_active');
        $form = $this->paellConfigFormBuilder
                    ->buildForm($this->ctrl
                    ->getFormAction($this, self::CMD_UPDATE_PLAYER))
                    ->withRequest($this->http->request());
        $data = $form->getData();
        if (!$data) {
            $this->main_tpl->setContent($this->renderer->render($form));
            return;
        }

        // General Settings
        if (isset($data[PaellaConfigFormBuilder::F_PAELLA_PLAYER_SECTION_GENERAL])) {
            $generals = $data[PaellaConfigFormBuilder::F_PAELLA_PLAYER_SECTION_GENERAL];
            if (isset($generals[PaellaConfigFormBuilder::F_PAELLA_PLAYER_OPTION])) {
                $paella_player_option = $generals[PaellaConfigFormBuilder::F_PAELLA_PLAYER_OPTION][0];
                PluginConfig::set(PluginConfig::F_PAELLA_OPTION, $paella_player_option);
                if ($paella_player_option === PluginConfig::PAELLA_OPTION_URL) {
                    PluginConfig::set(
                        PluginConfig::F_PAELLA_URL,
                        $generals[PaellaConfigFormBuilder::F_PAELLA_PLAYER_OPTION][1]['url']
                    );
                }
            }

            if (isset($generals[PaellaConfigFormBuilder::F_PAELLA_PLAYER_FALLBACK_LANGS_OPTION])) {
                $paella_fallback_langs_option = $generals[PaellaConfigFormBuilder::F_PAELLA_PLAYER_FALLBACK_LANGS_OPTION];
                PluginConfig::set(PluginConfig::F_PAELLA_FALLBACK_LANGS, $paella_fallback_langs_option);
            }

            if (isset($generals[PaellaConfigFormBuilder::F_PAELLA_PLAYER_PREVENT_VIDEO_DOWNLOAD])) {
                $paella_prevent_video_download = $generals[PaellaConfigFormBuilder::F_PAELLA_PLAYER_PREVENT_VIDEO_DOWNLOAD];
                PluginConfig::set(PluginConfig::F_PAELLA_PREVENT_VIDEO_DOWNLOAD, $paella_prevent_video_download);
            }
        }


        // Theme Settings
        if (isset($data[PaellaConfigFormBuilder::F_PAELLA_PLAYER_PREVIEW_THEME])) {
            $themes = $data[PaellaConfigFormBuilder::F_PAELLA_PLAYER_PREVIEW_THEME];
            if (isset($themes[PaellaConfigFormBuilder::F_PAELLA_PLAYER_THEME])) {
                $paella_player_theme = $themes[PaellaConfigFormBuilder::F_PAELLA_PLAYER_THEME][0];
                PluginConfig::set(PluginConfig::F_PAELLA_THEME, $paella_player_theme);
                if ($paella_player_theme === PluginConfig::PAELLA_OPTION_URL) {
                    PluginConfig::set(
                        PluginConfig::F_PAELLA_THEME_URL,
                        $themes[PaellaConfigFormBuilder::F_PAELLA_PLAYER_THEME][1]['url']
                    );
                }
            }
            if (isset($themes[PaellaConfigFormBuilder::F_PAELLA_PLAYER_LIVE_THEME])) {
                $paella_player_live_theme = $themes[PaellaConfigFormBuilder::F_PAELLA_PLAYER_LIVE_THEME][0];
                PluginConfig::set(PluginConfig::F_PAELLA_THEME_LIVE, $paella_player_live_theme);
                if ($paella_player_live_theme === PluginConfig::PAELLA_OPTION_URL) {
                    PluginConfig::set(
                        PluginConfig::F_PAELLA_THEME_URL_LIVE,
                        $themes[PaellaConfigFormBuilder::F_PAELLA_PLAYER_LIVE_THEME][1]['url']
                    );
                }
            }
        }


        // Preview Settings
        if (isset($data[PaellaConfigFormBuilder::F_PAELLA_PLAYER_PREVIEW_PREVIEW])) {
            $previews = $data[PaellaConfigFormBuilder::F_PAELLA_PLAYER_PREVIEW_PREVIEW];
            if (isset($previews[PaellaConfigFormBuilder::F_PAELLA_PLAYER_PREVIEW_FALLBACK])) {
                $paella_player_preview_fallback = $previews[PaellaConfigFormBuilder::F_PAELLA_PLAYER_PREVIEW_FALLBACK][0];
                PluginConfig::set(PluginConfig::F_PAELLA_PREVIEW_FALLBACK, $paella_player_preview_fallback);
                if ($paella_player_preview_fallback === PluginConfig::PAELLA_OPTION_URL) {
                    PluginConfig::set(
                        PluginConfig::F_PAELLA_PREVIEW_FALLBACK_URL,
                        $previews[PaellaConfigFormBuilder::F_PAELLA_PLAYER_PREVIEW_FALLBACK][1]['url']
                    );
                }
            }
        }


        // General Settings
        if (isset($data[PaellaConfigFormBuilder::F_PAELLA_PLAYER_PREVIEW_CAPTION])) {
            $captions = $data[PaellaConfigFormBuilder::F_PAELLA_PLAYER_PREVIEW_CAPTION];
            if (isset($captions[PaellaConfigFormBuilder::F_PAELLA_PLAYER_FALLBACK_CAPTIONS_OPTION])) {
                $paella_fallback_captions_option = $captions[PaellaConfigFormBuilder::F_PAELLA_PLAYER_FALLBACK_CAPTIONS_OPTION];
                PluginConfig::set(PluginConfig::F_PAELLA_FALLBACK_CAPTIONS, $paella_fallback_captions_option);
            }

            if (isset($captions[PaellaConfigFormBuilder::F_PAELLA_PLAYER_DISPLAY_CAPTION_TEXT_TYPE])) {
                $paella_display_caption_text_type = $captions[PaellaConfigFormBuilder::F_PAELLA_PLAYER_DISPLAY_CAPTION_TEXT_TYPE];
                PluginConfig::set(PluginConfig::F_PAELLA_DISPLAY_CAPTION_TEXT_TYPE, $paella_display_caption_text_type);
            }

            if (isset($captions[PaellaConfigFormBuilder::F_PAELLA_PLAYER_DISPLAY_CAPTION_TEXT_GENERATOR])) {
                $paella_display_caption_text_generator =
                    $captions[PaellaConfigFormBuilder::F_PAELLA_PLAYER_DISPLAY_CAPTION_TEXT_GENERATOR];
                PluginConfig::set(PluginConfig::F_PAELLA_DISPLAY_CAPTION_TEXT_GENERATOR, $paella_display_caption_text_generator);
            }

            if (isset($captions[PaellaConfigFormBuilder::F_PAELLA_PLAYER_DISPLAY_CAPTION_TEXT_GENERATOR_TYPE])) {
                $paella_display_caption_text_generator_type =
                    $captions[PaellaConfigFormBuilder::F_PAELLA_PLAYER_DISPLAY_CAPTION_TEXT_GENERATOR_TYPE];
                PluginConfig::set(
                    PluginConfig::F_PAELLA_DISPLAY_CAPTION_TEXT_GENERATOR_TYPE,
                    $paella_display_caption_text_generator_type
                );
            }
        }

        $this->ctrl->redirect($this, self::CMD_PLAYER);
    }

    /**
     *
     */
    protected function update(): void
    {
        $this->ctrl->saveParameter($this, 'subtab_active');
        $subtab_active = $this->http->request()->getQueryParams()['subtab_active'] ?? xoctMainGUI::SUBTAB_API;
        $xoctConfFormGUI = new xoctConfFormGUI($this, $subtab_active);
        $xoctConfFormGUI->setValuesByPost();
        if ($xoctConfFormGUI->saveObject()) {
            $this->main_tpl->setOnScreenMessage('success', $this->getLocaleString('msg_success', 'config'), true);
            $this->ctrl->redirect($this, self::CMD_STANDARD);
        }
        $this->main_tpl->setContent($xoctConfFormGUI->getHTML());
    }

    /**
     * Render the thumbnail subtab menu.
     *
     * @return void
     */
    protected function thumbnail(): void
    {
        $this->ctrl->saveParameter($this, 'subtab_active');
        $subtab_active = $this->http->request()->getQueryParams()['subtab_active'] ?? xoctMainGUI::SUBTAB_API;
        $this->tabs->setSubTabActive($subtab_active);
        $form = $this->thumbnailConfigFormBuilder->buildForm(
            $this->ctrl->getFormAction($this, self::CMD_UPDATE_THUMBNAIL));
        $this->main_tpl->setContent($this->renderer->render($form));
    }

    /**
     * Action to update thumbnail related config data.
     */
    protected function updateThumbnail(): void
    {
        $this->ctrl->saveParameter($this, 'subtab_active');
        $form = $this->thumbnailConfigFormBuilder->buildForm($this->ctrl->getFormAction($this, self::CMD_UPDATE_THUMBNAIL))
            ->withRequest($this->http->request());
        $data = $form->getData();
        if (!$data) {
            $this->main_tpl->setContent($this->renderer->render($form));
            return;
        }
        // Main enable upload thumbnail option.
        $thumbnail_upload_enabled = false;
        if (isset($data[ThumbnailConfigFormBuilder::F_THUMBNAIL_UPLOAD_ENABLED])) {
            $optional_data_enabled_thumbnail = $data[ThumbnailConfigFormBuilder::F_THUMBNAIL_UPLOAD_ENABLED];
            $thumbnail_upload_enabled = true;
            // Accepted mimetypes.
            if (isset($optional_data_enabled_thumbnail[ThumbnailConfigFormBuilder::F_THUMBNAIL_ACCEPTED_MIMETYPES])) {
                $thumbnail_upload_accepted_mimetypes =
                    $optional_data_enabled_thumbnail[ThumbnailConfigFormBuilder::F_THUMBNAIL_ACCEPTED_MIMETYPES];
                PluginConfig::set(PluginConfig::F_THUMBNAIL_ACCEPTED_MIMETYPES, $thumbnail_upload_accepted_mimetypes);
            }

            // Mode.
            if (isset($optional_data_enabled_thumbnail[ThumbnailConfigFormBuilder::F_THUMBNAIL_UPLOAD_MODE])) {
                $thumbnail_upload_mode =
                    $optional_data_enabled_thumbnail[ThumbnailConfigFormBuilder::F_THUMBNAIL_UPLOAD_MODE];
                PluginConfig::set(PluginConfig::F_THUMBNAIL_UPLOAD_MODE, $thumbnail_upload_mode);
            }
        }
        PluginConfig::set(PluginConfig::F_THUMBNAIL_UPLOAD_ENABLED, $thumbnail_upload_enabled);
        $this->ctrl->redirect($this, self::CMD_THUMBNAIL);
    }

    /**
     *
     */
    protected function confirmDelete(): void
    {
    }

    /**
     *
     */
    protected function delete(): void
    {
    }

    /**
     *
     */
    protected function add(): void
    {
    }

    /**
     *
     */
    protected function create(): void
    {
    }

    /**
     *
     */
    protected function edit(): void
    {
    }
}
