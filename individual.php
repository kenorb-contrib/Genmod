<?php
/**
 * Individual Page
 *
 * Display all of the information about an individual
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
 * Page does not validate see line number 1109 -> 15 August 2005
 *
 * @package Genmod
 * @subpackage Display
 * @version $Id: individual.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

/**
 * Inclusion of the configuration file
*/
require("config.php");

/**
 * Inclusion of the individual controller
*/
$controller = new IndividualController();

PrintHeader($controller->pagetitle);

$controller->CheckNoResult(GM_LANG_person_not_found);

$controller->CheckPrivate();

$controller->CheckRawEdited();

?>
<div class="DetailHeaderSection">
	<!-- NOTE: Display person picture -->
	<?php if ($controller->canshowhighlightedobj) {
		print '<div id="IndiPictureContainer" class="'.$TEXT_DIRECTION.'">';
			print $controller->HighlightedObject;
		print "</div>";
	}
	?>
	<!-- NOTE: Print person name and ID -->
	<span class="PageTitleName"><?php print $controller->indi->name; ?>
	<span><?php print $controller->indi->addxref; ?></span>
	<span><?php print PrintReady($controller->indi_userlink); ?></span>
	</span><br />
	
	<!-- NOTE: Print person additional name(s) and ID -->
	<?php if (strlen($controller->indi->addname) > 0) print "<span class=\"PageTitleName\">".$controller->indi->addname."</span><br />"; ?>
	
	<!-- NOTE: Display details of person if privacy allows -->
	<?php if ($controller->indi->disp) { 
		$names = $controller->indi->changednames;
		$num = 0;
		foreach($names as $key=>$name) {
			// Name not changed
			if ($name["old"] == $name["new"]) {
				print "<div class=\"IndiHeaderBlock";
				if ($controller->show_changes && $controller->indi->isdeleted) print " ChangeOld";
				print "\">";
				$controller->PrintNameRecord($name["old"], $num);
				print "</div>";
				$num++;
			}
			// Name changed
			elseif ($controller->show_changes && $controller->caneditown) {
				print "<div>";
				print "<table class=\"IndiHeaderBlock\">";
				if (!empty($name["old"])) {
					print "<tr><td class=\"ChangeOld\">";
					$controller->PrintNameRecord($name["old"], $num, false);
					print "</td></tr>";
				}
				if (!empty($name["new"])) {
					$controller->name_count--;
					print "<tr><td class=\"ChangeNew\">";
					$controller->PrintNameRecord($name["new"], $num);
					print "</td></tr>";
				}
				print "</table></div>";
				$num++;
			}
			else {
				if (!empty($name["old"]))print "<div class=\"IndiHeaderBlock\">".$controller->PrintNameRecord($name["old"], $num)."</div>";
				$num++;
			}
		}
		foreach ($controller->indi->facts as $key => $factobj) {
			if ($factobj->fact == "SEX") {
				print "<div class=\"IndiHeaderBlock $TEXT_DIRECTION ";
				if($controller->show_changes) print $factobj->style;
				print "\">";
				$controller->GenderRecord($factobj->factrec, $factobj->fact);
				print "<span class=\"IndiHeaderLabel $TEXT_DIRECTION\">".PrintReady(GM_LANG_sex.":")."</span>&nbsp;<span class=\"IndiHeaderField\">".$controller->indi->sexdetails["gender"];
				print " <img src=\"".$controller->indi->sexdetails["image"]."\" title=\"".$controller->indi->sexdetails["gender"]."\" alt=\"".$controller->indi->sexdetails["gender"];
				print "\" width=\"0\" height=\"0\" class=\"GenderImage\" border=\"0\" />";
				if ($controller->indi->canedit && !$controller->indi->isdeleted && $factobj->style != "ChangeOld") {
					if ($controller->indi->sexdetails["add"]) print "<br /><a class=\"SmallEditLinks\" href=\"#\" onclick=\"add_new_record('".$controller->xref."', 'SEX', 'add_gender', 'INDI'); return false;\">".GM_LANG_edit."</a>";
					else {
						print "<br /><a class=\"SmallEditLinks\" href=\"#\" onclick=\"edit_record('".$controller->xref."', 'SEX', 1, 'edit_gender', 'INDI'); return false;\">".GM_LANG_edit."</a> | ";
						print "<a class=\"SmallEditLinks\" href=\"#\" onclick=\"delete_record('".$controller->xref."', 'SEX', 1, 'edit_gender', 'INDI'); return false;\">".GM_LANG_delete."</a>\n";
					}
				}
				print "</span>";
				print "</div>";
			}
		}
		
		//-- - put the birth and death info in this section
		print "<div class=\"IndiHeaderBlock $TEXT_DIRECTION\">";
		$bfacts = $controller->indi->SelectFacts(array("BIRT"));
		foreach ($bfacts as $key => $factobj) {
			print "<span class=\"IndiHeaderLabel ".$factobj->style."\">".$factobj->descr.":</span>&nbsp;<span class=\"IndiHeaderField ".$factobj->style."\">";
			$factobj->PrintFactDate();
			$factobj->PrintFactPlace();
			print "</span><br />";
		}
		$dfacts = $controller->indi->SelectFacts(array("DEAT"));
		foreach ($dfacts as $key => $factobj) {
			print "<span class=\"IndiHeaderLabel ".$factobj->style."\">".$factobj->descr.":</span>&nbsp;<span class=\"IndiHeaderField ".$factobj->style."\">";
			$factobj->PrintFactDate();
			$factobj->PrintFactPlace();
			print "</span><br />";
		}
		if (GedcomConfig::$SHOW_LDS_AT_GLANCE) print "<br /><b>".GetLdsGlance($controller->indi->gedrec)."</b>";
		?>
		</div>
		<?php 
	}
	print "\n<br class=\"ClearBoth\" />";
	// Print indi counter only if displaying a non-private person
	if(GedcomConfig::$SHOW_COUNTER&& !$controller->IsPrintPreview()) print "<div class=\"PageCounter\">".GM_LANG_hit_count."&nbsp;".$hits."</div>\n";
	
print "</div>";

// Print the accesskeys
if ($controller->view != "preview") {
?>
	<div class="HeaderAccessKeys">
		<a class="HeaderAccessKeys" href="<?php print "pedigree.php?rootid=$pid";?>" title="<?php print GM_LANG_pedigree_chart ?>" tabindex="-1" accesskey="<?php print GM_LANG_accesskey_individual_pedigree; ?>"><?php print GM_LANG_pedigree_chart ?></a>
		<a class="HeaderAccessKeys" href="<?php print "descendancy.php?rootid=$pid";?>" title="<?php print GM_LANG_descend_chart ?>" tabindex="-1" accesskey="<?php print GM_LANG_accesskey_individual_descendancy; ?>"><?php print GM_LANG_descend_chart ?></a>
		<a class="HeaderAccessKeys" href="<?php print "timeline.php?pids0=$pid";?>" title="<?php print GM_LANG_timeline_chart ?>" tabindex="-1" accesskey="<?php print GM_LANG_accesskey_individual_timeline; ?>"><?php print GM_LANG_timeline_chart ?></a>
		<?php
		if (!empty($controller->user)&&!empty($controller->user->gedcomid[GedcomConfig::$GEDCOMID])) {
			?>
		<a class="HeaderAccessKeys" href="<?php print "relationship.php?pid1=".$controller->user->gedcomid[GedcomConfig::$GEDCOMID]."&amp;pid2=".$controller->xref;?>" title="<?php print GM_LANG_relationship_to_me ?>" tabindex="-1" accesskey="<?php print GM_LANG_accesskey_individual_relation_to_me; ?>"><?php print GM_LANG_relationship_to_me ?></a>
		<?php 	}
		if ($controller->canshowgedrec) {?>
		<a class="HeaderAccessKeys" href="javascript:show_gedcom_record();" title="<?php print GM_LANG_view_gedcom ?>" tabindex="-1" accesskey="<?php print GM_LANG_accesskey_individual_gedcom; ?>"><?php print GM_LANG_view_gedcom ?></a>
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
<?php $controller->PrintDetailJS(); ?>

<?php
// Print the tab doors
$controller->PrintTabs();

PrintFooter();
?>