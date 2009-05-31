<?php
/**
 * Individual Page
 *
 * Display all of the information about an individual
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
 * Page does not validate see line number 1109 -> 15 August 2005
 *
 * @package Genmod
 * @subpackage Display
 * @version $Id: individual.php,v 1.36 2006/04/30 18:44:14 roland-d Exp $
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

// ?><pre><?php
// print_r($controller);
// ?></pre><?php
?>
<div id="indi_content">
	<!-- NOTE: Display person picture -->
	<?php if ($controller->canShowHighlightedObject && !empty($controller->HighlightedObject)) {
		print "<div id=\"indi_picture\">";
			print $controller->HighlightedObject;
		print "</div>";
	}
	?>
	<!-- Sublinks table -->
	<div id="indi_menu">
		<table class="sublinks_table" cellspacing="4" cellpadding="0">
			<tr>
				<td class="list_label <?php echo $TEXT_DIRECTION; ?>" colspan="4"><?php echo $gm_lang["indis_charts"]; ?></td>
			</tr>
			<tr>
				<td class="sublinks_cell <?php echo $TEXT_DIRECTION; ?>">
				<?php 
				//-- get charts menu from menubar
				$menubar = new MenuBar(); $menu = $menubar->getChartsMenu($controller->pid); $menu->printMenu();
				if (file_exists("reports/individual.xml")) { ?>
					</td><td class="sublinks_cell <?php echo $TEXT_DIRECTION; ?>">
					<?php 
					//-- get reports menu from menubar
					$menubar = new MenuBar(); $menu = $menubar->getReportsMenu($controller->pid); $menu->printMenu();
				}
				if ($controller->user["canedit"]) { ?>
					</td>
					<td class="sublinks_cell <?php echo $TEXT_DIRECTION;?>">
					<?php $menu = $controller->getEditMenu(); $menu->printMenu();
				}
				if ($controller->show_menu_other) { ?>
					</td>
					<td class="sublinks_cell <?php echo $TEXT_DIRECTION; ?>">
					<?php $menu = $controller->getOtherMenu(); $menu->printMenu();
				} ?>
				</td>
			</tr>
		</table><br />
	</div>
	
	<!-- NOTE: Print if acceptance was successful -->
	<?php 
	if ($controller->accept_success) {
		if ($controller->accept_change) print "<b>".$gm_lang["accept_successful"]."</b><br />";
		else if ($controller->reject_change) print "<b>".$gm_lang["reject_successful"]."</b><br />";
	}
	?>
	
	<!-- NOTE: Print person name and ID -->
	<span class="name_head"><?php print $controller->indi->name; ?>
	<span>(<?php print $controller->indi->xref; ?>)</span>
	</span><br />
	
	<!-- NOTE: Print person additional name(s) and ID -->
	<?php if (strlen($controller->indi->addname) > 0) print "<span class=\"name_head\">".$controller->indi->addname."</span><br />"; ?>
	
	<!-- NOTE: Display details of person if privacy allows -->
	<?php if ($controller->indi->disp) { ?>
		<?php
		foreach ($controller->indi->globalfacts as $key => $value) {
			$fact = trim($value[0]);
			if ($fact=="SEX") {
				print "<div class=\"indi_spacer\">";
				print $controller->gender_record($value[1], $value[0]);
				print "<span class=\"label\">".$gm_lang["sex"].":    </span><span class=\"field\">".$controller->indi->sexdetails["gender"];
				print " <img src=\"".$controller->indi->sexdetails["image"]."\" title=\"".$controller->indi->sexdetails["gender"]."\" alt=\"".$controller->indi->sexdetails["gender"];
				print "\" width=\"0\" height=\"0\" class=\"sex_image\" border=\"0\" />";
				if ($controller->indi->sexdetails["add"]) print "<br /><a class=\"font9\" href=\"#\" onclick=\"add_new_record('".$controller->pid."', 'SEX'); return false;\">".$gm_lang["edit"]."</a>";
				else {
					print "<br /><a class=\"font9\" href=\"#\" onclick=\"edit_record('".$controller->pid."', 'SEX', 1, 'edit_gender'); return false;\">".$gm_lang["edit"]."</a> | ";
					print "<a class=\"font9\" href=\"#\" onclick=\"delete_record('".$controller->pid."', 'SEX', 1, 'edit_gender'); return false;\">".$gm_lang["delete"]."</a>\n";
				}
				print "</span>";
				print "</div>";
			}
			else if ($fact=="NAME") {
				print "<div class=\"indi_spacer\">";
				$controller->print_name_record($value[1], $value[2]);
				print "</div>";
			}
		}
		//-- - put the birth info in this section
		if ((!empty($controller->indi->brec)) || (!empty($controller->indi->drec)) || $SHOW_LDS_AT_GLANCE) {
		?>
		<div  class="indi_spacer">
		<?php if (!empty($controller->indi->brec)) { ?>
			
			<span class="label"><?php print $factarray["BIRT"].":"; ?></span>
			<span class="field">
				<?php print_fact_date($controller->indi->brec); ?>
				<?php print_fact_place($controller->indi->brec); ?>
			</span><br />
		<?php } ?>
		<?php
			// RFE [ 1229233 ] "DEAT" vs "DEAT Y"
			// The check $deathrec != "1 DEAT" will not show any records that only have 1 DEAT in them
			if ((!empty($controller->indi->drec)) && (trim($controller->indi->drec) != "1 DEAT")) {
		?>
			<span class="label"><?php print $factarray["DEAT"].":"; ?></span>
			<span class="field">
			<?php
				print_fact_date($controller->indi->drec);
				print_fact_place($controller->indi->drec);
			?>
			</span>
		<?php }
			if ($SHOW_LDS_AT_GLANCE) print "<br /><b>".get_lds_glance($controller->indi->gedrec)."</b>";
		?>
		</div>
		<?php 
		}
		if($SHOW_COUNTER) {
			// Print indi counter only if displaying a non-private person
			require("hitcount.php");
			print "\n<br /><br /><span style=\"margin-left: 3px;\">".$gm_lang["hit_count"]."&nbsp;".$hits."</div>\n";
		}
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
			if (!empty($controller->user["gedcomid"][$GEDCOM])) {
		?>
		<a class="accesskeys" href="<?php print "relationship.php?pid1=".$controller->user["gedcomid"][$GEDCOM]."&amp;pid2=".$controller->pid;?>" title="<?php print $gm_lang["relationship_to_me"] ?>" tabindex="-1" accesskey="<?php print $gm_lang["accesskey_individual_relation_to_me"]; ?>"><?php print $gm_lang["relationship_to_me"] ?></a>
		<?php 	}
		if ($controller->canShowGedcomRecord) {?>
		<a class="accesskeys" href="javascript:show_gedcom_record();" title="<?php print $gm_lang["view_gedcom"] ?>" tabindex="-1" accesskey="<?php print $gm_lang["accesskey_individual_gedcom"]; ?>"><?php print $gm_lang["view_gedcom"] ?></a>
		<?php } ?>
	</div>
	
<?php } ?>


<script language="JavaScript" type="text/javascript">
<!--
// javascript function to open a window with the raw gedcom in it
function show_gedcom_record(shownew) {
	fromfile="";
	if (shownew=="yes") fromfile='&fromfile=1';
	var recwin = window.open("gedrecord.php?pid=<?php print $controller->pid; ?>"+fromfile, "", "top=50,left=50,width=300,height=400,scrollbars=1,scrollable=1,resizable=1");
}

function showchanges() {
	window.location = '<?php print $SCRIPT_NAME."?pid=".$controller->pid."&show_changes=yes"; ?>';
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
	var tabid = new Array('0', 'facts','notes','sources','media','relatives');
	// show all tabs ?
	var disp='none';
	if (n==0) disp='block';
	// reset all tabs areas
	for (i=1; i<tabid.length; i++) document.getElementById(tabid[i]).style.display=disp;
	// current tab area
	if (n>0) document.getElementById(tabid[n]).style.display='block';
	// empty tabs
	for (i=0; i<tabid.length; i++) {
		var elt = document.getElementById('door'+i);
		if (document.getElementById('no_tab'+i)) { // empty ?
			if (<?php if (userCanEdit($gm_username)) echo 'true'; else echo 'false';?>) {
				elt.style.display='block';
				elt.style.opacity='0.4';
				elt.style.filter='alpha(opacity=40)';
			}
			else elt.style.display='none'; // empty and not editable ==> hide
		}
		else elt.style.display='block';
	}
	// current door
	for (i=0; i<tabid.length; i++) {
		document.getElementById('door'+i).className='shade1 rela';
		//document.getElementById('door'+i).className='tab_cell_inactive';
	}
	document.getElementById('door'+n).className='shade1';
	//document.getElementById('door'+n).className='tab_cell_active';
	return false;
}
//-->
</script>


<?php
if (!$controller->view) {
	?>
	<div class="door">
	<dl>
	<dd id="door1"><a href="javascript:;" onclick="tabswitch(1)" ><?php print $gm_lang["personal_facts"]?></a></dd>
	<dd id="door2"><a href="javascript:;" onclick="tabswitch(2)" ><?php print $gm_lang["notes"]?></a></dd>
	<dd id="door3"><a href="javascript:;" onclick="tabswitch(3)" ><?php print $gm_lang["ssourcess"]?></a></dd>
	<dd id="door4"><a href="javascript:;" onclick="tabswitch(4)" ><?php print $gm_lang["media"]?></a></dd>
	<dd id="door5"><a href="javascript:;" onclick="tabswitch(5)" ><?php print $gm_lang["relatives"]?></a></dd>
	<dd id="door0"><a href="javascript:;" onclick="tabswitch(0)" ><?php print $gm_lang["all"]?></a></dd>
	</dl>
	</div>
	<?php
}
?>
<br /><br />
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
	print "<tr id=\"row_top\"><td></td><td class=\"shade2 rela\">";
	print "<a href=\"javascript://\" onclick=\"togglerow('row_rela'); return false;\">";
	print "<img style=\"display:none;\" id=\"rela_plus\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"".$gm_lang["show_details"]."\" title=\"".$gm_lang["show_details"]."\" />";
	print "<img id=\"rela_minus\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["minus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"".$gm_lang["hide_details"]."\" title=\"".$gm_lang["hide_details"]."\" />";
	print " ".$gm_lang["relatives_events"];
	print "</a></td></tr>\n";
	$yetdied=false;
	$n_chil=1;
	$n_gchi=1;
	foreach ($controller->indi->indifacts as $key => $value) {
		// if (stristr($value[1], "1 DEAT")) $yetdied=true;
		// if (preg_match("/1 _GMFS @(.*)@/", $value[1], $match)>0) {
			// // do not show family events after death
			// if (!$yetdied) {
				// print_fact($value[1],trim($match[1]),$value[0], $controller->indi->getGedcomRecord());
			// }
		// }
		if ($controller->show_changes == "yes" && change_present($controller->pid, true, false, $value[0])) {
		// if ($show_changes == "yes" && isset($value["new"])) {
			print_fact($value[1], $controller->pid, $value[0], $value[2], $controller->indi->gedrec, "change_old");
			// print_fact($value["new"][1], $controller->pid, $value["new"][0], $value["new"][2], $controller->indi->getGedcomRecord(), "change_new");
			print_fact(retrieve_changed_fact($controller->pid, $value[0]), $controller->pid, $value[0], $value[2], $controller->indi->gedrec, "change_new");
		}
		else print_fact($value[1], $controller->pid, $value[0], $value[2], $controller->indi->gedrec);
		$FACT_COUNT++;
	}
}
if (!record_locked($pid)) {
	//-- new fact link
	if ((!$controller->view) &&($controller->user["canedit"])&&($controller->indi->disp)) {
		print_add_new_fact($pid, $controller->indi->indifacts, "INDI");
	}
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
			if ($fact=="NOTE") print_main_notes($value[1], 1, $pid, $value[0]);
			$FACT_COUNT++;
		}
		//-- New Note Link
		if (!$controller->view && $controller->user["canedit"] && $controller->indi->disp && !record_locked($pid)) {
		?>
			<tr>
				<td class="shade2"><?php print_help_link("add_note_help", "qm"); ?><?php echo $gm_lang["add_note_lbl"]; ?></td>
				<td class="shade1"><a href="javascript: <?php echo $gm_lang["add_note"]; ?>" onclick="add_new_record('<?php echo $controller->pid; ?>','NOTE', 'add_note'); return false;"><?php echo $gm_lang["add_note"]; ?></a>
				<br />
				</td>
			</tr>
		<?php
		}
	}
	else print "<tr><td id=\"no_tab2\" colspan=\"2\" class=\"shade1\">".$gm_lang["no_tab2"]."</td></tr>\n";
}
?>
</table>
<br />
</div>
<!-- =========================== Start 3rd tab individual page === Sources -->
<div id="sources" class="tab_page" style="display:none;" >
<?php
if ($SHOW_SOURCES>=getUserAccessLevel($gm_username)) { ?>
	<br />
	<table class="facts_table">
	<?php if (!$controller->indi->disp) {
		print "<tr><td class=\"shade1\">";
		print_privacy_error($CONTACT_EMAIL);
		print "</td></tr>";
	}
	else {
		if (count($controller->indi->sourfacts) > 0) {
			foreach ($controller->indi->sourfacts as $key => $value) {
				$fact = trim($value[0]);
				if ($fact=="SOUR") print_main_sources($value[1], 1, $pid, $value[0]);
				$FACT_COUNT++;
			}
			//-- New Source Link
			if (!$controller->view && $controller->user["canedit"] && $controller->indi->disp && !record_locked($pid)) { ?>
				<tr>
				<td class="shade2"><?php print_help_link("add_source_help", "qm"); ?><?php echo $gm_lang["add_source_lbl"]; ?></td>
				<td class="shade1">
				<a href="javascript: <?php echo $gm_lang["add_source"]; ?>" onclick="add_new_record('<?php echo $controller->pid; ?>','SOUR', 'add_source'); return false;"><?php echo $gm_lang["add_source"]; ?></a>
				<br />
				</td>
				</tr>
			<?php
			}
		}
		else print "<tr><td id=\"no_tab3\" colspan=\"2\" class=\"shade1\">".$gm_lang["no_tab3"]."</td></tr>\n";
	}
	?>
</table>
<br />
</div>
<?php
}
?>
<!-- ==================== Start 4th tab individual page ==== Media -->
<div id="media" class="tab_page" style="display:none;" >
<?php if (!$MULTI_MEDIA) print "<span id=\"no_tab4\">".$gm_lang["no_tab4"]."</span>\n"; ?>
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
			if (isset($mediarecord["new"]) && $controller->show_changes == "yes") {
				print_main_media($mediarecord["old"][1], $controller->pid, 1, false, "change_old");
				print_main_media($mediarecord["new"][1], $controller->pid, 1, true, "change_new");
			}
			else print_main_media($mediarecord[1], $controller->pid, 1);
		}
		//-- New Media link
		if (!$controller->view && $controller->user["canedit"] && $controller->indi->disp && !record_locked($pid)) {
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
	else print "<tr><td id=\"no_tab4\" colspan=\"2\" class=\"shade1\">".$gm_lang["no_tab4"]."</td></tr>\n";
}
?>
</table>
<br />
</div>
<!-- ============================= Start 5th tab individual page ==== Close relatives -->
<div id="relatives" class="tab_page" style="display:none;" >
<?php
// NOTE: parent families
?>
<table>
	<?php
	if (isset($controller->indi->parents)) {
		foreach ($controller->indi->parents as $famid => $family) {
			?>
			<tr>
				<td><img src="<?php print $GM_IMAGE_DIR."/".$GM_IMAGES["cfamily"]["small"]; ?>" border="0" class="icon" alt="" /></td>
				<td><span class="subheaders"><?php print $gm_lang["as_child"];?></span>
				<?php 
			if (!$controller->view) { ?>
				 - <a href="family.php?famid=<?php print $famid; ?>">[<?php print $gm_lang["view_family"]; ?><?php if ($SHOW_FAM_ID_NUMBERS) print " &lrm;(".$famid.")&lrm;"; ?>]</a>
			<?php } ?>
				</td>
			</tr>
			</table>
			<table class="facts_table">
			<tr>
				<td class="width20 shade2 center"><?php print $family["HUSB"]->label; ?></td>
				<td class="<?php print $controller->getPersonStyle($family["HUSB"]); ?>">
				<?php
				if (isset($family["HUSB"])) print_pedigree_person($family["HUSB"]->xref,2,!$controller->view);
				?>
				</td>
			</tr>
			<tr>
				<td class="width20 shade2 center"><?php print $family["WIFE"]->label; ?></td>
				<td class="<?php print $controller->getPersonStyle($family["WIFE"]); ?>">
				<?php
				if (isset($family["WIFE"])) print_pedigree_person($family["WIFE"]->xref,2,!$controller->view);
				?>
				</td>
			</tr>
	<?php
		}
	}
	if (isset($controller->indi->siblings)) {
		foreach ($controller->indi->siblings as $sibid => $sibling) {
			?>
			<tr>
				<td class="width20 shade2 center"><?php print $sibling->label; ?></td>
				<td class="<?php print $controller->getPersonStyle($sibling); ?>">
				<?php
				print_pedigree_person($sibid,2,!$controller->view);
				?>
				</td>
			</tr>
			<?php
		}
	}
	?>
	</table>

<?php
// NOTE: Half-siblings father
	if (isset($controller->indi->father_family)) {
		foreach ($controller->indi->father_family as $famid => $family) {
		?>
		<tr>
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
			<td class="width20 shade2 center"><?php print $controller->indi->father_family[$famid]["WIFE"]->label; ?></td>
			<td class="<?php print $controller->getPersonStyle($controller->indi->father_family[$famid]["WIFE"]); ?>">
			<?php
			if (isset($controller->indi->father_family[$famid]["WIFE"])) print_pedigree_person($controller->indi->father_family[$famid]["WIFE"]->xref,2,!$controller->view);
			?>
			</td>
		</tr>
	<?php
			foreach ($family["kids"] as $sibid => $sibling) {
				?>
				<tr>
					<td class="width20 shade2 center"><?php print $sibling->label; ?></td>
					<td class="<?php print $controller->getPersonStyle($sibling); ?>">
					<?php
					print_pedigree_person($sibid,2,!$controller->view);
					?>
					</td>
				</tr>
				<?php
			}
		}
	}
	?>
	</table>
<?php

// NOTE: Half-siblings mother
	if (isset($controller->indi->mother_family)) {
		foreach ($controller->indi->mother_family as $famid => $family) {
		?>
		<tr>
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
				<td class="width20 shade2 center"><?php print $family["HUSB"]->label; ?></td>
				<td class="<?php print $controller->getPersonStyle($family["HUSB"]); ?>">
				<?php
				if (isset($family["HUSB"])) print_pedigree_person($family["HUSB"]->xref,2,!$controller->view);
				?>
				</td>
			</tr>
		<?php
		}
		foreach ($family["kids"] as $sibid => $sibling) {
				?>
				<tr>
					<td class="width20 shade2 center"><?php print $sibling->label; ?></td>
					<td class="<?php print $controller->getPersonStyle($sibling); ?>">
					<?php
					print_pedigree_person($sibid,2,!$controller->view);
					?>
					</td>
				</tr>
				<?php
			}
		}
	}
	?>
	</table>
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
		<tr>
			<td class="width20 shade2 center"><?php print $family["parents"]["HUSB"]->label; ?></td>
			<td class="<?php print $controller->getPersonStyle($family["parents"]["HUSB"]); ?>">
			<?php
			if (isset($family["parents"]["HUSB"])) print_pedigree_person($family["parents"]["HUSB"]->xref,2,!$controller->view);
			?>
			</td>
		</tr>
		<tr>
			<td class="width20 shade2 center"><?php print $family["parents"]["WIFE"]->label; ?></td>
			<td class="<?php print $controller->getPersonStyle($family["parents"]["WIFE"]); ?>">
			<?php
			if (isset($family["parents"]["WIFE"])) print_pedigree_person($family["parents"]["WIFE"]->xref,2,!$controller->view);
			?>
			</td>
		</tr>
		<?php
		if (isset($family["kids"])) {
			foreach ($family["kids"] as $kidid => $kid) {
				?>
				<tr>
					<td class="width20 shade2 center"><?php print $family["kids"][$kidid]->label; ?></td>
					<td class="<?php print $controller->getPersonStyle($kid); ?>">
					<?php
					print_pedigree_person($family["kids"][$kidid]->xref,2,!$controller->view);
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
if (!$controller->close_relatives) print "<tr><td id=\"no_tab5\" colspan=\"2\" class=\"shade1\">".$gm_lang["no_tab5"]."</td></tr>\n";
?>
<br />
<?php
if (!$controller->view && $controller->user["canedit"] && $controller->indi->disp && !record_locked($pid)) { ?>
	<table class="facts_table">
	<?php
	if ((isset($controller->indi->parents)) && (count($controller->indi->parents)==0)) {
		if (userCanEdit($gm_username)&&($controller->indi->disp)) { ?>
			<tr>
				<td class = "shade1">
				<?php print_help_link("edit_add_parent_help", "qm", "add_father"); ?><a href="#" onclick="return addnewparent('<?php print $controller->pid; ?>', 'HUSB', 'add_father');"><?php print $gm_lang["add_father"]; ?></a>
				</td>
			</tr>
			<tr>
				<td class = "shade1">
				<?php print_help_link("edit_add_parent_help", "qm", "add_mother"); ?><a href="#" onclick="return addnewparent('<?php print $controller->pid; ?>', 'WIFE', 'add_mother');"><?php print $gm_lang["add_mother"]; ?></a>
				</td>
			</tr>
			<?php
		}
	}
	if (count($controller->indi->spouses)>1) { ?>
		<tr>
			<td class="shade1">
			<?php print_help_link("reorder_families_help", "qm"); ?>
			<a href="#" onclick="return reorder_families('<?php print $controller->pid; ?>', 'reorder_families');"><?php print $gm_lang["reorder_families"]; ?></a>
			</td>
		</tr>
	<?php } ?>
		<tr>
			<td class="shade1">
			<?php print_help_link("link_child_help", "qm"); ?>
			<a href="javascript: <?php print $gm_lang["link_as_child"]; ?>" onclick="return add_famc('<?php print $controller->pid; ?>', 'link_as_child');"><?php print $gm_lang["link_as_child"]; ?></a>
			</td>
		</tr>
		<?php if ($controller->indi->getSex()!="F") { ?>
		<tr>
			<td class="shade1">
			<?php print_help_link("add_wife_help", "qm"); ?>
			<a href="javascript: <?php print $gm_lang["add_new_wife"]; ?>" onclick="return addspouse('<?php print $controller->pid; ?>','WIFE', 'add_new_wife');"><?php print $gm_lang["add_new_wife"]; ?></a>
			</td>
		</tr>
		<tr>
			<td class="shade1">
			<?php print_help_link("link_new_wife_help", "qm"); ?>
			<a href="javascript: <?php print $gm_lang["link_new_wife"]; ?>" onclick="return linkspouse('<?php print $controller->pid; ?>','WIFE', 'link_new_wife');"><?php print $gm_lang["link_new_wife"]; ?></a>
			</td>
		</tr>
		<tr>
			<td class="shade1">
			<?php print_help_link("link_new_husband_help", "qm"); ?>
			<a href="javascript: <?php print $gm_lang["link_as_husband"]; ?>" onclick="return add_fams('<?php print $controller->pid; ?>','HUSB', 'link_as_husband');"><?php print $gm_lang["link_as_husband"]; ?></a>
			</td>
		</tr>
	   <?php }
		if ($controller->indi->getSex()!="M") { ?>
		<tr>
			<td class="shade1">
			<?php print_help_link("add_husband_help", "qm"); ?>
			<a href="javascript: <?php print $gm_lang["add_new_husb"]; ?>" onclick="return addspouse('<?php print $controller->pid; ?>','HUSB', 'add_new_husb');"><?php print $gm_lang["add_new_husb"]; ?></a>
			</td>
		</tr>
		<tr>
			<td class="shade1">
			<?php print_help_link("link_new_husband_help", "qm"); ?>
			<a href="javascript: <?php print $gm_lang["link_new_husb"]; ?>" onclick="return linkspouse('<?php print $controller->pid; ?>','HUSB', 'link_new_husb');"><?php print $gm_lang["link_new_husb"]; ?></a>
			</td>
		</tr>
		<tr>
			<td class="shade1">
			<?php print_help_link("link_wife_help", "qm"); ?>
			<a href="javascript: <?php print $gm_lang["link_as_wife"]; ?>" onclick="return add_fams('<?php print $controller->pid; ?>','WIFE', 'link_as_wife');"><?php print $gm_lang["link_as_wife"]; ?></a>
			</td>
		</tr>
		<?php } ?>
	</table>
<?php } ?>
<br />
</div>

<?php
// active tab
print "<script language=\"JavaScript\" type=\"text/javascript\">\n<!--\n";
if ($controller->view) print "tabswitch(0)";
else print "tabswitch(". ($controller->default_tab + 1) .")";
print "\n//-->\n</script>\n";
print_footer();
?>
