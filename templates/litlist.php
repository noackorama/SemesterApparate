<?
$_attributes['lit_select'] = array('style' => 'font-size:8pt;width:100%');

if ($msg)	{
	echo "\n<table width=\"99%\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\">";
	parse_msg ($msg,"§","blank",1,false);
	echo "\n</table>";
}
?>
<table width="100%" border="0" cellpadding="2" cellspacing="0">
<tr>
<td class="blank" width="75%" align="left" valign="top">

<table width="100%" border="0" cellpadding="2" cellspacing="0">
<tr><td align="center">
<?
$_the_treeview->showTree();
?>
</td></tr>
</table>
</td>
<td class="blank" align="center" valign="top">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
<?=$_the_clip_form->getFormStart($_the_treeview->getSelf());?>
<tr>
	<td class="blank" align="center" valign="top">
	<b><?=_("Merkliste:")?></b>
	<br>
	<?=$_the_clip_form->getFormField("clip_content", array_merge(array('size' => $_the_clipboard->getNumElements()),(array) $_attributes['lit_select']))?>
	<div align="center" style="background-image:url(<?= $GLOBALS['ASSETS_URL'] ?>images/border.jpg);background-repeat:repeat-y;margin:3px;"><img src="<?= $GLOBALS['ASSETS_URL'] ?>images/blank.gif" height="2" border="0"></div>
	<?=$_the_clip_form->getFormField("clip_cmd", $_attributes['lit_select'])?>
	<div align="center">
	<?=$_the_clip_form->getFormButton("clip_ok",array('style'=>'vertical-align:middle;margin:3px;'))?>
	</div>
	</td>
</tr>
</table>
<?
echo $_the_clip_form->getFormEnd();
?>
</td>
</tr>
<tr><td class="blank" colspan="2">&nbsp;</td></tr>
</table>
