<?php
// +---------------------------------------------------------------------------+
// esa_sendfile.php
// handles download of ESA documents and checks permissions
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
ob_start();

$_SERVER['SCRIPT_NAME'] = substr($_SERVER['SCRIPT_NAME'],0,-strlen('plugins_packages/data-quest/ESemesterApparatePlugin/esa_sendfile.php')).'bla';

require '../../../../lib/bootstrap.php';

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Default_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));

require_once ('config.inc.php');
require_once ('lib/datei.inc.php');
require_once ('lib/visual.inc.php');
require_once 'lib/functions.php';
require_once 'lib/messaging.inc.php';
require_once 'lib/classes/Seminar.class.php';

require_once "EsaFolder.class.php";

$file_id = Request::option('file_id');
$type = Request::int('type');
$file_name = Request::get('file_name');
$force_download = Request::int('force_download');

switch ($type) {
	//download linked file
	case 6:
	$path_file = getLinkPath($file_id);
	break;
	//we want to download from the regular upload-folder (this mode performs perm checks)
	default:
	$path_file = get_upload_file_path($file_id);
	break;
}

//replace bad charakters to avoid problems when saving the file
$file_name = prepareFilename(basename($file_name));
if ($force_download) {
	$content_type="application/octet-stream";
	$content_disposition="attachment";
} else {
	switch (strtolower(getFileExtension ($file_name))) {
		case "txt":
		$content_type="text/plain";
		$content_disposition="inline";
		break;
		case "css":
		$content_type="text/css";
		$content_disposition="inline";
		break;
		case "gif":
		$content_type="image/gif";
		$content_disposition="inline";
		break;
		case "jpeg":
		$content_type="image/jpeg";
		$content_disposition="inline";
		break;
		case "jpg":
		$content_type="image/jpeg";
		$content_disposition="inline";
		break;
		case "jpe":
		$content_type="image/jpeg";
		$content_disposition="inline";
		break;
		case "bmp":
		$content_type="image/x-ms-bmp";
		$content_disposition="inline";
		break;
		case "png":
		$content_type="image/png";
		$content_disposition="inline";
		break;
		case "wav":
		$content_type="audio/x-wav";
		$content_disposition="inline";
		break;
		case "ra":
		$content_type="application/x-pn-realaudio";
		$content_disposition="inline";
		break;
		case "ram":
		$content_type="application/x-pn-realaudio";
		$content_disposition="inline";
		break;
		case "mpeg":
		$content_type="video/mpeg";
		$content_disposition="inline";
		break;
		case "mpg":
		$content_type="video/mpeg";
		$content_disposition="inline";
		break;
		case "mpe":
		$content_type="video/mpeg";
		$content_disposition="inline";
		break;
		case "qt":
		$content_type="video/quicktime";
		$content_disposition="inline";
		break;
		case "mov":
		$content_type="video/quicktime";
		$content_disposition="inline";
		break;
		case "avi":
		$content_type="video/x-msvideo";
		$content_disposition="inline";
		break;
		case "rtf":
		$content_type="application/rtf";
		$content_disposition="inline";
		break;
		case "pdf":
		$content_type="application/pdf";
		$content_disposition="inline";
		break;
		case "doc":
		$content_type="application/msword";
		$content_disposition="inline";
		break;
		case "xls":
		$content_type="application/ms-excel";
		$content_disposition="inline";
		break;
		case "ppt":
		$content_type="application/ms-powerpoint";
		$content_disposition="inline";
		break;
		case "tgz":
		case "gz":
		$content_type="application/x-gzip";
		$content_disposition="inline";
		break;
		case "bz2":
		$content_type="application/x-bzip2";
		$content_disposition="inline";
		break;
		case "zip":
		$content_type="application/zip";
		$content_disposition="inline";
		break;
		case "swf":
		$content_type="application/x-shockwave-flash";
		$content_disposition="inline";
		break;
		case "csv":
		$content_type="text/csv";
		$content_disposition="inline";
		break;
		default:
		$content_type="application/octet-stream";
		$content_disposition="inline";
		break;
	}
}

//override disposition, if available
if ($disposition) $content_disposition = $disposition;
$no_access = true;
$doc = new StudipDocument($file_id);
$db = new DB_Seminar("SELECT Seminar_id, Lesezugriff FROM seminare WHERE MD5(CONCAT('esa',Seminar_id))='".$doc->getValue('seminar_id')."'");
$db->next_record();
$seminar_id = $db->f('Seminar_id');
$access_to_sem = $db->f('Lesezugriff');
if($seminar_id){
	if($GLOBALS['perm']->have_perm('root') || RolePersistence::isAssignedRole($GLOBALS['user']->id, 'Literaturadmin')) {
		$no_access = false;
	}
	if($no_access){
		$esafolder = new EsaFolder($doc->getValue('range_id'));
		if($esafolder->checkAccessTime()
		&& $GLOBALS['perm']->have_studip_perm('autor', $seminar_id)
		&& (!$doc->getValue('protected') || ($doc->getValue('protected') && ($access_to_sem > 1)))){
			$no_access = false;
		}
	}
}

//Nachricht bei verbotenem Download
if ($no_access) {
	$add_msg = sprintf(_("%sZur&uuml;ck%s zur Startseite"), '<a href="index.php"><b>&nbsp;', '</b></a>') . '<br />&nbsp;' ;
	// Start of Output
	$_include_additional_header = '<base href="'.$ABSOLUTE_URI_STUDIP.'">';
	$HELP_KEYWORD = 'Plugins.EsaPluginVeranstaltung';
	include ('lib/include/html_head.inc.php'); // Output of html head
	include ('lib/include/header.php');   // Output of Stud.IP head

	parse_window('error§' . _("Sie haben keine Zugriffsberechtigung f&uuml;r diesen Download!")
					. ($access_to_sem < 2 ? '<br>'._("Dieser Download betrifft urheberechtlich geschütztes Material und ist nur aus geschlossenen Veranstaltungen möglich.") : ''), '§', _("Download nicht m&ouml;glich"), $add_msg);
	include ('lib/include/html_end.inc.php');
	//Benachrichtigung an den/die Dozenten
	if($access_to_sem < 2){
		$seminar = Seminar::GetInstance($seminar_id);
		$message = sprintf(_("In der Veranstaltung **%s**, in der Sie als Dozent oder Tutor eingetragen sind, konnte ein Dokument des elektronischen Semesterapparates nicht heruntergeladen werden, da es sich um urheberrechtlich geschütztes Material handelt. Bitte stellen Sie unter [\"Administration dieser Veranstaltung/Zugangsberechtigungen\"]%s ein Passwort für die Veranstaltung ein, um den Zugriff auf dieses Material zu ermöglichen."), $seminar->getName(), $GLOBALS['ABSOLUTE_URI_STUDIP'].'admin_admission.php?select_sem_id='.$seminar->getId());
		$messaging = new messaging();
		foreach(array_merge($seminar->getMembers('dozent'),$seminar->getMembers('tutor')) as $member){
			setTempLanguage($member["user_id"]);
			$messaging->insert_message(addslashes($message), $member['username'], "____%system%____", FALSE, FALSE, "1", FALSE, _("Systemnachricht:")." "._("Zugriff auf urheberrechtlich geschütztes Material nicht freigeschaltet"));
			restoreLanguage();
		}
	}
	page_close();
	die;
}

// Check bei verlinkten Dateien ob sie erreichbar sind

if ($type == 6) {
	$link_data = parse_link($path_file);
	if (!($link_data['HTTP/1.0 200 OK'] || $link_data['HTTP/1.1 200 OK'])) {
		$_include_additional_header = '<base href="'.$ABSOLUTE_URI_STUDIP.'">';
		include ('lib/include/html_head.inc.php'); // Output of html head
		include ('lib/include/header.php');   // Output of Stud.IP head
		$add_msg= sprintf(_("%sZur&uuml;ck%s zum Downloadbereich"), '<a href="folder.php?back=TRUE"><b>&nbsp;', '</b></a>') . '<br />&nbsp;' ;
		parse_window('error§' . _("Diese Datei wird von einem externen Server geladen und ist dort momentan nicht erreichbar!"), '§', _("Download nicht m&ouml;glich"), $add_msg);
		include ('lib/include/html_end.inc.php');
		page_close();
		die;
	}
}

//Datei verschicken
if ($type == 6) {
	$filesize = $doc->getValue('filesize');
} else {
	$filesize = @filesize($path_file);
}
if (!$filesize)	$filesize = FALSE;

header("Expires: Mon, 12 Dec 2001 08:00:00 GMT");
header("Last-Modified: " . gmdate ("D, d M Y H:i:s") . " GMT");
if (1 || $_SERVER['HTTPS'] == "on"){
	header("Pragma: public");
	header("Cache-Control: private");
} else {
	header("Pragma: no-cache");
	header("Cache-Control: no-store, no-cache, must-revalidate");   // HTTP/1.1
}
header("Cache-Control: post-check=0, pre-check=0", false);
header("Content-Type: $content_type; name=\"$file_name\"");
header("Content-Description: File Transfer");
header("Content-Transfer-Encoding: binary");
if ($filesize != FALSE) header("Content-Length: $filesize");
header("Content-Disposition: $content_disposition; filename=\"$file_name\"");
ob_end_flush();

@readfile($path_file);
TrackAccess($file_id, 'dokument');
// Save data back to database.
page_close();
?>