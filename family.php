<?php
/**
 * Parses gedcom file and displays information about a family.
 *
 * You must supply a $famid value with the identifier for the family.
 *
 * Genmod: Genealogy Viewer
 * Copyright (C) 2005 Genmod Development Team
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
 * @version $Id: family.php,v 1.4 2006/01/12 17:49:38 roland-d Exp $
 */

/**
 * Inclusion of the family control file
*/
require_once 'includes/controllers/family_ctrl.php';

print_header($controller->getPageTitle());
?>

<script language="JavaScript" type="text/javascript">
<!--
	function show_gedcom_record(shownew) {
		fromfile="";
		if (shownew=="yes") fromfile='&fromfile=1';
		var recwin = window.open("gedrecord.php?pid=<?php print $controller->getFamilyID(); ?>"+fromfile, "", "top=50,left=50,width=300,height=400,scrollbars=1,scrollable=1,resizable=1");
	}
	function showchanges() {
		window.location = '<?php if (empty($SCRIPT_NAME)) $SCRIPT_NAME = $_SERVER['SCRIPT_FILENAME']; print $SCRIPT_NAME."?".preg_replace("/&amp;/", "&", $QUERY_STRING); if (!stristr($QUERY_STRING, "show_changes")) print "&show_changes=yes"; ?>';
	}
//-->
</script>
<table>
	<tr>
		<td>
		<?php
		print print_family_parents($controller->getFamilyID());
		if (!$controller->isPrintPreview() && $controller->display && userCanEdit($controller->uname)) {
		$husb = $controller->getHusband(); 
		if (empty($husb)) { ?>
			<?php print_help_link("edit_add_parent_help", "qm"); ?> 
			<a href="javascript <?php print $gm_lang["add_father"]; ?>" onclick="return addnewparentfamily('', 'HUSB', '<?php print $controller->famid; ?>', 'add_father');"><?php print $gm_lang["add_father"]; ?></a><br />
		<?php }
		$wife = $controller->getWife();
		if (empty($wife))  { ?>
			<?php print_help_link("edit_add_parent_help", "qm"); ?>
			<a href="javascript <?php print $gm_lang["add_mother"]; ?>" onclick="return addnewparentfamily('', 'WIFE', '<?php print $controller->famid; ?>', 'add_mother');"><?php print $gm_lang["add_mother"]; ?></a><br />
		<?php }
		}
		?></td>
		<td valign="top">
			<div class="accesskeys">
				<a class="accesskeys" href="<?php print 'timeline.php?pids[0]=' . $controller->parents['HUSB'].'&amp;pids[1]='.$controller->parents['WIFE'];?>" title="<?php print $gm_lang['parents_timeline'] ?>" tabindex="-1" accesskey="<?php print $gm_lang['accesskey_family_parents_timeline']; ?>"><?php print $gm_lang['parents_timeline'] ?></a>
				<a class="accesskeys" href="<?php print 'timeline.php?' . $controller->getChildrenUrlTimeline();?>" title="<?php print $gm_lang["children_timeline"] ?>" tabindex="-1" accesskey="<?php print $gm_lang['accesskey_family_children_timeline']; ?>"><?php print $gm_lang['children_timeline'] ?></a>
				<a class="accesskeys" href="<?php print 'timeline.php?pids[0]=' .$controller->getHusband().'&amp;pids[1]='.$controller->getWife().'&amp;'.$controller->getChildrenUrlTimeline(2);?>" title="<?php print $gm_lang['family_timeline'] ?>" tabindex="-1" accesskey="<?php print $gm_lang['accesskey_family_timeline']; ?>"><?php print $gm_lang['family_timeline'] ?></a>
				<?php if ($SHOW_GEDCOM_RECORD) { ?>
				<a class="accesskeys" href="javascript:show_gedcom_record();" title="<?php print $gm_lang["view_gedcom"] ?>" tabindex="-1" accesskey="<?php print $gm_lang["accesskey_family_gedcom"]; ?>"><?php print $gm_lang["view_gedcom"] ?></a>
				<?php } ?>
			</div>
			<?php
			if ($_REQUEST['view'] != 'preview') :
			?>
			<table class="sublinks_table" cellspacing="4" cellpadding="0">
				<tr>
					<td class="list_label <?php print $TEXT_DIRECTION?>" colspan="4"><?php print $gm_lang['fams_charts']?></td>
				</tr>
				<tr>
					<td class="sublinks_cell <?php print $TEXT_DIRECTION?>">
					<?php $menu = $controller->getChartsMenu(); $menu->printMenu();
					if (file_exists('reports/familygroup.xml')) :
					?>
					</td>
					<td class="sublinks_cell <?php print $TEXT_DIRECTION?>">
					<?php 
					$menu = $controller->getReportsMenu(); 
					$menu->printMenu();
					endif; // reports
					if (userCanEdit($controller->uname) && ($controller->display)) :
					?>
					</td>
					<td class="sublinks_cell <?php print $TEXT_DIRECTION?>">
					<?php 
					$menu = $controller->getEditMenu();
					$menu->printMenu();
					endif; // edit_fam
					if ($controller->display && ($SHOW_GEDCOM_RECORD || $ENABLE_CLIPPINGS_CART >= getUserAccessLevel())) :
					?>
					</td>
					<td class="sublinks_cell <?php print $TEXT_DIRECTION?>">
					<?php
					$menu = $controller->getOtherMenu();
					$menu->printMenu();
					endif; // other
					?>
					</td>
				</tr>
			</table>
			<?php
				if ($controller->accept_success) print "<b>".$gm_lang["accept_successful"]."</b><br />";
			endif;	// view != preview
			?>
		</td>
	</tr>
</table>
<table class="width95">
	<tr>
		<td valign="top" style="width: <?php print $pbwidth?>px;">
			<?php print_family_children($controller->getFamilyID());?>
		</td>
		<td valign="top">
			<?php print_family_facts($controller->getFamilyID());?>
		</td>
	</tr>
</table>
<br />
<?php print_footer();?>