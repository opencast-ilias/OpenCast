<?php

use srag\Plugins\Opencast\DI\OpencastDIC;
use srag\DIC\OpenCast\Exception\DICException;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsageGroup;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsageGroupRepository;

/**
 * Class xoctPublicationGroupTableGUI
 *
 * @author Farbod Zamani Boroujeni <zamani@elan-ev.de>
 */
class xoctPublicationGroupTableGUI extends ilTable2GUI
{
    public const TBL_ID = 'tbl_xoct_pub_g';
    /**
     * @var array
     */
    protected $filter = [];
    /**
     * @var ilOpenCastPlugin
     */
    protected $plugin;
    /**
     * @var OpencastDIC
     */
    protected $container;


    /**
     * @param xoctPublicationUsageGUI $a_parent_obj
     * @param string                  $a_parent_cmd
     */
    public function __construct(xoctPublicationUsageGUI $a_parent_obj, $a_parent_cmd)
    {
        parent::__construct($a_parent_obj, $a_parent_cmd);
        $this->container = OpencastDIC::getInstance();
        $this->plugin = $this->container->plugin();
        $this->setId(self::TBL_ID);
        $this->setPrefix(self::TBL_ID);
        $this->setFormName(self::TBL_ID);
        $this->ctrl->saveParameter($a_parent_obj, $this->getNavParameter());
        $this->parent_obj = $a_parent_obj;
        $this->setTitle($this->parent_obj->txt('table_title_usage_group'));
        $this->setRowTemplate(
            'tpl.publication_group.html',
            'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast'
        );
        $this->setFormAction($this->ctrl->getFormAction($a_parent_obj));
        $this->initColumns();
        $this->parseData();
    }


    /**
     * @param array $a_set
     *
     * @throws DICException
     */
    public function fillRow($a_set)
    {
        /**
         * @var $PublicationUsageGroup PublicationUsageGroup
         */
        $xoctPublicationUsageGroup = PublicationUsageGroup::find($a_set['id']);
        $this->tpl->setVariable('NAME', $xoctPublicationUsageGroup->getName());
        $this->tpl->setVariable('DISPLAY_NAME', $xoctPublicationUsageGroup->getDisplayName());
        $this->tpl->setVariable('DESCRIPTION', $xoctPublicationUsageGroup->getDescription());

        $this->addActionMenu($xoctPublicationUsageGroup);
    }


    protected function initColumns()
    {
        $this->addColumn($this->parent_obj->txt('group_name'));
        $this->addColumn($this->parent_obj->txt('group_display_name'));
        $this->addColumn($this->parent_obj->txt('group_description'));

        $this->addColumn($this->plugin->txt('common_actions'), '', '150px');
    }


    /**
     * @param PublicationUsageGroup $xoctPublicationUsageGroup
     *
     * @throws DICException
     */
    protected function addActionMenu(PublicationUsageGroup $xoctPublicationUsageGroup)
    {
        $current_selection_list = new ilAdvancedSelectionListGUI();
        $current_selection_list->setListTitle($this->plugin->txt('common_actions'));
        $current_selection_list->setId(self::TBL_ID . '_actions_' . $xoctPublicationUsageGroup->getId());
        $current_selection_list->setUseImages(false);

        $this->ctrl->setParameter($this->parent_obj, 'id', $xoctPublicationUsageGroup->getId());
        $current_selection_list->addItem(
            $this->parent_obj->txt(xoctPublicationUsageGUI::CMD_EDIT),
            xoctPublicationUsageGUI::CMD_EDIT_GROUP,
            $this->ctrl->getLinkTarget($this->parent_obj, xoctPublicationUsageGUI::CMD_EDIT_GROUP)
        );
        $current_selection_list->addItem(
            $this->parent_obj->txt(xoctPublicationUsageGUI::CMD_DELETE),
            xoctPublicationUsageGUI::CMD_DELETE_GROUP,
            $this->ctrl->getLinkTarget($this->parent_obj, xoctPublicationUsageGUI::CMD_CONFIRM_DELETE_GROUP)
        );

        $this->tpl->setVariable('ACTIONS', $current_selection_list->getHTML());
    }


    protected function parseData()
    {
        $groups = PublicationUsageGroupRepository::getSortedArrayList();
        $this->setData($groups);
    }


    /**
     * @param $item
     */
    protected function addAndReadFilterItem(ilFormPropertyGUI $item)
    {
        $this->addFilterItem($item);
        $item->readFromSession();
        if ($item instanceof ilCheckboxInputGUI) {
            $this->filter[$item->getPostVar()] = $item->getChecked();
        } else {
            $this->filter[$item->getPostVar()] = $item->getValue();
        }
    }
}
