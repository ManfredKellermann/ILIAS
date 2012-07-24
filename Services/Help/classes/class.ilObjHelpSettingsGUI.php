<?php
/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/Object/classes/class.ilObject2GUI.php");

/**
 * Help settings gui class
 *
 * @author Alex Killing <alex.killing@gmx.de>
 * @version $Id$
 *
 * @ilCtrl_Calls ilObjHelpSettingsGUI: ilPermissionGUI
 * @ilCtrl_isCalledBy ilObjHelpSettingsGUI: ilAdministrationGUI
 *
 * @ingroup ServicesHelp
 */
class ilObjHelpSettingsGUI extends ilObject2GUI
{
	/**
	 * Constructor (please note that we, due to the general call in
	 * ilAdministrationGUI, we simulate the old ilObjectGUI constructor here)
	 *
	 * @param
	 * @return
	 */
	function __construct($a_data, $a_ref_id)
	{
		parent::__construct($a_ref_id);
	}
	
	
	/**
	 * Get type
	 */
	function getType()
	{
		return "hlps";
	}

	/**
	 * Execute command
	 *
	 * @access public
	 *
	 */
	public function executeCommand()
	{
		global $rbacsystem, $ilErr, $ilAccess, $lng;
		
		$lng->loadLanguageModule("help");

		$next_class = $this->ctrl->getNextClass($this);
		$cmd = $this->ctrl->getCmd();

		$this->prepareOutput();

		if (!$ilAccess->checkAccess('read','',$this->object->getRefId()))
		{
			$ilErr->raiseError($this->lng->txt('no_permission'),$ilErr->WARNING);
		}

		switch($next_class)
		{
			case 'ilpermissiongui':
				$this->tabs_gui->setTabActive('perm_settings');
				include_once("Services/AccessControl/classes/class.ilPermissionGUI.php");
				$perm_gui =& new ilPermissionGUI($this);
				$ret =& $this->ctrl->forwardCommand($perm_gui);
				break;

			default:
				if(!$cmd || $cmd == 'view')
				{
					$cmd = "editSettings";
				}

				$this->$cmd();
				break;
		}
		return true;
	}

	/**
	* Edit news settings.
	*/
	public function editSettings()
	{
		global $ilCtrl, $lng, $ilSetting, $ilTabs, $ilToolbar;
		
		$ilTabs->activateTab("settings");
		
		if ($this->checkPermissionBool("write"))
		{
			// help file
			include_once("./Services/Form/classes/class.ilFileInputGUI.php");
			$fi = new ilFileInputGUI($lng->txt("help_help_file"), "help_file");
			$fi->setSuffixes(array("zip"));
			$ilToolbar->addInputItem($fi, true);
			$ilToolbar->addFormButton($lng->txt("upload"), "uploadHelpFile");
		}
		$ilToolbar->setFormAction($ilCtrl->getFormAction($this), true);
		
		include_once("./Services/Help/classes/class.ilHelpModuleTableGUI.php");
		$tab = new ilHelpModuleTableGUI($this, "editSettings");
		
		$this->tpl->setContent($tab->getHTML());
	}

	/**
	 * administration tabs show only permissions and trash folder
	 */
	function getAdminTabs(&$tabs_gui)
	{
		global $tree;

		if ($this->checkPermissionBool("visible,read"))
		{
			$tabs_gui->addTab("settings",
				$this->lng->txt("settings"),
				$this->ctrl->getLinkTarget($this, "editSettings"));

		}
		
		if ($this->checkPermissionBool("edit_permission"))
		{
			$tabs_gui->addTab("perm_settings",
				$this->lng->txt("perm_settings"),
				$this->ctrl->getLinkTargetByClass(array(get_class($this),'ilpermissiongui'), "perm")
			);
		}
	}

	
	
	
	/**
	 * Upload help file
	 *
	 * @param
	 * @return
	 */
	function uploadHelpFile()
	{
		global $lng, $ilCtrl;
		
		if ($this->checkPermissionBool("write"))
		{
			$this->object->uploadHelpModule($_FILES["help_file"]);
			ilUtil::sendSuccess($lng->txt("help_module_uploaded"), true);
		}
		
		$ilCtrl->redirect($this, "editSettings");
	}
	
	/**
	 * Confirm help modules deletion
	 */
	function confirmHelpModulesDeletion()
	{
		global $ilCtrl, $tpl, $lng;
			
		if (!is_array($_POST["id"]) || count($_POST["id"]) == 0)
		{
			ilUtil::sendInfo($lng->txt("no_checkbox"), true);
			$ilCtrl->redirect($this, "editSettings");
		}
		else
		{
			include_once("./Services/Utilities/classes/class.ilConfirmationGUI.php");
			$cgui = new ilConfirmationGUI();
			$cgui->setFormAction($ilCtrl->getFormAction($this));
			$cgui->setHeaderText($lng->txt("help_sure_delete_help_modules"));
			$cgui->setCancel($lng->txt("cancel"), "editSettings");
			$cgui->setConfirm($lng->txt("delete"), "deleteHelpModules");
			
			foreach ($_POST["id"] as $i)
			{
				$cgui->addItem("id[]", $i, $this->object->lookupModuleTitle($i));
			}
			
			$tpl->setContent($cgui->getHTML());
		}
	}
	
	/**
	 * Delete help modules
	 *
	 * @param
	 * @return
	 */
	function deleteHelpModules()
	{
		global $ilDB, $ilCtrl;
		
		if (is_array($_POST["id"]))
		{
			foreach ($_POST["id"] as $i)
			{
				$this->object->deleteModule((int) $i);
			}
		}
		
		$ilCtrl->redirect($this, "editSettings");
	}
	
}
?>