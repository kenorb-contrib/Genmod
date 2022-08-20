<?php
/**
 * Parses gedcom file and displays information about a family.
 *
 * You must supply a $famid value with the identifier for the family.
 *
 * Genmod: Genealogy Viewer
 * Copyright (C) 2005 - 2012 Genmod Development Team
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @package Genmod
 * @subpackage Charts
 * @version $Id: family.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

/**
 * Inclusion of the  config file
*/
require_once 'config.php';

$controller = new FamilyController();

PrintHeader($controller->pagetitle);

$controller->CheckNoResult(GM_LANG_family_not_found);

$controller->CheckPrivate();

$controller->CheckRawEdited();

?>
<?php $controller->PrintDetailJS(); ?>
<div class="DetailHeaderSection">
<?php
	print "<div class=\"PageTitleName\">".PrintReady($controller->title);
	print "</div>\r\n";
?>
</div>
<div id="FamParents">
	<?php
	print PersonFunctions::PrintFamilyParents($controller->family, 0, "", "", "", $controller->view);
	if (!$controller->isPrintPreview() && $controller->family->disp && $controller->family->canedit) {
		if (!is_object($controller->family->husb) && !$controller->family->isdeleted) { ?>
			<?php PrintHelpLink("edit_add_parent_help", "qm"); ?> 
			<a href="javascript <?php print GM_LANG_add_father; ?>" onclick="return addnewparentfamily('', 'HUSB', '<?php print $controller->family->xref; ?>', 'add_father');"><?php print GM_LANG_add_father; ?></a><br />
		<?php }
		if (!is_object($controller->family->wife) && !$controller->family->isdeleted)  { ?>
			<?php PrintHelpLink("edit_add_parent_help", "qm"); ?>
			<a href="javascript <?php print GM_LANG_add_mother; ?>" onclick="return addnewparentfamily('', 'WIFE', '<?php print $controller->family->xref; ?>', 'add_mother');"><?php print GM_LANG_add_mother; ?></a><br />
		<?php }
	} ?>
	<div class="HeaderAccessKeys">
		<a class="HeaderAccessKeys" href="<?php print 'timeline.php?pids0=' . $controller->family->husb_id.'&amp;pids1='.$controller->family->wife_id;?>" title="<?php print GM_LANG_parents_timeline ?>" tabindex="-1" accesskey="<?php print GM_LANG_accesskey_family_parents_timeline; ?>"><?php print GM_LANG_parents_timeline ?></a>
		<a class="HeaderAccessKeys" href="<?php print 'timeline.php?' . $controller->getChildrenUrlTimeline();?>" title="<?php print GM_LANG_children_timeline ?>" tabindex="-1" accesskey="<?php print GM_LANG_accesskey_family_children_timeline; ?>"><?php print GM_LANG_children_timeline ?></a>
		<a class="HeaderAccessKeys" href="<?php print 'timeline.php?pids0=' .$controller->family->husb_id.'&amp;pids1='.$controller->family->wife_id.'&amp;'.$controller->getChildrenUrlTimeline(2);?>" title="<?php print GM_LANG_family_timeline ?>" tabindex="-1" accesskey="<?php print GM_LANG_accesskey_family_timeline; ?>"><?php print GM_LANG_family_timeline ?></a>
		<?php if ($gm_user->userCanViewGedlines()) { ?>
			<a class="HeaderAccessKeys" href="javascript:show_gedcom_record();" title="<?php print GM_LANG_view_gedcom ?>" tabindex="-1" accesskey="<?php print GM_LANG_accesskey_family_gedcom; ?>"><?php print GM_LANG_view_gedcom ?></a>
		<?php } ?>
	</div>
</div>
<table id="FamLowerBlock">
	<tr>
		<td id="FamChildren" style="width: <?php print $pbwidth+38?>px;">
		<?php PersonFunctions::PrintFamilyChildren($controller->family, "", 0, "", $controller->view);?>
		</td>
		<td id="FamGroupDetails">
		<?php 
		$controller->PrintFamilyGroupHeader();
		$controller->PrintTabs();
		?>
		</td>
	</tr>
</table>
<?php PrintFooter();?>