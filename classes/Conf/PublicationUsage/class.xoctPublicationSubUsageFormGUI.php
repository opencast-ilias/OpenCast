<?php

use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsageGroup;
use srag\Plugins\Opencast\DI\OpencastDIC;

/**
 * Class xoctPublicationSubUsageFormGUI
 *
 * @author Farbod Zamani Boroujeni <zamani@elan-ev.de>
 */
class xoctPublicationSubUsageFormGUI extends ilPropertyFormGUI
{
    public const F_PARENT_USAGE_ID = 'parent_usage_id';
    public const F_TITLE = 'title';
    public const F_DESCRIPTION = 'description';
    public const F_DISPLAY_NAME = 'display_name';
    public const F_GROUP_ID = 'group_id';
    public const F_CHANNEL = 'channel';
    public const F_STATUS = 'status';
    public const F_SEARCH_KEY = 'search_key';
    public const F_FLAVOR = PublicationUsage::SEARCH_KEY_FLAVOR;
    public const F_TAG = PublicationUsage::SEARCH_KEY_TAG;
    public const F_MD_TYPE = 'md_type';
    public const F_ALLOW_MULTIPLE = 'allow_multiple';
    public const F_MEDIATYPE = 'mediatype';
    public const F_IGNORE_OBJECT_SETTINGS = 'ignore_object_settings';
    public const F_EXT_DL_SOURCE = 'ext_dl_source';

    /**
     * @var  PublicationUsage
     */
    protected $object;
    /**
     * @var xoctPublicationUsageGUI
     */
    protected $parent_gui;
    /**
     * @var bool $is_new
     */
    protected $is_new = true;
    /**
     * @var ilOpenCastPlugin
     */
    protected $plugin;
    /**
     * @var OpencastDIC
     */
    protected $container;


    /**
     * @param xoctPublicationUsageGUI $parent_gui
     * @param xoctPublicationSubUsage $xoctPublicationSubUsage
     */
    public function __construct($parent_gui, $xoctPublicationSubUsage, $is_new = true)
    {
        global $DIC;
        $this->container = OpencastDIC::getInstance();
        $this->plugin = $this->container->plugin();
        $DIC->ui()->mainTemplate()->addJavaScript(
            $this->plugin->getDirectory().'/js/opencast/dist/index.js'
        );
        $DIC->ui()->mainTemplate()->addOnLoadCode('il.Opencast.Form.publicationUsage.init()');
        parent::__construct();
        $this->object = $xoctPublicationSubUsage;
        $this->parent_gui = $parent_gui;
        $this->parent_gui->setTab();
        $this->ctrl->saveParameter($parent_gui, 'id');
        $this->is_new = $is_new;
        $this->initForm();
    }


    /**
     *
     */
    protected function initForm()
    {
        $this->setTarget('_top');
        $this->setFormAction($this->ctrl->getFormAction($this->parent_gui));
        $this->initButtons();

        $te = new ilTextInputGUI($this->parent_gui->txt(self::F_PARENT_USAGE_ID), self::F_PARENT_USAGE_ID);
        $te->setRequired(true);
        $te->setDisabled(true);
        $this->addItem($te);

        $te = new ilTextInputGUI($this->parent_gui->txt(self::F_TITLE), self::F_TITLE);
        $te->setRequired(true);
        $te->setDisabled(true);
        $this->addItem($te);

        // F_DISPLAY_NAME
        $max_lenght = 20;
        $display_name = (!empty($this->object->getDisplayName()) ? $this->object->getDisplayName() : '{added display name}');
        $info = sprintf($this->parent_gui->txt(self::F_DISPLAY_NAME . '_info'), $max_lenght, strtolower($display_name));
        $te = new ilTextInputGUI($this->parent_gui->txt(self::F_DISPLAY_NAME), self::F_DISPLAY_NAME);
        $te->setInfo($info);
        $te->setMaxLength($max_lenght);
        $this->addItem($te);

        $te = new ilTextAreaInputGUI($this->parent_gui->txt(self::F_DESCRIPTION), self::F_DESCRIPTION);
        $this->addItem($te);

        // F_GROUP_ID
        $xoctPublicationUsageGroupsArray = PublicationUsageGroup::getArray('id', 'name');
        $empty_groups = ['' => '-'];
        $publication_groups = $empty_groups + $xoctPublicationUsageGroupsArray;
        $te = new ilSelectInputGUI($this->parent_gui->txt(self::F_GROUP_ID), self::F_GROUP_ID);
        $te->setOptions($publication_groups);
        $this->addItem($te);

        $te = new ilTextInputGUI($this->parent_gui->txt(self::F_CHANNEL), self::F_CHANNEL);
        $te->setRequired(true);
        $this->addItem($te);

        $te = new ilSelectInputGUI($this->parent_gui->txt(self::F_MD_TYPE), self::F_MD_TYPE);
        $te->setRequired(true);
        $te->setOptions([
            PublicationUsage::MD_TYPE_PUBLICATION_ITSELF => $this->parent_gui->txt(
                                                                'md_type_' . PublicationUsage::MD_TYPE_PUBLICATION_ITSELF
                                                            ),
            PublicationUsage::MD_TYPE_ATTACHMENT         => $this->parent_gui->txt(
                                                                'md_type_' . PublicationUsage::MD_TYPE_ATTACHMENT
                                                            ),
            PublicationUsage::MD_TYPE_MEDIA              => $this->parent_gui->txt('md_type_' . PublicationUsage::MD_TYPE_MEDIA)
        ]);
        $this->addItem($te);

        $radio = new ilRadioGroupInputGUI($this->parent_gui->txt(self::F_SEARCH_KEY), self::F_SEARCH_KEY);
        $radio->setInfo($this->parent_gui->txt(self::F_SEARCH_KEY . '_info'));

        $opt = new ilRadioOption($this->parent_gui->txt(self::F_FLAVOR), self::F_FLAVOR);
        $te = new ilTextInputGUI('', self::F_FLAVOR);
        $te->setInfo($this->parent_gui->txt(self::F_FLAVOR . '_info'));
        $opt->addSubItem($te);
        $radio->addOption($opt);

        $opt = new ilRadioOption($this->parent_gui->txt(self::F_TAG), self::F_TAG);
        $te = new ilTextInputGUI('', self::F_TAG);
        $opt->addSubItem($te);
        $radio->addOption($opt);

        $radio->setValue(self::F_FLAVOR);
        $this->addItem($radio);

        //F_MEDIATYPE
        $te = new ilTextInputGUI($this->parent_gui->txt(self::F_MEDIATYPE), self::F_MEDIATYPE);
        $te->setInfo($this->parent_gui->txt(self::F_MEDIATYPE . '_info'));
        $this->addItem($te);

        if (in_array($this->object->getParentUsageId(),
            [PublicationUsage::USAGE_DOWNLOAD, PublicationUsage::USAGE_DOWNLOAD_FALLBACK])) {
            $allow_multiple = new ilCheckboxInputGUI($this->parent_gui->txt(self::F_ALLOW_MULTIPLE), self::F_ALLOW_MULTIPLE);
            $allow_multiple->setInfo($this->parent_gui->txt(self::F_ALLOW_MULTIPLE . '_info'));
            //F_IGNORE_OBJECT_SETTINGS
            $ignore_object_setting = new ilCheckboxInputGUI(
                                        $this->parent_gui->txt(self::F_IGNORE_OBJECT_SETTINGS),
                                        self::F_IGNORE_OBJECT_SETTINGS
                                    );
            $ignore_object_setting->setInfo($this->parent_gui->txt(self::F_IGNORE_OBJECT_SETTINGS . '_info'));
            //F_EXT_DL_SOURCE
            $ext_dl_source = new ilCheckboxInputGUI(
                $this->parent_gui->txt(self::F_EXT_DL_SOURCE),
                self::F_EXT_DL_SOURCE
            );
            $ext_dl_source->setInfo($this->parent_gui->txt(self::F_EXT_DL_SOURCE . '_info'));
        } else {
            $allow_multiple = new ilHiddenInputGUI(self::F_ALLOW_MULTIPLE);
            $allow_multiple->setValue(0);
            $ignore_object_setting = new ilHiddenInputGUI(self::F_IGNORE_OBJECT_SETTINGS);
            $ignore_object_setting->setValue(0);
            $ext_dl_source = new ilHiddenInputGUI(self::F_EXT_DL_SOURCE);
            $ext_dl_source->setValue(0);
        }
        $this->addItem($allow_multiple);
        $this->addItem($ignore_object_setting);
        $this->addItem($ext_dl_source);
    }


    /**
     *
     */
    public function fillForm()
    {
        $array = [
            self::F_PARENT_USAGE_ID => $this->object->getParentUsageId(),
            self::F_TITLE => $this->object->getTitle(),
            self::F_DISPLAY_NAME => $this->object->getDisplayName(),
            self::F_DESCRIPTION => $this->object->getDescription(),
            self::F_GROUP_ID => $this->object->getGroupId(),
            self::F_CHANNEL => $this->object->getChannel(),
            self::F_SEARCH_KEY => $this->object->getSearchKey(),
            self::F_FLAVOR => $this->object->getFlavor(),
            self::F_TAG => $this->object->getTag(),
            self::F_MD_TYPE => $this->object->getMdType(),
            self::F_ALLOW_MULTIPLE => $this->object->isAllowMultiple(),
            self::F_MEDIATYPE => $this->object->getMediaType(),
            self::F_IGNORE_OBJECT_SETTINGS => $this->object->ignoreObjectSettings(),
            self::F_EXT_DL_SOURCE => $this->object->isExternalDownloadSource(),
        ];

        $this->setValuesByArray($array);
    }


    /**
     * returns whether checkinput was successful or not.
     *
     * @return bool
     */
    public function fillObject(): bool
    {
        if (!$this->checkInput()) {
            return false;
        }

        $this->object->setParentUsageId($this->getInput(self::F_PARENT_USAGE_ID));
        $this->object->setTitle($this->getInput(self::F_TITLE));
        $this->object->setDisplayName($this->getInput(self::F_DISPLAY_NAME));
        $this->object->setDescription($this->getInput(self::F_DESCRIPTION));
        $this->object->setGroupId($this->getInput(self::F_GROUP_ID));
        $this->object->setChannel($this->getInput(self::F_CHANNEL));
        $this->object->setSearchKey($this->getInput(self::F_SEARCH_KEY));
        $this->object->setFlavor($this->getInput(self::F_FLAVOR));
        $this->object->setTag($this->getInput(self::F_TAG));
        $this->object->setMdType($this->getInput(self::F_MD_TYPE));
        $this->object->setAllowMultiple((bool)$this->getInput(self::F_ALLOW_MULTIPLE));
        $this->object->setMediaType($this->getInput(self::F_MEDIATYPE));
        $this->object->setIgnoreObjectSettings((bool)$this->getInput(self::F_IGNORE_OBJECT_SETTINGS));
        $this->object->setExternalDownloadSource((bool)$this->getInput(self::F_EXT_DL_SOURCE));

        return true;
    }


    /**
     * @return bool
     */
    public function saveObject(): bool
    {
        if (!$this->fillObject()) {
            return false;
        }
        if ($this->is_new) {
            $this->object->create();
        } else {
            $this->object->update();
        }

        return true;
    }


    /**
     *
     */
    protected function initButtons()
    {
        if ($this->is_new) {
            $this->setTitle($this->parent_gui->txt('create_sub'));
            $this->addCommandButton(
                xoctPublicationUsageGUI::CMD_CREATE_SUB,
                $this->parent_gui->txt(xoctPublicationUsageGUI::CMD_CREATE)
            );
        } else {
            $this->setTitle($this->parent_gui->txt('edit_sub'));
            $this->addCommandButton(
                xoctPublicationUsageGUI::CMD_UPDATE_SUB,
                $this->parent_gui->txt(xoctPublicationUsageGUI::CMD_UPDATE)
            );
        }

        $this->addCommandButton(xoctPublicationUsageGUI::CMD_CANCEL, $this->parent_gui->txt(xoctPublicationUsageGUI::CMD_CANCEL));
    }
}
