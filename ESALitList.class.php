<?php
// +---------------------------------------------------------------------------+
// ESALitList.class.php
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

require_once "lib/classes/StudipLitList.class.php";
require_once "lib/classes/lit_search_plugins/StudipLitSearchPluginZ3950Abstract.class.php";

require_once "lib/datei.inc.php";

class PluginDbView extends DbView
{
	static public function add_my_views($views)
	{
		self::$dbviews += $views;
	}
}

/**
* class for managing ESA literature lists
*
* see lib/classes/StudipLitList.class.php
*
* @access	public
* @author	André Noack <noack@data-quest.de>
* @version	$Id:$
* @package	esesemesterapparate
*/
class ESALitList extends StudipLitList {
	
	
    public static function initDbView()
    {
        $views["ESA_LIT_GET_LIST_BY_RANGE"]["query"] = 
        "SELECT * FROM esa_lit_list WHERE list_id IN(&)";
        $views["ESA_LIT_LIST_GET_ELEMENTS"]["query"] = 
        "SELECT * FROM lit_list_content INNER JOIN esa_lit_list_content USING(list_element_id) WHERE list_id IN(&)";
        $views["ESA_LIT_DELETE_LIST"]["query"] = 
        "DELETE FROM esa_lit_list WHERE list_id IN(&)";
        $views["ESA_LIT_DELETE_ELEMENTS"]["query"] = 
        "DELETE FROM esa_lit_list_content WHERE list_element_id IN(&)";
	    PluginDbView::add_my_views($views);
    }
    
    function __construct($range_id) {
	    
	    self::initDbView();
	    
		if ($GLOBALS['LIT_LIST_FORMAT_TEMPLATE']){
			$this->format_default = $GLOBALS['LIT_LIST_FORMAT_TEMPLATE'];
		}
		$plugin_name = "StudipLitSearchPlugin" . $GLOBALS['ESA_LIT_CATALOG'];
		include_once("lib/classes/lit_search_plugins/{$plugin_name}.class.php");
		$this->search_plugin = new $plugin_name();
		$this->range_type = 'esa';
		$object_name = get_object_name($range_id, 'sem');
		$this->root_name = _("Semesterapparat") . ": " . $object_name['name'];
		$this->cat_element = new StudipLitCatElement();
		$this->range_id = md5('esa' . $range_id);
		parent::TreeAbstract(); //calling the baseclass constructor
	}
	
	function init(){
		parent::init();
		if($this->getNumKids('root')){
			$list_ids = $this->getKids('root');
			$this->view->params[0] = $list_ids;
			$rs = $this->view->get_query("view:ESA_LIT_GET_LIST_BY_RANGE");
			while ($rs->next_record()){
				$this->tree_data[$rs->f("list_id")]['is_dynamic'] = $rs->f('is_dynamic');
				$this->tree_data[$rs->f("list_id")]['query'] = $rs->f('query');
				$this->tree_data[$rs->f("list_id")]['query_interval'] = $rs->f('query_interval');
				$this->tree_data[$rs->f("list_id")]['last_query_time'] = $rs->f('last_query_time');
			}
			$this->view->params[0] = $list_ids;
			$rs = $this->view->get_query("view:ESA_LIT_LIST_GET_ELEMENTS");
			while ($rs->next_record()){
				$this->tree_data[$rs->f("list_element_id")]['dokument_id'] = $rs->f('dokument_id');
			}
		}
	}
	
	function updateDynamicList($list_id, $force_update = false){
		if($this->getValue($list_id, 'is_dynamic') && $this->getValue( $list_id, 'query')
		&& ($force_update 
		|| ($this->getValue( $list_id, 'query_interval') && ($this->getValue( $list_id, 'last_query_time') + $this->getValue( $list_id, 'query_interval') * 86400) < time() )
		)){
			$search_query[0]['search_term'] = $this->getValue($list_id, 'query');
			$search_query[0]['search_field'] = $GLOBALS['ESA_LIT_CATALOG_SEARCH_FIELD'];
			$search_query[0]['search_truncate'] = 'none';
			$this->search_plugin->doSearch($search_query);
			$catalog_ids = array();
			if($this->search_plugin->getNumHits()){
				foreach(range(1, $GLOBALS['ESA_LIT_CATALOG_SEARCH_MAX_HITS']) as $i){
					if($i > $this->search_plugin->getNumHits()) break;
					$cat_element = $this->search_plugin->getSearchResult($i);
					$cat_element->setValue("catalog_id", "new_entry");
					$cat_element->setValue("user_id", "studip");
					if ( ($existing_element = $cat_element->checkElement()) ){
						$cat_element->setValue('catalog_id', $existing_element);
					}
					$cat_element->insertData();
					$catalog_ids[$cat_element->getValue("catalog_id")] = StudipLitSearchPluginZ3950Abstract::ConvertUmlaute($cat_element->getShortName());
				}
				if(count($catalog_ids)){
					uasort($catalog_ids, 'strnatcasecmp');
					$this->view->params[] = array($list_id);
					$rs = $this->view->get_query("view:LIT_DEL_LIST_CONTENT_ALL");
					$deleted = $rs->affected_rows();
					$inserted = $this->insertElementBulk(array_keys($catalog_ids), $list_id);
				}
			}
			DbManager::get()->exec("UPDATE esa_lit_list SET last_query_time=UNIX_TIMESTAMP() WHERE list_id='$list_id'");
		}
		return $inserted;
	}
	
	function isDynamicElement($id){
		return $this->getValue($this->getValue($id, 'parent_id') , 'is_dynamic');
	}
	
	function checkDynamicListUpdate(){
		$ret = false;
		if($this->getNumKids('root')){
			foreach($this->getKids('root') as $list_id){
				$ret += $this->updateDynamicList($list_id);
			}
		}
		return $ret;
	}
	
	function getDocumentLink($item_id){
		$ret = '';
		if($this->getValue($item_id, 'dokument_id')){
			$db = new DB_Seminar("SELECT dokumente.*, IF(IFNULL(name,'')='', filename,name) AS t_name FROM dokumente WHERE dokument_id='".$this->getValue($item_id, 'dokument_id')."'");
			if($db->next_record()){
				$titel = '<a href="' . ESemesterApparatePlugin::GetDownloadLink($db->f('dokument_id'), $db->f('filename'), ($db->f("url")!="" ? 6 : 0)) . '">'
				. GetFileIcon(getFileExtension($db->f('filename')), true) . htmlReady($db->f("t_name"));
				if (($db->f("filesize") /1024 / 1024) >= 1) $titel .= "&nbsp;&nbsp;(".round ($db->f("filesize") / 1024 / 1024)." MB)";
				else $titel .= "&nbsp;&nbsp;(".round ($db->f("filesize") / 1024)." kB)";
				if ($db->f("protected")==1) $titel .= "&nbsp;<img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/black/info-circle.png\" ".tooltip(_("Diese Datei ist urheberrechtlich geschützt!")).">";
				if ($db->f("url")!="")	$titel .= "&nbsp;<img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/black/link-extern.png\" ".tooltip(_("Diese Datei wird von einem externen Server geladen!")).">";
				$titel .= '</a>';
				$ret .= '<div>'.$titel.'</div>';
			}
		}
		return $ret;
	}

	function getAvailableDocuments(){
		$db = new DB_Seminar("SELECT dokumente.*, IF(IFNULL(name,'')='', filename,name) AS t_name FROM dokumente WHERE range_id='{$this->range_id}' ORDER BY t_name");
		$ret = array();
		while ($db->next_record()){
			$ret[$db->f('dokument_id')] = $db->f('t_name') . '  (' . ($db->f('url') ? 'link, ' : '') .round($db->f('filesize')/1024).'kB, '.date("d.m.Y - H:i", $db->f('chdate')).')';
		}
	return $ret;
	}
	
	function GetFormattedListsByRange($range_id, $last_modified_since = false, $copy_link = true){
		self::initDbView();
	    $ret = false;
		$dbv = new DbView();
		$tree = TreeAbstract::GetInstance("ESALitList", $range_id);
		$esa_folder = new EsaFolder($tree->range_id);
		$document_access = $esa_folder->checkAccessTime();
		if ( ($lists = $tree->getVisibleListIds()) ){
			for ($i = 0; $i < count($lists); ++$i){
				if ( ($tree->tree_data[$lists[$i]]['user_id'] != $GLOBALS['auth']->auth['uid'])
				&& ($last_modified_since !== false)
				&& ($tree->tree_data[$lists[$i]]['chdate'] > $last_modified_since) ){
					$ret .= '<div align="left" style="color:red" title="' . htmlReady(sprintf(_("Letzte Änderung am %s von %s"),
					date('d M Y H:i',$tree->tree_data[$lists[$i]]['chdate']),
					$tree->tree_data[$lists[$i]]['fullname'])) . '">';
					$ret .=  "<b><u>" . htmlReady($tree->tree_data[$lists[$i]]['name']) . "</u></b>\n<br>\n";
					$ret .= '</div>';
				} else {
					$ret .= "\n<div align=\"left\"><b><u>" . htmlReady($tree->tree_data[$lists[$i]]['name']) . "</u></b></div>";
				}
				if ($copy_link){
					$ret .= "\n<div align=\"right\" style=\"font-size:10pt\"><a href=\"admin_lit_list.php?cmd=CopyUserList&_range_id=self&user_list={$lists[$i]}#anchor\"><img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/black/link-intern.png\" border=\"0\">"
						. "&nbsp;" . _("Literaturliste kopieren") . "</a></div>";
				} else {
					$ret .= "\n<br>\n";
				}
				$ret .= "\n<span style=\"font-size:10pt\">\n";
				if ($tree->hasKids($lists[$i])){
					$dbv->params[0] = $lists[$i];
					$rs = $dbv->get_query("view:LIT_LIST_GET_ELEMENTS");
					while ($rs->next_record()){
						if ( ($tree->tree_data[$rs->f('list_element_id')]['user_id'] != $GLOBALS['auth']->auth['uid'])
						&& ($last_modified_since !== false)
						&& ($tree->tree_data[$rs->f('list_element_id')]['chdate'] > $last_modified_since) ){
							$ret .= '<span style="color:red" title="' . htmlReady(sprintf(_("Letzte Änderung am %s von %s"),
							date('d M Y H:i',$tree->tree_data[$rs->f('list_element_id')]['chdate']),
							$tree->tree_data[$rs->f('list_element_id')]['fullname'])) . '">';
							$ret .=  formatReady($tree->getFormattedEntry($rs->f('list_element_id'), $rs->Record), false, true);
							if($document_access) $ret .= $tree->getDocumentLink($rs->f('list_element_id'));
							$ret .= "\n<br>\n";
							$ret .= '</span>';
						} else {
							$ret .=  formatReady($tree->getFormattedEntry($rs->f('list_element_id'), $rs->Record), false, true);
							if($document_access) $ret .= $tree->getDocumentLink($rs->f('list_element_id'));
							$ret .= "\n<br>\n";
						}
					}
				}
				$ret .= "\n</span><br>";
			}
		}
		return $ret;
	}
	
	function deleteList($list_id){
		$esa_elements = array();
		$this->view->params[0] = array($list_id);
		$rs = $this->view->get_query("view:ESA_LIT_LIST_GET_ELEMENTS");
		while ($rs->next_record()){
			$esa_elements[] = $rs->f('list_element_id');
		}
		$deleted = parent::deleteList($list_id);
		$this->view->params[0] = array($list_id);
		$rs = $this->view->get_query("view:ESA_LIT_DELETE_LIST");
		$deleted += $rs->affected_rows();
		if(count($esa_elements)){
			$this->view->params[0] = $esa_elements;
			$rs = $this->view->get_query("view:ESA_LIT_DELETE_ELEMENTS");
			$deleted += $rs->affected_rows();
		}
		return $deleted;
	}
	
	function DeleteListsByRange($range_id){
		self::initDbView();
	    $deleted = null;
		$view = new DbView();
		$view->params[] = $range_id;
		$rs = $view->get_query("view:LIT_GET_LIST_BY_RANGE");
		while ($rs->next_record()){
			$list_ids[] =  $rs->f("list_id");
		}
		if (is_array($list_ids)){
			$esa_elements = array();
			$view->params[0] = $list_ids;
			$rs = $view->get_query("view:ESA_LIT_LIST_GET_ELEMENTS");
			while ($rs->next_record()){
				$esa_elements[] = $rs->f('list_element_id');
			}
			$view->params[] = $list_ids;
			$rs = $view->get_query("view:LIT_DEL_LIST");
			$deleted['list'] = $rs->affected_rows();
			$view->params[] = $list_ids;
			$rs = $view->get_query("view:LIT_DEL_LIST_CONTENT_ALL");
			$deleted['list_content'] = $rs->affected_rows();
			$view->params[0] = $list_ids;
			$rs = $view->get_query("view:ESA_LIT_DELETE_LIST");
			$deleted['list'] += $rs->affected_rows();
			if(count($esa_elements)){
				$view->params[0] = $esa_elements;
				$rs = $view->get_query("view:ESA_LIT_DELETE_ELEMENTS");
				$deleted['list_content'] += $rs->affected_rows();
			}
		}
		return $deleted;
	}
	
	function copySemApp($to_range_id = null){
		if(!$to_range_id) $to_range_id = $GLOBALS['user']->id;
		if($this->getNumKids('root')){
			$user_list = TreeAbstract::GetInstance("StudipLitList", $to_range_id);
			$lists = $this->getKids('root');
			$new_list_values['list_id'] = $user_list->getNewListId();
			$new_list_values['range_id'] = $to_range_id;
			$new_list_values['name'] = mysql_escape_string($this->root_name);
			$new_list_values['user_id'] = $to_range_id;
			$new_list_values['format'] = mysql_escape_string($this->format_default);
			$new_list_values['priority'] = $user_list->getMaxPriority("root") + 1;
			if ($user_list->insertList($new_list_values)){
				foreach($lists as $list_id){
					$this->view->params[] = $this->getNewListElementId();
					$this->view->params[] = $new_list_values['list_id'];
					$this->view->params[] = $list_id;
					$rs = $this->view->get_query("view:LIT_INS_LIST_CONTENT_COPY");
				}
			}
			return $new_list_values['list_id'];
		}
		return false;
	}
}
?>
