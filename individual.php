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
 * Inclusion of the individual controller
*/
require_once("includes/controllers/individual_ctrl.php");

print_header($controller->PageTitle);
// NOTE: If the person is not found, display message
if (!$controller->indi->exist) {
	print $gm_lang["person_not_found"];
	print_footer();
	exit;
}
else if (!$controller->indi->dispname) {
	print_privacy_error($CONTACT_EMAIL);
	print_footer();
	exit;
}
$controller->indi->getActionList();

// Check if the record is raw-edited
if($controller->show_changes && GetChangeData(true, $controller->pid, true)) {
	$sql = "SELECT COUNT(ch_id) FROM ".$TBLPREFIX."changes WHERE ch_gedfile='".$GEDCOMID."' AND ch_type='edit_raw' AND ch_gid='".$controller->pid."'";
	if ($res = NewQuery($sql)) {
		$row = $res->FetchRow();
		$res->FreeResult();
		if ($row[0]>0) print $gm_lang["is_rawedited"];
	}
}

if(!isset($FACT_COUNT)) $FACT_COUNT = 0;
$namesprinted = false;
$prtcount = 0;
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
	<span><?php if ($SHOW_ID_NUMBERS) print PrintReady("(".$controller->indi->xref.")"); ?></span>
	<span><?php print PrintReady($controller->indi_username); ?></span>
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
					if ($controller->indi->indideleted) print " change_old";
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
				if (!$controller->indi->indideleted && !$changed && $controller->canedit) {
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
					if (!$controller->indi->indideleted) {
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
								if ($controller->indi->indideleted) print " change_old";
								print "\">";
								$controller->print_name_record($name["old"], $num);
								print "</div>";
								$num++;
								continue;
							}
							// Name changed
							if ($controller->caneditown && $show_changes != "no" && $name["old"] != $name["new"] && !empty($name["new"])&& !empty($name["old"])) {
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
							if ($controller->caneditown && $show_changes != "no" && empty($name["old"]) && !empty($name["new"])) {
								print "<div class=\"indi_spacer change_new\">";
								$controller->print_name_record($name["new"], $num);
								print "</div>";
								$num++;
							}
							// Name deleted
							if ($controller->caneditown && $show_changes != "no" && empty($name["new"]) && !empty($name["old"])) {
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
			if ($controller->indi->indideleted) {
				if (!empty($controller->brec)) {
					print "<tr><td class=\"change_old\">";
					print "<span class=\"label\">".$factarray["BIRT"].":</span>";
					print "<span class=\"field\">";
					print_fact_date($controller->indi->brec);
					print_fact_place($controller->indi->brec);
					print "</span></td></tr>";
				}
			}
			else {
				if ($controller->indi->indinew && !empty($controller->indi->brec)) {
					print "<tr><td class=\"change_new\">";
					print "<span class=\"label\">".$factarray["BIRT"].":</span>";
					print "<span class=\"field\">";
					print_fact_date($controller->indi->brec);
					print_fact_place($controller->indi->brec);
					print "</span></td></tr>";
				}
				else {
					if (!empty($controller->indi->newbrec)) {
						if (!empty($controller->indi->brec)) {
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
						if (!empty($controller->indi->brec)) { 
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
			if (!empty($controller->indi->brec)) { 
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
			if ($controller->indi->indideleted && !empty($controller->drec)) {
				print "<tr><td class=\"change_old\">";
				print "<span class=\"label\">".$factarray["DEAT"].":</span>";
				print "<span class=\"field\">";
				print_fact_date($controller->indi->drec);
				print_fact_place($controller->indi->drec);
				print "</span></td></tr>";
			}
			else {
				if ($controller->indi->indinew && !empty($controller->indi->drec)) {
					print "<tr><td class=\"change_new\">";
					print "<span class=\"label\">".$factarray["DEAT"].":</span>";
					print "<span class=\"field\">";
					print_fact_date($controller->indi->drec);
					print_fact_place($controller->indi->drec);
					print "</span></td></tr>";
				}
				else {
					if (!empty($controller->indi->newdrec)) {
						if (!empty($controller->indi->drec)) {
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
						if (!empty($controller->indi->drec)&& trim($controller->indi->drec) != "1 DEAT") { 
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
			if (!empty($controller->indi->drec)&& trim($controller->indi->drec) != "1 DEAT") { 
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

<div id="show_changes"></div>
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
	
// javascript function to open a window with the raw gedcom in it
function show_gedcom_record(shownew) {
	fromfile="";
	if (shownew=="yes") fromfile='&fromfile=1';
	var recwin = window.open("gedrecord.php?pid=<?php print $controller->pid; ?>"+fromfile, "", "top=50,left=50,width=300,height=400,scrollbars=1,scrollable=1,resizable=1");
}

function reload() {
	window.location.reload();
}

function showchanges() {
		sndReq('show_changes', 'set_show_changes', 'set_show_changes', '<?php if ($show_changes == "yes") print "no"; else print "yes"; ?>');
		window.location.reload();
}
// The function below does not go well with validation.
// The option to use getElementsByName is used in connection with code from
// the functions_print.php file.
function togglerow(label) {
	ebn = document.getElementsByName(label);
	if (ebn.length) disp = ebn[0].style.display;
	else disp="";
	if (disp=="none") {
		disp="table-row";
		if (document.all) disp="inline"; // IE
		document.getElementById('rela_plus').style.display="none";
		document.getElementById('rela_minus').style.display="inline";
	}
	else {
		disp="none";
		document.getElementById('rela_plus').style.display="inline";
		document.getElementById('rela_minus').style.display="none";
	}
	for (i=0; i<ebn.length; i++) ebn[i].style.display=disp;
}
function tabswitch(n) {
	sndReq('dummy', 'remembertab', 'xref', '<?php print JoinKey($controller->pid, $GEDCOMID); ?>' , 'tab_tab', n, 'type', 'indi');
	if (n==7) n = 0;
	var tabid = new Array('0', 'facts','notes','sources','media','relatives','actions');
	// show all tabs ?
	var disp='none';
	if (n==0) disp='block';
	// reset all tabs areas
	for (i=1; i<tabid.length; i++) document.getElementById(tabid[i]).style.display=disp;
	if ('<?php echo $view; ?>' != 'preview') {
		// current tab area
		if (n>0) document.getElementById(tabid[n]).style.display='block';
		// empty tabs
		for (i=0; i<tabid.length; i++) {
			var elt = document.getElementById('door'+i);
			if (document.getElementById('no_tab'+i)) { // empty ?
				if (<?php if ($Users->userCanEdit($gm_username)) echo 'true'; else echo 'false';?>) {
					elt.style.display='block';
					elt.style.opacity='0.4';
					elt.style.filter='alpha(opacity=40)';
				}
				else elt.style.display='none'; // empty and not editable ==> hide
			}
			else elt.style.display='block';
			if (<?php if (!is_object($Actions)) echo 'true'; else echo 'false';?> && tabid[i]=='actions') elt.style.display='none';
		}
		// current door
		for (i=0; i<tabid.length; i++) {
			document.getElementById('door'+i).className='shade1 rela';
		}
		document.getElementById('door'+n).className='shade1';
		return false;
	}
}
//-->
</script>


<?php
if (!$controller->view) {
	?>
	<br /><br /><div class="door">
	<dl>
	<dd id="door5"><a href="javascript:;" onclick="tabswitch(5)" ><?php print $gm_lang["relatives"]?></a></dd>
	<dd id="door1"><a href="javascript:;" onclick="tabswitch(1)" ><?php print $gm_lang["personal_facts"]?></a></dd>
	<dd id="door3"><a href="javascript:;" onclick="tabswitch(3)" ><?php print $gm_lang["ssourcess"]?></a></dd>
	<dd id="door4"><a href="javascript:;" onclick="tabswitch(4)" ><?php print $gm_lang["media"]?></a></dd>
	<dd id="door2"><a href="javascript:;" onclick="tabswitch(2)" ><?php print $gm_lang["notes"]?></a></dd>
	<dd id="door0"><a href="javascript:;" onclick="tabswitch(0)" ><?php print $gm_lang["all"]?></a></dd>
	<dd id="door6"><a href="javascript:;" onclick="tabswitch(6)" ><?php print $gm_lang["research_log"]?></a></dd>
	</dl>
	</div><div id="dummy"></div>
	<?php
}
?>
<br />
<!-- ============================= Start 5th tab individual page ==== Close relatives -->
<div id="relatives" class="tab_page" style="display:none;" >
<?php
$show_full = true;
// NOTE: parent families
?>
	<?php
	if (isset($controller->indi->parents)) {
		foreach ($controller->indi->parents as $famid => $family) {
			?>
			<table>
			<tr>
				<td><img src="<?php print $GM_IMAGE_DIR."/".$GM_IMAGES["cfamily"]["small"]; ?>" border="0" class="icon" alt="" /></td>
				<td><span class="subheaders"><?php print $family["label"];?></span>
				<?php 
			if (!$controller->view) { ?>
				 - <a href="family.php?famid=<?php print $famid; ?>">[<?php print $gm_lang["view_family"]; ?><?php if ($SHOW_FAM_ID_NUMBERS) print " &lrm;(".$famid.")&lrm;"; ?>]</a>&nbsp;&nbsp;
				 <?php if (empty($family["HUSB"]) && $controller->canedit) { 
				 	print_help_link("edit_add_parent_help", "qm"); ?>
					<a href="javascript <?php print $gm_lang["add_father"]; ?>" onclick="return addnewparentfamily('', 'HUSB', '<?php print $famid; ?>', 'add_father');"><?php print $gm_lang["add_father"]; ?></a>
				<?php }
				if (empty($family["WIFE"]) && $controller->canedit) {
					print_help_link("edit_add_parent_help", "qm"); ?>
					<a href="javascript <?php print $gm_lang["add_mother"]; ?>" onclick="return addnewparentfamily('', 'WIFE', '<?php print $famid; ?>', 'add_mother');"><?php print $gm_lang["add_mother"]; ?></a>
				<?php } 
			}?>
				</td>
			</tr>
			</table>
			<table class="facts_table">
			<?php if(!empty($family["HUSB"])) { ?>
			<tr>
				<td class="width20 shade2 center"
				<?php if ($controller->show_changes && !empty($family["HUSB"]->indinew)) print " style=\"border: solid #0000FF 2px; vertical-align: middle;\"";
				else print " style=\"vertical-align: middle;\"";?>
				>
				<?php print $family["HUSB"]->label; ?></td>
				<td class="<?php print $controller->getPersonStyle($family["HUSB"]); ?>">
				<?php
				if (isset($family["HUSB"])) {
					print_pedigree_person($family["HUSB"]->xref,2,!$controller->view, $prtcount);
					$prtcount++;
				}
				?>
				</td>
			</tr>
			<?php }
			if(!empty($family["WIFE"])) { ?>
			<tr>
				<td class="width20 shade2 center"
				<?php if ($controller->show_changes && !empty($family["WIFE"]->indinew)) print " style=\"border: solid #0000FF 2px; vertical-align: middle;\"";
				else print " style=\"vertical-align: middle;\"";?>
				>
				<?php print $family["WIFE"]->label; ?></td>
				<td class="<?php print $controller->getPersonStyle($family["WIFE"]); ?>">
				<?php
				if (isset($family["WIFE"])) {
					print_pedigree_person($family["WIFE"]->xref,2,!$controller->view, $prtcount);
					$prtcount++;
				}
				?>
				</td>
			</tr>
	<?php }
	if (isset($controller->indi->siblings)) {
		foreach ($controller->indi->siblings as $sibid => $sibling) {
			if (isset($sibling->label[$famid])) {
			?>
			<tr>
				<td class="width20 shade2 center"
				<?php if ($controller->show_changes && !empty($sibling->indinew)) print " style=\"border: solid #0000FF 2px; vertical-align: middle;\"";
				else print " style=\"vertical-align: middle;\"";?>
				>
				<?php print $sibling->label[$famid]; ?></td>
				<td class="<?php print $controller->getPersonStyle($sibling); ?>">
				<?php
				print_pedigree_person($sibid,2,!$controller->view, $prtcount);
				$prtcount++;
				?>
				</td>
			</tr>
			<?php
			}
		}
	}
	print "</table>";
}
}
	?>

<?php
// NOTE: Half-siblings father
	if (isset($controller->indi->father_family)) {
		foreach ($controller->indi->father_family as $famid => $family) {
		?>
		<table><tr>
			<td><img src="<?php print $GM_IMAGE_DIR."/".$GM_IMAGES["cfamily"]["small"]; ?>" border="0" class="icon" alt="" /></td>
			<td><span class="subheaders"><?php print $family["label"];?></span>
			<?php 
		if (!$controller->view) { ?>
			 - <a href="family.php?famid=<?php print $famid; ?>">[<?php print $gm_lang["view_family"]; ?><?php if ($SHOW_FAM_ID_NUMBERS) print " &lrm;(".$famid.")&lrm;"; ?>]</a>
		<?php } ?>
			</td>
		</tr>
		</table>
		<table class="facts_table">
		<tr>
			<td class="width20 shade2 center" style="vertical-align: middle;"><?php print $controller->indi->father_family[$famid]["WIFE"]->label; ?></td>
			<td class="<?php print $controller->getPersonStyle($controller->indi->father_family[$famid]["WIFE"]); ?>">
			<?php
			if (isset($controller->indi->father_family[$famid]["WIFE"])) {
				print_pedigree_person($controller->indi->father_family[$famid]["WIFE"]->xref,2,!$controller->view, $prtcount);
				$prtcount++;
			}
			?>
			</td>
		</tr>
	<?php
			foreach ($family["kids"] as $sibid => $sibling) {
				?>
				<tr>
					<td class="width20 shade2 center" style="vertical-align: middle;"><?php print $sibling->label; ?></td>
					<td class="<?php print $controller->getPersonStyle($sibling); ?>">
					<?php
					print_pedigree_person($sibid,2,!$controller->view, $prtcount);
					$prtcount++;
					?>
					</td>
				</tr>
				<?php
			}
		print "</table>";
		}
	}
	?>
<?php

// NOTE: Half-siblings mother
	if (isset($controller->indi->mother_family)) {
		foreach ($controller->indi->mother_family as $famid => $family) {
		?>
		<table><tr>
			<td><img src="<?php print $GM_IMAGE_DIR."/".$GM_IMAGES["cfamily"]["small"]; ?>" border="0" class="icon" alt="" /></td>
			<td><span class="subheaders"><?php print $family["label"];?></span>
			<?php 
		if (!$controller->view) { ?>
			 - <a href="family.php?famid=<?php print $famid; ?>">[<?php print $gm_lang["view_family"]; ?><?php if ($SHOW_FAM_ID_NUMBERS) print " &lrm;(".$famid.")&lrm;"; ?>]</a>
		<?php } ?>
			</td>
		</tr>
		</table>
		<table class="facts_table">
		<?php
		if (!empty($family["HUSB"]->xref)) {?>
			
			<tr>
				<td class="width20 shade2 center"  style="vertical-align: middle;"><?php print $family["HUSB"]->label; ?></td>
				<td class="<?php print $controller->getPersonStyle($family["HUSB"]); ?>">
				<?php
				if (isset($family["HUSB"])) {
					print_pedigree_person($family["HUSB"]->xref,2,!$controller->view, $prtcount);
					$prtcount++;
				}
				?>
				</td>
			</tr>
		<?php
		}
		foreach ($family["kids"] as $sibid => $sibling) {
				?>
				<tr>
					<td class="width20 shade2 center" style="vertical-align: middle;"><?php print $sibling->label; ?></td>
					<td class="<?php print $controller->getPersonStyle($sibling); ?>">
					<?php
					print_pedigree_person($sibid,2,!$controller->view, $prtcount);
					$prtcount++;
					?>
					</td>
				</tr>
				<?php
			}
		print "</table>";
		}
	}
	?>
<?php
// NOTE: spouses and children
if (isset($controller->indi->spouses)) {
	foreach ($controller->indi->spouses as $famid => $family) {
		?>
		<table>
		<tr>
			<td><img src="<?php print $GM_IMAGE_DIR."/".$GM_IMAGES["cfamily"]["small"]; ?>" border="0" class="icon" alt="" /></td>
			<td><span class="subheaders"><?php print PrintReady($family["label"]);?></span>
			<?php 
		if (!$controller->view) { ?>
			 - <a href="family.php?famid=<?php print $famid; ?>">[<?php print $gm_lang["view_family"]; ?><?php if ($SHOW_FAM_ID_NUMBERS) print " &lrm;($famid)&lrm;"; ?>]</a>
		<?php } ?>
			</td>
		</tr>
		</table>
		<table class="facts_table">
		<?php if (!empty($family["parents"]["HUSB"])) { ?>
		<tr>
			<td class="width20 shade2 center
			<?php
				if ($family["parents"]["HUSB"]->indinew) print " change_new";
				else if ($family["parents"]["HUSB"]->indideleted) print " change_old";
			?>
			" style="vertical-align: middle;">
			<?php print $family["parents"]["HUSB"]->label; ?></td>
			<td class="<?php print $controller->getPersonStyle($family["parents"]["HUSB"]); ?>">
			<?php
			if (isset($family["parents"]["HUSB"])) {
				if ($family["parents"]["HUSB"]->show_changes) print_pedigree_person($family["parents"]["HUSB"]->xref,2,!$controller->view, $prtcount, "", $family["parents"]["HUSB"]->newgedrec);
				else print_pedigree_person($family["parents"]["HUSB"]->xref,2,!$controller->view, $prtcount);
				$prtcount++;
			}
			?>
			</td>
		</tr>
		<?php }
			if (!empty($family["parents"]["WIFE"])) { ?>
		<tr>
			<td class="width20 shade2 center 
			<?php
				if ($family["parents"]["WIFE"]->indinew) print " change_new";
				else if ($family["parents"]["WIFE"]->indideleted) print " change_old";
			?>
			" style="vertical-align: middle;">
			<?php print $family["parents"]["WIFE"]->label; ?></td>
			<td class="<?php print $controller->getPersonStyle($family["parents"]["WIFE"]); ?>">
			<?php
			if (isset($family["parents"]["WIFE"])) {
				if ($family["parents"]["WIFE"]->show_changes) print_pedigree_person($family["parents"]["WIFE"]->xref,2,!$controller->view, $prtcount, "", $family["parents"]["WIFE"]->newgedrec);
				else print_pedigree_person($family["parents"]["WIFE"]->xref,2,!$controller->view, $prtcount);
				$prtcount++;
			}
			?>
			</td>
		</tr>
		<?php
			}
		if (isset($family["kids"])) {
			foreach ($family["kids"] as $kidid => $kid) {
//				print_r($kid);
				?>
				<tr>
					<td class="width20 shade2 center 
					<?php
					if (!empty($family["kids"][$kidid]->xref) && $controller->show_changes) {
						if ($family["kids"][$kidid]->indinew) print " change_new";
						else if ($family["kids"][$kidid]->indideleted) print " change_old";
					}
					?>
					" style="vertical-align: middle;">
					<?php print $family["kids"][$kidid]->label; ?></td>
					<td class="<?php print $controller->getPersonStyle($kid); ?>">
					<?php
					print_pedigree_person($family["kids"][$kidid]->xref,2,!$controller->view, $prtcount);
					$prtcount++;
					?>
					</td>
				</tr>
				<?php
			}
		}
		?>
		</table>
		<?php
	}
}
if (!$controller->indi->close_relatives) print "<tr><td id=\"no_tab5\" colspan=\"2\" class=\"shade1\">".$gm_lang["no_tab5"]."</td></tr>\n";
?>
<br />
</div>
<!-- ======================== Start 1st tab individual page ============ Personal Facts and Details -->
<div id="facts" class="tab_page" style="display:none;" >
<br />
<table class="facts_table">
<?php
if (!$controller->indi->disp) {
	print "<tr><td class=\"shade1\" colspan=\"2\">";
	print_privacy_error($CONTACT_EMAIL);
	print "</td></tr>";
}
else {
	if (count($controller->indi->indifacts) == 0) print "<tr><td id=\"no_tab1\" colspan=\"2\" class=\"shade1\">".$gm_lang["no_tab1"]."</td></tr>\n";
	echo '<tr id="row_top"><td></td><td class="shade2 rela">';
	echo '<a href="#" onclick="togglerow(\'row_rela\'); return false;">';
	echo '<img style="display:none;" id="rela_plus" src="'.$GM_IMAGE_DIR.'/'.$GM_IMAGES["plus"]["other"].'" border="0" width="11" height="11" alt="'.$gm_lang["show_details"].'" title="'.$gm_lang["show_details"].'" />';
	echo '<img id="rela_minus" src="'.$GM_IMAGE_DIR.'/'.$GM_IMAGES["minus"]["other"].'" border="0" width="11" height="11" alt="'.$gm_lang["hide_details"].'" title="'.$gm_lang["hide_details"].'" />';
	echo ' '.$gm_lang["relatives_events"];
	echo '</a></td></tr>';
	$yetdied=false;
	$n_chil=1;
	$n_gchi=1;
	foreach ($controller->indi->indifacts as $key => $value) {
		$addfam = "";
		$ctf = preg_match("/1 _GMFS @(.*)@/", $value[1], $matchf);
		if ($ctf>0) {
			$pid = $matchf[1]; // Set for edit link
			$value[2] = 1; // Always first marriage fact for a FAM
			$addfam = $matchf[0]."\r\n";
		}
		else $pid = $controller->pid;
		if (!$controller->show_changes) {
			if ($controller->indi->indideleted) print_fact($value[1], $pid, $value[0], $value[2], $controller->indi->gedrec, "deleted");
			else print_fact($value[1], $pid, $value[0], $value[2], $controller->indi->gedrec, "", $controller->canedit);
		}
		else {
			if ($controller->indi->indideleted && $controller->show_changes) print_fact($value[1], $pid, $value[0], $value[2], $controller->indi->gedrec, "change_old");
			else {
				if ($controller->show_changes && IsChangedFact($pid, $value[1])) {
					$adds = "";
					if (!isset($value[3]) || $value[3] != "new") {
						print_fact($value[1], $pid, $value[0], $value[2], $controller->indi->gedrec, "change_old", $controller->canedit);
					}
					$cts = preg_match("/1 _GMS @(.*)@/", $value[1], $matchs);
					if ($cts>0) $adds = $matchs[0]."\r\n";
					$newfact = RetrieveChangedFact($pid, $value[0], $value[1]);
					if (!empty($newfact)) print_fact($newfact.$addfam.$adds, $controller->pid, $value[0], $value[2], $controller->indi->gedrec, "change_new", $controller->canedit);
				}
				else if ($controller->show_changes && isset($value[3]) && $value[3] == "new" ) {
					print_fact($value[1], $pid, $value[0], $value[2], $controller->indi->gedrec, "change_new", $controller->canedit);
				}
				else print_fact($value[1], $pid, $value[0], $value[2], $controller->indi->gedrec, "", $controller->canedit);
			}
		}
		$FACT_COUNT++;
	}
}
//-- new fact link
if ((!$controller->view) &&($controller->canedit)&&($controller->indi->disp) && !$controller->indi->indideleted) {
	PrintAddNewFact($controller->pid, $controller->indi->indifacts, "INDI");
}
?>
</table>
<br />
</div>
<script language="JavaScript" type="text/javascript">
<!--
	// hide button if list is empty
	ebn = document.getElementsByName('row_rela');
	if (ebn.length==0) document.getElementById('row_top').style.display="none";
	<?php if (!$EXPAND_RELATIVES_EVENTS) print "togglerow('row_rela');"?>
//-->
</script>
<!-- =========================== Start 3rd tab individual page === Sources -->
<div id="sources" class="tab_page" style="display:none;" >
	<br />
	<table class="facts_table">
	<?php if (!$controller->indi->disp) {
		print "<tr><td class=\"shade1\">";
		print_privacy_error($CONTACT_EMAIL);
		print "</td></tr>";
	}
	else {
		if (count($controller->indi->sourfacts) > 0 && $SHOW_SOURCES >= $Users->getUserAccessLevel($gm_username)) {
			foreach ($controller->indi->sourfacts as $key => $value) {
				$fact = trim($value[0]);
				if ($fact=="SOUR") {
					if (!$controller->show_changes) {
						if ($controller->indi->indideleted) print_main_sources($value[1], 1, $pid, $value[2], "deleted");
						else print_main_sources($value[1], 1, $pid, $value[2], "", $controller->canedit);
					}
					else {
						if ($controller->indi->indideleted) print_main_sources($value[1], 1, $pid, $value[2], "change_old", $controller->canedit);
						else {
							if (IsChangedFact($pid, $value[1])) {
								if (!isset($value[3]) || $value[3] != "new") print_main_sources($value[1], 1, $pid, $value[2], "change_old", $controller->canedit);
								print_main_sources(RetrieveChangedFact($pid, $value[0], $value[1]), 1, $pid, $value[2], "change_new", $controller->canedit);
							}
							else if (isset($value[3]) && $value[3] == "new") print_main_sources($value[1], 1, $pid, $value[2], "change_new", $controller->canedit);
							else print_main_sources($value[1], 1, $pid, $value[2], "", $controller->canedit);
						}
					}
					$FACT_COUNT++;
				}
			}
			//-- New Source Link
		}
		else print "<tr><td id=\"no_tab3\" colspan=\"2\" class=\"shade1\">".$gm_lang["no_tab3"]."</td></tr>\n";
		if (!$controller->view && $controller->canedit && $controller->indi->disp && !$controller->indi->indideleted) { ?>
			<tr>
			<td class="width20 shade2"><?php print_help_link("add_source_help", "qm"); ?><?php echo $gm_lang["add_source_lbl"]; ?></td>
			<td class="shade1">
			<a href="javascript: <?php echo $gm_lang["add_source"]; ?>" onclick="add_new_record('<?php echo $controller->pid; ?>','SOUR', 'add_source'); return false;"><?php echo $gm_lang["add_source"]; ?></a>
			<br />
			</td>
			</tr>
		<?php
		}
	}
	?>
</table>
<br />
</div>	

<!-- ==================== Start 4th tab individual page ==== Media -->
<div id="media" class="tab_page" style="display:none;" >
<br />
<table class="facts_table">
<?php
if (!$controller->indi->disp) {
	print "<tr><td class=\"shade1\">";
	print_privacy_error($CONTACT_EMAIL);
	print "</td></tr>";
}
else {
	// NOTE: See if media is present
	if (count($controller->indi->mediafacts) > 0) {
		foreach ($controller->indi->mediafacts as $key => $mediarecord) {
			$ct = preg_match("/\d\sOBJE\s@(.*)@/", $mediarecord[1], $match);
			$media_id = $match[1];
			$mediaged = FindMediaRecord($media_id);
			if (!$controller->show_changes) {
				if ($controller->indi->indideleted) print_main_media($mediarecord[1], $pid, 0, $mediarecord[2], false, "deleted", $controller->canedit);
				else print_main_media($mediarecord[1], $pid, 0, $mediarecord[2], false, "", $controller->canedit);
			}
			else {
				if ($controller->show_changes && $controller->indi->indideleted) {
					print_main_media($mediarecord[1], $pid, 0, $mediarecord[2], false, "change_old", $controller->canedit);
				}
				else {
					if ($controller->show_changes && (IsChangedFact($pid, $mediarecord[1]) || IsChangedFact($media_id, $mediaged))) {
						if (!isset($mediarecord[3]) || $mediarecord[3] != "new") {
							print_main_media($mediarecord[1], $pid, 0, $mediarecord[2], false, "change_old", $controller->canedit);
						}
						$factnew = RetrieveChangedFact($pid, $mediarecord[0], $mediarecord[1]);
						if (!empty($factnew)) {
							print_main_media($factnew, $pid, 0, $mediarecord[2], true,  "change_new", $controller->canedit);
						}
					}
					else if ($controller->show_changes && isset($mediarecord[3]) && $mediarecord[3] == "new") print_main_media($mediarecord[1], $pid, 0, $mediarecord[2], true, "change_new", $controller->canedit);
					else print_main_media($mediarecord[1], $pid, 0, $mediarecord[2], false, "", $controller->canedit);
				}
			}
		}
	}
	else print "<tr><td id=\"no_tab4\" colspan=\"2\" class=\"shade1\">".$gm_lang["no_tab4"]."</td></tr>\n";
	//-- New Media link
	if (!$controller->view && $controller->canedit && $controller->indi->disp && !$controller->indi->indideleted) {
	?>
		<tr>
			<td class="shade2 width20"><?php print_help_link("add_media_help", "qm"); ?><?php print $gm_lang["add_media_lbl"]; ?></td>
			<td class="shade1">
			<a href="javascript: <?php echo $gm_lang["add_media_lbl"]; ?>" onclick="add_new_record('<?php echo $controller->pid; ?>','OBJE', 'add_media'); return false;"><?php echo $gm_lang["add_media"]; ?></a>
			</td>
		</tr>
	<?php
	}
}

?>
</table>
<br />
</div>
<!-- ======================== Start 2nd tab individual page ==== Notes ======= -->
<div id="notes" class="tab_page" style="display:none;" >
<br />
<table class="facts_table">
<?php if (!$controller->indi->disp) {
   print "<tr><td class=\"shade1\">";
   print_privacy_error($CONTACT_EMAIL);
   print "</td></tr>";
}
else {
	if (count($controller->indi->notefacts) > 0) {
		foreach ($controller->indi->notefacts as $key => $value) {
			$fact = trim($value[0]);
			if ($fact=="NOTE") {
				if (!$controller->show_changes) {
					if ($controller->indi->indideleted) print_main_notes($value[1], 1, $pid, $value[2], "deleted", $controller->canedit);
					else print_main_notes($value[1], 1, $pid, $value[2], "", $controller->canedit);
				}
				else {
					if ($controller->indi->indideleted) print_main_notes($value[1], 1, $pid, $value[2], "change_old", $controller->canedit);
					else {
						if (IsChangedFact($pid, $value[1])) {
							if (!isset($value[3]) || $value[3] != "new") print_main_notes($value[1], 1, $pid, $value[2], "change_old", $controller->canedit);
								print_main_notes(RetrieveChangedFact($pid, $value[0], $value[1]), 1, $pid, $value[2], "change_new", $controller->canedit);
						}
						else if (isset($value[3]) && $value[3] == "new") print_main_notes($value[1], 1, $pid, $value[2], "change_new", $controller->canedit);
						else print_main_notes($value[1], 1, $pid, $value[2], "", $controller->canedit);
					}
				}
				$FACT_COUNT++;
			}
		}
	}
	else print "<tr><td id=\"no_tab2\" colspan=\"2\" class=\"shade1\">".$gm_lang["no_tab2"]."</td></tr>\n";
	//-- New Note Link
		if (!$controller->view && $controller->canedit && $controller->indi->disp && !$controller->indi->indideleted) { ?>
		<tr>
			<td class="shade2 width20"><?php print_help_link("add_note_help", "qm"); ?><?php echo $gm_lang["add_note_lbl"]; ?></td>
			<td class="shade1"><a href="javascript: <?php echo $gm_lang["add_note"]; ?>" onclick="add_new_record('<?php echo $controller->pid; ?>','NOTE', 'add_note'); return false;"><?php echo $gm_lang["add_note"]; ?></a>
			<br />
			</td>
		</tr>
		<tr>
			<td class="shade2 width20"><?php print_help_link("add_general_note_help", "qm"); ?><?php echo $gm_lang["add_gnote_lbl"]; ?></td>
			<td class="shade1"><a href="javascript: <?php echo $gm_lang["add_gnote"]; ?>" onclick="add_new_record('<?php echo $controller->pid; ?>','GNOTE', 'add_gnote'); return false;"><?php echo $gm_lang["add_gnote"]; ?></a>
			<br />
			</td>
		</tr>
	<?php
	}
}
?>
</table>
<br />
</div>
<!-- ======================== Start 6nd tab individual page ==== Research ======= -->
<div id="actions" class="tab_page" style="display:none;" >
<?php if (is_object($Actions)) {
?><br />
<form name="actionform" method="post" action="individual.php">
<input name="pid" type="hidden" value="<?php print $controller->pid;?>" />
<table class="facts_table">
<?php if (!$controller->indi->disp) {
   print "<tr><td class=\"shade1\">";
   print_privacy_error($CONTACT_EMAIL);
   print "</td></tr>";
}
else {
	if (count($controller->indi->actionlist) > 0) {
		foreach ($controller->indi->actionlist as $key => $action) {
			$action->PrintThis();
		}
	}
	else print "<tr><td id=\"no_tab6\" colspan=\"2\" class=\"shade1\">".$gm_lang["no_tab6"]."</td></tr>\n";
	//-- New action Link
		if (!$controller->view && $controller->canedit && $controller->indi->disp && !$controller->indi->indideleted) { 
			$Actions->PrintAddLink();
	}
}
?>
</table>
</form>
<br />
<?php } ?>
</div>

<?php
// active tab
print "<script type=\"text/javascript\">\n<!--\n";
if ($controller->view) print "tabswitch(0)";
else if (isset($_SESSION["indi"][JoinKey($controller->pid, $GEDCOMID)])) print "tabswitch(".$_SESSION["indi"][JoinKey($controller->pid, $GEDCOMID)].")";
else print "tabswitch(". ($controller->default_tab + 1) .")";
print "\n//-->\n</script>\n";
print_footer();
?>