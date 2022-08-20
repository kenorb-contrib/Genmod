<?php
/**
 * Edit Privacy Settings
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA02111-1307USA
 *
 * This Page Is Valid XHTML 1.0 Transitional! > 12 September 2005
 *
 * @author GM Development Team
 * @package Genmod
 * @subpackage Privacy
 * @version $Id: edit_privacy.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

/**
 * Inclusion of the configuration file
*/
require "config.php";

// Reload the privacy settings with no user overrides
PrivacyController::ReadPrivacy(GedcomConfig::$GEDCOMID, false);

if (empty($gedid)) $gedid = GedcomConfig::$GEDCOMID;

if ((!$gm_user->userGedcomAdmin($gedid))||(empty($gedid))) {
	header("Location: editgedcoms.php");
	exit;
}

$PRIVACY_CONSTANTS = array();
$PRIVACY_CONSTANTS[$PRIV_HIDE] = "PRIV_HIDE";
$PRIVACY_CONSTANTS[$PRIV_PUBLIC] = "PRIV_PUBLIC";
$PRIVACY_CONSTANTS[$PRIV_USER] = "PRIV_USER";
$PRIVACY_CONSTANTS[$PRIV_NONE] = "PRIV_NONE";
if (!isset($PRIVACY_BY_YEAR)) $PRIVACY_BY_YEAR = $boolarray[false];
if (!isset($MAX_ALIVE_AGE)) $MAX_ALIVE_AGE = 120;

/**
 * print write_access option
 *
 * @param string $checkVar
 */
function WriteAccessOption($checkVar) {
global $PRIV_HIDE, $PRIV_PUBLIC, $PRIV_USER, $PRIV_NONE;

print "<option value=\"PRIV_PUBLIC\"";
if ($checkVar==$PRIV_PUBLIC) print " selected=\"selected\"";
print ">".GM_LANG_PRIV_PUBLIC."</option>\n";
print "<option value=\"PRIV_USER\"";
if ($checkVar==$PRIV_USER) print " selected=\"selected\"";
print ">".GM_LANG_PRIV_USER."</option>\n";
print "<option value=\"PRIV_NONE\"";
if ($checkVar==$PRIV_NONE) print " selected=\"selected\"";
print ">".GM_LANG_PRIV_NONE."</option>\n";
print "<option value=\"PRIV_HIDE\"";
if ($checkVar==$PRIV_HIDE) print " selected=\"selected\"";
print ">".GM_LANG_PRIV_HIDE."</option>\n";
}

/**
 * print yes/no select option
 *
 * @param string $checkVar
 */
function WriteYesNo($checkVar) {

print "<option";
if ($checkVar == false) print " selected=\"selected\"";
print " value=\"no\">";
print GM_LANG_no;
print "</option>\n";

print "<option";
if ($checkVar == true) print " selected=\"selected\"";
print " value=\"yes\">";
print GM_LANG_yes;
print "</option>";
}

/**
 * print hide/show select option
 *
 * @param string $checkVar
 */
function WriteHideShow($checkVar) {

print "<option";
if ($checkVar == 0) print " selected=\"selected\"";
print " value=\"0\">";
print GM_LANG_hide;
print "</option>\n";

print "<option";
if ($checkVar == 1) print " selected=\"selected\"";
print " value=\"1\">";
print GM_LANG_show;
print "</option>";
}
/**
 * print find and print gedcom record ID
 *
 * @param string $checkVar	gedcom key
 * @param string $outputVar	error message style
 */
function SearchIdDetails($checkVar, $outputVar) {

	$object = ConstructObject($checkVar);
	
	if (!$object->isempty) {
		if ($object->type=="INDI") {
			print "\n<span class=\"ListItem\">".$object->name;
			PersonFunctions::PrintFirstMajorFact($object);
			print "</span>\n";
		}
		else if ($object->type=="SOUR") {
			print "\n<span class=\"ListItem\">".$object->name;
			print "</span>\n";
		}
		else if ($object->type=="FAM") {
			print "\n<span class=\"ListItem\">".$object->name;
			print "</span>\n";
		}
		else if ($object->type=="OBJE") {
			print "\n<span class=\"ListItem\">".$object->title;
			print "</span>\n";
		}
		else if ($object->type=="NOTE") {
			print "\n<span class=\"ListItem\">".$object->title;
			print "</span>\n";
		}
		else print $object->type." ".$object->xref;
	}
	else {
		print "<span class=\"Error\">";
		if ($outputVar == 1) {
			print GM_LANG_unable_to_find_privacy_indi;
			print "<br />[" . $checkVar . "]";
		}
		if ($outputVar == 2) {
			print GM_LANG_unable_to_find_privacy_indi;
		}
		print "</span><br /><br />";
	}
}

function PrintFactChoice() {

	$factarr = get_defined_constants(true);
	foreach ($factarr["user"] as $factkey=>$label) {
		$fcheck = substr($factkey, 0, 8);
		if ($fcheck == "GM_FACT_") {
			$f6=substr($factkey,8,6);
			$tag = substr($factkey, 8);
			if ($f6 != "_BIRT_" && $f6 != "_DEAT_" && $f6 != "_MARR_") {
				print "<option";
				print " value=\"";
				print $tag;
				print "\">";
				print $tag . " - " . str_replace("<br />", " ", $label);
				print "</option>";
			}
		}
	}
}


if (empty($action)) $action="";
PrintHeader(GM_LANG_privacy_header);
if ($action=="update") {
	if (!isset($_POST)) $_POST = $HTTP_POST_VARS;
/*	print "<pre>";
	print_r($_POST);
	print "</pre>";
*/	$boolarray = array();
	$boolarray["yes"]="1";
	$boolarray["no"]="0";
	$boolarray[false]="0";
	$boolarray[true]="1";
	$settings = PrivacyController::GetPrivacyObject(GedcomConfig::$GEDCOMID);
	$settings->GEDCOM = get_gedcom_from_id(GedcomConfig::$GEDCOMID);
	$settings->GEDCOMID = GedcomConfig::$GEDCOMID;
	$settings->PRIV_USER = $PRIV_USER;
	$settings->PRIV_NONE = $PRIV_NONE;
	$settings->PRIV_HIDE = $PRIV_HIDE;
	$settings->PRIV_PUBLIC = $PRIV_PUBLIC;
	$settings->PRIVACY_VERSION = $PRIVACY_VERSION;
	$settings->SHOW_DEAD_PEOPLE = $_POST["v_SHOW_DEAD_PEOPLE"];
	$settings->HIDE_LIVE_PEOPLE = $_POST["v_HIDE_LIVE_PEOPLE"];
	$settings->SHOW_LIVING_NAMES = $_POST["v_SHOW_LIVING_NAMES"];
	$settings->SHOW_SOURCES = $_POST["v_SHOW_SOURCES"];
	$settings->LINK_PRIVACY = $boolarray[$_POST["v_LINK_PRIVACY"]];
	$settings->MAX_ALIVE_AGE = $_POST["v_MAX_ALIVE_AGE"];
	if ($MAX_ALIVE_AGE!=$_POST["v_MAX_ALIVE_AGE"]) AdminFunctions::ResetIsDead();
	$settings->ENABLE_CLIPPINGS_CART = $_POST["v_ENABLE_CLIPPINGS_CART"];
	$settings->SHOW_ACTION_LIST = $_POST["v_SHOW_ACTION_LIST"];
	$settings->PRIVACY_BY_YEAR = $boolarray[$_POST["v_PRIVACY_BY_YEAR"]];
	$settings->PRIVACY_BY_RESN = $boolarray[$_POST["v_PRIVACY_BY_RESN"]];
	$settings->USE_RELATIONSHIP_PRIVACY = $boolarray[$_POST["v_USE_RELATIONSHIP_PRIVACY"]];
	$settings->MAX_RELATION_PATH_LENGTH = $_POST["v_MAX_RELATION_PATH_LENGTH"];
	$settings->CHECK_MARRIAGE_RELATIONS = $boolarray[$_POST["v_CHECK_MARRIAGE_RELATIONS"]];
	$settings->CHECK_CHILD_DATES = $boolarray[$_POST["v_CHECK_CHILD_DATES"]];
	
	if (!isset($_POST["v_person_privacy_del"])) $v_person_privacy_del = array();
	else $v_person_privacy_del = $_POST["v_person_privacy_del"];
	if (!is_array($v_person_privacy_del)) $v_person_privacy_del = array();
	
	if (!isset($_POST["v_person_privacy"])) $v_person_privacy = array();
	else $v_person_privacy = $_POST["v_person_privacy"];
	if (!is_array($v_person_privacy)) $v_person_privacy = array();
	
	foreach($person_privacy as $key=>$value) {
		if (!isset($v_person_privacy_del[$key])) {
			if (isset($v_person_privacy[$key])) $person_privacy[$key] = $v_person_privacy[$key];
			else $person_privacy[$key] = $PRIVACY_CONSTANTS[$value];
		}
		else unset($person_privacy[$key]);
	}
	if ((!empty($_POST["v_new_person_privacy_access_ID"]))&&(!empty($_POST["v_new_person_privacy_acess_option"]))) {
		$obj = ConstructObject($_POST["v_new_person_privacy_access_ID"]);
		if (!$obj->isempty) $person_privacy[$_POST["v_new_person_privacy_access_ID"]] = $_POST["v_new_person_privacy_acess_option"];
	}
	
	if (file_exists("setpersonprivacy.php")) include("setpersonprivacy.php");
	$settings->person_privacy = $person_privacy;

	if (!isset($_POST["v_user_privacy_del"])) $v_user_privacy_del = array();
	else $v_user_privacy_del = $_POST["v_user_privacy_del"];
	if (!is_array($v_user_privacy_del)) $v_user_privacy_del = array();
	
	if (!isset($_POST["v_user_privacy"])) $v_user_privacy = array();
	else $v_user_privacy = $_POST["v_user_privacy"];
	if (!is_array($v_user_privacy)) $v_user_privacy = array();
	
	foreach($user_privacy as $key=>$value) {
		foreach($value as $id=>$setting) {
			if (!isset($v_user_privacy_del[$key][$id])) {
				if (isset($v_user_privacy[$key][$id])) $user_privacy[$key][$id] = $v_user_privacy[$key][$id];
				else $user_privacy[$key][$id] = $setting;
			}
			else unset($user_privacy[$key][$id]);
		}
	}
	if ((!empty($_POST["v_new_user_privacy_username"]))&&(!empty($_POST["v_new_user_privacy_access_ID"]))) {
		$obj = ConstructObject($_POST["v_new_user_privacy_access_ID"]);
		if (!$obj->isempty) $user_privacy[$_POST["v_new_user_privacy_username"]][$_POST["v_new_user_privacy_access_ID"]] = $_POST["v_new_user_privacy_acess_option"];
	}
	$settings->user_privacy = $user_privacy;	
		
	if (!isset($_POST["v_global_facts_del"])) $v_global_facts_del = array();
	else $v_global_facts_del = $_POST["v_global_facts_del"];
	if (!is_array($v_global_facts_del)) $v_global_facts_del = array();

	if (!isset($_POST["v_global_facts"])) $v_global_facts = array();
	else $v_global_facts = $_POST["v_global_facts"];
	if (!is_array($v_global_facts)) $v_global_facts = array();
	
	foreach($global_facts as $tag=>$value) {
		foreach($value as $key=>$setting) {
			if (!isset($v_global_facts_del[$tag][$key])) {
				if (isset($v_global_facts[$tag][$key])) $global_facts[$tag][$key] = $v_global_facts[$tag][$key];
				else $global_facts[$tag][$key] = $PRIVACY_CONSTANTS[$setting];
			}
			else unset($global_facts[$tag][$key]);
		}
	}
	if (!empty($_POST["v_new_global_facts_abbr"]) && !empty($_POST["v_new_global_facts_choice"]) && !empty($_POST["v_new_global_facts_access_option"])) {
		$global_facts[$_POST["v_new_global_facts_abbr"]][$_POST["v_new_global_facts_choice"]] = $_POST["v_new_global_facts_access_option"];
	}
	$settings->global_facts = $global_facts;
	
	if (!isset($_POST["v_person_facts_del"])) $v_person_facts_del = array();
	else $v_person_facts_del = $_POST["v_person_facts_del"];
	if (!is_array($v_person_facts_del)) $v_person_facts_del = array();
	
	if (!isset($_POST["v_person_facts"])) $v_person_facts = array();
	else $v_person_facts = $_POST["v_person_facts"];
	if (!is_array($v_person_facts)) $v_person_facts = array();
	
	foreach($person_facts as $id=>$value) {
		foreach($value as $tag=>$value1) {
			foreach($value1 as $key=>$setting) {
				if (!isset($v_person_facts_del[$id][$tag][$key])) {
					if (isset($v_person_facts[$id][$tag][$key])) $person_facts[$id][$tag][$key] = $v_person_facts[$id][$tag][$key];
					else $person_facts[$id][$tag][$key] = $PRIVACY_CONSTANTS[$setting];
				}
				else unset($person_facts[$id][$tag][$key]);
			}
		}
	}
	if (!empty($_POST["v_new_person_facts_access_ID"]) && !empty($_POST["v_new_person_facts_abbr"]) && !empty($_POST["v_new_global_facts_choice"]) && !empty($_POST["v_new_global_facts_access_option"])) {
		$obj = ConstructObject($_POST["v_new_person_facts_access_ID"]);
		if (!$obj->isempty) $person_facts[$_POST["v_new_person_facts_access_ID"]][$_POST["v_new_person_facts_abbr"]][$_POST["v_new_person_facts_choice"]] = $_POST["v_new_person_facts_acess_option"];
	}
	$settings->person_facts = $person_facts;	
	PrivacyController::StorePrivacy($settings);
	WriteToLog("Privacy-&gt; Privacy file updated", "I", "G", GedcomConfig::$GEDCOMID);
	
}

?>
<script language="JavaScript" type="text/javascript">
<!--
	var pastefield;
	function paste_id(value) {
		pastefield.value=value;
	}
	var helpWin;
	function helpPopup(which) {
		if ((!helpWin)||(helpWin.closed)) helpWin = window.open('editconfig_help.php?help='+which,'','left=50,top=50,width=500,height=320,resizable=1,scrollbars=1');
		else helpWin.location = 'editconfig_help.php?help='+which;
		return false;
	}
//-->
</script>
<!-- Setup the left box -->
<div id="AdminColumnLeft">
	<?php AdminFunctions::AdminLink("admin.php", GM_LANG_admin); ?>
	<?php AdminFunctions::AdminLink("editgedcoms.php", GM_LANG_manage_gedcoms); ?>
</div>

<!-- Setup the middle box -->
<div id="AdminColumnMiddle">

<form name="editprivacyform" method="post" action="edit_privacy.php">
	<input type="hidden" name="action" value="update" />
	<?php print "<input type=\"hidden\" name=\"gedid\" value=\"".GedcomConfig::$GEDCOMID."\" />\n";

	// NOTE: General Privacy Settings header bar
	?>
	<table class="NavBlockTable AdminNavBlockTable">
	<tr>
		<td class="NavBlockHeader AdminNavBlockHeader"><?php
			print "<span class=\"AdminNavBlockTitle\">".GM_LANG_edit_privacy_title." - ".$GEDCOMS[$gedid]["title"]. "</span>";
			if (UserController::CheckPrivacyOverrides($gedid)) {
				print "<br /><br /><span class=\"Error\">".GM_LANG_user_overr_exists;
				if ($gm_user->UserIsAdmin()) print "<br /><a href=\"useradmin.php?action=listusers&amp;filter=privoverride&amp;gedid=$gedid\">".GM_LANG_user_overr_show."</a>";
				print "</span>";
			} ?>
		</td>
	</tr>
		<tr>
			<td class="NavBlockHeader ConfigNavBlockHeader">
			<?php
			print "<a href=\"javascript: ".GM_LANG_general_privacy."\" onclick=\"expand_layer('general-privacy-options');return false\"><img id=\"general-privacy-options_img\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["minus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a> ";
				PrintHelpLink("general_privacy_help", "qm", "general_privacy");
				?>
				<a href="javascript: <?php print GM_LANG_general_privacy; ?>" onclick="expand_layer('general-privacy-options');return false"><?php print GM_LANG_general_privacy; ?></a>
			</td>
		</tr>
	</table>
	
	<?php // NOTE: General Privacy Settings options
	?>
	<div id="general-privacy-options" style="display: block">
	<table class="NavBlockTable AdminNavBlockTable">
	<tr>
		<td class="NavBlockLabel AdminNavBlockLabel"><div class="HelpIconContainer"><?php PrintHelpLink("PRIVACY_BY_RESN_help", "qm", "PRIVACY_BY_RESN"); print "</div>".GM_LANG_PRIVACY_BY_RESN; ?>
		</td>
		<td class="NavBlockField AdminNavBlockField">
		<select size="1" name="v_PRIVACY_BY_RESN"><?php WriteYesNo($PRIVACY_BY_RESN); ?></select>
		</td>
	</tr>
	<tr>
		<td class="NavBlockLabel AdminNavBlockLabel"><div class="HelpIconContainer"><?php PrintHelpLink("SHOW_SOURCES_help", "qm", "SHOW_SOURCES"); print "</div>".GM_LANG_SHOW_SOURCES; ?>
		</td>
		<td class="NavBlockField AdminNavBlockField">
		<select size="1" name="v_SHOW_SOURCES"><?php WriteAccessOption($SHOW_SOURCES); ?></select>
		</td>
	</tr>
	<tr>
		<td class="NavBlockLabel AdminNavBlockLabel"><div class="HelpIconContainer"><?php PrintHelpLink("LINK_PRIVACY_help", "qm", "LINK_PRIVACY"); print "</div>".GM_LANG_LINK_PRIVACY; ?>
		</td>
		<td class="NavBlockField AdminNavBlockField">
		<select size="1" name="v_LINK_PRIVACY"><?php WriteYesNo($LINK_PRIVACY); ?></select>
		</td>
	</tr>
	<tr>
		<td class="NavBlockLabel AdminNavBlockLabel"><div class="HelpIconContainer"><?php PrintHelpLink("ENABLE_CLIPPINGS_CART_help", "qm", "ENABLE_CLIPPINGS_CART"); print "</div>".GM_LANG_ENABLE_CLIPPINGS_CART; ?>
		</td>
		<td class="NavBlockField AdminNavBlockField">
		<select size="1" name="v_ENABLE_CLIPPINGS_CART"><?php WriteAccessOption($ENABLE_CLIPPINGS_CART); ?></select>
		</td>
	</tr>
	<tr>
		<td class="NavBlockLabel AdminNavBlockLabel"><div class="HelpIconContainer"><?php PrintHelpLink("SHOW_RESEARCH_LOG_help", "qm", "SHOW_RESEARCH_LOG"); print "</div>".GM_LANG_SHOW_RESEARCH_LOG; ?>
		</td>
		<td class="NavBlockField AdminNavBlockField">
		<select size="1" name="v_SHOW_ACTION_LIST"><?php WriteAccessOption($SHOW_ACTION_LIST); ?></select>
		</td>
	</tr>
	</table>
</div>	
	
	<?php // NOTE: Age related Privacy Settings header bar
	?>
	<table class="NavBlockTable AdminNavBlockTable">
		<tr>
			<td class="NavBlockHeader ConfigNavBlockHeader">
		<?php
			print "<a href=\"javascript: ".GM_LANG_age_privacy."\" onclick=\"expand_layer('age-privacy-options');return false\"><img id=\"age-privacy-options_img\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a> ";
		PrintHelpLink("age_privacy_help", "qm", "age_privacy");
			?>
			<a href="javascript: <?php print GM_LANG_age_privacy; ?>" onclick="expand_layer('age-privacy-options');return false"><b><?php print GM_LANG_age_privacy; ?></b></a>
			</td>
	</tr>
	</table>
	<?php // NOTE: Age related Privacy Settings options
	?>
	<div id="age-privacy-options" style="display: none">
	<table class="NavBlockTable AdminNavBlockTable">
	<tr>
		<td class="NavBlockLabel AdminNavBlockLabel"><div class="HelpIconContainer"><?php PrintHelpLink("HIDE_LIVE_PEOPLE_help", "qm", "HIDE_LIVE_PEOPLE"); print "</div>".GM_LANG_HIDE_LIVE_PEOPLE;?></td>
		<td class="NavBlockField AdminNavBlockField">
			<select size="1" name="v_HIDE_LIVE_PEOPLE"><?php WriteAccessOption($HIDE_LIVE_PEOPLE); ?></select>
		</td>
	</tr>
	<tr>
		<td class="NavBlockLabel AdminNavBlockLabel"><div class="HelpIconContainer"><?php PrintHelpLink("SHOW_DEAD_PEOPLE_help", "qm", "SHOW_DEAD_PEOPLE"); print "</div>".GM_LANG_SHOW_DEAD_PEOPLE; ?>
		</td>
		<td class="NavBlockField AdminNavBlockField">
		<select size="1" name="v_SHOW_DEAD_PEOPLE"><?php WriteAccessOption($SHOW_DEAD_PEOPLE); ?></select>
		</td>
	</tr>
	<tr>
		<td class="NavBlockLabel AdminNavBlockLabel"><div class="HelpIconContainer"><?php PrintHelpLink("SHOW_LIVING_NAMES_help", "qm", "SHOW_LIVING_NAMES"); print "</div>".GM_LANG_SHOW_LIVING_NAMES; ?>
		</td>
		<td class="NavBlockField AdminNavBlockField">
		<select size="1" name="v_SHOW_LIVING_NAMES"><?php WriteAccessOption($SHOW_LIVING_NAMES); ?></select>
		</td>
	</tr>
	<tr>
		<td class="NavBlockLabel AdminNavBlockLabel"><div class="HelpIconContainer"><?php PrintHelpLink("PRIVACY_BY_YEAR_help", "qm", "PRIVACY_BY_YEAR"); print "</div>".GM_LANG_PRIVACY_BY_YEAR; ?>
		</td>
		<td class="NavBlockField AdminNavBlockField">
		<select size="1" name="v_PRIVACY_BY_YEAR"><?php WriteYesNo($PRIVACY_BY_YEAR); ?></select>
		</td>
	</tr>
	
	<tr>
		<td class="NavBlockLabel AdminNavBlockLabel"><div class="HelpIconContainer"><?php PrintHelpLink("USE_RELATIONSHIP_PRIVACY_help", "qm", "USE_RELATIONSHIP_PRIVACY"); print "</div>".GM_LANG_USE_RELATIONSHIP_PRIVACY; ?>
		</td>
		<td class="NavBlockField AdminNavBlockField">
		<select size="1" name="v_USE_RELATIONSHIP_PRIVACY"><?php WriteYesNo($USE_RELATIONSHIP_PRIVACY); ?></select>
		</td>
	</tr>

	<tr>
		<td class="NavBlockLabel AdminNavBlockLabel"><div class="HelpIconContainer"><?php PrintHelpLink("MAX_RELATION_PATH_LENGTH_help", "qm", "MAX_RELATION_PATH_LENGTH"); print "</div>".GM_LANG_MAX_RELATION_PATH_LENGTH; ?>
		</td>
		<td class="NavBlockField AdminNavBlockField">
		<select size="1" name="v_MAX_RELATION_PATH_LENGTH"><?php
		for ($y = 1; $y <= 10; $y++) {
			print "<option";
			if ($MAX_RELATION_PATH_LENGTH == $y) print " selected=\"selected\"";
			print ">";
			print $y;
			print "</option>";
		}
		?></select>
		</td>
	</tr>

	<tr>
		<td class="NavBlockLabel AdminNavBlockLabel"><div class="HelpIconContainer"><?php PrintHelpLink("CHECK_MARRIAGE_RELATIONS_help", "qm", "CHECK_MARRIAGE_RELATIONS"); print "</div>".GM_LANG_CHECK_MARRIAGE_RELATIONS; ?>
		</td>
		<td class="NavBlockField AdminNavBlockField">
		<select size="1" name="v_CHECK_MARRIAGE_RELATIONS"><?php WriteYesNo($CHECK_MARRIAGE_RELATIONS); ?></select>
		</td>
	</tr>
	<tr>
		<td class="NavBlockLabel AdminNavBlockLabel"><div class="HelpIconContainer"><?php PrintHelpLink("CHECK_CHILD_DATES_help", "qm", "CHECK_CHILD_DATES"); print "</div>".GM_LANG_CHECK_CHILD_DATES;?></td>
		<td class="NavBlockField AdminNavBlockField"><select name="v_CHECK_CHILD_DATES">
				<option value="yes" <?php if ($CHECK_CHILD_DATES) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
				<option value="no" <?php if (!$CHECK_CHILD_DATES) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
			</select>
		</td>
	</tr>
	
	<tr>
		<td class="NavBlockLabel AdminNavBlockLabel"><div class="HelpIconContainer"><?php PrintHelpLink("MAX_ALIVE_AGE_help", "qm", "MAX_ALIVE_AGE"); print "</div>".GM_LANG_MAX_ALIVE_AGE?>
		</td>
		<td class="NavBlockField AdminNavBlockField"><input type="text" name="v_MAX_ALIVE_AGE" value="<?php print $MAX_ALIVE_AGE?>" size="5"/>
		</td>
	</tr>
	</table>
</div>	

<?php //--------------person_privacy------------------------------------------------------------------------ 

	// NOTE: General Person Settings header bar
	?>
	 <table class="NavBlockTable AdminNavBlockTable">
	 	<tr>
	 		<td class="NavBlockHeader ConfigNavBlockHeader">
	 		<?php
		print "<a href=\"javascript: ".GM_LANG_person_privacy."\" onclick=\"expand_layer('person-privacy-options');return false\"><img id=\"person-privacy-options_img\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a> ";?><?php PrintHelpLink("person_privacy_help", "qm", "person_privacy");?>
			<a href="javascript: <?php print GM_LANG_person_privacy; ?>" onclick="expand_layer('person-privacy-options');return false"><b><?php print GM_LANG_person_privacy; ?></b></a>
			</td>
		</tr>
	</table>
	
	<?php // NOTE: General Privacy Settings options
	?>
	<div id="person-privacy-options" style="display: none">
		<table class="NavBlockTable AdminNavBlockTable">
			<tr>
				<td class="NavBlockHeader ConfigNavBlockSubbar" colspan="2"><?php print GM_LANG_add_new_pp_setting; ?>
				</td>
			</tr>
			
			<tr>
			<td class="NavBlockColumnHeader"><?php print GM_LANG_id; ?></td>
			<td class="NavBlockColumnHeader"><?php print GM_LANG_accessible_by; ?></td>
			</tr>
			
			<tr>
			<td class="NavBlockField AdminNavBlockField">
				<input type="text" class="PidInputField" name="v_new_person_privacy_access_ID" id="v_new_person_privacy_access_ID" size="4" />
				<?php
			 LinkFunctions::PrintFindIndiLink("v_new_person_privacy_access_ID", $gedid);
			 LinkFunctions::PrintFindFamilyLink("v_new_person_privacy_access_ID");
			 LinkFunctions::PrintFindSourceLink("v_new_person_privacy_access_ID");
			 LinkFunctions::PrintFindMediaLink("v_new_person_privacy_access_ID");			 
			 LinkFunctions::PrintFindNoteLink("v_new_person_privacy_access_ID");			 
			 ?>
			</td>
			<td class="NavBlockField AdminNavBlockField">
				<select size="1" name="v_new_person_privacy_acess_option"><?php WriteAccessOption(""); ?></select>
			</td>
			</tr>
		</table>
	
		<?php
		if (count($person_privacy) > 0) {
		?>
		<table class="NavBlockTable AdminNavBlockTable">
			<tr>
			<td class="NavBlockHeader ConfigNavBlockSubbar" colspan="4"><?php print GM_LANG_edit_exist_person_privacy_settings; ?>
			</td>
			</tr>
			
			<tr>
			<td class="NavBlockColumnHeader NavBlockCheckRadioHeader"><?php print GM_LANG_delete; ?></td>
			<td class="NavBlockColumnHeader"><?php print GM_LANG_id; ?></td>
			<td class="NavBlockColumnHeader"><?php print GM_LANG_full_name; ?></td>
			<td class="NavBlockColumnHeader"><?php print GM_LANG_accessible_by; ?></td>
			</tr>
			<?php
			foreach($person_privacy as $key=>$value) {
			?>
			<tr>
			<td class="NavBlockField AdminNavBlockField NavBlockCheckRadio">
			<input type="checkbox" name="v_person_privacy_del[<?php print $key; ?>]" value="1" />
			</td>
			<td class="NavBlockField AdminNavBlockField"><?php print $key; ?></td>
			<td class="NavBlockField AdminNavBlockField"><?php SearchIdDetails($key, 1); ?></td>
			<td class="NavBlockField AdminNavBlockField">
				<select size="1" name="v_person_privacy[<?php print $key; ?>]"><?php WriteAccessOption($value); ?></select>
			</td>
			</tr>
			<?php
			}?>
		</table>
		<?php
		}?>
	</div>
	
<?php //--------------user_privacy-------------------------------------------------------------------------- 

	// User Privacy Settings header bar
	?>
	<table class="NavBlockTable AdminNavBlockTable">
		<tr>
			<td class="NavBlockHeader ConfigNavBlockHeader">
	 		<?php
			print "<a href=\"javascript: ".GM_LANG_user_privacy."\" onclick=\"expand_layer('user-privacy-options');return false\"><img id=\"user-privacy-options_img\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a> ";?><?php PrintHelpLink("user_privacy_help", "qm", "user_privacy");?>
		<a href="javascript: <?php print GM_LANG_user_privacy; ?>" onclick="expand_layer('user-privacy-options');return false"><b><?php print GM_LANG_user_privacy; ?></b></a>
			</td>
		</tr>
	</table>
	
	<?php // User Privacy Settings options
	?>
	<div id="user-privacy-options" style="display: none">
		<table class="NavBlockTable AdminNavBlockTable">
			<tr>
			<td class="NavBlockHeader ConfigNavBlockSubbar" colspan="3"><?php print GM_LANG_add_new_up_setting; ?>
			</td>
			</tr>
			
			<tr>
			<td class="NavBlockColumnHeader"><?php print GM_LANG_user_name; ?></td>
			<td class="NavBlockColumnHeader"><?php print GM_LANG_id; ?></td>
			<td class="NavBlockColumnHeader"><?php print GM_LANG_show_question; ?></td>
			</tr>
			
			<tr class="<?php print $TEXT_DIRECTION; ?>">
			<td class="NavBlockField AdminNavBlockField">
				<select size="1" name="v_new_user_privacy_username">
				<?php
				$users = UserController::GetUsers("lastname", "asc", "firstname");
				foreach($users as $username => $user)
				{
				print "<option";
				print " value=\"";
				print $username;
				print "\">";
				print $user->firstname." ".$user->lastname;
				print "</option>";
				}
				print "<option value=\"all\">".GM_LANG_all_users."</option>";
				?>
				</select>
			</td>
			<td class="NavBlockField AdminNavBlockField">
				<input type="text" class="PidInputField" name="v_new_user_privacy_access_ID" id="v_new_user_privacy_access_ID" size="4" />
				<?php
			 LinkFunctions::PrintFindIndiLink("v_new_user_privacy_access_ID","");
			 LinkFunctions::PrintFindFamilyLink("v_new_user_privacy_access_ID");
			 LinkFunctions::PrintFindSourceLink("v_new_user_privacy_access_ID");
			 LinkFunctions::PrintFindMediaLink("v_new_user_privacy_access_ID");			 
			 LinkFunctions::PrintFindNoteLink("v_new_user_privacy_access_ID");			 
				?>
			</td>
			<td class="NavBlockField AdminNavBlockField">
				<select size="1" name="v_new_user_privacy_acess_option"><?php WriteHideShow(""); ?></select>
			</td>
			</tr>
		</table>
	 <?php
		if (count($user_privacy) > 0) {
		?>
		<table class="NavBlockTable AdminNavBlockTable">
			<tr>
			<td class="NavBlockHeader ConfigNavBlockSubbar" colspan="5"><?php print GM_LANG_edit_exist_user_privacy_settings; ?>
			</td>
			</tr>
			<tr>
				<td class="NavBlockColumnHeader NavBlockCheckRadioHeader"><?php print GM_LANG_delete; ?></td>
				<td class="NavBlockColumnHeader"><?php print GM_LANG_user_name; ?></td>
				<td class="NavBlockColumnHeader"><?php print GM_LANG_id; ?></td>
				<td class="NavBlockColumnHeader"><?php print GM_LANG_show_question; ?></td>
				<td class="NavBlockColumnHeader"><?php print GM_LANG_full_name; ?></td>
			</tr>
		 <?php
			foreach($user_privacy as $key=>$value) {
				foreach($value as $id=>$setting) {
			?>
			<tr class="<?php print $TEXT_DIRECTION; ?>">
			<td class="NavBlockField AdminNavBlockField NavBlockCheckRadio">
			<input type="checkbox" name="v_user_privacy_del[<?php print $key; ?>][<?php print $id; ?>]" value="1" />
			</td>
			<td class="NavBlockField AdminNavBlockField"><?php if ($key == "all") print GM_LANG_all_users; else print $key; ?></td>
		<td class="NavBlockField AdminNavBlockField"><?php print $id; ?></td>
			<td class="NavBlockField AdminNavBlockField">
				<select size="1" name="v_user_privacy[<?php print $key; ?>][<?php print $id; ?>]"><?php WriteHideShow($setting); ?></select>
			</td>
			<td class="NavBlockField AdminNavBlockField"><?php SearchIdDetails($id, 2); ?>
			</td>
			</tr>
			
			<?php
				}
			}?>
		</table>
		<?php
		}?>
</div> 
<?php //-------------global_facts------------------------------------------------------------------------ 
	
	// NOTE: Global Settings header bar
	?>
	<table class="NavBlockTable AdminNavBlockTable"><tr><td class="NavBlockHeader ConfigNavBlockHeader">
	 <?php
	print "<a href=\"javascript: ".GM_LANG_global_facts."\" onclick=\"expand_layer('global-facts-options');return false\"><img id=\"global-facts-options_img\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a> ";?>
		 <?php PrintHelpLink("global_facts_help", "qm", "global_facts");?>
	 <a href="javascript: <?php print GM_LANG_global_facts; ?>" onclick="expand_layer('global-facts-options');return false"><b><?php print GM_LANG_global_facts; ?></b></a></td>
	</tr>
	</table>
	
	<?php // NOTE: General User Privacy Settings options
	?>
	<div id="global-facts-options" style="display: none">
		<table class="NavBlockTable AdminNavBlockTable">
			<tr>
			<td class="NavBlockHeader ConfigNavBlockSubbar" colspan="2"><?php print GM_LANG_add_new_gf_setting; ?></td>
			</tr>
			<tr>
			<td class="NavBlockColumnHeader"><?php print GM_LANG_name_of_fact; ?></td>
			<td class="NavBlockColumnHeader"><?php print GM_LANG_accessible_by; ?></td>
			</tr>
			<tr class="<?php print $TEXT_DIRECTION; ?>">
			<td class="NavBlockField AdminNavBlockField" rowspan="2">
				<select size="1" name="v_new_global_facts_abbr">
				<?php
				print "<option value=\"\">".GM_LANG_choice."</option>";
					PrintFactChoice()
				?>
				</select>
			</td>
			<td class="NavBlockField AdminNavBlockField">
				<select size="1" name="v_new_global_facts_choice">
				<option value="details"><?php print GM_LANG_fact_details; ?></option>
				<option value="show"><?php print GM_LANG_fact_show; ?></option>
				</select>
			</td>
			</tr>
			<tr>
			<td class="NavBlockField AdminNavBlockField">
				<select size="1" name="v_new_global_facts_access_option"><?php WriteAccessOption(""); ?></select>
			</td>
			</tr>
		</table>
		<?php
		if (count($global_facts) > 0) {
		?>
		<table class="NavBlockTable AdminNavBlockTable">
			<tr>
			<td class="NavBlockHeader ConfigNavBlockSubbar" colspan="4"><?php print GM_LANG_edit_exist_global_facts_settings; ?></td>
			</tr>
			<tr>
			<td class="NavBlockColumnHeader NavBlockCheckRadioHeader"><?php print GM_LANG_delete; ?></td>
			<td class="NavBlockColumnHeader"><?php print GM_LANG_name_of_fact; ?></td>
			<td class="NavBlockColumnHeader"><?php print GM_LANG_choice; ?></td>
			<td class="NavBlockColumnHeader"><?php print GM_LANG_accessible_by; ?></td>
			</tr>
			<?php
			foreach($global_facts as $tag=>$value) {
				foreach($value as $key=>$setting) {
			?>
			<tr class="<?php print $TEXT_DIRECTION; ?>">
			<td class="NavBlockField AdminNavBlockField NavBlockCheckRadio">
			<input type="checkbox" name="v_global_facts_del[<?php print $tag; ?>][<?php print $key; ?>]" value="1" /></td>
			<td class="NavBlockField AdminNavBlockField">
			<?php
				if (defined("GM_FACT_".$tag)) print $tag. " - ".constant("GM_FACT_".$tag);
				else print $tag;
				?>
			</td>
			<td class="NavBlockField AdminNavBlockField"><?php
			if ($key == "show") print GM_LANG_fact_show;
			if ($key == "details") print GM_LANG_fact_details;
			?></td>
			<td class="NavBlockField AdminNavBlockField">
				<select size="1" name="v_global_facts[<?php print $tag; ?>][<?php print $key; ?>]"><?php WriteAccessOption($setting); ?></select>
			</td>
			</tr>
			<?php
				}
		}
			?>
		</table>
		<?php
		}
		else print "&nbsp;";
		?>
	</div>
<?php //-------------person_facts------------------------------------------------------------------------ 
		// NOTE: Person Facts header bar
	?>
	<table class="NavBlockTable AdminNavBlockTable"><tr><td class="NavBlockHeader ConfigNavBlockHeader">
	 <?php
	print "<a href=\"javascript: ".GM_LANG_person_facts."\" onclick=\"expand_layer('person-facts-options');return false\"><img id=\"person-facts-options_img\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a> ";?>
		 <?php PrintHelpLink("person_facts_help", "qm", "person_facts");?>
	 <a href="javascript: <?php print GM_LANG_person_facts; ?>" onclick="expand_layer('person-facts-options');return false"><b><?php print GM_LANG_person_facts; ?></b></a></td>
	</tr>
	</table>
	
	<?php // NOTE: Person Facts options
	?>
	<div id="person-facts-options" style="display: none">
		<table class="NavBlockTable AdminNavBlockTable">
			<?php //--Start--add person_facts for individuals----------------------------------------------- 
			?>
			<tr>
			<td class="NavBlockHeader ConfigNavBlockSubbar" colspan="3"><?php print GM_LANG_add_new_pf_setting_indi; ?></td>
			</tr>
			<tr>
			<td class="NavBlockColumnHeader"><?php print GM_LANG_id; ?></td>
			<td class="NavBlockColumnHeader"><?php print GM_LANG_name_of_fact; ?></td>
			<td class="NavBlockColumnHeader"><?php print GM_LANG_accessible_by; ?></td>
			</tr>
			<tr>
			<td class="NavBlockField AdminNavBlockField" rowspan="2">
				<input type="text" class="PidInputField" name="v_new_person_facts_access_ID" id="v_new_person_facts_access_ID" size="4" />
				<?php
			 LinkFunctions::PrintFindIndiLink("v_new_person_facts_access_ID","");
			 LinkFunctions::PrintFindFamilyLink("v_new_person_facts_access_ID");
			 LinkFunctions::PrintFindSourceLink("v_new_person_facts_access_ID");
			 LinkFunctions::PrintFindMediaLink("v_new_person_facts_access_ID");			 
			 LinkFunctions::PrintFindNoteLink("v_new_person_facts_access_ID");			 
				?>
			</td>
			<td class="NavBlockField AdminNavBlockField" rowspan="2">
				<select size="1" name="v_new_person_facts_abbr">
				<?php
				PrintFactChoice();
				?>
				</select>
			</td>
			<td class="NavBlockField AdminNavBlockField">
				<select size="1" name="v_new_person_facts_choice">
				<option value="details"><?php print GM_LANG_fact_details; ?></option>
				<option value="show"><?php print GM_LANG_fact_show; ?></option>
				</select>
			</td>
			</tr>
			<tr>
			<td class="NavBlockField AdminNavBlockField">
				<select size="1" name="v_new_person_facts_acess_option"><?php WriteAccessOption(""); ?></select>
			</td>
			</tr>
			<?php //--End----add person_facts for individuals-----------------------------------------------
		 	?>
		</table>
	<?php
	if (count($person_facts) > 0) {
	?>
	<table class="NavBlockTable AdminNavBlockTable">
		<tr>
		<td class="NavBlockHeader ConfigNavBlockSubbar" colspan="6"><?php print GM_LANG_edit_exist_person_facts_settings; ?></td>
		</tr>
		<tr>
		<td class="NavBlockColumnHeader NavBlockCheckRadioHeader"><?php print GM_LANG_delete; ?></td>
		<td class="NavBlockColumnHeader"><?php print GM_LANG_id; ?></td>
		<td class="NavBlockColumnHeader"><?php print GM_LANG_full_name; ?></td>
		<td class="NavBlockColumnHeader"><?php print GM_LANG_name_of_fact; ?></td>
		<td class="NavBlockColumnHeader"><?php print GM_LANG_accessible_by; ?></td>
		</tr> 
		<?php
		foreach($person_facts as $id=>$value) {
			foreach($value as $tag=>$value1) {
				foreach($value1 as $key=>$setting) {
		?>
		<tr>
		<td class="NavBlockField AdminNavBlockField NavBlockCheckRadio" rowspan="2">
		<input type="checkbox" name="v_person_facts_del[<?php print $id; ?>][<?php print $tag; ?>][<?php print $key; ?>]" value="1" /></td>
		<td class="NavBlockField AdminNavBlockField" rowspan="2"><?php print $id; ?></td>
		<td class="NavBlockField AdminNavBlockField" rowspan="2"><?php
			SearchIdDetails($id, 2);
		?></td>
		<td class="NavBlockField AdminNavBlockField" rowspan="2">
		<?php
			print $tag. " - ".constant("GM_FACT_".$tag);
		?></td>
		<td class="NavBlockField AdminNavBlockField"><?php
		if ($key == "show") print GM_LANG_fact_show;
		if ($key == "details") print GM_LANG_fact_details;
		?></td>
		</tr>
		<tr>
		<td class="NavBlockField AdminNavBlockField">
			<select size="1" name="v_person_facts[<?php print $id; ?>][<?php print $tag; ?>][<?php print $key; ?>]"><?php WriteAccessOption($setting); ?></select>
		</td>
		</tr>
		<?php
				}
			}
		}
		?>
	</table>
	<?php
	}?>
	</div>
	<table class="NavBlockTable AdminNavBlockTable" border="0">
	<tr><td class="NavBlockFooter">
	<input type="submit" value="<?php print GM_LANG_save_config?>" onclick="closeHelp();" />
	&nbsp;&nbsp;
	<input type="reset" value="<?php print GM_LANG_reset?>" /><br />
	</td></tr>
	</table>
	</form>
</div>
<?php
PrintFooter();

?>