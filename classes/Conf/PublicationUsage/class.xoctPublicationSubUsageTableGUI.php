<?php

use srag\Plugins\Opencast\DI\OpencastDIC;
use srag\DIC\OpenCast\Exception\DICException;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationSubUsage;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsageGroup;

/**
 * Class xoctPublicationSubUsageTableGUI
 *
 * @author Farbod Zamani Boroujeni <zamani@elan-ev.de>
 */
class xoctPublicationSubUsageTableGUI extends ilTable2GUI
{
    public const TBL_ID = 'tbl_xoct_pub_sub_u';
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
        $this->setTitle($this->parent_obj->txt('table_title_sub_usage'));
        $this->setRowTemplate(
            'tpl.publication_sub_usage.html',
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
         * @var $PublicationSubUsage PublicationSubUsage
         */
        $PublicationSubUsage = PublicationSubUsage::find($a_set['id']);
        $this->tpl->setVariable('PARENT_USAGE_ID', $PublicationSubUsage->getParentUsageId());
        $this->tpl->setVariable('TITLE', $PublicationSubUsage->getTitle());
        $this->tpl->setVariable('DISPLAY_NAME', $PublicationSubUsage->getDisplayName());
        $this->tpl->setVariable('DESCRIPTION', $PublicationSubUsage->getDescription());
        $this->tpl->setVariable('CHANNEL', $PublicationSubUsage->getChannel());
        $this->tpl->setVariable('MD_TYPE', $this->parent_obj->txt('md_type_' . $PublicationSubUsage->getMdType()));
        if ($PublicationSubUsage->getMdType() === PublicationUsage::MD_TYPE_PUBLICATION_ITSELF) {
            $this->tpl->setVariable('FLAVOR', '&nbsp');
            $this->tpl->setVariable('TAG', '&nbsp');
        } elseif ($PublicationSubUsage->getSearchKey() == xoctPublicationUsageFormGUI::F_FLAVOR) {
            $this->tpl->setVariable('FLAVOR', $PublicationSubUsage->getFlavor());
            $this->tpl->setVariable('TAG', '&nbsp');
        } else {
            $this->tpl->setVariable('TAG', $PublicationSubUsage->getTag());
            $this->tpl->setVariable('FLAVOR', '&nbsp');
        }
        $group_name = '';
        if (!is_null($PublicationSubUsage->getGroupId())) {
            $PublicationUsageGroup = PublicationUsageGroup::find($PublicationSubUsage->getGroupId());
            $group_name = $PublicationUsageGroup ? $PublicationUsageGroup->getName() : $group_name;
        }
        $this->tpl->setVariable('GROUP_NAME', $group_name);

        $extras = [];
        if ($PublicationSubUsage->getParentUsageId() == PublicationUsage::USAGE_DOWNLOAD ||
            $PublicationSubUsage->getParentUsageId() == PublicationUsage::USAGE_DOWNLOAD_FALLBACK) {
            if ($PublicationSubUsage->isExternalDownloadSource()) {
                $extras[] = $this->parent_obj->txt('ext_dl_source');
            }
        }
        $this->tpl->setVariable('EXTRA_CONFIG', implode('<br>', $extras));

        $this->addActionMenu($PublicationSubUsage);
    }


    protected function initColumns()
    {
        $this->addColumn($this->parent_obj->txt('parent_usage_id'));
        $this->addColumn($this->parent_obj->txt('title'));
        $this->addColumn($this->parent_obj->txt('display_name'));
        $this->addColumn($this->parent_obj->txt('description'));
        $this->addColumn($this->parent_obj->txt('channel'));
        $this->addColumn($this->parent_obj->txt('md_type'));
        $this->addColumn($this->parent_obj->txt('flavor'));
        $this->addColumn($this->parent_obj->txt('tag'));
        $this->addColumn($this->parent_obj->txt('group_th'));
        $this->addColumn($this->parent_obj->txt('extra_config'));

        $this->addColumn($this->plugin->txt('common_actions'), '', '150px');
    }


    /**
     * @param PublicationSubUsage $PublicationSubUsage
     *
     * @throws DICException
     */
    protected function addActionMenu(PublicationSubUsage $PublicationSubUsage)
    {
        $current_selection_list = new ilAdvancedSelectionListGUI();
        $current_selection_list->setListTitle($this->plugin->txt('common_actions'));
        $current_selection_list->setId(self::TBL_ID . '_actions_' . $PublicationSubUsage->getId());
        $current_selection_list->setUseImages(false);

        $this->ctrl->setParameter($this->parent_obj, 'id', $PublicationSubUsage->getId());
        $current_selection_list->addItem(
            $this->parent_obj->txt(xoctPublicationUsageGUI::CMD_EDIT),
            xoctPublicationUsageGUI::CMD_EDIT_SUB,
            $this->ctrl->getLinkTarget($this->parent_obj, xoctPublicationUsageGUI::CMD_EDIT_SUB)
        );
        $current_selection_list->addItem(
            $this->parent_obj->txt(xoctPublicationUsageGUI::CMD_DELETE),
            xoctPublicationUsageGUI::CMD_DELETE_SUB,
            $this->ctrl->getLinkTarget($this->parent_obj, xoctPublicationUsageGUI::CMD_CONFIRM_DELETE_SUB)
        );

        $this->tpl->setVariable('ACTIONS', $current_selection_list->getHTML());
    }


    protected function parseData()
    {
        $subs = PublicationSubUsage::getArray();
        // Sorting by parent usage id.
        usort($subs, function ($sub1, $sub2) {
            return strcmp($sub1['parent_usage_id'], $sub2['parent_usage_id']);
        });
        // Sorting by title.
        usort($subs, function ($sub1, $sub2) {
            return strcmp($sub1['title'], $sub2['title']);
        });
        $this->setData($subs);
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
