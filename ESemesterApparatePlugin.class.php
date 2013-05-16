<?php
// +---------------------------------------------------------------------------+
// ESemesterApparatePlugin.class.php
// Stud.IP system plugin class for managing esa lit lists and documents
//
// Copyright (c) 2007 André Noack <noack@data-quest.de>
// Suchi & Berg GmbH <info@data-quest.de>
// +---------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// +---------------------------------------------------------------------------+
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// +---------------------------------------------------------------------------+

require_once 'lib/include/reiter.inc.php';
require_once 'lib/classes/StudipSemSearch.class.php';
require_once 'lib/classes/Seminar.class.php';
require_once 'EsaLitClipBoard.class.php';
require_once "vendor/flexi/flexi.php";
require_once "ESALitListViewAdmin.class.php";
require_once "EsaFolder.class.php";

/**
* system plugin class for managing esa lit lists and documents
*
*
*
* @access	public
* @author	André Noack <noack@data-quest.de>
* @version	$Id:$
* @package	esesemesterapparate
*/
class ESemesterApparatePlugin extends AbstractStudIPSystemPlugin {

	var $gc_probability = 2;
	var $access_granted = false;
	var $template_factory;

	function GetDownloadLink($file_id, $file_name, $type = 0, $dltype = 'normal'){
		$base = 'plugins_packages/data-quest/ESemesterApparatePlugin/esa_sendfile.php?';
		if ($dltype == 'force_download' || $dltype == 'force') {
			$link['force_download'] = 1;
		}
		$link['type'] = $type;
		$link['file_id'] = $file_id;
		$link['file_name'] = $file_name;
		$link['cid'] = null;
		return UrlHelper::getLink($base , $link);
	}

	/**
	 *
	 */
	function __construct(){
		parent::__construct();
		$this->access_granted = $GLOBALS['perm']->have_perm('root') || RolePersistence::isAssignedRole($GLOBALS['user']->id, 'Literaturadmin');
		$this->template_factory = new Flexi_TemplateFactory(dirname(__FILE__).'/templates/');

		if($this->access_granted){
			$navigation = new PluginNavigation();
			$navigation->setDisplayname(_("Semesterapparate"));
			$navigation->addLinkParam('action', 'main');
			$config_nav0 = clone $navigation;
			$navigation->addSubmenu($config_nav0);
			$config_nav1 = new PluginNavigation();
			$config_nav1->setDisplayname(_("Dokumente"));
			$config_nav1->addLinkParam('action', 'documents');
			$navigation->addSubmenu($config_nav1);
			$config_nav2 = new PluginNavigation();
			$config_nav2->setDisplayname(_("Persönliche Literaturlisten"));
			$config_nav2->addLinkParam('action', 'pers_lit_list');
			$navigation->addSubmenu($config_nav2);
			$config_nav3 = new PluginNavigation();
			$config_nav3->setDisplayname(_("Literatursuche"));
			$config_nav3->addLinkParam('action', 'lit_search');
			$navigation->addSubmenu($config_nav3);
			$this->setNavigation($navigation);
			$this->setDisplayType(SYSTEM_PLUGIN_TOOLBAR + SYSTEM_PLUGIN_STARTPAGE);
		}
	}

	function getPluginiconname()
	{
		return Assets::image_path('header/resources.png');
	}

	function hasBackgroundTasks(){
		return true;
	}

	function doBackgroundTasks(){
		$zufall = mt_rand();
		if (($zufall % 100) < $this->gc_probability){
			$this->doGarbageCollect($limit = 5);
		}
	}

	function display_action($action) {
		if(!$this->access_granted) {
			throw new AccessDeniedException($this->getDisplayTitle(). ' - '. _("Keine Berechtigung."));
		}

		$this->session =& $_SESSION['semesterapparate'];

		if(in_array($_REQUEST['action'], words('pers_lit_list lit_search goto_sem'))){
			$jump = $_REQUEST['action']  == 'pers_lit_list' ? 'admin_lit_list.php?_range_id=self' : 'lit_search.php';
			switch($_REQUEST['action']) {
				case 'pers_lit_list':
					$jump = 'admin_lit_list.php?_range_id=self';
					break;
				case 'lit_search':
					$jump = 'lit_search.php';
					break;
				case 'goto_sem':
					$jump = 'details.php?sem_id=' . $this->session['current_seminar'];
					break;
				default:
					$jump = 'index.php';
			}
			header("Location: " . UrlHelper::getURL($jump));
			page_close();
			die();
		}
		if($_REQUEST['action'] == 'documents'){
			$GLOBALS['HELP_KEYWORD'] = 'Plugins.EsaPluginDokumente';
		} else {
			$GLOBALS['HELP_KEYWORD'] = 'Plugins.EsaPluginListen';
		}
		$GLOBALS['CURRENT_PAGE'] = $this->getDisplayTitle();

		$seminar_chooser = $this->getSeminarChooser();
		if($this->session['current_seminar']){
		    try {
		        $this->current_seminar = Seminar::GetInstance($this->session['current_seminar']);
		        $GLOBALS['CURRENT_PAGE'] .= ' - ' . $this->current_seminar->getName();
		        $nav = new PluginNavigation();
		        $nav->setDisplayname(_("zur ausgewählten Veranstaltung"));
		        $nav->addLinkParam('action', 'goto_sem');
		        $this->getNavigation()->addSubmenu($nav);
		    } catch (Exception $e) {
		        $this->session['current_seminar'] = null;
		    }
		}
		ob_start();
		echo $seminar_chooser;
		if($_REQUEST['action'] == 'main' || !$_REQUEST['action']){
			$this->displayMainPage();
		}
		if($_REQUEST['action'] == 'documents'){
			$this->displayDocumentsPage();
		}
		// close the page
		$layout = $GLOBALS['template_factory']->open('layouts/base.php');
		$layout->content_for_layout = ob_get_clean();
		echo $layout->render();
	}

	function displayDocumentsPage(){
		if($this->session['current_seminar']){
			if (strpos($_REQUEST['open'], "_") !== false){
				list($open_id, $open_cmd) = explode('_', $_REQUEST['open']);
			} else {
				$open_id = $_REQUEST['open'];
			}
			if ($open_cmd == 'rfu' && (!$_REQUEST['cancel_x'])) {
				$upload = $open_id;
				$refresh = $open_id;
			}
			if ($open_cmd == 'led' && (!$_REQUEST['cancel_x'])) {
				$link = $open_id;
			}
			if ($open_cmd ==  'c') {
				$change = $open_id;
			}
			if ($open_cmd == 'sc' && (!$_REQUEST['cancel_x'])) {
				$doc = new StudipDocument($open_id);
				$doc->setValue('description' , stripslashes($_REQUEST['change_description']));
				$doc->setValue('name' , stripslashes($_REQUEST['change_name']));
				$doc->setValue('protected', (int)$_REQUEST['change_protected']);
				if($doc->store()) $msg="msg§" . _("Die Eingaben wurden gespeichert.");
			}
			if ($open_cmd == 'fd' && (!$_REQUEST['cancel_x'])) {
				$doc = new StudipDocument($open_id);
				if(!$doc->getValue('url')){
					@unlink(get_upload_file_path($open_id));
				}
				if($doc->delete()) {
					$msg="msg§" . _("Die Datei wurde gelöscht.");
					$db = new DB_Seminar("DELETE FROM esa_lit_list_content WHERE dokument_id='$open_id'");
				}
			}

			if (($_REQUEST['doc_cmd']=="upload") && (!$_REQUEST['cancel_x'])) {
				$refresh = $_REQUEST['refresh'];
				//Dokument_id erzeugen
				$dokument_id=md5(uniqid('dokumente',1));
				//Erzeugen des neuen Speicherpfads
				$newfile = get_upload_file_path($dokument_id);
				//Kopieren und Fehlermeldung
				if (!@move_uploaded_file($_FILES['the_file']['tmp_name'], $newfile)) {
					$msg.= "error§" . _("Datei&uuml;bertragung gescheitert!");
				} else {
					if ($refresh){
						@copy($newfile, get_upload_file_path($refresh));
						@unlink($newfile);
						$dokument_id = $refresh;
					}
					$msg="msg§" . _("Die Datei wurde erfolgreich auf den Server &uuml;bertragen!");
				}
				$fn1 = strrchr($_FILES['the_file']['name'],"/");  // Unix-Pfadtrenner
				$fn2 = strrchr($_FILES['the_file']['name'],"\\"); // Windows-Pfadtrenner
				if ($fn1) $the_file_name = $fn1;
				else if ($fn2) $the_file_name = $fn2;
				else $the_file_name = $_FILES['the_file']['name'];
				$description = trim($_REQUEST['description']);  	// laestige white spaces loswerden
				$name = trim($_REQUEST['name']);  			// laestige white spaces loswerden
				$protected = (int)$_REQUEST['protected'];
				if (!$name) $name = $the_file_name;
				if ($_FILES['the_file']['size'] > 0) {
					$doc =& new StudipDocument($dokument_id);
				if (!$refresh){
					$doc->setValue('range_id' , md5('esa'.$this->session['current_seminar']));
					$doc->setValue('seminar_id' ,  md5('esa'.$this->session['current_seminar']));
					$doc->setValue('description' , stripslashes($description));
					$doc->setValue('name' , stripslashes($name));
					$doc->setValue('protected', $protected);
				} else {
					if (!$doc->getValue('name') || $doc->getValue('filename') == $doc->getValue('name')){
						$doc->setValue('name' , stripslashes($name));
					}
				}
				$doc->setValue('filename' , stripslashes($the_file_name));
				$doc->setValue('filesize' , $_FILES['the_file']['size']);
				$doc->setValue('autor_host' , $_SERVER['REMOTE_ADDR']);
				$doc->setValue('user_id' , $GLOBALS['user']->id);
				$doc->store();
				}
				$open_id = $dokument_id;
			}

			if ($_REQUEST['doc_cmd'] == "link"  && !$_REQUEST['cancel_x']){
				$link_data = parse_link($_REQUEST['the_link']);
				if ($link_data["response_code"] == 200) {
					$url_parts = parse_url($_REQUEST['the_link']);
					$the_file_name = basename($url_parts['path']);
					$name = !$_REQUEST['name'] ? $the_file_name : $_REQUEST['name'];
					$doc = new StudipDocument($_REQUEST['link_update']);
					$doc->setValue('user_id', $GLOBALS['user']->id);
					$doc->setValue('range_id' , md5('esa'.$this->session['current_seminar']));
					$doc->setValue('seminar_id' ,  md5('esa'.$this->session['current_seminar']));
					$doc->setValue('description' , stripslashes($_REQUEST['description']));
					$doc->setValue('name' , stripslashes($name));
					$doc->setValue('protected', ($_REQUEST['protect'] == 'on' ? 1 : 0));
					$doc->setValue('filename' , stripslashes($the_file_name));
					$doc->setValue('filesize' , $link_data["Content-Length"]);
					$doc->setValue('autor_host' , $_SERVER['REMOTE_ADDR']);
					$doc->setValue('url' , $_REQUEST['the_link']);
					if($doc->store()){
						if($_REQUEST['link_update']){
							$msg = "msg§" . _("Die Dateiverlinkung wurde aktualisiert.");
						} else {
							$msg = "msg§" . _("Die Dateiverlinkung war erfolgreich.");
						}
					}
					$open_id = $doc->getId();
				} else {
					$msg = "error§" . _("Die Dateiverlinkung war nicht erfolgreich.") . ' (' . htmlReady($link_data['response']) .')';
					$link_failed = true;
				}


			}

			$open[$open_id] = true;

			$form_fields['accesstime_start']  = array('type' => 'date',  'separator' => '&nbsp;', 'default' => 'YYYY-MM-DD', 'date_popup' => true);
			$form_fields['accesstime_end']  = array('type' => 'date',  'separator' => '&nbsp;', 'default' => 'YYYY-MM-DD', 'date_popup' => true);
			$form_buttons['set_accesstime'] = array('caption' => _("Übernehmen"), 'title' => _("Zugriffszeiten übernehmen"));
			$form_buttons['upload'] = array('caption' => _("Hochladen"), 'title' => _("Eine neue Datei hochladen"));
			$form_buttons['link'] = array('caption' => _("Datei verlinken"), 'title' => _("Eine neue Datei verlinken"));
			$form = new StudipForm($form_fields, $form_buttons, 'studipform', false);

			$esafolder = new EsaFolder(md5('esa'.$this->session['current_seminar']));
			if($form->isClicked('set_accesstime')){
				$accesstime_start = StudipForm::SQLDateToTimestamp($form->getFormFieldValue('accesstime_start'));
				$accesstime_end = StudipForm::SQLDateToTimestamp($form->getFormFieldValue('accesstime_end'));
				if($accesstime_start != $esafolder->getValue('accesstime_start')){
					$esafolder->setValue('accesstime_start', ($accesstime_start < 100000 ? 0 : $accesstime_start));
					$msg = 'msg§' . _("Der Beginn des Zugriffszeitraums wurde geändert.") . '§';
				}
				if($accesstime_end != $esafolder->getValue('accesstime_end')){
					$esafolder->setValue('accesstime_end', ($accesstime_end < 100000 ? 0 : $accesstime_end));
					$msg .= 'msg§' . _("Das Ende des Zugriffszeitraums wurde geändert.");
				}
				if(!$esafolder->store()) $msg = '';
				$form->doFormReset();
			}
			if($esafolder->getValue('accesstime_start') > 0) $form->form_values['accesstime_start'] = StudipForm::TimestampToSQLDate($esafolder->getValue('accesstime_start'));
			if($esafolder->getValue('accesstime_end') > 0) $form->form_values['accesstime_end'] = StudipForm::TimestampToSQLDate($esafolder->getValue('accesstime_end'));
			if($esafolder->isNew()) $esafolder->store();

			echo '<table width="100%" border="0" cellspacing="0" cellpadding="0">';
			if($msg){
				parse_msg($msg);
			} else {
				echo '<tr><td class="blank" colspan="2">&nbsp;</td></tr>';
			}
			echo '<tr><td class="blank" colspan="2">';
			if($form->isClicked('upload')){
				$this->displayUploadForm();
			} elseif($form->isClicked('link') || $link_failed){
				$this->displayLinkForm(md5(uniqid('dokumente',1)), false, true);
			} else {
				$this->displayDateForm($form);
			}
			$this->displayDocuments($open, $change, $upload, $refresh, $link);
			echo '</td></tr><tr><td class="blank">&nbsp;</td></tr></table>';
		}
	}
	function displayMainPage(){
		if($this->session['current_seminar']){
			$_the_treeview = new ESALitListViewAdmin($this->session['current_seminar']);
			$_the_clipboard = new EsaLitClipBoard();
			$_the_treeview->base_uri = PluginEngine::getLink($this, array('action' => 'main'));
			$_the_treeview->clip_board = $_the_clipboard;
			$_the_tree = $_the_treeview->tree;
			if($_the_tree->checkDynamicListUpdate()) $_the_tree->init();
			$_the_treeview->parseCommand();
			//always show existing lists
			$_the_treeview->open_ranges['root'] = true;
			//if there are no lists always open root element
			if (!$_the_tree->hasKids('root')){
				$_the_treeview->open_items['root'] = true;
			}

			$_the_clip_form = $_the_clipboard->getFormObject();
			if ($_the_clip_form->isClicked("clip_ok")){
				$clip_cmd = explode("_",$_the_clip_form->getFormFieldValue("clip_cmd"));
				if ($clip_cmd[0] == "ins"){
					if (is_array($_the_clip_form->getFormFieldValue("clip_content"))){
						$inserted = $_the_tree->insertElementBulk($_the_clip_form->getFormFieldValue("clip_content"), $clip_cmd[1]);
						if ($inserted){
							$_the_tree->init();
							$_the_treeview->open_ranges[$clip_cmd[1]] = true;
							$_msg .= "msg§" . sprintf(_("%s Eintr&auml;ge aus ihrer Merkliste wurden in <b>%s</b> eingetragen."),
							$inserted, htmlReady($_the_tree->tree_data[$clip_cmd[1]]['name'])) . "§";
						}
					} else {
						$_msg .= "info§" . _("Sie haben keinen Eintrag in ihrer Merkliste ausgew&auml;hlt!") . "§";
					}
				}
				$_the_clipboard->doClipCmd();
			}

			if ( ($lists = $_the_tree->getListIds()) && $_the_clipboard->getNumElements()){
				for ($i = 0; $i < count($lists); ++$i){
					if(!$_the_tree->tree_data[$lists[$i]]['is_dynamic']){
						$_the_clip_form->form_fields['clip_cmd']['options'][]
						= array('name' => my_substr(sprintf(_("In \"%s\" eintragen"), $_the_tree->tree_data[$lists[$i]]['name']),0,50),
						'value' => 'ins_' . $lists[$i]);
					}
				}
			}

			$msg .= $_the_clipboard->msg;
			if (is_array($_the_treeview->msg)){
				foreach ($_the_treeview->msg as $t_msg){
					if (!$msg || ($msg && (strpos($t_msg, $msg) === false))){
						$msg .= $msg . "§";
					}
				}
			}
			$template = $this->template_factory->open('litlist');
			$template->set_attribute('msg', $msg);
			$template->set_attribute('plugin', $this);
			$template->set_attribute('_the_treeview', $_the_treeview);
			$template->set_attribute('_the_tree', $_the_tree);
			$template->set_attribute('_the_clipboard', $_the_clipboard);
			$template->set_attribute('_the_clip_form', $_the_clip_form);
			echo $template->render();
		}
	}

	function getSeminarChooser(){
		$search_obj = new StudipSemSearch("search_sem", false, false);
		$search_form = $search_obj->form;
		if($search_form->isSended() && $search_form->getFormFieldValue('sem') != 'all'){
			$_SESSION['_default_sem'] = SemesterData::GetSemesterIdByIndex($search_form->getFormFieldValue('sem'));
		}

		if(!$search_form->isSended()){
			$search_form->form_fields['sem']['default_value'] = SemesterData::GetSemesterIndexById($_SESSION['_default_sem']);
			$search_form->form_fields['qs_choose']['default_value'] = 'title_lecturer_number';
		}

		if($search_obj->search_button_clicked && !$search_obj->new_search_button_clicked){
			$search_obj->doSearch();
			if ($search_obj->found_rows){
				$this->session['search_result'] = array_flip($search_obj->search_result->getRows("seminar_id"));
				$msg[] = array('info', sprintf(_("Ihre Suche ergab %s Treffer."), count($this->session['search_result'])));
			} else {
				$this->session['search_result'] = array();
				$msg[] = array('info', sprintf(_("Ihre Suche ergab keine Treffer!")));
			}
		}
		if($search_obj->new_search_button_clicked){
			$this->session['search_result'] = array();
			$this->session['current_seminar'] = null;
		}
		if(Request::submitted('do_choose_seminar') && Request::get('choose_seminar')){
			$this->session['current_seminar'] = Request::option('choose_seminar');
		}
		$this->seminar_plugin = null;
		if ($this->session['current_seminar']) {
			foreach (PluginManager::getInstance()->getPlugins('StandardPlugin', $this->session['current_seminar']) as $seminar_plugin) {
				if ($seminar_plugin instanceof ESAVeranstaltungsPlugin) {
					$seminar_plugin->setId($this->session['current_seminar']);
					$this->seminar_plugin = $seminar_plugin;
					break;
				}
			}
			if(Request::submitted('do_plugin_activation')) {
                if (!$_REQUEST['plugin_activated'] && is_object($this->seminar_plugin)) {
                	$this->seminar_plugin->setActivated(false);
                	$this->seminar_plugin = null;
                	$msg[] =  array('msg', sprintf(_("Die Anzeige in der Veranstaltung wurde deaktiviert.")));
                }
                if ($_REQUEST['plugin_activated'] && !is_object($this->seminar_plugin)) {
                	$seminar_plugin = PluginManager::getInstance()->getPlugin('ESAVeranstaltungsPlugin');
                	if ($seminar_plugin) {
                		$seminar_plugin->setId($this->session['current_seminar']);
                		$seminar_plugin->setActivated(true);
                		$this->seminar_plugin = $seminar_plugin;
                	    $msg[] =  array('msg', sprintf(_("Die Anzeige in der Veranstaltung wurde aktiviert.")));
                	}
                }
			}
		}

		$template = $this->template_factory->open('choose_seminar');
		$template->set_attribute('msg', $msg);
        $template->set_attribute('plugin', $this);
		$template->set_attribute('search_obj', $search_obj);
        return $template->render();
	}

	function getSeminareOptions(){
		$ret = array();
		if(count($this->session['search_result'])){
			$db = new DB_Seminar();
			$query = "SELECT Seminar_id,IF(s.visible=0,CONCAT(s.Name, ' "._("(versteckt)")."'), s.Name) AS Name,
					sd1.name AS startsem,IF(s.duration_time=-1, '"._("unbegrenzt")."', sd2.name) AS endsem,
					count(range_id) as sem_app
					FROM seminare s
					LEFT JOIN semester_data sd1 ON ( start_time BETWEEN sd1.beginn AND sd1.ende)
					LEFT JOIN semester_data sd2 ON ((start_time + duration_time) BETWEEN sd2.beginn AND sd2.ende)
					LEFT JOIN lit_list ON range_id = md5(CONCAT('esa',Seminar_id))
					WHERE Seminar_id IN ('".join("','", array_keys($this->session['search_result']))."') GROUP BY Seminar_id ORDER BY s.Name";
			$db->query($query);
			while($db->next_record()) {
				$name = $db->f("Name") . " (".$db->f('startsem') . ($db->f('startsem') != $db->f('endsem') ? " - ".$db->f('endsem') : ""). ")";
				if($db->f('sem_app')) $name .= ' '. sprintf(_("(%s vorhanden)"), $db->f('sem_app'));
				$ret[$db->f("Seminar_id")] = $name;
			}
		} else if (is_object($this->current_seminar)){
			$ret[$this->current_seminar->getId()] = $this->current_seminar->getName();
		}
		return $ret;
	}

	function displayDocuments($open, $change, $upload, $refresh, $filelink) {
		global $_fullname_sql;
		$db3=new DB_Seminar;
		$base_uri = PluginEngine::getLink($this, array('action' => 'documents'));
			$s=0;
			$range_id = md5('esa' . $this->session['current_seminar']);
			$db3->query("SELECT ". $_fullname_sql['full'] ." AS fullname, username, a.user_id, a.*, IF(IFNULL(a.name,'')='', a.filename,a.name) AS t_name FROM dokumente a LEFT JOIN auth_user_md5 USING (user_id) LEFT JOIN user_info USING (user_id) WHERE range_id = '$range_id' ORDER BY a.chdate DESC");
			$documents_count = $db3->num_rows();
			//Hier wird der Ordnerinhalt (Dokumente) gelistet
			if ($documents_count){
				if($change){
					echo '<form action="'.$base_uri.'" method="post">';
				}
				while ($db3->next_record()) {
					$type = ($db3->f('url') != '')? 6 : 0;
					$doc_anker = '';
					//Ankerlogik
					if (($change) || ($upload)) {
						if (($change == $db3->f("dokument_id")) || ($upload == $db3->f("dokument_id")))
						$doc_anker = ' name="anker" ';
					} elseif ($open['anker'] == $db3->f("dokument_id"))
						$doc_anker = ' name="anker" ';
					//Icon auswaehlen
					$icon = '<a href="' .  ESemesterApparatePlugin::GetDownloadLink($db3->f('dokument_id'), $db3->f('filename'), $type) . '">'
							. GetFileIcon(getFileExtension($db3->f('filename')), true) . '</a>';
					//Link erstellen
					if (isset($open[$db3->f("dokument_id")]))
						$link=$base_uri."&close=".$db3->f("dokument_id")."#anker";
					else
						$link=$base_uri."&open=".$db3->f("dokument_id")."#anker";

					//Workaround for older data from previous versions (chdate is 0)
					$chdate = (($db3->f("chdate")) ? $db3->f("chdate") : $db3->f("mkdate"));

					//Titelbereich erstellen
					$box = "";
					if ($change == $db3->f("dokument_id")){
						$titel= "<input style=\"font-size:8 pt; width: 100%;\" type=\"text\" size=20 maxlength=255 name=\"change_name\" value=\"".htmlReady($db3->f("name"))."\" />";
					} else {
						$tmp_titel=htmlReady(mila($db3->f("t_name")));

						//create a link onto the titel, too
						if ($link)
							$tmp_titel = "<a $doc_anker href=\"$link\" class=\"tree\" >$tmp_titel</a>";

						//add the size
						if (($db3->f("filesize") /1024 / 1024) >= 1)
						$titel= $tmp_titel."&nbsp;&nbsp;(".round ($db3->f("filesize") / 1024 / 1024)." MB";
						else
						$titel= $tmp_titel."&nbsp;&nbsp;(".round ($db3->f("filesize") / 1024)." kB";

						//add number of downloads
						$titel .= " / ".(($db3->f("downloads") == 1) ? $db3->f("downloads")." "._("Download") : $db3->f("downloads")." "._("Downloads")).")";

					}
					//Zusatzangaben erstellen
					$zusatz="<a href=\"".UrlHelper::getLink("about.php?username=".$db3->f("username"))."\"><font color=\"#333399\">".htmlReady($db3->f("fullname"))."</font></a>&nbsp;".date("d.m.Y - H:i", $chdate);

					?><table width="100%" cellpadding=0 cellspacing=0 border=0><tr>
					<td class="blank" width="*">&nbsp;</td><?

					if ($db3->f("protected")==1)
						$zusatz .= "&nbsp;<img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/blue/info-circle.png\" ".tooltip(_("Diese Datei ist urheberrechtlich geschützt!")).">";
					if ($db3->f("url")!="")
						$zusatz .= "&nbsp;<img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/blue/link-extern.png\" ".tooltip(_("Diese Datei wird von einem externen Server geladen!")).">";

					$zusatz .= $box;

					//Dokumenttitelzeile ausgeben
					if (isset($open[$db3->f("dokument_id")]))
						printhead ("90%", 0, $link, "open", false, $icon, $titel, $zusatz, $chdate);
					else
						printhead ("90%", 0, $link, "close", false, $icon, $titel, $zusatz, $chdate);

					//Dokumentansicht aufgeklappt
					if (isset($open[$db3->f("dokument_id")])) {
						$content='';


						if ($change == $db3->f("dokument_id")) { 	//Aenderungsmodus, Formular aufbauen
							if ($db3->f("protected")==1)
								$protect = "checked";
							$content.= "\n&nbsp;<input type=\"CHECKBOX\" value=\"1\" name=\"change_protected\" $protect>&nbsp;"._("geschützter Inhalt")."</br>";
							$content.= "<br /><textarea name=\"change_description\" rows=3 cols=40>".$db3->f("description")."</textarea><br />";
							$content .= Studip\Button::createAccept(_("Übernehmen"));
							$content.= "&nbsp;";
							$content .= Studip\Button::createCancel(_("Abbrechen"), 'cancel');
							$content.= "<input type=\"hidden\" name=\"open\" value=\"".$db3->f("dokument_id")."_sc_\" />";
							$content.= "<input type=\"hidden\" name=\"type\" value=0 />";
						}
						else {
							if ($db3->f("description"))
							$content= htmlReady($db3->f("description"), TRUE, TRUE);
							else
							$content= _("Keine Beschreibung vorhanden");
							$content.=  "<br /><br />" . sprintf(_("<b>Dateigr&ouml;&szlig;e:</b> %s kB"), round ($db3->f("filesize") / 1024));
							$content.=  "&nbsp; " . sprintf(_("<b>Dateiname:</b> %s "),$db3->f("filename"));
						}

						$content.= "\n";

						if ($upload == $db3->f("dokument_id")) {
							$content.= $this->displayUploadForm($refresh, false);
						}

						//Editbereich ertstellen
						$edit='';
						if (($change != $db3->f("dokument_id")) && ($upload != $db3->f("dokument_id")) && $filelink != $db3->f("dokument_id")) {
							$type = ($db3->f('url') != '')? 6 : 0;
							$edit= '&nbsp;' . Studip\LinkButton::create(_("Herunterladen"), decodeHTML(ESemesterApparatePlugin::GetDownloadLink( $db3->f('dokument_id'), $db3->f('filename'), $type, 'force')));
							$fext = getFileExtension(strtolower($db3->f('filename')));
								if ($type!=6)
									$edit.= "&nbsp;&nbsp;&nbsp;" . Studip\LinkButton::create(_("Bearbeiten"), "$base_uri&open=".$db3->f("dokument_id")."_c_#anker");
								if ($type==6)
									$edit.= "&nbsp;&nbsp;&nbsp;" . Studip\LinkButton::create(_("Bearbeiten"), "$base_uri&open=".$db3->f("dokument_id")."_led_&rnd=".rand()."#anker");
								else
									$edit.= "&nbsp;" . Studip\LinkButton::create(_("Aktualisieren"), "$base_uri&open=".$db3->f("dokument_id")."_rfu_#anker");
								$edit.= "&nbsp;" . Studip\LinkButton::create(_("Löschen"), "$base_uri&open=".$db3->f("dokument_id")."_fd_");
						}


						//Dokument-Content ausgeben
						?><td class="blank" width="*">&nbsp;</td></tr></table><table width="100%" cellpadding=0 cellspacing=0 border=0><tr><?
						?><td class="blank" width="*">&nbsp;</td><?

						if ($db3->f("protected")) {
							$content .= "<br><br><hr><table><tr><td><img src=\"".$GLOBALS['ASSETS_URL']."images/messagebox/advice.png\" valign=\"middle\"></td><td><font size=\"2\"><b>"
							._("Diese Datei ist urheberrechtlich geschützt.<br>Sie darf nur im Rahmen dieser Veranstaltung verwendet werden, jede weitere Verbreitung ist strafbar!")
							."</td></tr></table>";
						}
						if ($filelink == $db3->f("dokument_id")) {
							$content .= $this->displayLinkForm($db3->f("dokument_id"),true,FALSE);
						}
						printcontent ("100%",TRUE, $content, $edit);
					}
					echo '<td class="blank" width="*">&nbsp;</td></tr></table>';
				}
			}
			if($change) echo '</form>';
	}

	function displayUploadForm($refresh = false, $printout = true){
		$base_uri = PluginEngine::getLink($this, array('action' => 'documents'));
		if (!$refresh)
			$print="\n<table width=\"100%\" style=\"border-style: solid; border-color: #000000;  border-width: 1px;\" border=0 cellpadding=5 cellspacing=0>";
		else
			$print="\n<br /><br />" . _("Sie haben diese Datei zum Aktualisieren ausgew&auml;hlt. Sie <b>&uuml;berschreiben</b> damit die vorhandene Datei durch eine neue Version!") . "<br /><br /><center><table width=\"90%\" style=\"{border-style: solid; border-color: #000000;  border-width: 1px;}\" border=0 cellpadding=2 cellspacing=3>";
		$print.="\n";
		$print.="\n<tr><td class=\"table_row_odd\" width=\"20%\"><font size=-1><b>";
		$max_filesize = ini_get('upload_max_filesize');

		$print.= _("Zul&auml;ssige Dateitypen:") . "</b></td><font><td class=\"table_row_odd\" width=\"80%\"><font size=-1>";
		$print .= '*.*';
		$print.="</font></td></tr>";
		$print.="\n<tr><td class=\"table_row_odd\" width=\"20%\"><font size=-1><b>" . _("Maximale Gr&ouml;&szlig;e:") . "</b></font></td><td class=\"table_row_odd\" width=\"80%\"><font size=-1><b>".$max_filesize ." </b></font></td></tr>";
		$print.= "\n<form enctype=\"multipart/form-data\" NAME=\"upload_form\" action=\"" . $base_uri . "\" method=\"post\">";
		$print.= "<tr><td class=\"table_row_even\" colspan=2><font size=-1>" . _("1. Klicken Sie auf <b>'Durchsuchen...'</b>, um eine Datei auszuw&auml;hlen.") . " </font></td></tr>";
		$print.= "\n<tr>";
		$print.= "\n<td class=\"table_row_odd\" colspan=2 align=\"left\" valign=\"center\"><font size=-1>&nbsp;" . _("Dateipfad:") . "&nbsp;</font><br />";
		$print.= "&nbsp;<INPUT NAME=\"the_file\" TYPE=\"file\"  style=\"width: 70%\" SIZE=\"30\">&nbsp;</td></td>";
		$print.= "\n</tr>";
	if (!$refresh) {
		$print.= "<tr><td class=\"table_row_even\" colspan=2><font size=-1>" . _("2. Geben Sie eine kurze Beschreibung und einen Namen f&uuml;r die Datei ein.") . "</font></td></tr>";
		$print.= "\n<tr><td class=\"table_row_odd\" colspan=2 align=\"left\" valign=\"center\"><font size=-1>&nbsp;" . _("Name:") . "&nbsp;</font><br>";
		$print.= "\n&nbsp;<input type=\"TEXT\" name=\"name\" style=\"width: 70%\" size=\"40\" maxlength\"255\" /></td></tr>";
		$print.= "\n<tr><td class=\"table_row_odd\" colspan=2 align=\"left\" valign=\"center\"><font size=-1>&nbsp;" . _("Beschreibung:") . "&nbsp;</font><br>";
		$print.= "\n&nbsp;<TEXTAREA NAME=\"description\"  style=\"width: 70%\" COLS=40 ROWS=3 WRAP=PHYSICAL></TEXTAREA>&nbsp;</td></tr>";
		$print.= "\n<tr><td class=\"table_row_odd\" colspan=2 align=\"left\" valign=\"center\"><font size=-1>&nbsp;" . _("geschützter Inhalt:") . "&nbsp;</font>";
		$print.= "\n<input style=\"vertical-align:middle\" type=\"checkbox\" value=\"1\" name=\"protected\"></td></tr>";
		$print.= "\n<tr><td class=\"table_row_even\"colspan=2 ><font size=-1>" . _("3. Klicken Sie auf <b>'absenden'</b>, um die Datei hochzuladen") . "</font></td></tr>";
		} else {
			$print.= "\n<tr><td class=\"table_row_even\"colspan=2 ><font size=-1>" . _("2. Klicken Sie auf <b>'absenden'</b>, um die Datei hochzuladen und damit die alte Version zu &uuml;berschreiben.") . "</font></td></tr>";
		}
		$print.= "\n<tr><td class=\"table_row_odd\" colspan=2 align=\"center\" valign=\"center\">";
		$print.= Studip\Button::createAccept(_("Absenden"), 'create');
		$print.="&nbsp;";
		$print.= Studip\LinkButton::createCancel(_("Abbrechen"), "{$base_uri}&cancel_x=true");
		$print.= "</td></tr>";
		$print.= "\n<input type=\"hidden\" name=\"doc_cmd\" value=\"upload\">";
		$print.= "\n<input type=\"hidden\" name=\"refresh\" value=\"$refresh\">";
		$print.= "\n</form></table>";
		if($printout) echo $print;
		else return $print;
	}

	function displayLinkForm($dokument_id = false, $updating = false, $printout = false) {
		$base_uri = PluginEngine::getLink($this, array('action' => 'documents'));

		if ($_REQUEST['protect']=="on") $protect = "checked";
		$the_link = stripslashes($_REQUEST['the_link']);
		$name = stripslashes($_REQUEST['name']);
		$description = stripslashes($_REQUEST['description']);

		$print = "";
		$hiddenurl = FALSE;
		if ($updating == TRUE) {
			$db=new DB_Seminar;
			$db->query("SELECT * FROM dokumente WHERE dokument_id='$dokument_id'");
			if ($db->next_record()) {
				$the_link = $db->f("url");
				$protect = $db->f("protected");
				if ($protect==1) $protect = "checked";
				$name = $db->f("name");
				$description = $db->f("description");
				if ($db->f("user_id") != $GLOBALS['user']->id) { // check if URL can be seen
					$url_parts = @parse_url( $the_link );
					if ($url_parts["user"] && $url_parts["user"]!="anonymous") {
						$hiddenurl = TRUE;
					}

				}
			}
		}

		$print.="\n<table width=\"100%\" style=\"border-style: solid; border-color: #000000;  border-width: 1px;\" border=0 cellpadding=5 cellspacing=0>";

		$print.="</font></td></tr>";
		$print.= "\n<form enctype=\"multipart/form-data\" NAME=\"link_form\" action=\"" . $base_uri . "\" method=\"post\">";
		$print.= "<tr><td class=\"table_row_even\" colspan=2><font size=-1>" . _("1. Geben Sie hier den <b>vollständigen Pfad</b> zu der Datei an die sie verlinken wollen.") . " </font></td></tr>";
		$print.= "\n<tr>";
		$print.= "\n<td class=\"table_row_odd\" colspan=2 align=\"left\" valign=\"center\"><font size=-1>&nbsp;" . _("Dateipfad:") . "&nbsp;</font><br />";
		if ($hiddenurl)
		$print.= "&nbsp;<INPUT NAME=\"the_link\" TYPE=\"text\"  style=\"width: 70%\" SIZE=\"30\" value=\"***\">&nbsp;</td></td>";
		else
		$print.= '&nbsp;<INPUT NAME="the_link" TYPE="text"  style="width: 70%" SIZE="30" value="'.$the_link.'">&nbsp;</td></td>';
		$print.= "\n</tr>";
		$print.= "<tr><td class=\"table_row_even\" colspan=2><font size=-1>" . _("2. Sie können hier angeben, ob es sich um eine urheberrechtlich geschützte Datei handelt.") . "</font></td></tr>";
		$print.= "\n<tr><td class=\"table_row_odd\" colspan=2 align=\"left\" valign=\"center\"><font size=-1>&nbsp;" . _("Geschützt:") . "&nbsp;</font>";
		$print.= "\n&nbsp;<input type=\"CHECKBOX\" name=\"protect\" $protect></td></tr>";

		$print.= "<tr><td class=\"table_row_even\" colspan=2><font size=-1>" . _("3. Geben Sie eine kurze Beschreibung und einen Namen für die Datei ein.") . "</font></td></tr>";
		$print.= "\n<tr><td class=\"table_row_odd\" colspan=2 align=\"left\" valign=\"center\"><font size=-1>&nbsp;" . _("Name:") . "&nbsp;</font><br>";
		$print.= "\n".'&nbsp;<input type="TEXT" name="name" style="width: 70%" size="40" maxlength"255" value="'.$name.'"></td></tr>';

		$print.= "\n<tr><td class=\"table_row_odd\" colspan=2 align=\"left\" valign=\"center\"><font size=-1>&nbsp;" . _("Beschreibung:") . "&nbsp;</font><br>";
		$print.= "\n&nbsp;<TEXTAREA NAME=\"description\"  style=\"width: 70%\" COLS=40 ROWS=3 WRAP=PHYSICAL>$description</TEXTAREA>&nbsp;</td></tr>";
		$print.= "\n<tr><td class=\"table_row_even\"colspan=2 ><font size=-1>" . _("4. Klicken Sie auf <b>'absenden'</b>, um die Datei zu verlinken") . "</font></td></tr>";
		$print.= "\n<tr><td class=\"table_row_odd\" colspan=2 align=\"center\" valign=\"center\">";
		$print.= Studip\Button::createAccept(_("Absenden"), 'create');
		$print.="&nbsp;";
		$print.= Studip\LinkButton::createCancel(_("Abbrechen"), "{$base_uri}&cancel_x=true");
		$print.= "</td></tr>";
		if ($updating == TRUE) {
			$print.= "\n<input type=\"hidden\" name=\"link_update\" value=\"$dokument_id\">";
		}
		$print.= "\n<input type=\"hidden\" name=\"doc_cmd\" value=\"link\">";

		$print.= "\n</form></table><br />";

		if($printout) echo $print;
		else return $print;
	}

	function displayDateForm($form){
		$base_uri = PluginEngine::getLink($this, array('action' => 'documents'));
		$p = chr(10).$form->getFormStart($base_uri);
		$p .= chr(10).'<div style="margin:10px;font-size:10pt;">';
		$p .= _("Die Dokumente sind zugänglich von:") .'&nbsp;';
		$p .= chr(10) . $form->getFormField('accesstime_start');
		$p .= '&nbsp;&nbsp;&nbsp;'. _("bis:") .'&nbsp;';
		$p .= chr(10) . $form->getFormField('accesstime_end');
		$p .= '&nbsp;&nbsp;&nbsp;'. $form->getFormButton('set_accesstime', array('style' => 'vertical-align:middle'));
		$p .= chr(10). '</div>';
		$p .= chr(10).'<div style="margin:10px;font-size:10pt;">';
		$p .= $form->getFormButton('upload') .  '&nbsp;&nbsp;&nbsp;' . $form->getFormButton('link');
		$p .= chr(10). '</div>';
		$p .= $form->getFormEnd();
		echo $p;
	}

	function doGarbageCollect($limit = 1){
		$db = new DB_Seminar();
		$db2 = new DB_Seminar();
		$db->query("SELECT folder_id FROM esa_folder
					LEFT JOIN seminare ON MD5( CONCAT( 'esa', Seminar_id ) ) = folder_id
					WHERE Seminar_id IS NULL LIMIT $limit");
		while($db->next_record()){
			ESALitList::DeleteListsByRange($db->f('folder_id'));
			$db2->query("SELECT dokument_id FROM dokumente WHERE range_id='".$db->f("folder_id")."'");
			while ($db2->next_record()) {
				@unlink(get_upload_file_path($db2->f('dokument_id')));
			}
			$db2->query("DELETE FROM dokumente WHERE range_id='".$db->f("folder_id")."'");
			$db2->query("DELETE FROM esa_folder WHERE folder_id ='".$db->f("folder_id")."'");
		}
	}

}
?>
