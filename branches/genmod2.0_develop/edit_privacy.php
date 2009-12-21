<?php
/**
 * Edit Privacy Settings
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
 * This Page Is Valid XHTML 1.0 Transitional! > 12 September 2005
 *
 * @author GM Development Team
 * @package Genmod
 * @subpackage Privacy
 * @version $Id$
 */

/**
 * Inclusion of the configuration file
*/
require "config.php";

// Reload the privacy settings with no user overrides
PrivacyController::ReadPrivacy($GEDCOMID, false);

if (empty($gedid)) $gedid = $GEDCOMID;

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
function write_access_option($checkVar) {
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
function write_yes_no($checkVar) {

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
function write_hide_show($checkVar) {

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
function search_ID_details($checkVar, $outputVar) {
	global $GEDCOMS;

	$indirec = FindGedcomRecord($checkVar);
    
	if (!empty($indirec)) {
		$ct = preg_match("/0 @(.*)@ (.*)/", $indirec, $match);
		if ($ct>0) {
			$pid = $match[1];
			$type = trim($match[2]);
		}
		if ($type=="INDI") {
			$name = GetPersonName($pid);
			print "\n<span class=\"list_item\">$name";
			print_first_major_fact($pid);
			print "</span>\n";
		}
		else if ($type=="SOUR") {
			$name = GetSourceDescriptor($pid);
			print "\n<span class=\"list_item\">$name";
			print "</span>\n";
		}
		else if ($type=="FAM") {
			$name = GetFamilyDescriptor($pid);
			print "\n<span class=\"list_item\">$name";
			print "</span>\n";
		}
		else if ($type=="OBJE") {
			$name = GetMediaDescriptor($pid);
			print "\n<span class=\"list_item\">$name";
			print "</span>\n";
		}
		else print "$type $pid";
	}
	else {
		print "<span class=\"error\">";
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
?>
<table class="facts_table <?php print $TEXT_DIRECTION ?>">
	<tr>
		<td colspan="2" class="admin_topbottombar"><?php
			print "<h3>".GM_LANG_edit_privacy_title." - ".$GEDCOMS[$gedid]["title"]. "</h3>";
			if (UserController::CheckPrivacyOverrides($gedid)) {
				print "<span class=\"error\">".GM_LANG_user_overr_exists;
				if ($gm_user->UserIsAdmin()) print "<a href=\"useradmin.php?action=listusers&amp;filter=privoverride&amp;gedid=$gedid\"> ".GM_LANG_user_overr_show."</a>";
				print "</span><br />";
			}
			print "<br /><a href=\"editgedcoms.php\"><b>";
			print GM_LANG_lang_back_manage_gedcoms;
			print "</b></a><br />";?>
		</td>
	</tr>
</table>
<?php
if ($action=="update") {
	if (!isset($_POST)) $_POST = $HTTP_POST_VARS;
	$boolarray = array();
	$boolarray["yes"]="1";
	$boolarray["no"]="0";
	$boolarray[false]="0";
	$boolarray[true]="1";
	print "<table class=\"facts_table $TEXT_DIRECTION\">";
	print "<tr><td class=\"shade2\">";
	print GM_LANG_performing_update;
	print "<br />";
	$settings = PrivacyController::GetPrivacyObject($GEDCOMID);
	$settings->GEDCOM = get_gedcom_from_id($GEDCOMID);
	$settings->GEDCOMID = $GEDCOMID;
	$settings->PRIV_USER = $PRIV_USER;
	$settings->PRIV_NONE = $PRIV_NONE;
	$settings->PRIV_HIDE = $PRIV_HIDE;
	$settings->PRIV_PUBLIC = $PRIV_PUBLIC;
	$settings->PRIVACY_VERSION = $PRIVACY_VERSION;
	print GM_LANG_config_file_read;
	print "</td></tr></table>\n";
	$settings->SHOW_DEAD_PEOPLE = $_POST["v_SHOW_DEAD_PEOPLE"];
	$settings->HIDE_LIVE_PEOPLE = $_POST["v_HIDE_LIVE_PEOPLE"];
	$settings->SHOW_LIVING_NAMES = $_POST["v_SHOW_LIVING_NAMES"];
	$settings->SHOW_SOURCES = $_POST["v_SHOW_SOURCES"];
	$settings->LINK_PRIVACY = $boolarray[$_POST["v_LINK_PRIVACY"]];
	$settings->MAX_ALIVE_AGE = $_POST["v_MAX_ALIVE_AGE"];
	if ($MAX_ALIVE_AGE!=$_POST["v_MAX_ALIVE_AGE"]) ResetIsDead();
	$settings->ENABLE_CLIPPINGS_CART = $_POST["v_ENABLE_CLIPPINGS_CART"];
	$settings->SHOW_ACTION_LIST = $_POST["v_SHOW_ACTION_LIST"];
	$settings->PRIVACY_BY_YEAR = $boolarray[$_POST["v_PRIVACY_BY_YEAR"]];
	$settings->PRIVACY_BY_RESN = $boolarray[$_POST["v_PRIVACY_BY_RESN"]];
	$settings->USE_RELATIONSHIP_PRIVACY = $boolarray[$_POST["v_USE_RELATIONSHIP_PRIVACY"]];
	$settings->MAX_RELATION_PATH_LENGTH = $_POST["v_MAX_RELATION_PATH_LENGTH"];
	$settings->CHECK_MARRIAGE_RELATIONS = $boolarray[$_POST["v_CHECK_MARRIAGE_RELATIONS"]];
	$settings->CHECK_CHILD_DATES = $boolarray[$_POST["v_CHECK_CHILD_DATES"]];
	
	if (!isset($v_person_privacy_del)) $v_person_privacy_del = array();
	if (!is_array($v_person_privacy_del)) $v_person_privacy_del = array();
	if (!isset($v_person_privacy)) $v_person_privacy = array();
	if (!is_array($v_person_privacy)) $v_person_privacy = array();
	foreach($person_privacy as $key=>$value) {
		if (!isset($v_person_privacy_del[$key])) {
			if (isset($v_person_privacy[$key])) $person_privacy[$key] = $v_person_privacy[$key];
			else $person_privacy[$key] = $PRIVACY_CONSTANTS[$value];
		}
		else unset($person_privacy[$key]);
	}
	if ((!empty($v_new_person_privacy_access_ID))&&(!empty($v_new_person_privacy_acess_option))) {
		$ged = FindGedcomRecord($v_new_person_privacy_access_ID);
		$v_new_person_privacy_access_ID = GetRecID($ged);
		if (!empty($v_new_person_privacy_access_ID)) $person_privacy[$v_new_person_privacy_access_ID] = $v_new_person_privacy_acess_option;
	}
	
	if (file_exists("setpersonprivacy.php")) include("setpersonprivacy.php");
	$settings->person_privacy = $person_privacy;

	if (!isset($v_user_privacy_del)) $v_user_privacy_del = array();
	if (!is_array($v_user_privacy_del)) $v_user_privacy_del = array();
	if (!isset($v_user_privacy)) $v_user_privacy = array();
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
	if ((!empty($v_new_user_privacy_username))&&(!empty($v_new_user_privacy_access_ID))) {
		$ged = FindGedcomRecord($v_new_user_privacy_access_ID);
		$v_new_user_privacy_access_ID = GetRecID($ged);
		if (!empty($v_new_user_privacy_access_ID)) $user_privacy[$v_new_user_privacy_username][$v_new_user_privacy_access_ID] = $v_new_user_privacy_acess_option;
	}
	$settings->user_privacy = $user_privacy;	
		
	if (!isset($v_global_facts_del)) $v_global_facts_del = array();
	if (!is_array($v_global_facts_del)) $v_global_facts_del = array();
	if (!isset($v_global_facts)) $v_global_facts = array();
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
	if (!empty($v_new_global_facts_abbr) && !empty($v_new_global_facts_choice) && !empty($v_new_global_facts_access_option)) {
		$global_facts[$v_new_global_facts_abbr][$v_new_global_facts_choice] = $v_new_global_facts_access_option;
	}
	$settings->global_facts = $global_facts;
	
	if (!isset($v_person_facts_del)) $v_person_facts_del = array();
	if (!is_array($v_person_facts_del)) $v_person_facts_del = array();
	if (!isset($v_person_facts)) $v_person_facts = array();
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
	if (!empty($v_new_person_facts_access_ID) && !empty($v_new_person_facts_abbr) && !empty($v_new_global_facts_choice) && !empty($v_new_global_facts_access_option)) {
		$ged = FindGedcomRecord($v_new_person_facts_access_ID);
		$v_new_person_facts_access_ID = GetRecID($ged);
		if (!empty($v_new_person_facts_access_ID)) $person_facts[$v_new_person_facts_access_ID][$v_new_person_facts_abbr][$v_new_person_facts_choice] = $v_new_person_facts_acess_option;
	}
	$settings->person_facts = $person_facts;	
	PrivacyController::StorePrivacy($settings);
	WriteToLog("Privacy-> Privacy file updated", "I", "G", $GEDCOMID);
	
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

<form name="editprivacyform" method="post" action="edit_privacy.php">
    <input type="hidden" name="action" value="update" />
    <?php print "<input type=\"hidden\" name=\"gedid\" value=\"".$GEDCOMID."\" />\n";

    // NOTE: General Privacy Settings header bar
    ?>
	<table class="facts_table">
    	<tr>
    		<td class="topbottombar <?php print $TEXT_DIRECTION;?>">
		<?php
    		print "<a href=\"javascript: ".GM_LANG_general_privacy."\" onclick=\"expand_layer('general-privacy-options');return false\"><img id=\"general-privacy-options_img\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["minus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a> ";
		print_help_link("general_privacy_help", "qm", "general_privacy");
    		?>
        	<a href="javascript: <?php print GM_LANG_general_privacy; ?>" onclick="expand_layer('general-privacy-options');return false"><b><?php print GM_LANG_general_privacy; ?></b></a>
        	</td>
	</tr>
	</table>
    
    <?php // NOTE: General Privacy Settings options
    ?>
    <div id="general-privacy-options" style="display: block">
    <table class="facts_table">
      <tr>
        <td class="shade2 wrap width40"><?php print_help_link("PRIVACY_BY_RESN_help", "qm", "PRIVACY_BY_RESN"); print GM_LANG_PRIVACY_BY_RESN; ?>
        </td>
        <td class="shade1 width60">
          <select size="1" name="v_PRIVACY_BY_RESN"><?php write_yes_no($PRIVACY_BY_RESN); ?></select>
        </td>
      </tr>
      <tr>
        <td class="shade2 wrap"><?php print_help_link("SHOW_SOURCES_help", "qm", "SHOW_SOURCES"); print GM_LANG_SHOW_SOURCES; ?>
        </td>
        <td class="shade1">
          <select size="1" name="v_SHOW_SOURCES"><?php write_access_option($SHOW_SOURCES); ?></select>
        </td>
      </tr>
      <tr>
        <td class="shade2 wrap"><?php print_help_link("LINK_PRIVACY_help", "qm", "LINK_PRIVACY"); print GM_LANG_LINK_PRIVACY; ?>
        </td>
        <td class="shade1">
          <select size="1" name="v_LINK_PRIVACY"><?php write_yes_no($LINK_PRIVACY); ?></select>
        </td>
      </tr>
      <tr>
        <td class="shade2 wrap"><?php print_help_link("ENABLE_CLIPPINGS_CART_help", "qm", "ENABLE_CLIPPINGS_CART"); print GM_LANG_ENABLE_CLIPPINGS_CART; ?>
        </td>
        <td class="shade1">
          <select size="1" name="v_ENABLE_CLIPPINGS_CART"><?php write_access_option($ENABLE_CLIPPINGS_CART); ?></select>
        </td>
      </tr>
      <tr>
        <td class="shade2 wrap"><?php print_help_link("SHOW_RESEARCH_LOG_help", "qm", "SHOW_RESEARCH_LOG"); print GM_LANG_SHOW_RESEARCH_LOG; ?>
        </td>
        <td class="shade1">
          <select size="1" name="v_SHOW_ACTION_LIST"><?php write_access_option($SHOW_ACTION_LIST); ?></select>
        </td>
      </tr>
    </table>
  </div>    

    <?php // NOTE: Age related Privacy Settings header bar
    ?>
	<table class="facts_table">
    	<tr>
    		<td class="topbottombar <?php print $TEXT_DIRECTION;?>">
		<?php
    		print "<a href=\"javascript: ".GM_LANG_age_privacy."\" onclick=\"expand_layer('age-privacy-options');return false\"><img id=\"age-privacy-options_img\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a> ";
		print_help_link("age_privacy_help", "qm", "age_privacy");
    		?>
        	<a href="javascript: <?php print GM_LANG_age_privacy; ?>" onclick="expand_layer('age-privacy-options');return false"><b><?php print GM_LANG_age_privacy; ?></b></a>
        	</td>
	</tr>
	</table>
    <?php // NOTE: Age related Privacy Settings options
    ?>
    <div id="age-privacy-options" style="display: none">
    <table class="facts_table">
	<tr>
		<td class="shade2 wrap width40 <?php print $TEXT_DIRECTION;?>"><?php print_help_link("HIDE_LIVE_PEOPLE_help", "qm", "HIDE_LIVE_PEOPLE"); print GM_LANG_HIDE_LIVE_PEOPLE;?></td>
		<td class="shade1 width60">
			<select size="1" name="v_HIDE_LIVE_PEOPLE"><?php write_access_option($HIDE_LIVE_PEOPLE); ?></select>
		</td>
	</tr>
      <tr>
        <td class="shade2 wrap width40 <?php print $TEXT_DIRECTION;?>"><?php print_help_link("SHOW_DEAD_PEOPLE_help", "qm", "SHOW_DEAD_PEOPLE"); print GM_LANG_SHOW_DEAD_PEOPLE; ?>
        </td>
        <td class="shade1 width60">
          <select size="1" name="v_SHOW_DEAD_PEOPLE"><?php write_access_option($SHOW_DEAD_PEOPLE); ?></select>
        </td>
      </tr>
      <tr>
        <td class="shade2 wrap"><?php print_help_link("SHOW_LIVING_NAMES_help", "qm", "SHOW_LIVING_NAMES"); print GM_LANG_SHOW_LIVING_NAMES; ?>
        </td>
        <td class="shade1">
          <select size="1" name="v_SHOW_LIVING_NAMES"><?php write_access_option($SHOW_LIVING_NAMES); ?></select>
        </td>
      </tr>
      <tr>
        <td class="shade2 wrap"><?php print_help_link("PRIVACY_BY_YEAR_help", "qm", "PRIVACY_BY_YEAR"); print GM_LANG_PRIVACY_BY_YEAR; ?>
        </td>
        <td class="shade1">
          <select size="1" name="v_PRIVACY_BY_YEAR"><?php write_yes_no($PRIVACY_BY_YEAR); ?></select>
        </td>
      </tr>
      
      <tr>
        <td class="shade2 wrap"><?php print_help_link("USE_RELATIONSHIP_PRIVACY_help", "qm", "USE_RELATIONSHIP_PRIVACY"); print GM_LANG_USE_RELATIONSHIP_PRIVACY; ?>
        </td>
        <td class="shade1">
          <select size="1" name="v_USE_RELATIONSHIP_PRIVACY"><?php write_yes_no($USE_RELATIONSHIP_PRIVACY); ?></select>
        </td>
      </tr>

      <tr>
        <td class="shade2 wrap"><?php print_help_link("MAX_RELATION_PATH_LENGTH_help", "qm", "MAX_RELATION_PATH_LENGTH"); print GM_LANG_MAX_RELATION_PATH_LENGTH; ?>
        </td>
        <td class="shade1">
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
        <td class="shade2 wrap"><?php print_help_link("CHECK_MARRIAGE_RELATIONS_help", "qm", "CHECK_MARRIAGE_RELATIONS"); print GM_LANG_CHECK_MARRIAGE_RELATIONS; ?>
        </td>
        <td class="shade1">
          <select size="1" name="v_CHECK_MARRIAGE_RELATIONS"><?php write_yes_no($CHECK_MARRIAGE_RELATIONS); ?></select>
        </td>
      </tr>
	<tr>
		<td class="shade2 wrap"><?php print_help_link("CHECK_CHILD_DATES_help", "qm", "CHECK_CHILD_DATES"); print GM_LANG_CHECK_CHILD_DATES;?></td>
		<td class="shade1"><select name="v_CHECK_CHILD_DATES">
				<option value="yes" <?php if ($CHECK_CHILD_DATES) print "selected=\"selected\""; ?>><?php print GM_LANG_yes;?></option>
				<option value="no" <?php if (!$CHECK_CHILD_DATES) print "selected=\"selected\""; ?>><?php print GM_LANG_no;?></option>
			</select>
		</td>
	</tr>
      
	  <tr>
		<td class="shade2 wrap"><?php print_help_link("MAX_ALIVE_AGE_help", "qm", "MAX_ALIVE_AGE"); print GM_LANG_MAX_ALIVE_AGE?>
		</td>
		<td class="shade1"><input type="text" name="v_MAX_ALIVE_AGE" value="<?php print $MAX_ALIVE_AGE?>" size="5"/>
		</td>
	  </tr>
    </table>
  </div>    
  
  <?php //--------------person_privacy------------------------------------------------------------------------ 
  
	// NOTE: General Person Settings header bar
  	?>
     <table class="facts_table">
     	<tr>
     		<td class="topbottombar <?php print $TEXT_DIRECTION;?>">
     		<?php
		print "<a href=\"javascript: ".GM_LANG_person_privacy."\" onclick=\"expand_layer('person-privacy-options');return false\"><img id=\"person-privacy-options_img\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a> ";?><?php print_help_link("person_privacy_help", "qm", "person_privacy");?>
        	<a href="javascript: <?php print GM_LANG_person_privacy; ?>" onclick="expand_layer('person-privacy-options');return false"><b><?php print GM_LANG_person_privacy; ?></b></a>
        	</td>
		</tr>
	</table>
    
    <?php // NOTE: General Privacy Settings options
    ?>
    <div id="person-privacy-options" style="display: none">
    	<table class="facts_table">
        	<tr>
            	<td class="topbottombar" colspan="2"><b><?php print GM_LANG_add_new_pp_setting; ?></b>
            	</td>
            </tr>
            
            <tr>
              <td class="shade2 width40"><?php print GM_LANG_id; ?></td>
              <td class="shade2 width60"><?php print GM_LANG_accessible_by; ?></td>
            </tr>
            
            <tr>
              <td class="shade1">
                <input type="text" class="pedigree_form" name="v_new_person_privacy_access_ID" id="v_new_person_privacy_access_ID" size="4" />
                <?php
			 LinkFunctions::PrintFindIndiLink("v_new_person_privacy_access_ID", $gedid);
			 LinkFunctions::PrintFindFamilyLink("v_new_person_privacy_access_ID");
			 LinkFunctions::PrintFindSourceLink("v_new_person_privacy_access_ID");
			 LinkFunctions::PrintFindObjectLink("v_new_person_privacy_access_ID");             
			 LinkFunctions::PrintFindNoteLink("v_new_person_privacy_access_ID");             
			 ?>
              </td>
              <td class="shade1">
                <select size="1" name="v_new_person_privacy_acess_option"><?php write_access_option(""); ?></select>
              </td>
            </tr>
		</table>
      
          <?php
          if (count($person_privacy) > 0) {
          ?>
          <table class="facts_table">
            <tr>
              <td class="topbottombar" colspan="4"><?php print GM_LANG_edit_exist_person_privacy_settings; ?>
              </td>
            </tr>
            
            <tr>
              <td class="shade2 width5"><?php print GM_LANG_delete; ?></td>
              <td class="shade2 width5"><?php print GM_LANG_id; ?></td>
              <td class="shade2 width30"><?php print GM_LANG_full_name; ?></td>
              <td class="shade2 width60"><?php print GM_LANG_accessible_by; ?></td>
            </tr>
            <?php
            foreach($person_privacy as $key=>$value) {
            ?>
            <tr>
              <td class="shade1">
              <input type="checkbox" name="v_person_privacy_del[<?php print $key; ?>]" value="1" />
              </td>
              <td class="shade1"><?php print $key; ?></td>
              <td class="shade1 wrap"><?php search_ID_details($key, 1); ?></td>
              <td class="shade1">
                <select size="1" name="v_person_privacy[<?php print $key; ?>]"><?php write_access_option($value); ?></select>
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
	<table class="facts_table">
		<tr>
			<td class="topbottombar <?php print $TEXT_DIRECTION;?>">
     		<?php
    		print "<a href=\"javascript: ".GM_LANG_user_privacy."\" onclick=\"expand_layer('user-privacy-options');return false\"><img id=\"user-privacy-options_img\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a> ";?><?php print_help_link("user_privacy_help", "qm", "user_privacy");?>
		<a href="javascript: <?php print GM_LANG_user_privacy; ?>" onclick="expand_layer('user-privacy-options');return false"><b><?php print GM_LANG_user_privacy; ?></b></a>
        	</td>
		</tr>
	</table>
    
    <?php // User Privacy Settings options
    ?>
    <div id="user-privacy-options" style="display: none">
          <table class="facts_table">
            <tr>
              <td class="topbottombar" colspan="3"><b><?php print GM_LANG_add_new_up_setting; ?></b>
              </td>
            </tr>
            
            <tr>
              <td class="shade2 width25"><?php print GM_LANG_user_name; ?></td>
              <td class="shade2 width15"><?php print GM_LANG_id; ?></td>
              <td class="shade2 width60"><?php print GM_LANG_show_question; ?></td>
            </tr>
            
            <tr class="<?php print $TEXT_DIRECTION; ?>">
              <td class="shade1">
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
              <td class="shade1">
                <input type="text" class="pedigree_form" name="v_new_user_privacy_access_ID" id="v_new_user_privacy_access_ID" size="4" />
                <?php
			 LinkFunctions::PrintFindIndiLink("v_new_user_privacy_access_ID","");
			 LinkFunctions::PrintFindFamilyLink("v_new_user_privacy_access_ID");
			 LinkFunctions::PrintFindSourceLink("v_new_user_privacy_access_ID");
			 LinkFunctions::PrintFindObjectLink("v_new_user_privacy_access_ID");             
			 LinkFunctions::PrintFindNoteLink("v_new_user_privacy_access_ID");             
                ?>
              </td>
              <td class="shade1">
                <select size="1" name="v_new_user_privacy_acess_option"><?php write_hide_show(""); ?></select>
              </td>
            </tr>
          </table>
       <?php
          if (count($user_privacy) > 0) {
          ?>
          <table class="facts_table">
            <tr>
              <td class="topbottombar" colspan="5"><?php print GM_LANG_edit_exist_user_privacy_settings; ?>
              </td>
            </tr>
			<tr>
				<td class="shade2 width5"><?php print GM_LANG_delete; ?></td>
				<td class="shade2 width20"><?php print GM_LANG_user_name; ?></td>
				<td class="shade2 width15"><?php print GM_LANG_id; ?></td>
				<td class="shade2 width10"><?php print GM_LANG_show_question; ?></td>
				<td class="shade2 width50"><?php print GM_LANG_full_name; ?></td>
			</tr>
           <?php
            foreach($user_privacy as $key=>$value) {
	            foreach($value as $id=>$setting) {
            ?>
            <tr class="<?php print $TEXT_DIRECTION; ?>">
              <td class="shade1">
              <input type="checkbox" name="v_user_privacy_del[<?php print $key; ?>][<?php print $id; ?>]" value="1" />
              </td>
              <td class="shade1"><?php if ($key == "all") print GM_LANG_all_users; else print $key; ?></td>
	      <td class="shade1"><?php print $id; ?></td>
              <td class="shade1">
                <select size="1" name="v_user_privacy[<?php print $key; ?>][<?php print $id; ?>]"><?php write_hide_show($setting); ?></select>
              </td>
              <td class="shade1"><?php search_ID_details($id, 2); ?>
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
	<table class="facts_table"><tr><td class="topbottombar <?php print $TEXT_DIRECTION;?>">
     <?php
    print "<a href=\"javascript: ".GM_LANG_global_facts."\" onclick=\"expand_layer('global-facts-options');return false\"><img id=\"global-facts-options_img\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a> ";?>
    	   <?php print_help_link("global_facts_help", "qm", "global_facts");?>
	   <a href="javascript: <?php print GM_LANG_global_facts; ?>" onclick="expand_layer('global-facts-options');return false"><b><?php print GM_LANG_global_facts; ?></b></a></td>
      </tr>
    </table>
    
    <?php // NOTE: General User Privacy Settings options
    ?>
    <div id="global-facts-options" style="display: none">
          <table class="facts_table">
            <tr>
              <td class="topbottombar" colspan="3"><b><?php print GM_LANG_add_new_gf_setting; ?></b></td>
            </tr>
            <tr>
              <td class="shade2 width40"><?php print GM_LANG_name_of_fact; ?></td>
              <td class="shade2 width30"><?php print GM_LANG_choice; ?></td>
              <td class="shade2 width30"><?php print GM_LANG_accessible_by; ?></td>
            </tr>
            <tr class="<?php print $TEXT_DIRECTION; ?>">
              <td class="shade1">
                <select size="1" name="v_new_global_facts_abbr">
                <?php
                print "<option value=\"\">".GM_LANG_choice."</option>";
					PrintFactChoice()
                ?>
                </select>
              </td>
              <td class="shade1">
                <select size="1" name="v_new_global_facts_choice">
                  <option value="details"><?php print GM_LANG_fact_details; ?></option>
                  <option value="show"><?php print GM_LANG_fact_show; ?></option>
                </select>
              </td>
              <td class="shade1">
                <select size="1" name="v_new_global_facts_access_option"><?php write_access_option(""); ?></select>
              </td>
            </tr>
          </table>
          <?php
          if (count($global_facts) > 0) {
          ?>
          <table class="facts_table">
            <tr>
              <td class="topbottombar" colspan="4"><b><?php print GM_LANG_edit_exist_global_facts_settings; ?></b></td>
            </tr>
            <tr>
              <td class="shade2 width5"><?php print GM_LANG_delete; ?></td>
              <td class="shade2 width35"><?php print GM_LANG_name_of_fact; ?></td>
              <td class="shade2 width30"><?php print GM_LANG_choice; ?></td>
              <td class="shade2 width30"><?php print GM_LANG_accessible_by; ?></td>
            </tr>
            <?php
            foreach($global_facts as $tag=>$value) {
	            foreach($value as $key=>$setting) {
            ?>
            <tr class="<?php print $TEXT_DIRECTION; ?>">
              <td class="shade1">
              <input type="checkbox" name="v_global_facts_del[<?php print $tag; ?>][<?php print $key; ?>]" value="1" /></td>
              <td class="shade1">
              <?php
                if (defined("GM_FACT_".$tag)) print constant("GM_FACT_".$tag);
                else print $tag;
                ?>
              </td>
              <td class="shade1"><?php
              if ($key == "show") print GM_LANG_fact_show;
              if ($key == "details") print GM_LANG_fact_details;
              ?></td>
              <td class="shade1">
                <select size="1" name="v_global_facts[<?php print $tag; ?>][<?php print $key; ?>]"><?php write_access_option($setting); ?></select>
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
	<table class="facts_table"><tr><td class="topbottombar <?php print $TEXT_DIRECTION;?>">
     <?php
    print "<a href=\"javascript: ".GM_LANG_person_facts."\" onclick=\"expand_layer('person-facts-options');return false\"><img id=\"person-facts-options_img\" src=\"".GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a> ";?>
    	   <?php print_help_link("person_facts_help", "qm", "person_facts");?>
	   <a href="javascript: <?php print GM_LANG_person_facts; ?>" onclick="expand_layer('person-facts-options');return false"><b><?php print GM_LANG_person_facts; ?></b></a></td>
      </tr>
    </table>
    
    <?php // NOTE: Person Facts options
    ?>
    <div id="person-facts-options" style="display: none">
          <table class="facts_table">
            <?php //--Start--add person_facts for individuals----------------------------------------------- 
            ?>
            <tr>
              <td class="topbottombar" colspan="4"><b><?php print GM_LANG_add_new_pf_setting_indi; ?></b></td>
            </tr>
            <tr>
              <td class="shade2 width10"><?php print GM_LANG_id; ?></td>
              <td class="shade2 width30"><?php print GM_LANG_name_of_fact; ?></td>
              <td class="shade2 width30"><?php print GM_LANG_choice; ?></td>
              <td class="shade2 width30"><?php print GM_LANG_accessible_by; ?></td>
            </tr>
            <tr class="<?php print $TEXT_DIRECTION; ?>">
              <td class="shade1">
                <input type="text" class="pedigree_form" name="v_new_person_facts_access_ID" id="v_new_person_facts_access_ID" size="4" />
                <?php
             LinkFunctions::PrintFindIndiLink("v_new_person_facts_access_ID","");
			 LinkFunctions::PrintFindFamilyLink("v_new_person_facts_access_ID");
			 LinkFunctions::PrintFindSourceLink("v_new_person_facts_access_ID");
			 LinkFunctions::PrintFindObjectLink("v_new_person_facts_access_ID");             
			 LinkFunctions::PrintFindNoteLink("v_new_person_facts_access_ID");             
                ?>
              </td>
              <td class="shade1">
                <select size="1" name="v_new_person_facts_abbr">
                <?php
                PrintFactChoice();
                ?>
                </select>
              </td>
              <td class="shade1">
                <select size="1" name="v_new_person_facts_choice">
                  <option value="details"><?php print GM_LANG_fact_details; ?></option>
                  <option value="show"><?php print GM_LANG_fact_show; ?></option>
                </select>
              </td>
              <td class="shade1">
                <select size="1" name="v_new_person_facts_acess_option"><?php write_access_option(""); ?></select>
              </td>
            </tr>
            <?php //--End----add person_facts for individuals-----------------------------------------------
         	?>
          </table>
      <?php
      if (count($person_facts) > 0) {
      ?>
      <table class="facts_table">
        <tr>
          <td class="topbottombar" colspan="6"><b><?php print GM_LANG_edit_exist_person_facts_settings; ?></b></td>
        </tr>
        <tr>
          <td class="shade2"><?php print GM_LANG_delete; ?></td>
          <td class="shade2"><?php print GM_LANG_id; ?></td>
		<td class="shade2"><?php print GM_LANG_full_name; ?></td>
          <td class="shade2"><?php print GM_LANG_name_of_fact; ?></td>
          <td class="shade2"><?php print GM_LANG_choice; ?></td>
          <td class="shade2"><?php print GM_LANG_accessible_by; ?></td>
        </tr> 
        <?php
        foreach($person_facts as $id=>$value) {
            foreach($value as $tag=>$value1) {
	            foreach($value1 as $key=>$setting) {
        ?>
        <tr class="<?php print $TEXT_DIRECTION; ?>">
          <td class="shade1">
          <input type="checkbox" name="v_person_facts_del[<?php print $id; ?>][<?php print $tag; ?>][<?php print $key; ?>]" value="1" /></td>
          <td class="shade1"><?php print $id; ?></td>
          <td class="shade1"><?php
              search_ID_details($id, 2);
          ?></td>
          <td class="shade1">
          <?php
            print $tag. " - ".constant("GM_FACT_".$tag);
          ?></td>
          <td class="shade1"><?php
          if ($key == "show") print GM_LANG_fact_show;
          if ($key == "details") print GM_LANG_fact_details;
          ?></td>
          <td class="shade1">
            <select size="1" name="v_person_facts[<?php print $id; ?>][<?php print $tag; ?>][<?php print $key; ?>]"><?php write_access_option($setting); ?></select>
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
    <table class="facts_table" border="0">
	<tr><td class="topbottombar">
	<input type="submit" value="<?php print GM_LANG_save_config?>" onclick="closeHelp();" />
	&nbsp;&nbsp;
	<input type="reset" value="<?php print GM_LANG_reset?>" /><br />
	</td></tr>
	</table>
    </form>
<?php
PrintFooter();

?>