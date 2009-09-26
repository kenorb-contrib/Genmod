<?php
/**
 * Individual Page
 *
 * Display all of the information about an individual
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
 * Page does not validate see line number 1109 -> 15 August 2005
 *
 * @package Genmod
 * @subpackage Display
 * @version $Id$
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");
require_once 'includes/functions/functions_charts.php';

/**
 * Inclusion of the individual controller
*/
$controller = new IndividualController();

print_header($controller->pagetitle);

$controller->CheckNoResult($gm_lang["person_not_found"]);

$controller->CheckPrivate();

$controller->CheckRawEdited();

// ?><pre><?php
// print_r($controller);
// ?></pre><?php
?>
<div id="indi_content" class="<?php echo $TEXT_DIRECTION;?>">
	<!-- NOTE: Display person picture -->
	<?php if ($controller->canshowhighlightedobj && $controller->HighlightedObject != "") {
		print '<div id="indi_picture" class="'.$TEXT_DIRECTION.'">';
			print $controller->HighlightedObject;
		print "</div>";
	}
	?>
	<!-- NOTE: Print person name and ID -->
	<span class="name_head"><?php print $controller->indi->name; ?>
	<span><?php print $controller->indi->addxref; ?></span>
	<span><?php print PrintReady($controller->indi_userlink); ?></span>
	</span><br />
	
	<!-- NOTE: Print person additional name(s) and ID -->
	<?php if (strlen($controller->indi->addname) > 0) print "<span class=\"name_head\">".$controller->indi->addname."</span><br />"; ?>
	
	<!-- NOTE: Display details of person if privacy allows -->
	<?php if ($controller->indi->disp) { 
		$names = $controller->indi->changednames;
		$num = 0;
		foreach($names as $key=>$name) {
			// Name not changed
			if ($name["old"] == $name["new"]) {
				print "<div class=\"indi_spacer";
				if ($controller->indi->isdeleted && $controller->show_changes) print " change_old";
				print "\">";
				$controller->PrintNameRecord($name["old"], $num);
				print "</div>";
				$num++;
			}
			// Name changed
			elseif ($controller->caneditown && $controller->show_changes) {
				print "<div>";
				print "<table class=\"indi_spacer\">";
				if (!empty($name["old"])) {
					print "<tr><td class=\"change_old\">";
					$controller->PrintNameRecord($name["old"], $num, false);
					print "</td></tr>";
				}
				if (!empty($name["new"])) {
					$controller->name_count--;
					print "<tr><td class=\"change_new\">";
					$controller->PrintNameRecord($name["new"], $num);
					print "</td></tr>";
				}
				print "</table></div>";
				$num++;
			}
			else {
				if (!empty($name["old"]))print "<div class=\"indi_spacer\">".$controller->PrintNameRecord($name["old"], $num)."</div>";
				$num++;
			}
		}
		foreach ($controller->indi->facts as $key => $factobj) {
			if ($factobj->fact == "SEX") {
				print "<div class=\"indi_spacer $TEXT_DIRECTION ";
				if($controller->show_changes) print $factobj->style;
				print "\">";
				$controller->GenderRecord($factobj->factrec, $factobj->fact);
				print "<span class=\"label $TEXT_DIRECTION\">".PrintReady($gm_lang["sex"].":    ")."</span><span class=\"field\">".$controller->indi->sexdetails["gender"];
				print " <img src=\"".$controller->indi->sexdetails["image"]."\" title=\"".$controller->indi->sexdetails["gender"]."\" alt=\"".$controller->indi->sexdetails["gender"];
				print "\" width=\"0\" height=\"0\" class=\"sex_image\" border=\"0\" />";
				if (!$controller->indi->isdeleted && $factobj->style != "change_old" && $controller->indi->canedit) {
					if ($controller->indi->sexdetails["add"]) print "<br /><a class=\"font9\" href=\"#\" onclick=\"add_new_record('".$controller->xref."', 'SEX'); return false;\">".$gm_lang["edit"]."</a>";
					else {
						print "<br /><a class=\"font9\" href=\"#\" onclick=\"edit_record('".$controller->xref."', 'SEX', 1, 'edit_gender', 'INDI'); return false;\">".$gm_lang["edit"]."</a> | ";
						print "<a class=\"font9\" href=\"#\" onclick=\"delete_record('".$controller->xref."', 'SEX', 1, 'edit_gender', 'INDI'); return false;\">".$gm_lang["delete"]."</a>\n";
					}
				}
				print "</span>";
				print "</div>";
			}
		}
		
		//-- - put the birth and death info in this section
		print "<div class=\"indi_spacer\" style=\"line-height:20px;\">";
		$bfacts = $controller->indi->SelectFacts(array("BIRT"));
		foreach ($bfacts as $key => $factobj) {
			if ($factobj->style != "") $style = " class=\"".$factobj->style."\"";
			else $style = "";
			print "<span".$style.">".$factobj->descr.": ";
			$factobj->PrintFactDate(false, false, false, $controller->indi->xref);
			$factobj->PrintFactPlace();
			print "</span><br />";
		}
		$dfacts = $controller->indi->SelectFacts(array("DEAT"));
		foreach ($dfacts as $key => $factobj) {
			if ($factobj->style != "") $style = " class=\"".$factobj->style."\"";
			else $style = "";
			print "<span".$style.">".$factobj->descr.": ";
			$factobj->PrintFactDate(false, false, false, $controller->indi->xref);
			$factobj->PrintFactPlace();
			print "</span><br style=\"line-height:30px;\" />";
		}
		if ($SHOW_LDS_AT_GLANCE) print "<br /><b>".GetLdsGlance($controller->indi->gedrec)."</b>";
		?>
		</div>
		<?php 
	}
	// Print indi counter only if displaying a non-private person
	if($SHOW_COUNTER) print "\n<br /><br /><div style=\"margin-left: 3px; width: 100%;\">".$gm_lang["hit_count"]."&nbsp;".$hits."</div>\n";
	
print "</div><br />";

// Print the accesskeys
if (!$controller->view) {
?>
	<div class="accesskeys">
		<a class="accesskeys" href="<?php print "pedigree.php?rootid=$pid";?>" title="<?php print $gm_lang["pedigree_chart"] ?>" tabindex="-1" accesskey="<?php print $gm_lang["accesskey_individual_pedigree"]; ?>"><?php print $gm_lang["pedigree_chart"] ?></a>
		<a class="accesskeys" href="<?php print "descendancy.php?pid=$pid";?>" title="<?php print $gm_lang["descend_chart"] ?>" tabindex="-1" accesskey="<?php print $gm_lang["accesskey_individual_descendancy"]; ?>"><?php print $gm_lang["descend_chart"] ?></a>
		<a class="accesskeys" href="<?php print "timeline.php?pids[]=$pid";?>" title="<?php print $gm_lang["timeline_chart"] ?>" tabindex="-1" accesskey="<?php print $gm_lang["accesskey_individual_timeline"]; ?>"><?php print $gm_lang["timeline_chart"] ?></a>
		<?php
		if (!empty($controller->user)&&!empty($controller->user->gedcomid[$GEDCOMID])) {
			?>
		<a class="accesskeys" href="<?php print "relationship.php?pid1=".$controller->user->gedcomid[$GEDCOMID]."&amp;pid2=".$controller->xref;?>" title="<?php print $gm_lang["relationship_to_me"] ?>" tabindex="-1" accesskey="<?php print $gm_lang["accesskey_individual_relation_to_me"]; ?>"><?php print $gm_lang["relationship_to_me"] ?></a>
		<?php 	}
		if ($controller->canshowgedrec) {?>
		<a class="accesskeys" href="javascript:show_gedcom_record();" title="<?php print $gm_lang["view_gedcom"] ?>" tabindex="-1" accesskey="<?php print $gm_lang["accesskey_individual_gedcom"]; ?>"><?php print $gm_lang["view_gedcom"] ?></a>
		<?php } ?>
	</div>
	
<?php } ?>

<script type="text/javascript">
<!--
	function openerpasteid(id) {
		window.opener.paste_id(id);
		window.close();
	}
	
	var pastefield;
	function paste_id(value) {
		pastefield.value = value;
		pastefield.focus();
	}
	
//-->
</script>
<div id="show_changes"></div>
<?php $controller->PrintDetailJS(); ?>

<?php
// Print the tab doors
$controller->PrintTabs();

print_footer();
?>