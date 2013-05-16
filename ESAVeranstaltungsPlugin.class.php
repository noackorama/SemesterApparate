<?php
// +---------------------------------------------------------------------------+
// ESAVeranstaltungsPlugin.class.php
// Stud.IP standard plugin class for viewing esa lit lists in courses
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
require_once "vendor/flexi/flexi.php";
require_once "ESemesterApparatePlugin.class.php";

/**
* plugin class for viewing esa lit lists in courses
*
*
*
* @access	public
* @author	André Noack <noack@data-quest.de>
* @version	$Id:$
* @package	esesemesterapparate
*/
class ESAVeranstaltungsPlugin extends AbstractStudIPStandardPlugin {

	var $template_factory;

	/**
	 *
	 */
	function ESAVeranstaltungsPlugin(){
		AbstractStudIPStandardPlugin::AbstractStudIPStandardPlugin();
		$this->seminar_id =& $GLOBALS['SessSemName'][1];
        $this->template_factory = new Flexi_TemplateFactory(dirname(__FILE__).'/templates/');
		$navigation = new PluginNavigation();
		$navigation->setDisplayname(_("Semesterapparat"));
		$printview = new PluginNavigation();
		$printview->setDisplayname(_("Druckansicht"));
		$printview->addLinkParam('printview', '1');
		$navigation->addSubmenu($printview);
		$this->setNavigation($navigation);

        $this->setPluginIconName("images/literature-white.png");
        $navigation->setImage(Assets::image_path('/images/icons/16/white/literature.png'));
        $navigation->setActiveImage(Assets::image_path('/images/icons/16/black/literature.png'));
	}

	function initialize(){
		$GLOBALS['HELP_KEYWORD'] = 'Plugins.EsaPluginVeranstaltung';
	}

	function actionShow(){
		$tree =& TreeAbstract::GetInstance("ESALitList", $this->seminar_id);
		if($tree->checkDynamicListUpdate())	$tree->init();
		if($_REQUEST['action'] == 'copy'){
			$new_list_id = $tree->copySemApp($GLOBALS['user']->id);
			ob_end_clean();
			header("Location: {$GLOBALS['ABSOLUTE_URI_STUDIP']}admin_lit_list.php?_range_id=self&open_item=$new_list_id#anchor");
			page_close();
			die();
		}
		if($_REQUEST['printview'] == 1){
			ob_end_clean();
			$GLOBALS['_include_stylesheet'] = "style_print.css"; // use special stylesheet for printing
			include ('lib/include/html_head.inc.php'); // Output of html head
		}
		$template = $this->template_factory->open('litveranstaltung');
		$template->set_attribute('plugin', $this);
		$template->set_attribute('printview', $_REQUEST['printview']);
		echo $template->render();
	}
}
?>
