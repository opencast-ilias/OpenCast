<?php

use ILIAS\UI\Component\Input\Field\Input;
use srag\DIC\OpenCast\DICTrait;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\Model\Object\ObjectSettings;
use srag\Plugins\Opencast\Model\User\xoctUser;

/**
 * Class xoctConfFormGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class xoctConfFormGUI extends ilPropertyFormGUI
{
    use DICTrait;
    public const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;

    /**
     * @var  PluginConfig
     */
    protected $object;
    /**
     * @var xoctConfGUI
     */
    protected $parent_gui;
    /**
     * @var string
     */
    protected $subtab_active;


    /**
     * @param $parent_gui
     */
    public function __construct(xoctConfGUI $parent_gui, $subtab_active)
    {
        parent::__construct();
        $this->parent_gui = $parent_gui;
        $this->subtab_active = $subtab_active;
        $this->initForm();
    }


    /**
     *
     */
    protected function initForm()
    {
        $this->setTarget('_top');
        $this->setFormAction(self::dic()->ctrl()->getFormAction($this->parent_gui));
        $this->initButtons();

        switch ($this->subtab_active) {
            case xoctMainGUI::SUBTAB_API:
                $this->initAPISection();
                break;
            case xoctMainGUI::SUBTAB_EVENTS:
                $this->initEventsSection();
                break;
            case xoctMainGUI::SUBTAB_TOU:
                $this->initToUSection();
                break;
            case xoctMainGUI::SUBTAB_GROUPS_ROLES:
                $this->initGroupsRolesSection();
                break;
            case xoctMainGUI::SUBTAB_SECURITY:
                $this->initSecuritySection();
                break;
            case xoctMainGUI::SUBTAB_ADVANCED:
                $this->initAdvancedSection();
                break;
        }
    }


    /**
     *
     */
    protected function initButtons()
    {
        $this->addCommandButton(xoctConfGUI::CMD_UPDATE, $this->parent_gui->txt(xoctConfGUI::CMD_UPDATE));
    }


    /**
     *
     */
    public function fillForm()
    {
        $array = [];
        foreach ($this->getItems() as $item) {
            $this->getValuesForItem($item, $array);
        }
        $this->setValuesByArray($array);
    }


    /**
     * @param $item
     * @param $array
     *
     * @internal param $key
     */
    private function getValuesForItem($item, &$array)
    {
        if (self::checkItem($item)) {
            $key = $item->getPostVar();
            $array[$key] = PluginConfig::getConfig($key);
            if (self::checkForSubItem($item)) {
                foreach ($item->getSubItems() as $subitem) {
                    $this->getValuesForItem($subitem, $array);
                }
            }
        }
    }


    /**
     * @return bool
     */
    public function saveObject()
    {
        if (!$this->checkInput()) {
            return false;
        }
        foreach ($this->getItems() as $item) {
            $this->saveValueForItem($item);
        }
        PluginConfig::set(PluginConfig::F_CONFIG_VERSION, PluginConfig::CONFIG_VERSION);

        return true;
    }


    /**
     * @param $item
     */
    private function saveValueForItem($item)
    {
        if (self::checkItem($item)) {
            $key = $item->getPostVar();
            PluginConfig::set($key, $this->getInput($key));
            if (self::checkForSubItem($item)) {
                foreach ($item->getSubItems() as $subitem) {
                    $this->saveValueForItem($subitem);
                }
            }
        }
    }


    /**
     * @param $item
     *
     * @return bool
     */
    public static function checkForSubItem($item)
    {
        return !$item instanceof ilFormSectionHeaderGUI and !$item instanceof ilMultiSelectInputGUI;
    }


    /**
     * @param $item
     *
     * @return bool
     */
    public static function checkItem($item)
    {
        return !$item instanceof ilFormSectionHeaderGUI;
    }


    /**
     *
     */
    protected function initAPISection()
    {
        $h = new ilFormSectionHeaderGUI();
        $h->setTitle($this->parent_gui->txt('curl'));
        $this->addItem($h);

        $te = new ilTextInputGUI($this->parent_gui->txt(PluginConfig::F_API_VERSION), PluginConfig::F_API_VERSION);
        $te->setInfo($this->parent_gui->txt(PluginConfig::F_API_VERSION . '_info'));
        $te->setRequired(true);
        $this->addItem($te);

        $te = new ilTextInputGUI($this->parent_gui->txt(PluginConfig::F_API_BASE), PluginConfig::F_API_BASE);
        $te->setInfo($this->parent_gui->txt(PluginConfig::F_API_BASE . '_info'));
        $te->setRequired(true);
        $this->addItem($te);

        $te = new ilTextInputGUI($this->parent_gui->txt(PluginConfig::F_CURL_USERNAME), PluginConfig::F_CURL_USERNAME);
        $te->setInfo($this->parent_gui->txt(PluginConfig::F_CURL_USERNAME . '_info'));
        $te->setRequired(true);
        $this->addItem($te);

        $te = new ilTextInputGUI($this->parent_gui->txt(PluginConfig::F_CURL_PASSWORD), PluginConfig::F_CURL_PASSWORD);
        $te->setInfo($this->parent_gui->txt(PluginConfig::F_CURL_PASSWORD . '_info'));
        $te->setRequired(true);
        $this->addItem($te);
    }


    /**
     *
     */
    protected function initEventsSection()
    {
        $h = new ilFormSectionHeaderGUI();
        $h->setTitle($this->parent_gui->txt('events'));
        $this->addItem($h);

        $te = new ilTextInputGUI($this->parent_gui->txt(PluginConfig::F_WORKFLOW), PluginConfig::F_WORKFLOW);
        $te->setInfo($this->parent_gui->txt(PluginConfig::F_WORKFLOW . '_info'));
        $te->setRequired(true);
        $this->addItem($te);

        $te = new ilNumberInputGUI(
            $this->parent_gui->txt(PluginConfig::F_CURL_MAX_UPLOADSIZE),
            PluginConfig::F_CURL_MAX_UPLOADSIZE
        );
        $te->setInfo($this->parent_gui->txt(PluginConfig::F_CURL_MAX_UPLOADSIZE . '_info'));
        $te->setRequired(false);
        $this->addItem($te);

        $te = new ilTextInputGUI($this->parent_gui->txt(PluginConfig::F_WORKFLOW_UNPUBLISH), PluginConfig::F_WORKFLOW_UNPUBLISH);
        $te->setInfo($this->parent_gui->txt(PluginConfig::F_WORKFLOW_UNPUBLISH . '_info'));
        $this->addItem($te);

        $te = new ilTextInputGUI($this->parent_gui->txt(PluginConfig::F_EDITOR_LINK), PluginConfig::F_EDITOR_LINK);
        $te->setInfo($this->parent_gui->txt(PluginConfig::F_EDITOR_LINK . '_info'));
        $this->addItem($te);

        $te = new ilTextInputGUI($this->parent_gui->txt(PluginConfig::F_SCHEDULE_CHANNEL), PluginConfig::F_SCHEDULE_CHANNEL);
        $te->setInfo($this->parent_gui->txt(PluginConfig::F_SCHEDULE_CHANNEL . '_info'));
        $te->setMulti(true);
        $this->addItem($te);

        $cb = new ilCheckboxInputGUI($this->parent_gui->txt(PluginConfig::F_CREATE_SCHEDULED_ALLOWED), PluginConfig::F_CREATE_SCHEDULED_ALLOWED);
        $cb->setInfo($this->parent_gui->txt(PluginConfig::F_CREATE_SCHEDULED_ALLOWED . '_info'));
        $this->addItem($cb);

        $cb = new ilCheckboxInputGUI($this->parent_gui->txt(PluginConfig::F_EXT_DL_SOURCE), PluginConfig::F_EXT_DL_SOURCE);
        $cb->setInfo($this->parent_gui->txt(PluginConfig::F_EXT_DL_SOURCE . '_info'));
        $this->addItem($cb);

        $cb = new ilCheckboxInputGUI($this->parent_gui->txt(PluginConfig::F_STUDIO_ALLOWED), PluginConfig::F_STUDIO_ALLOWED);
        $cb->setInfo($this->parent_gui->txt(PluginConfig::F_STUDIO_ALLOWED . '_info'));
        $this->addItem($cb);

        // Studio Link.
        $te = new ilTextInputGUI($this->parent_gui->txt(PluginConfig::F_STUDIO_URL), PluginConfig::F_STUDIO_URL);
        $te->setInfo($this->parent_gui->txt(PluginConfig::F_STUDIO_URL . '_info'));
        $cb->addSubItem($te);

        $cb = new ilCheckboxInputGUI($this->parent_gui->txt(PluginConfig::F_AUDIO_ALLOWED), PluginConfig::F_AUDIO_ALLOWED);
        $cb->setInfo($this->parent_gui->txt(PluginConfig::F_AUDIO_ALLOWED . '_info'));
        $this->addItem($cb);

        // INTERNAL VIDEO PLAYER
        $cb = new ilCheckboxInputGUI($this->parent_gui->txt(PluginConfig::F_INTERNAL_VIDEO_PLAYER), PluginConfig::F_INTERNAL_VIDEO_PLAYER);
        $cb->setInfo($this->parent_gui->txt(PluginConfig::F_INTERNAL_VIDEO_PLAYER . '_info'));
        $this->addItem($cb);

        $cbs = new ilCheckboxInputGUI($this->parent_gui->txt(PluginConfig::F_USE_GENERATED_STREAMING_URLS), PluginConfig::F_USE_GENERATED_STREAMING_URLS);
        $cbs->setInfo($this->parent_gui->txt(PluginConfig::F_USE_GENERATED_STREAMING_URLS . '_info'));
        $cbs->setRequired(false);
        $cb->addSubItem($cbs);

        $te = new ilTextInputGUI($this->parent_gui->txt(PluginConfig::F_STREAMING_URL), PluginConfig::F_STREAMING_URL);
        $te->setInfo($this->parent_gui->txt(PluginConfig::F_STREAMING_URL . '_info'));
        $te->setRequired(true);
        $cbs->addSubItem($te);

        $cbs = new ilCheckboxInputGUI($this->parent_gui->txt(PluginConfig::F_USE_HIGH_LOW_RES_SEGMENT_PREVIEWS), PluginConfig::F_USE_HIGH_LOW_RES_SEGMENT_PREVIEWS);
        $cbs->setInfo($this->parent_gui->txt(PluginConfig::F_USE_HIGH_LOW_RES_SEGMENT_PREVIEWS . '_info'));
        $cbs->setRequired(false);
        $cb->addSubItem($cbs);

        // LIVE STREAMS
        $cbs = new ilCheckboxInputGUI($this->parent_gui->txt(PluginConfig::F_ENABLE_LIVE_STREAMS), PluginConfig::F_ENABLE_LIVE_STREAMS);
        $cbs->setInfo($this->parent_gui->txt(PluginConfig::F_ENABLE_LIVE_STREAMS . '_info'));
        $cbs->setRequired(false);
        $this->addItem($cbs);

        $te = new ilTextInputGUI($this->parent_gui->txt(PluginConfig::F_PRESENTATION_NODE), PluginConfig::F_PRESENTATION_NODE);
        $te->setInfo($this->parent_gui->txt(PluginConfig::F_PRESENTATION_NODE . '_info'));
        $te->setRequired(true);
        $cbs->addSubItem($te);

        $te = new ilSelectInputGUI($this->parent_gui->txt(PluginConfig::F_LIVESTREAM_TYPE), PluginConfig::F_LIVESTREAM_TYPE);
        $te->setInfo($this->parent_gui->txt(PluginConfig::F_LIVESTREAM_TYPE . '_info'));
        $te->setOptions([
            'hls' => 'HLS (.m3u8 / .m3u)',
            'mpegts' => 'MPEG-TS (.ts)',
        ]);
        $te->setRequired(true);
        $cbs->addSubItem($te);

        $ni = new ilNumberInputGUI($this->parent_gui->txt(PluginConfig::F_START_X_MINUTES_BEFORE_LIVE), PluginConfig::F_START_X_MINUTES_BEFORE_LIVE);
        $ni->setInfo($this->parent_gui->txt(PluginConfig::F_START_X_MINUTES_BEFORE_LIVE . '_info'));
        $cbs->addSubItem($ni);

        $cbs2 = new ilCheckboxInputGUI($this->parent_gui->txt(PluginConfig::F_ENABLE_CHAT), PluginConfig::F_ENABLE_CHAT);
        $cbs2->setInfo($this->parent_gui->txt(PluginConfig::F_ENABLE_CHAT . '_info'));
        $cbs2->setRequired(false);
        $cbs->addSubItem($cbs2);

        // MODALS
        $cb = new ilCheckboxInputGUI($this->parent_gui->txt(PluginConfig::F_USE_MODALS), PluginConfig::F_USE_MODALS);
        $cb->setInfo($this->parent_gui->txt(PluginConfig::F_USE_MODALS . '_info'));
        $this->addItem($cb);


        // QUALITY REPORT
        $cb = new ilCheckboxInputGUI($this->parent_gui->txt(PluginConfig::F_REPORT_QUALITY), PluginConfig::F_REPORT_QUALITY);
        $cb->setInfo($this->parent_gui->txt(PluginConfig::F_REPORT_QUALITY . '_info'));
        $this->addItem($cb);

        $te = new ilTextInputGUI($this->parent_gui->txt(PluginConfig::F_REPORT_QUALITY_EMAIL), PluginConfig::F_REPORT_QUALITY_EMAIL);
        $te->setInfo($this->parent_gui->txt(PluginConfig::F_REPORT_QUALITY_EMAIL . '_info'));
        $te->setRequired(true);
        $cb->addSubItem($te);

        $te = new ilTextAreaInputGUI($this->parent_gui->txt(PluginConfig::F_REPORT_QUALITY_TEXT), PluginConfig::F_REPORT_QUALITY_TEXT);
        $te->setInfo($this->parent_gui->txt(PluginConfig::F_REPORT_QUALITY_TEXT . '_info'));
        $te->setRequired(true);
        $te->setRows(8);
        $te->setUseRte(1);
        $te->setRteTagSet("extended");
        $te->disableButtons([
            'charmap',
            'undo',
            'redo',
            'justifyleft',
            'justifycenter',
            'justifyright',
            'justifyfull',
            'anchor',
            'fullscreen',
            'cut',
            'copy',
            'paste',
            'pastetext',
            'formatselect',
        ]);
        $cb->addSubItem($te);

        $ri = new ilRadioGroupInputGUI($this->parent_gui->txt(PluginConfig::F_REPORT_QUALITY_ACCESS), PluginConfig::F_REPORT_QUALITY_ACCESS);
        $ro = new ilRadioOption($this->parent_gui->txt(PluginConfig::F_REPORT_QUALITY_ACCESS . '_' . PluginConfig::ACCESS_ALL), PluginConfig::ACCESS_ALL);
        $ri->addOption($ro);
        $ro = new ilRadioOption($this->parent_gui->txt(PluginConfig::F_REPORT_QUALITY_ACCESS . '_' . PluginConfig::ACCESS_OWNER_ADMIN), PluginConfig::ACCESS_OWNER_ADMIN);
        $ri->addOption($ro);
        $ri->setRequired(true);
        $cb->addSubItem($ri);


        // DATE REPORT
        $cb = new ilCheckboxInputGUI($this->parent_gui->txt(PluginConfig::F_REPORT_DATE), PluginConfig::F_REPORT_DATE);
        $cb->setInfo($this->parent_gui->txt(PluginConfig::F_REPORT_DATE . '_info'));
        $this->addItem($cb);

        $te = new ilTextInputGUI($this->parent_gui->txt(PluginConfig::F_REPORT_DATE_EMAIL), PluginConfig::F_REPORT_DATE_EMAIL);
        $te->setInfo($this->parent_gui->txt(PluginConfig::F_REPORT_DATE_EMAIL . '_info'));
        $te->setRequired(true);
        $cb->addSubItem($te);

        $te = new ilTextAreaInputGUI($this->parent_gui->txt(PluginConfig::F_REPORT_DATE_TEXT), PluginConfig::F_REPORT_DATE_TEXT);
        $te->setInfo($this->parent_gui->txt(PluginConfig::F_REPORT_DATE_TEXT . '_info'));
        $te->setRequired(true);
        $te->setRows(8);
        $te->setUseRte(true);
        $te->setRteTagSet("extended");
        $te->disableButtons([
            'charmap',
            'undo',
            'redo',
            'justifyleft',
            'justifycenter',
            'justifyright',
            'justifyfull',
            'anchor',
            'fullscreen',
            'cut',
            'copy',
            'paste',
            'pastetext',
            'formatselect',
        ]);
        $cb->addSubItem($te);

        // SCHEDULED METADATA EDITABLE
        $ri = new ilRadioGroupInputGUI($this->parent_gui->txt(PluginConfig::F_SCHEDULED_METADATA_EDITABLE), PluginConfig::F_SCHEDULED_METADATA_EDITABLE);
        $ro = new ilRadioOption($this->parent_gui->txt(PluginConfig::F_SCHEDULED_METADATA_EDITABLE . '_' . PluginConfig::NO_METADATA), PluginConfig::NO_METADATA);
        $ri->addOption($ro);
        $ro = new ilRadioOption($this->parent_gui->txt(PluginConfig::F_SCHEDULED_METADATA_EDITABLE . '_' . PluginConfig::ALL_METADATA), PluginConfig::ALL_METADATA);
        $ro->setInfo($this->parent_gui->txt(PluginConfig::F_SCHEDULED_METADATA_EDITABLE . '_' . PluginConfig::ALL_METADATA . '_info'));
        $ri->addOption($ro);
        $ro = new ilRadioOption($this->parent_gui->txt(PluginConfig::F_SCHEDULED_METADATA_EDITABLE . '_' . PluginConfig::METADATA_EXCEPT_DATE_PLACE), PluginConfig::METADATA_EXCEPT_DATE_PLACE);
        $ri->addOption($ro);
        $this->addItem($ri);
    }



    private function initToUSection()
    {
        $h = new ilFormSectionHeaderGUI();
        $h->setTitle($this->parent_gui->txt('eula'));
        $this->addItem($h);

        $te = new ilTextAreaInputGUI($this->parent_gui->txt(PluginConfig::F_EULA), PluginConfig::F_EULA);
        $te->setRequired(true);
        $te->setUseRte(true);
        $te->setRteTagSet("extended");
        $te->disableButtons([
            'charmap',
            'undo',
            'redo',
            'justifyleft',
            'justifycenter',
            'justifyright',
            'justifyfull',
            'anchor',
            'fullscreen',
            'cut',
            'copy',
            'paste',
            'pastetext',
            'formatselect',
        ]);
        $te->setRows(5);
        $this->addItem($te);

        // Terms of Use
        $terms = new ilCheckboxInputGUI($this->parent_gui->txt("accept_terms"), PluginConfig::F_ACCEPT_TERMS);
        $terms->setInfo($this->parent_gui->txt("accept_terms_info"));
        $this->addItem($terms);

        // Reset?
        $reset = new ilCheckboxInputGUI($this->parent_gui->txt(PluginConfig::F_RESET), PluginConfig::F_RESET);
        $reset->setInfo($this->parent_gui->txt(PluginConfig::F_RESET . "_info"));
        $this->addItem($reset);
    }

    /**
     *
     */
    protected function initGroupsRolesSection()
    {
        $h = new ilFormSectionHeaderGUI();
        $h->setTitle($this->parent_gui->txt('groups'));
        $this->addItem($h);

        // groups
        foreach (PluginConfig::$groups as $group) {
            $te = new ilTextInputGUI($this->parent_gui->txt($group), $group);
            $te->setInfo($this->parent_gui->txt($group . '_info'));
            $this->addItem($te);
        }

        $h = new ilFormSectionHeaderGUI();
        $h->setTitle($this->parent_gui->txt('roles'));
        $this->addItem($h);

        // standard roles
        $te = new ilTextInputGUI($this->parent_gui->txt('std_roles'), PluginConfig::F_STD_ROLES);
        $te->setInfo($this->parent_gui->txt('std_roles_info'));
        $te->setMulti(true);
        $te->setInlineStyle('min-width:250px');
        $this->addItem($te);

        $te = new ilTextInputGUI($this->parent_gui->txt(PluginConfig::F_ROLE_USER_ACTIONS), PluginConfig::F_ROLE_USER_ACTIONS);
        $te->setInfo($this->parent_gui->txt(PluginConfig::F_ROLE_USER_ACTIONS. "_info"));
        $te->setMulti(true);
        $this->addItem($te);

        // other roles
        foreach (PluginConfig::$roles as $role) {
            $te = new ilTextInputGUI($this->parent_gui->txt($role), $role);
            $te->setInfo($this->parent_gui->txt($role . '_info'));
            $te->setRequired(true);
            $this->addItem($te);
        }

        $cb = new ilCheckboxInputGUI($this->parent_gui->txt(PluginConfig::F_IDENTIFIER_TO_UPPERCASE), PluginConfig::F_IDENTIFIER_TO_UPPERCASE);
        $this->addItem($cb);
    }


    /**
     *
     */
    protected function initSecuritySection()
    {
        ilUtil::sendInfo($this->parent_gui->txt('security_info'), true);
        $h = new ilFormSectionHeaderGUI();
        $h->setTitle($this->parent_gui->txt('security'));
        $this->addItem($h);

        $cb = new ilCheckboxInputGUI($this->parent_gui->txt(PluginConfig::F_SIGN_PLAYER_LINKS), PluginConfig::F_SIGN_PLAYER_LINKS);
        $this->addItem($cb);

        $cb_sub = new ilCheckboxInputGUI($this->parent_gui->txt(PluginConfig::F_SIGN_PLAYER_LINKS_OVERWRITE_DEFAULT), PluginConfig::F_SIGN_PLAYER_LINKS_OVERWRITE_DEFAULT);
        $cb->addSubItem($cb_sub);

        $cb_sub_2 = new ilNumberInputGUI($this->parent_gui->txt(PluginConfig::F_SIGN_PLAYER_LINKS_ADDITIONAL_TIME_PERCENT), PluginConfig::F_SIGN_PLAYER_LINKS_ADDITIONAL_TIME_PERCENT);
        $cb_sub_2->setInfo($this->parent_gui->txt(PluginConfig::F_SIGN_PLAYER_LINKS_ADDITIONAL_TIME_PERCENT . '_info'));
        $cb_sub->addSubItem($cb_sub_2);

        $cb_sub = new ilCheckboxInputGUI($this->parent_gui->txt(PluginConfig::F_SIGN_PLAYER_LINKS_WITH_IP), PluginConfig::F_SIGN_PLAYER_LINKS_WITH_IP);
        $cb->addSubItem($cb_sub);

        $cb = new ilCheckboxInputGUI($this->parent_gui->txt(PluginConfig::F_SIGN_DOWNLOAD_LINKS), PluginConfig::F_SIGN_DOWNLOAD_LINKS);
        $cb->setInfo($this->parent_gui->txt(PluginConfig::F_SIGN_DOWNLOAD_LINKS . '_info'));
        $this->addItem($cb);

        $cb_sub = new ilNumberInputGUI($this->parent_gui->txt(PluginConfig::F_SIGN_DOWNLOAD_LINKS_TIME), PluginConfig::F_SIGN_DOWNLOAD_LINKS_TIME);
        $cb_sub->setInfo($this->parent_gui->txt(PluginConfig::F_SIGN_DOWNLOAD_LINKS_TIME . '_info'));
        $cb->addSubItem($cb_sub);

        $cb = new ilCheckboxInputGUI($this->parent_gui->txt(PluginConfig::F_SIGN_THUMBNAIL_LINKS), PluginConfig::F_SIGN_THUMBNAIL_LINKS);
        $this->addItem($cb);

        $cb_sub = new ilNumberInputGUI($this->parent_gui->txt(PluginConfig::F_SIGN_THUMBNAIL_LINKS_TIME), PluginConfig::F_SIGN_THUMBNAIL_LINKS_TIME);
        $cb_sub->setInfo($this->parent_gui->txt(PluginConfig::F_SIGN_THUMBNAIL_LINKS_TIME . '_info'));
        $cb->addSubItem($cb_sub);

        $cb_sub = new ilCheckboxInputGUI($this->parent_gui->txt(PluginConfig::F_SIGN_THUMBNAIL_LINKS_WITH_IP), PluginConfig::F_SIGN_THUMBNAIL_LINKS_WITH_IP);
        $cb->addSubItem($cb_sub);

        $cb = new ilCheckboxInputGUI($this->parent_gui->txt(PluginConfig::F_SIGN_ANNOTATION_LINKS), PluginConfig::F_SIGN_ANNOTATION_LINKS);
        $this->addItem($cb);

        $cb_sub = new ilNumberInputGUI($this->parent_gui->txt(PluginConfig::F_SIGN_ANNOTATION_LINKS_TIME), PluginConfig::F_SIGN_ANNOTATION_LINKS_TIME);
        $cb_sub->setInfo($this->parent_gui->txt(PluginConfig::F_SIGN_ANNOTATION_LINKS_TIME . '_info'));
        $cb->addSubItem($cb_sub);

        $cb_sub = new ilCheckboxInputGUI($this->parent_gui->txt(PluginConfig::F_SIGN_ANNOTATION_LINKS_WITH_IP), PluginConfig::F_SIGN_ANNOTATION_LINKS_WITH_IP);
        $cb->addSubItem($cb_sub);

        $cb = new ilCheckboxInputGUI($this->parent_gui->txt(PluginConfig::F_ANNOTATION_TOKEN_SEC), PluginConfig::F_ANNOTATION_TOKEN_SEC);
        $cb->setInfo($this->parent_gui->txt(PluginConfig::F_ANNOTATION_TOKEN_SEC . '_info'));
        $this->addItem($cb);

        $cb = new ilCheckboxInputGUI($this->parent_gui->txt(PluginConfig::F_PRESIGN_LINKS), PluginConfig::F_PRESIGN_LINKS);
        $cb->setInfo($this->parent_gui->txt(PluginConfig::F_PRESIGN_LINKS . '_info'));
        $this->addItem($cb);
    }


    protected function initAdvancedSection()
    {
        $h = new ilFormSectionHeaderGUI();
        $h->setTitle($this->parent_gui->txt('advanced'));
        $this->addItem($h);

        $cb = new ilCheckboxInputGUI($this->parent_gui->txt(PluginConfig::F_COMMON_IDP), PluginConfig::F_COMMON_IDP);
        $cb->setInfo($this->parent_gui->txt(PluginConfig::F_COMMON_IDP . '_info'));
        $this->addItem($cb);

        $te = new ilSelectInputGUI($this->parent_gui->txt(PluginConfig::F_USER_MAPPING), PluginConfig::F_USER_MAPPING);
        $te->setInfo($this->parent_gui->txt(PluginConfig::F_USER_MAPPING . '_info'));
        $te->setOptions([
            xoctUser::MAP_EXT_ID => 'External-ID',
            xoctUser::MAP_LOGIN => 'Login',
            xoctUser::MAP_EMAIL => 'E-Mail',
        ]);
        $this->addItem($te);

        $cb = new ilRadioGroupInputGUI($this->parent_gui->txt(PluginConfig::F_ACTIVATE_CACHE), PluginConfig::F_ACTIVATE_CACHE);
        $cb->setInfo($this->parent_gui->txt(PluginConfig::F_ACTIVATE_CACHE . '_info'));
        $opt = new ilRadioOption(
            $this->parent_gui->txt(PluginConfig::F_ACTIVATE_CACHE . '_' . PluginConfig::CACHE_DISABLED),
            PluginConfig::CACHE_DISABLED
        );
        $cb->addOption($opt);
        $opt = new ilRadioOption(
            $this->parent_gui->txt(PluginConfig::F_ACTIVATE_CACHE . '_' . PluginConfig::CACHE_STANDARD),
            PluginConfig::CACHE_STANDARD
        );
        $opt->setInfo($this->parent_gui->txt(PluginConfig::F_ACTIVATE_CACHE . '_' . PluginConfig::CACHE_STANDARD . '_info', '', []));
        $cb->addOption($opt);
        $opt = new ilRadioOption(
            $this->parent_gui->txt(PluginConfig::F_ACTIVATE_CACHE . '_' . PluginConfig::CACHE_DATABASE),
            PluginConfig::CACHE_DATABASE
        );
        $opt->setInfo($this->parent_gui->txt(PluginConfig::F_ACTIVATE_CACHE . '_' . PluginConfig::CACHE_DATABASE . '_info'));
        $cb->addOption($opt);
        $this->addItem($cb);

        $te = new ilSelectInputGUI($this->parent_gui->txt(PluginConfig::F_CURL_DEBUG_LEVEL), PluginConfig::F_CURL_DEBUG_LEVEL);
        $te->setInfo($this->parent_gui->txt(PluginConfig::F_CURL_DEBUG_LEVEL . '_info'));
        $te->setOptions([
            xoctLog::DEBUG_DEACTIVATED => $this->parent_gui->txt('log_level_' . xoctLog::DEBUG_DEACTIVATED),
            xoctLog::DEBUG_LEVEL_1 => $this->parent_gui->txt('log_level_' . xoctLog::DEBUG_LEVEL_1),
            xoctLog::DEBUG_LEVEL_2 => $this->parent_gui->txt('log_level_' . xoctLog::DEBUG_LEVEL_2),
            xoctLog::DEBUG_LEVEL_3 => $this->parent_gui->txt('log_level_' . xoctLog::DEBUG_LEVEL_3),
            xoctLog::DEBUG_LEVEL_4 => $this->parent_gui->txt('log_level_' . xoctLog::DEBUG_LEVEL_4),
        ]);
        $this->addItem($te);

        $cb = new ilCheckboxInputGUI($this->parent_gui->txt(PluginConfig::F_INGEST_UPLOAD), PluginConfig::F_INGEST_UPLOAD);
        $cb->setInfo($this->parent_gui->txt(PluginConfig::F_INGEST_UPLOAD . '_info'));
        $this->addItem($cb);

        $cb = new ilCheckboxInputGUI($this->parent_gui->txt(PluginConfig::F_LOAD_TABLE_SYNCHRONOUSLY), PluginConfig::F_LOAD_TABLE_SYNCHRONOUSLY);
        $cb->setInfo($this->parent_gui->txt(PluginConfig::F_LOAD_TABLE_SYNCHRONOUSLY . '_info'));
        $this->addItem($cb);
    }
}
