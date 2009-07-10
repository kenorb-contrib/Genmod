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

print_header($controller->PageTitle);

$controller->CheckNoResult($gm_lang["person_not_found"]);

$controller->CheckPrivate();

$controller->CheckRawEdited();

$namesprinted = false;
// ?><pre><?php
// print_r($controller);
// ?></pre><?php
?>
<div id="indi_content" class="<?php echo $TEXT_DIRECTION;?>">
	<!-- NOTE: Display person picture -->
	<?php if ($controller->canShowHighlightedObject && !empty($controller->HighlightedObject)) {
		print '<div id="indi_picture" class="'.$TEXT_DIRECTION.'">';
			print $controller->HighlightedObject;
		print "</div>";
	}
	?>
	
	<!-- NOTE: Print person name and ID -->
	<span class="name_head"><?php print $controller->indi->name; ?>
	<span><?php print $controller->indi->addxref; ?></span>
	<span><?php print PrintReady($controller->uname); ?></span>
	</span><br />
	
	<!-- NOTE: Print person additional name(s) and ID -->
	<?php if (strlen($controller->indi->addname) > 0) print "<span class=\"name_head\">".$controller->indi->addname."</span><br />"; ?>
	
	<!-- NOTE: Display details of person if privacy allows -->
	<?php if ($controller->indi->disp) { ?>
		<?php
		foreach ($controller->indi->globalfacts as $key => $value) {
			$fact = trim($value[0]);
			if ($fact=="SEX") {
				$changed = false;
				print "<div class=\"indi_spacer $TEXT_DIRECTION";
				if($controller->show_changes) {
					if ($controller->indi->isdeleted) print " change_old";
					else if (isset($value[3]) && $value[3] == "new") print " change_new";
					else if (IsChangedFact($controller->indi->xref, $value[1])) {
						print " change_old";
						$changed = true;
					}
				}
				print "\">";
				print PrintReady($controller->gender_record($value[1], $value[0]));
				print "<span class=\"label $TEXT_DIRECTION\">".PrintReady($gm_lang["sex"].":    ")."</span><span class=\"field\">".$controller->indi->sexdetails["gender"];
				print " <img src=\"".$controller->indi->sexdetails["image"]."\" title=\"".$controller->indi->sexdetails["gender"]."\" alt=\"".$controller->indi->sexdetails["gender"];
				print "\" width=\"0\" height=\"0\" class=\"sex_image\" border=\"0\" />";
				if (!$controller->indi->isdeleted && !$changed && $controller->indi->canedit) {
					if ($controller->indi->sexdetails["add"]) print "<br /><a class=\"font9\" href=\"#\" onclick=\"add_new_record('".$controller->pid."', 'SEX'); return false;\">".$gm_lang["edit"]."</a>";
					else {
						print "<br /><a class=\"font9\" href=\"#\" onclick=\"edit_record('".$controller->pid."', 'SEX', 1, 'edit_gender'); return false;\">".$gm_lang["edit"]."</a> | ";
						print "<a class=\"font9\" href=\"#\" onclick=\"delete_record('".$controller->pid."', 'SEX', 1, 'edit_gender'); return false;\">".$gm_lang["delete"]."</a>\n";
					}
				}
				print "</span>";
				print "</div>";
				if ($changed && $controller->show_changes) {
					print "<div class=\"indi_spacer change_new\">";
					$newrec = RetrieveChangedFact($controller->indi->xref, "SEX", $value[1]);
					print $controller->gender_record($newrec, $value[0]);
					if (empty($newrec)) $add = true;
					else $add = false;
					print "<span class=\"label\">".$gm_lang["sex"].":    </span><span class=\"field\">".$controller->indi->sexdetails["gender"];
					print " <img src=\"".$controller->indi->sexdetails["image"]."\" title=\"".$controller->indi->sexdetails["gender"]."\" alt=\"".$controller->indi->sexdetails["gender"];
					print "\" width=\"0\" height=\"0\" class=\"sex_image\" border=\"0\" />";
					if (!$controller->indi->isdeleted) {
						if ($controller->indi->sexdetails["add"] || $add) print "<br /><a class=\"font9\" href=\"#\" onclick=\"add_new_record('".$controller->pid."', 'SEX'); return false;\">".$gm_lang["edit"]."</a>";
						else {
							print "<br /><a class=\"font9\" href=\"#\" onclick=\"edit_record('".$controller->pid."', 'SEX', 1, 'edit_gender'); return false;\">".$gm_lang["edit"]."</a> | ";
							print "<a class=\"font9\" href=\"#\" onclick=\"delete_record('".$controller->pid."', 'SEX', 1, 'edit_gender'); return false;\">".$gm_lang["delete"]."</a>\n";
						}
					}
					print "</span>";
					print "</div>";
				}
			}
			else {
				if ($fact=="NAME") {
					if (!$namesprinted) {
						$names = $controller->indi->changednames;
						$num = 0;
						foreach($names as $key=>$name) {
							// Name not changed
							if ($name["old"] == $name["new"]) {
								print "<div class=\"indi_spacer";
								if ($controller->indi->isdeleted) print " change_old";
								print "\">";
								$controller->print_name_record($name["old"], $num);
								print "</div>";
								$num++;
								continue;
							}
							// Name changed
							if ($controller->indi->can_editown && $controller->show_changes && $name["old"] != $name["new"] && !empty($name["new"])&& !empty($name["old"])) {
								print "<div>";
								print "<table class=\"indi_spacer\">";
								print "<tr><td class=\"change_old\">";
								$controller->print_name_record($name["old"], $num, false);
								$controller->name_count--;
								print "</td></tr>";
								print "<tr><td class=\"change_new\">";
								$controller->print_name_record($name["new"], $num);
								print "</td></tr></table></div>";
								$num++;
								continue;
							}
							// Name added
							if ($controller->indi->can_editown && $show_changes && empty($name["old"]) && !empty($name["new"])) {
								print "<div class=\"indi_spacer change_new\">";
								$controller->print_name_record($name["new"], $num);
								print "</div>";
								$num++;
							}
							// Name deleted
							if ($controller->indi->can_editown && $show_changes && empty($name["new"]) && !empty($name["old"])) {
								print "<div class=\"indi_spacer change_old\">";
								$controller->print_name_record($name["old"], $num, false);
								print "</div>";
								$num++;
							}
							else {
								if (!empty($name["old"]))print "<div class=\"indi_spacer\">".$controller->print_name_record($name["old"], $num)."</div>";
								$num++;
							}
						}
					$namesprinted = true;
					}
				}
			}
		}
		print "<table>";
		//-- - put the birth info in this section
		if ($controller->show_changes) {
			if ($controller->indi->isdeleted) {
				if (!$controller->indi->brec == "") {
					print "<tr><td class=\"change_old\">";
					print "<span class=\"label\">".$factarray["BIRT"].":</span>";
					print "<span class=\"field\">";
					print_fact_date($controller->indi->brec);
					print_fact_place($controller->indi->brec);
					print "</span></td></tr>";
				}
			}
			else {
				if ($controller->indi->isnew && !$controller->indi->brec == "") {
					print "<tr><td class=\"change_new\">";
					print "<span class=\"label\">".$factarray["BIRT"].":</span>";
					print "<span class=\"field\">";
					print_fact_date($controller->indi->brec);
					print_fact_place($controller->indi->brec);
					print "</span></td></tr>";
				}
				else {
					if (!$controller->indi->newbrec == "") {
						if (!$controller->indi->brec == "") {
							print "<tr><td class=\"change_old\">";
							print "<span class=\"label\">".$factarray["BIRT"].":</span>";
							print "<span class=\"field\">";
							print_fact_date($controller->indi->brec);
							print_fact_place($controller->indi->brec);
							print "</span></td></tr>";
						}
						print "<tr><td class=\"change_new\">";
						print "<span class=\"label\">".$factarray["BIRT"].":</span>";
						print "<span class=\"field\">";
						print_fact_date($controller->indi->newbrec);
						print_fact_place($controller->indi->newbrec);
						print "</span></td></tr>";
					}
					else {
						if (!$controller->indi->brec == "") { 
							print "<tr><td class=\"$TEXT_DIRECTION\"><span class=\"label\">".$factarray["BIRT"].":"."</span>";
							print "<span class=\"field\">";
							print_fact_date($controller->indi->brec);
							print_fact_place($controller->indi->brec);
							print "</span></td></tr>";
						}
					}
				}
			}
		}
		else {
			if (!$controller->indi->brec == "") { 
				print "<tr><td><span class=\"label\">".$factarray["BIRT"].":"."</span>";
				print "<span class=\"field\">";
				print_fact_date($controller->indi->brec);
				print_fact_place($controller->indi->brec);
				print "</span></td></tr>";
			}
		}
		// RFE [ 1229233 ] "DEAT" vs "DEAT Y"
		// The check $deathrec != "1 DEAT" will not show any records that only have 1 DEAT in them
		if ($controller->show_changes) {
			if ($controller->indi->isdeleted && !$controller->indi->drec == "") {
				print "<tr><td class=\"change_old\">";
				print "<span class=\"label\">".$factarray["DEAT"].":</span>";
				print "<span class=\"field\">";
				print_fact_date($controller->indi->drec);
				print_fact_place($controller->indi->drec);
				print "</span></td></tr>";
			}
			else {
				if ($controller->indi->isnew && !$controller->indi->drec == "") {
					print "<tr><td class=\"change_new\">";
					print "<span class=\"label\">".$factarray["DEAT"].":</span>";
					print "<span class=\"field\">";
					print_fact_date($controller->indi->drec);
					print_fact_place($controller->indi->drec);
					print "</span></td></tr>";
				}
				else {
					if (!empty($controller->indi->newdrec)) {
						if (!$controller->indi->drec == "") {
							print "<tr><td class=\"change_old\">";
							print "<span class=\"label\">".$factarray["DEAT"].":</span>";
							print "<span class=\"field\">";
							print_fact_date($controller->indi->drec);
							print_fact_place($controller->indi->drec);
							print "</span></td></tr>";
						}
						print "<tr><td class=\"change_new\">";
						print "<span class=\"label\">".$factarray["DEAT"].":</span>";
						print "<span class=\"field\">";
						print_fact_date($controller->indi->newdrec);
						print_fact_place($controller->indi->newdrec);
						print "</span></td></tr>";
					}
					else {
						if (!$controller->indi->drec == ""&& trim($controller->indi->drec) != "1 DEAT") { 
							print "<tr><td><span class=\"label\">".$factarray["DEAT"].":</span>";
							print "<span class=\"field\">";
							print_fact_date($controller->indi->drec);
							print_fact_place($controller->indi->drec);
							print "</span></td></tr>";
						}
					}
				}
			}
		}
		else {
			if (!$controller->indi->drec == "" && trim($controller->indi->drec) != "1 DEAT") { 
				print "<tr><td><span class=\"label\">".$factarray["DEAT"].":</span>";
				print "<span class=\"field\">";
				print_fact_date($controller->indi->drec);
				print_fact_place($controller->indi->drec);
				print "</span></td></tr>";
			} 
		}
		if ($SHOW_LDS_AT_GLANCE) print "<br /><b>".GetLdsGlance($controller->indi->gedrec)."</b>";
		?>
		</table>
		<?php 
	}
	if($SHOW_COUNTER) {
		// Print indi counter only if displaying a non-private person
		print "\n<br /><br /><span style=\"margin-left: 3px;\">".$gm_lang["hit_count"]."&nbsp;".$hits."</span>\n";
	}
print "</div>";

// Print the accesskeys
if (!$controller->view) {
?>
	<div class="accesskeys">
		<a class="accesskeys" href="<?php print "pedigree.php?rootid=$pid";?>" title="<?php print $gm_lang["pedigree_chart"] ?>" tabindex="-1" accesskey="<?php print $gm_lang["accesskey_individual_pedigree"]; ?>"><?php print $gm_lang["pedigree_chart"] ?></a>
		<a class="accesskeys" href="<?php print "descendancy.php?pid=$pid";?>" title="<?php print $gm_lang["descend_chart"] ?>" tabindex="-1" accesskey="<?php print $gm_lang["accesskey_individual_descendancy"]; ?>"><?php print $gm_lang["descend_chart"] ?></a>
		<a class="accesskeys" href="<?php print "timeline.php?pids[]=$pid";?>" title="<?php print $gm_lang["timeline_chart"] ?>" tabindex="-1" accesskey="<?php print $gm_lang["accesskey_individual_timeline"]; ?>"><?php print $gm_lang["timeline_chart"] ?></a>
		<?php
		if (!empty($controller->user)&&!empty($controller->user->gedcomid[$GEDCOM])) {
			?>
		<a class="accesskeys" href="<?php print "relationship.php?pid1=".$controller->user->gedcomid[$GEDCOM]."&amp;pid2=".$controller->pid;?>" title="<?php print $gm_lang["relationship_to_me"] ?>" tabindex="-1" accesskey="<?php print $gm_lang["accesskey_individual_relation_to_me"]; ?>"><?php print $gm_lang["relationship_to_me"] ?></a>
		<?php 	}
		if ($controller->canShowGedcomRecord) {?>
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