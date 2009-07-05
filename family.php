<?php
/**
 * Parses gedcom file and displays information about a family.
 *
 * You must supply a $famid value with the identifier for the family.
 *
 * Genmod: Genealogy Viewer
 * Copyright (C) 2005 - 2008 Genmod Development Team
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
 * @version $Id$
 */

/**
 * Inclusion of the  config file
*/
require_once 'config.php';

require_once 'includes/functions/functions_charts.php';

$controller = new FamilyController();

print_header($controller->getPageTitle());

$controller->CheckNoResult($gm_lang["family_not_found"]);

$controller->CheckPrivate();

$controller->CheckRawEdited();

?>
<div id="show_changes"></div>
<?php $controller->PrintDetailJS(); ?>
<table>
	<tr>
		<td>
		<?php
		print PrintFamilyParents($controller->getFamilyID());
		if (!$controller->isPrintPreview() && $controller->display && $controller->family->canedit) {
		if (empty($controller->family->husb) && !$controller->family->isdeleted) { ?>
			<?php print_help_link("edit_add_parent_help", "qm"); ?> 
			<a href="javascript <?php print $gm_lang["add_father"]; ?>" onclick="return addnewparentfamily('', 'HUSB', '<?php print $controller->family->xref; ?>', 'add_father');"><?php print $gm_lang["add_father"]; ?></a><br />
		<?php }
		if (empty($controller->family->wife) && !$controller->family->isdeleted)  { ?>
			<?php print_help_link("edit_add_parent_help", "qm"); ?>
			<a href="javascript <?php print $gm_lang["add_mother"]; ?>" onclick="return addnewparentfamily('', 'WIFE', '<?php print $controller->family->xref; ?>', 'add_mother');"><?php print $gm_lang["add_mother"]; ?></a><br />
		<?php }
		}
		?></td>
		<td valign="top">
			<div class="accesskeys">
				<a class="accesskeys" href="<?php print 'timeline.php?pids[0]=' . $controller->parents['HUSB'].'&amp;pids[1]='.$controller->parents['WIFE'];?>" title="<?php print $gm_lang['parents_timeline'] ?>" tabindex="-1" accesskey="<?php print $gm_lang['accesskey_family_parents_timeline']; ?>"><?php print $gm_lang['parents_timeline'] ?></a>
				<a class="accesskeys" href="<?php print 'timeline.php?' . $controller->getChildrenUrlTimeline();?>" title="<?php print $gm_lang["children_timeline"] ?>" tabindex="-1" accesskey="<?php print $gm_lang['accesskey_family_children_timeline']; ?>"><?php print $gm_lang['children_timeline'] ?></a>
				<a class="accesskeys" href="<?php print 'timeline.php?pids[0]=' .$controller->getHusband().'&amp;pids[1]='.$controller->getWife().'&amp;'.$controller->getChildrenUrlTimeline(2);?>" title="<?php print $gm_lang['family_timeline'] ?>" tabindex="-1" accesskey="<?php print $gm_lang['accesskey_family_timeline']; ?>"><?php print $gm_lang['family_timeline'] ?></a>
				<?php if ($Users->userCanViewGedlines()) { ?>
				<a class="accesskeys" href="javascript:show_gedcom_record();" title="<?php print $gm_lang["view_gedcom"] ?>" tabindex="-1" accesskey="<?php print $gm_lang["accesskey_family_gedcom"]; ?>"><?php print $gm_lang["view_gedcom"] ?></a>
				<?php } ?>
			</div>
		</td>
	</tr>
</table>
<table class="width95">
	<tr>
		<td valign="top" style="width: <?php print $pbwidth?>px;">
			<?php PrintFamilyChildren($controller->getFamilyID());?>
		</td>
		<td valign="top">
			<?php PrintFamilyFacts($controller->getFamilyID(), 0, $controller->family->canedit);?>
		</td>
	</tr>
</table>
<br />
<?php print_footer();?>