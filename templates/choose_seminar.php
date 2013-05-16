<table class="table_row_odd" border="0" cellspacing="0" cellpadding="4" width="100%">
<?if(count($msg)){?>
	<?=parse_msg_array($msg,'blank',2,0)?>
<?}?>
<?=$search_obj->getFormStart(PluginEngine::getLink($plugin, array('action' => $_REQUEST['action'])));?>
<tr>
<td  valign="middle" width="20%">
<?=_("Veranstaltungssuche:")?>
</td>
<td valign="middle">
<?=$search_obj->getSearchField("quick_search",array( 'style' => 'vertical-align:middle;font-size:10pt;','size' => 55));?>
&nbsp;
<?=$search_obj->getSearchButton(array('style' => 'vertical-align:middle'));?>
&nbsp;
<?=$search_obj->getNewSearchButton(array('style' => 'vertical-align:middle'));?>

</td>
</tr>
<tr>
<td valign="middle">
<?=_("Suche einschränken:")?>
</td>
<td valign="middle">
<?=_("Suchfelder:")?>
<?=$search_obj->getSearchField("qs_choose",array('style' => 'vertical-align:middle;font-size:10pt;'));?>
&nbsp;&nbsp;
<?=_("Semester:")?>
&nbsp;
<?=$search_obj->getSearchField("sem",array('style' => 'vertical-align:middle;font-size:10pt;'));?>
</td></tr>
<?=$search_obj->getFormEnd();?>

<form action="<?=PluginEngine::getLink($plugin, array('action' => $_REQUEST['action']))?>" method="post">
<tr>
<td  valign="middle">
<?=_("Veranstaltung auswählen:")?>
</td>
<td  valign="middle">
<select name="choose_seminar">
<?foreach($plugin->getSeminareOptions() as $key => $value){
	?>
	<option value="<?=$key?>" <?=($key == $plugin->session['current_seminar'] ? 'selected' : '')?>><?=htmlReady(my_substr($value,0,80))?></option>
	<?
}?>
</select>
&nbsp;
<?= Studip\Button::create(_("Veranstaltung auswählen"), 'do_choose_seminar');?>
</td>
</tr>
<? if ($plugin->session['current_seminar']) : ?>
<tr>
<td  valign="middle">
<?=_("Semesterapparat in der Veranstaltung aktivieren:")?>
</td>
<td  valign="middle">
<input style="vertical-align:middle;" type="checkbox" name="plugin_activated" value="1" <?=($plugin->seminar_plugin ? 'checked' : '')?>>
&nbsp;
<?= Studip\Button::create('OK', 'do_plugin_activation', array('class' => 'accept', 'title' => _("Einstellung ändern")));?>
</td>
</tr>
<? endif;?>
</form>
</table>
