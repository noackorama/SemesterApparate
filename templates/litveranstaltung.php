<table width="100%" border="0" cellpadding="2" cellspacing="0">
	<tr>
	<td class="blank" width="99%" align="left" valign="top">
	<table width="100%" border="0" cellpadding="20" cellspacing="0">
		<tr><td align="left" class="blank">
<?
if ( ($list = ESALitList::GetFormattedListsByRange($plugin->seminar_id, false, false))){
	echo $list;
} else {
	echo _("Es wurde noch kein Semesterapparat zur Verfügung gestellt.");
}
?>
		</td></tr>
	</table>
</td>
<td class="blank" align="center" valign="top">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr>
<td class="blank" width="270" align="right" valign="top">
<?
$infobox[0] = array ("kategorie" => _("Information:"),
					"eintrag" =>	array(
									array("icon" => "icons/16/black/info-circle.png","text"  =>	_("Hier sehen sie den Semesterapparat der Veranstaltung.")),
									)
					);
$infobox[1] = array ("kategorie" => _("Aktionen:"));
$infobox[1]["eintrag"][] = array("icon" => "icons/16/black/link-intern.png","text"  => '<a href="'.PluginEngine::getLink($plugin, array('action' => 'copy')).'">'._("Semesterapparat kopieren").'</a>');
$infobox[1]["eintrag"][] = array("icon" => "blank.gif","text"  =>  _("Sie k&ouml;nnen den kompletten Semesterapparat in ihren pers&ouml;nlichen Literaturbereich kopieren, um erweiterte Informationen über die Eintr&auml;ge zu erhalten.")
																	.'<br>' . _("Die zu den Einträgen gehörenden Dokumente werden <u>nicht</u> mit kopiert."));
if(!$printview) print_infobox ($infobox,"infobox/literaturelist.jpg");
?>
</td>
</tr>
</table>
</td>
</tr>
<tr><td class="blank" colspan="2">&nbsp;</td></tr>
</table>
