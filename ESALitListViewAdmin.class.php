<?php
// +---------------------------------------------------------------------------+
// ESALitListViewAdmin.class.php
// class for managing ESA literature lists
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

require_once "ESALitList.class.php";
require_once "lib/classes/StudipLitListViewAdmin.class.php";

/**
* class for managing ESA literature lists
*
* see lib/classes/StudipLitListViewAdmin.class.php
*
* @access	public
* @author	André Noack <noack@data-quest.de>
* @version	$Id:$
* @package	esesemesterapparate
*/
class ESALitListViewAdmin extends StudipLitListViewAdmin {
	function ESALitListViewAdmin($range_id){
		$this->use_aging = true;
		$this->format_info = _("Felder müssen in geschweiften Klammern (z.B. {dc_title}) angegeben werden.\n")
							. _("Felder und Text, der zwischen senkrechten Strichen steht, wird nur angezeigt, wenn das angegebene Feld nicht leer ist. (z.B. |Anmerkung: {note}|)\n")
							. _("Folgende Felder können angezeigt werden:\n")
							. _("Titel - dc_title\n")
							. _("Verfasser oder Urheber - dc_creator\n")
							. _("Thema und Stichwörter - dc_subject\n")
							. _("Inhaltliche Beschreibung - dc_description\n")
							. _("Verleger, Herausgeber - dc_publisher\n")
							. _("Weitere beteiligten Personen und Körperschaften - dc_contributor\n")
							. _("Datum - dc_date\n")
							. _("Ressourcenart - dc_type\n")
							. _("Format - dc_format\n")
							. _("Ressourcen-Identifikation - dc_identifier\n")
							. _("Quelle - dc_source\n")
							. _("Sprache - dc_language\n")
							. _("Beziehung zu anderen Ressourcen - dc_relation\n")
							. _("Räumliche und zeitliche Maßangaben - dc_coverage\n")
							. _("Rechtliche Bedingungen - dc_rights\n")
							. _("Zugriffsnummer - accession_number\n")
							. _("Jahr - year\n")
							. _("alle Autoren - authors\n")
							. _("Herausgeber mit Jahr - published\n")
							. _("Anmerkung - note\n")
							. _("link in externes Bibliothekssystem - external_link\n");

		parent::TreeView("ESALitList", $range_id); //calling the baseclass constructor
	}
	
	function parseCommand(){
		if ($_REQUEST['mode'])
			$this->mode = $_REQUEST['mode'];
		if ($_REQUEST['litcmd']){
			$exec_func = "execCommand" . $_REQUEST['litcmd'];
			if (method_exists($this,$exec_func)){
				if ($this->$exec_func()){
					$this->tree->init();
				}
			}
		}
	}
	
	function getItemContent($item_id){
		$edit_content = false;
		if ($item_id == $this->edit_item_id){
			$edit_content = $this->getEditItemContent();
		}
		$content .= "\n<table width=\"90%\" cellpadding=\"2\" cellspacing=\"0\" align=\"center\" style=\"font-size:10pt\">";
		$content .= $this->getItemMessage($item_id);
		if (!$edit_content){
			if ($item_id == "root" && $this->tree->range_type != 'user'){
				$content .= "\n<form name=\"userlist_form\" action=\"" . $this->getSelf("cmd=CopyUserList") . "\" method=\"POST\">";
				$user_lists = $this->tree->GetListsByRange($GLOBALS['auth']->auth['uid']);
				$content .= "\n<tr><td class=\"steel1\" align=\"left\"><b>" . _("Pers&ouml;nliche Literaturlisten:")
				."</b><br><br>\n<select name=\"user_list\" style=\"vertical-align:middle;width:70%;\">";
				if (is_array($user_lists)){
					foreach ($user_lists as $list_id => $list_name){
						$content .= "\n<option value=\"$list_id\">" . htmlReady($list_name) . "</option>";
					}
				}
				$content .= "\n</select>&nbsp;&nbsp;<input type=\"image\" " . makeButton("kopieerstellen","src")
				. tooltip(_("Eine Kopie der ausgewählten Liste erstellen")) . " style=\"vertical-align:middle;\" border=\"0\"></td></tr></form>";
			}
			if ($this->tree->isElement($item_id)) {
				$content .= "\n<tr><td class=\"steelgraulight\" align=\"left\" style=\"font-size:10pt;border-top: 1px solid black;border-left: 1px solid black;border-right: 1px solid black;\">" . _("Vorschau:") ."<br>";
				$content .= "\n<tr><td class=\"steel1\" align=\"left\" style=\"font-size:10pt;border-left: 1px solid black;border-right: 1px solid black;\">" . formatReady($this->tree->getFormattedEntry($item_id), false, true) . $this->tree->getDocumentLink($item_id)." </td></tr>";
			} elseif ($item_id != 'root') {
				$content .= "\n<tr><td class=\"steelgraulight\" align=\"left\" style=\"font-size:10pt;border-top: 1px solid black;border-left: 1px solid black;border-right: 1px solid black;\">" . _("Formatierung:") ." </td></tr>";
				$content .= "\n<tr><td class=\"steel1\" align=\"left\" style=\"font-size:10pt;border-left: 1px solid black;border-right: 1px solid black;\">" . htmlReady($this->tree->tree_data[$item_id]['format'],false,true) ." &nbsp;</td></tr>";
				$content .= "\n<tr><td class=\"steelgraulight\" align=\"left\" style=\"font-size:10pt;border-left: 1px solid black;border-right: 1px solid black;\">" . _("Sichtbarkeit:") . "</td></tr>";
				$content .= "\n<tr><td class=\"steel1\" align=\"left\" style=\"font-size:10pt;border-left: 1px solid black;border-right: 1px solid black;\">"
				. ($this->tree->tree_data[$item_id]['visibility']
				? "<img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/black/visibility-visible.png\" border=\"0\" style=\"vertical-align:bottom;\">&nbsp;" . _("Sichtbar")
				: "<img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/black/visibility-invisible.png\" border=\"0\" style=\"vertical-align:bottom;\">&nbsp;" . _("Unsichtbar")) . " </td></tr>";
				$content .= "\n<tr><td class=\"steelgraulight\" align=\"left\" style=\"font-size:10pt;border-left: 1px solid black;border-right: 1px solid black;\">" . _("Dynamische Liste:") . "</td></tr>";
				$content .= "\n<tr><td class=\"steel1\" align=\"left\" style=\"font-size:10pt;border-left: 1px solid black;border-right: 1px solid black;\">";
				if($this->tree->getValue($item_id, 'is_dynamic')){
					$content .= _("Ja") . ';&nbsp;&nbsp;&nbsp;' . _("Aktualisierungsintervall: ") . ($this->tree->getValue($item_id, 'query_interval') ? $this->tree->getValue($item_id, 'query_interval') . _(" Tage") : _("Keine Aktualisierung"));
				} else {
					$content .= _("Nein");
				}
				$content .= "</td></tr>";
				
			}
		} else {
			$content .= "\n<tr><td class=\"steel1\" align=\"left\">$edit_content</td></tr>";
		}
		if (!$edit_content && $item_id != 'root'){
			$content .= "\n<tr><td class=\"steelgraulight\" align=\"right\" style=\"font-size:10pt;border-bottom: 1px solid black;border-left: 1px solid black;border-right: 1px solid black;\">" . _("Letzte &Auml;nderung:") . strftime(" %d.%m.%Y ", $this->tree->tree_data[$item_id]['chdate'])
							. "(<a href=\"" . UrlHelper::getLink("about.php?username=" . $this->tree->tree_data[$item_id]['username']) . "\">" . htmlReady($this->tree->tree_data[$item_id]['fullname']) . "</a>) </td></tr>";
		}
		$content .= "</table>";
		if (!$edit_content){
			$content .= "\n<table width=\"90%\" cellpadding=\"2\" cellspacing=\"2\" align=\"center\" style=\"font-size:10pt\">";
			$content .= "\n<tr><td align=\"center\">&nbsp;</td></tr>";
			$content .= "\n<tr><td align=\"center\">";
			if ($item_id == "root"){
				$content .= "<a href=\"" . $this->getSelf("cmd=NewItem&item_id=$item_id") . "\">"
				. "<img " .makeButton("neueliteraturliste","src") . tooltip(_("Eine neue Literaturliste anlegen."))
				. " border=\"0\"></a>&nbsp;";
			}
			if ($this->mode != "NewItem"){
				if ($item_id != "root"){
					if(!$this->tree->isDynamicElement($item_id)){
						$content .= "<a href=\"" . $this->getSelf("cmd=EditItem&item_id=$item_id") . "\">"
						. "<img " .makeButton("bearbeiten","src") . tooltip(_("Dieses Element bearbeiten"))
						. " border=\"0\"></a>&nbsp;";
					}
					if ($this->tree->isElement($item_id)){
						if(!$this->tree->isDynamicElement($item_id)){
							$cmd = "DeleteItem";
						}
						$content .= "<a href=\"".UrlHelper::getLink('admin_lit_element.php?_catalog_id=' . $this->tree->tree_data[$item_id]['catalog_id'])."\">"
							. "<img " .makeButton("details","src") . tooltip(_("Detailansicht dieses Eintrages ansehen."))
							. " border=\"0\"></a>&nbsp;";
					} else {
						$cmd = "AssertDeleteItem";
						$content .= "<a href=\"" . $this->getSelf("cmd=SortKids&item_id=$item_id") . "\">"
						. "<img " .makeButton("sortieren","src") . tooltip(_("Elemente dieser Liste alphabetisch sortieren"))
						. " border=\"0\"></a>&nbsp;";
						$content .= '<a href="' . GetDownloadLink('', $this->tree->tree_data[$item_id]['name'] . '.txt', 5, 'force', $this->tree->range_id, $item_id) . '">'
						. "<img " .makeButton("export","src") . tooltip(_("Export der Liste in EndNote kompatiblem Format"))
						. " border=\"0\"></a>&nbsp;";
					}
					if($cmd){
						$content .= "<a href=\"" . $this->getSelf("cmd=$cmd&item_id=$item_id") . "\">"
						. "<img " .makeButton("loeschen","src") . tooltip(_("Dieses Element löschen"))
						. " border=\"0\"></a>&nbsp;";
					}
					if ($this->tree->isElement($item_id)){
						if (!$this->clip_board->isInClipboard($this->tree->tree_data[$item_id]["catalog_id"])){
							$content .= "<a href=\"". $this->getSelf("cmd=InClipboard&item_id=$item_id") . "\">"
										. "<img " . makeButton("merkliste","src") . " border=\"0\" " .
										tooltip(_("Eintrag in Merkliste aufnehmen")) . "></a>";
						}
					}
				}
			}
			$content .= "</td></tr></table>";
		}
		return $content;
	}
	
	function getEditItemContent(){
		$content .= "\n<form name=\"item_form\" action=\"" . $this->getSelf("cmd=InsertItem&item_id={$this->edit_item_id}") . "\" method=\"POST\">";
		$content .= "\n<input type=\"HIDDEN\" name=\"parent_id\" value=\"{$this->tree->tree_data[$this->edit_item_id]['parent_id']}\">";
		if ($this->tree->isElement($this->edit_item_id)){
			$content .= "\n<tr><td class=\"steelgraulight\"style=\"font-size:10pt;border-top: 1px solid black;border-left: 1px solid black;border-right: 1px solid black;\" ><b>". _("Anmerkung zu einem Eintrag bearbeiten:") . "</b></td></tr>";
			$edit_name = "note";
			$rows = 5;
			$content .= "<tr><td class=\"steel1\" align=\"center\" style=\"font-size:10pt;border-left: 1px solid black;border-right: 1px solid black;\"><textarea name=\"edit_{$edit_name}\" style=\"width:100%\" rows=\"$rows\">" . $this->tree->tree_data[$this->edit_item_id][$edit_name]
				. "</textarea></td></tr>";
			$content .= "\n<tr><td class=\"steelgraulight\"style=\"font-size:10pt;border-left: 1px solid black;border-right: 1px solid black;\" ><b>". _("Datei diesem Eintrag zuordnen:") . "</b></td></tr>";
			$content .= "<tr><td class=\"steel1\" align=\"left\" style=\"font-size:10pt;border-bottom: 1px solid black;border-left: 1px solid black;border-right: 1px solid black;\">
						<select style=\"width:100%\" name=\"edit_dokument_id\"><option value=\"\"></option>";
			foreach($this->tree->getAvailableDocuments() as $dok_id => $dok_name){
				$content .= '<option value="'.$dok_id.'" '.($this->tree->getValue($this->edit_item_id, 'dokument_id') == $dok_id ? 'selected' : '').'>'.htmlReady($dok_name).'</option>';
			}
			$content .= "</select></td></tr>";
		} else {
			$content .= "\n<tr><td class=\"steelgraulight\" style=\"font-size:10pt;border-top: 1px solid black;border-left: 1px solid black;border-right: 1px solid black;\" ><b>". _("Name der Liste bearbeiten:") . "</b></td></tr>";
			$content .= "<tr><td class=\"steel1\" align=\"center\" style=\"font-size:10pt;border-left: 1px solid black;border-right: 1px solid black;\"><input type=\"text\" name=\"edit_name\" style=\"width:100%\" value=\"" . $this->tree->tree_data[$this->edit_item_id]['name']
				. "\"></td></tr>";

			$edit_name = "format";
			$rows = 2;
			$content .= "\n<tr><td class=\"steelgraulight\" style=\"font-size:10pt;border-left: 1px solid black;border-right: 1px solid black;\" ><b>". _("Formatierung der Liste bearbeiten:") . "</b>"
					. "&nbsp;<img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/black/info-circle.png\""
					. tooltip($this->format_info, TRUE, TRUE) . " align=\"absmiddle\"></td></tr>";
			$content .= "<tr><td class=\"steel1\" align=\"center\" style=\"font-size:10pt;border-left: 1px solid black;border-right: 1px solid black;\"><textarea name=\"edit_{$edit_name}\" style=\"width:100%\" rows=\"$rows\">" . $this->tree->tree_data[$this->edit_item_id][$edit_name]
				. "</textarea></td></tr>";
			$content .= "\n<tr><td class=\"steelgraulight\" style=\"font-size:10pt;border-left: 1px solid black;border-right: 1px solid black;\" >
			<span style=\"font-weight:bold;display:block;float:left;width:200px;\">". _("Sichtbarkeit der Liste:") . "</span>
			<input type=\"radio\" name=\"edit_visibility\" value=\"1\" style=\"vertical-align:bottom\" "
			. (($this->tree->tree_data[$this->edit_item_id]['visibility']) ? "checked" : "") . ">" . _("Ja")
			. "&nbsp;<input type=\"radio\" name=\"edit_visibility\" value=\"0\" style=\"vertical-align:bottom\" "
			. ((!$this->tree->tree_data[$this->edit_item_id]['visibility']) ? "checked" : "") . ">" . _("Nein") . "</td></tr>";
			$content .= "\n<tr>
			<td class=\"steelgraulight\" style=\"font-size:10pt;border-left: 1px solid black;border-right: 1px solid black;\">
			<span style=\"font-weight:bold;display:block;float:left;width:200px;\">". _("Dynamische Liste:") . "</span>
			<input type=\"radio\" name=\"edit_is_dynamic\" value=\"1\" style=\"vertical-align:bottom\" "
			. (($this->tree->tree_data[$this->edit_item_id]['is_dynamic']) ? "checked" : "") . ">" . _("Ja")
			. "&nbsp;<input type=\"radio\" name=\"edit_is_dynamic\" value=\"0\" style=\"vertical-align:bottom\" "
			. ((!$this->tree->tree_data[$this->edit_item_id]['is_dynamic']) ? "checked" : "") . ">" . _("Nein") . "
			</td>
			</tr>";
			$content .= "\n<tr><td class=\"steelgraulight\" style=\"font-size:10pt;border-left: 1px solid black;border-right: 1px solid black;\" ><b>". _("Suchanfrage:") . "</b>"
					. "</td></tr>";
			$content .= "<tr><td class=\"steel1\" align=\"center\" style=\"font-size:10pt;border-left: 1px solid black;border-right: 1px solid black;\">
						<input name=\"edit_query\" style=\"width:100%\" value=\"".htmlReady( $this->tree->getValue($this->edit_item_id,'query'))."\">"
					. "</td></tr>";
			$content .= "\n<tr><td class=\"steelgraulight\" style=\"font-size:10pt;border-left: 1px solid black;border-right: 1px solid black;\" ><b>". _("Aktualisierungsintervall:") . "</b>"
					. "</td></tr>";
			$content .= "<tr><td class=\"steel1\" align=\"left\" style=\"font-size:10pt;border-bottom: 1px solid black;border-left: 1px solid black;border-right: 1px solid black;\">
						<select name=\"edit_query_interval\">";
			foreach(range(0,14) as $i){
				$content .= "<option ".($this->tree->getValue($this->edit_item_id,'query_interval') == $i ? 'selected' : '') . ">$i</option>";
			}
			$content .=	"</select>&nbsp;" . _("Tag(e)") . "</td></tr>";

		}
		$content .= "<tr><td class=\"steel1\">&nbsp;</td></tr><tr><td class=\"steel1\" align=\"center\"><input type=\"image\" "
				. makeButton("speichern","src") . tooltip("Einstellungen speichern") . " border=\"0\">"
				. "&nbsp;<a href=\"" . $this->getSelf("cmd=Cancel&item_id="
				. $this->edit_item_id) . "\">"
				. "<img " .makeButton("abbrechen","src") . tooltip(_("Aktion abbrechen"))
				. " border=\"0\"></a></td></tr>";
		$content .= "\n</form>";

		return $content;
	}
	
		
	function execCommandInsertItem(){
		parent::execCommandInsertItem();
		$item_id = $_REQUEST['item_id'];
		$db = DBManager::get();
		if (isset($_REQUEST['edit_is_dynamic'])){
			$query = "REPLACE INTO esa_lit_list (list_id,is_dynamic,query,query_interval)
			VALUES('$item_id','".(int)$_REQUEST['edit_is_dynamic']."','".trim($_REQUEST['edit_query'])."','".(int)$_REQUEST['edit_query_interval']."')";
			$db->exec($query);
			if($_REQUEST['edit_is_dynamic']){
				if(trim($_REQUEST['edit_query'])){
					$this->tree->init();
					$inserted = $this->tree->updateDynamicList($item_id, true);
					$this->msg[$item_id] .= "§info§" . sprintf(_("Die Suchanfrage ergab %s Treffer."),$this->tree->search_plugin->getNumHits()) ;
					if(!$inserted) {
						$this->msg[$item_id] .= "§error§" . _("Die Ergebnisse der Suchanfrage konnten nicht gespeichert werden.");
						$this->msg[$item_id] .= '§'.$this->tree->search_plugin->getError('msg');
					}
				} else {
					$this->msg[$item_id] .= "§info§" . _("Sie haben keine Suchanfrage eingegeben!");
				}
			}
		}
		if (isset($_REQUEST['edit_dokument_id'])){
			$query = "REPLACE INTO esa_lit_list_content (list_element_id,dokument_id)
			VALUES('$item_id','".$_REQUEST['edit_dokument_id']."')";
			if($db->exec($query)){
				if ($_REQUEST['edit_dokument_id']){
					$this->msg[$item_id] .= "§msg§" . _("Datei wurde zugewiesen.");
				} else {
					$this->msg[$item_id] .= "§msg§" . _("Dateizuweisung aufgehoben") ;
				}
			}
		}
		return true;
	}
	
	function getSelf($param = false){
		$url = $this->base_uri . "&" . "foo=" . DbView::get_uniqid();
		if ($this->mode)
			$url .= "&mode=" . $this->mode;
		if ($param)
			$url .= "&" . str_replace('cmd', 'litcmd', $param);
		$url .= "#anchor";
		return $url;
	}
}
?>
