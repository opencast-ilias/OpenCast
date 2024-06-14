<?php

declare(strict_types=1);

use ILIAS\HTTP\Services;
use srag\Plugins\Opencast\Model\Object\ObjectSettings;

/**
 * Why is this a trait: The class `ilObjOpenCastGUI` is already very large and quite overloaded with methods. in addition, this is a special case where ILIAS is actually "bypassed" and code is overwritten by ILIAS. Therefore, all this functionality is stored in a trait to show how much code is needed and how easy it would be to remove it.
 *
 * @author Fabian Schmid <fabian@sr.solutions>
 *
 * @property Services $http
 * @property ilCtrl   $ctrl
 */
trait ilObjShowDuplicates
{

    private $items_to_delete = [];

    /**
     * Overwritten/copied to allow recognition of duplicates and show them in delete confirmation
     */
    #[ReturnTypeWillChange]
    public function deleteObject(bool $error = false): void
    {
        $item_ref_id = $this->http->request()->getQueryParams()["item_ref_id"] ?? null;

        if ($item_ref_id !== null) {
            $this->items_to_delete['id'] = [$item_ref_id];
        }

        $post = $this->http->request()->getParsedBody() ?? null;
        $value = $post["id"] ?? null;
        if (is_array($value)) {
            foreach ($value as $idx => $id) {
                $this->items_to_delete["id"][$idx] = (int) $id;
            }
        }

        // SAVE POST VALUES (get rid of this
        ilSession::set("saved_post", $this->items_to_delete["id"]);

        if (!$this->showDeleteConfirmation($this->items_to_delete["id"] ?? [], $error)) {
            $this->ctrl->returnToParent($this);
        }
    }

    /**
     * Overwritten/copied to allow recognition of duplicates and show them in delete confirmation
     */
    public function showDeleteConfirmation(array $a_ids, bool $a_supress_message = false): bool
    {
        // dependendies
        $il_setting = $this->ilias_dic->settings();
        $main_tpl = $this->tpl;
        $il_language = $this->ilias_dic->language();
        $ctrl = $this->ilias_dic->ctrl();

        if (!is_array($a_ids) || count($a_ids) == 0) {
            $main_tpl->setOnScreenMessage('failure', $il_language->txt("no_checkbox"), true);

            return false;
        }

        // Remove duplicate entries
        $a_ids = array_map('intval', array_unique($a_ids));

        $cgui = new ilConfirmationGUI();

        if (!$a_supress_message) {
            $msg = $il_language->txt("info_delete_sure");

            if ($il_setting->get('enable_trash') === ''
                || $il_setting->get('enable_trash') === '0') {
                $msg .= "<br/>" . $il_language->txt("info_delete_warning_no_trash");
            }

            $cgui->setHeaderText($msg);
        }

        $cgui->setFormAction($ctrl->getFormAction($this));
        $cgui->setCancel($il_language->txt("cancel"), "cancelDelete");
        $cgui->setConfirm($il_language->txt("confirm"), "confirmedDelete");

        $form_name = "cgui_" . md5(uniqid('', true));
        $cgui->setFormName($form_name);

        $deps = [];
        foreach ($a_ids as $ref_id) {
            $obj_id = ilObject::_lookupObjId($ref_id);
            $type = ilObject::_lookupType($obj_id);
            $title = call_user_func([ilObjectFactory::getClassByType($type), '_lookupTitle'], $obj_id);
            /*$alt = $il_language->txt("icon") . " " . ilPlugin::lookupTxt(
                    "rep_robj",
                    $type,
                    "obj_" . $type
                );*/

            $title .= $this->handleMultiReferences($obj_id, $ref_id, $form_name);

            $cgui->addItem("id[]", (string) $ref_id, $title, ilObject::_getIcon($obj_id, "small", $type), '');

            ilObject::collectDeletionDependencies($deps, $ref_id, $obj_id, $type);
        }
        $deps_html = "";

        if (is_array($deps) && $deps !== []) {
            $tab = new ilRepDependenciesTableGUI($deps);
            $deps_html = "<br/><br/>" . $tab->getHTML();
        }

        $main_tpl->setContent($cgui->getHTML() . $deps_html);

        return true;
    }

    /**
     * Overwritten/copied to allow recognition of duplicates and show them in delete confirmation
     */
    protected function buildPath(array $ref_ids): ?array
    {
        // dependencies
        $tree = $this->ilias_dic->repositoryTree();

        if ($ref_ids === []) {
            return null;
        }

        $result = [];
        foreach ($ref_ids as $ref_id) {
            $path = "";
            $path_full = $tree->getPathFull($ref_id);
            foreach ($path_full as $idx => $data) {
                if ($idx) {
                    $path .= " &raquo; ";
                }
                if ($ref_id != $data['ref_id']) {
                    $path .= $data['title'];
                } else {
                    $path .= ('<a target="_top" href="' . ilLink::_getLink(
                            (int) $data['ref_id'],
                            $data['type']
                        ) . '">' . $data['title'] . '</a>');
                }
            }

            $result[] = $path;
        }

        return $result;
    }

    /**
     * Overwritten/copied to allow recognition of duplicates and show them in delete confirmation
     */
    public function handleMultiReferences(int $a_obj_id, int $a_ref_id, string $a_form_name): string
    {
        // dependencies
        $il_language = $this->ilias_dic->language();
        $il_tree = $this->ilias_dic->repositoryTree();
        $il_access_handler = $this->ilias_dic->access();

        // process

        /** @var ObjectSettings $objectSettings */
        $objectSettings = ObjectSettings::find($a_obj_id);
        if ($all_refs = $objectSettings->getDuplicatesOnSystem()) {
            $il_language->loadLanguageModule("rep");

            $may_delete_any = 0;
            $counter = 0;
            $items = [];
            foreach ($all_refs as $mref_id) {
                // not the already selected reference, no refs from trash

                if ($mref_id != $a_ref_id && !$il_tree->isDeleted($mref_id)) {
                    if ($il_access_handler->checkAccess("read", "", $mref_id)) {
                        $may_delete = false;
                        if ($il_access_handler->checkAccess("delete", "", $mref_id)) {
                            $may_delete = true;
                            $may_delete_any++;
                        }

                        $path = $this->buildPath([$mref_id]);
                        $items[] = [
                            "id" => $mref_id,
                            "path" => array_shift($path),
                            "delete" => $may_delete
                        ];
                    } else {
                        $counter++;
                    }
                }
            }

            // render
            $tpl = new ilTemplate(
                "./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/default/tpl.rep_multi_ref.html",
                true, true
            );

            $tpl->setVariable("TXT_INTRO", $il_language->txt("rep_multiple_reference_deletion_intro"));

            if ($may_delete_any !== 0) {
                $tpl->setVariable(
                    "TXT_INSTRUCTION",
                    $il_language->txt("rep_multiple_reference_deletion_instruction")
                );
            }

            if ($items !== []) {
                $var_name = "mref_id[]";

                foreach ($items as $item) {
                    if ($item["delete"]) {
                        $tpl->setCurrentBlock("cbox");
                        $tpl->setVariable("ITEM_NAME", $var_name);
                        $tpl->setVariable("ITEM_VALUE", $item["id"]);
                    } else {
                        $tpl->setCurrentBlock("item_info");
                        $tpl->setVariable(
                            "TXT_ITEM_INFO",
                            $il_language->txt("rep_no_permission_to_delete")
                        );
                    }
                    $tpl->parseCurrentBlock();

                    $tpl->setCurrentBlock("item");
                    $tpl->setVariable("ITEM_TITLE", $item["path"]);
                    $tpl->parseCurrentBlock();
                }

                if ($may_delete_any > 1) {
                    $tpl->setCurrentBlock("cbox");
                    $tpl->setVariable("ITEM_NAME", "sall_" . $a_ref_id);
                    $tpl->setVariable("ITEM_VALUE", "");
                    $tpl->setVariable(
                        "ITEM_ADD",
                        " onclick=\"il.Util.setChecked('" . $a_form_name . "', '" . $var_name . "', document."
                        . $a_form_name . ".sall_" . $a_ref_id . ".checked)\""
                    );
                    $tpl->parseCurrentBlock();

                    $tpl->setCurrentBlock("item");
                    $tpl->setVariable("ITEM_TITLE", $il_language->txt("select_all"));
                    $tpl->parseCurrentBlock();
                }
            }

            if ($counter !== 0) {
                $tpl->setCurrentBlock("add_info");
                $tpl->setVariable(
                    "TXT_ADDITIONAL_INFO",
                    sprintf($il_language->txt("rep_object_references_cannot_be_read"), $counter)
                );
                $tpl->parseCurrentBlock();
            }

            return $tpl->get();
        }

        return '';
    }
}
