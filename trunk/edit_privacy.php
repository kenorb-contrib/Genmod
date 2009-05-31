<?php
/**
 * Edit Privacy Settings
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
 * This Page Is Valid XHTML 1.0 Transitional! > 12 September 2005
 *
 * @author GM Development Team
 * @package Genmod
 * @subpackage Privacy
 * @version $Id: edit_privacy.php,v 1.7 2006/02/19 18:40:23 roland-d Exp $
 */

/**
 * Inclusion of the configuration file
*/
require "config.php";

/**
 * Inclusion of the gedcom record class
*/
require_once("includes/gedcomrecord.php");

/**
 * Inclusion of the language files
*/
require($GM_BASE_DIRECTORY.$factsfile["english"]);
if (file_exists($GM_BASE_DIRECTORY.$factsfile[$LANGUAGE])) require($GM_BASE_DIRECTORY.$factsfile[$LANGUAGE]);

if (empty($ged)) $ged = $GEDCOM;

if ((!userGedcomAdmin($gm_username, $ged))||(empty($ged))) {
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
  global $gm_lang;
  print "<option value=\"PRIV_PUBLIC\"";
  if ($checkVar==$PRIV_PUBLIC) print " selected=\"selected\"";
  print ">".$gm_lang["PRIV_PUBLIC"]."</option>\n";
  print "<option value=\"PRIV_USER\"";
  if ($checkVar==$PRIV_USER) print " selected=\"selected\"";
  print ">".$gm_lang["PRIV_USER"]."</option>\n";
  print "<option value=\"PRIV_NONE\"";
  if ($checkVar==$PRIV_NONE) print " selected=\"selected\"";
  print ">".$gm_lang["PRIV_NONE"]."</option>\n";
  print "<option value=\"PRIV_HIDE\"";
  if ($checkVar==$PRIV_HIDE) print " selected=\"selected\"";
  print ">".$gm_lang["PRIV_HIDE"]."</option>\n";
}

/**
 * print yes/no select option
 *
 * @param string $checkVar
 */
function write_yes_no($checkVar) {
  global $gm_lang;

  print "<option";
  if ($checkVar == false) print " selected=\"selected\"";
  print " value=\"no\">";
  print $gm_lang["no"];
  print "</option>\n";

  print "<option";
  if ($checkVar == true) print " selected=\"selected\"";
  print " value=\"yes\">";
  print $gm_lang["yes"];
  print "</option>";
}

/**
 * print find and print gedcom record ID
 *
 * @param string $checkVar	gedcom key
 * @param string $outputVar	error message style
 */
function search_ID_details($checkVar, $outputVar) {
	global $GEDCOMS, $GEDCOM;
	global $gm_lang;

	$indirec = find_gedcom_record($checkVar);
    
	if (!empty($indirec)) {
		$ct = preg_match("/0 @(.*)@ (.*)/", $indirec, $match);
		if ($ct>0) {
			$pid = $match[1];
			$type = trim($match[2]);
		}
		if ($type=="INDI") {
			$name = get_person_name($pid);
			print "\n<span class=\"list_item\">$name";
			print_first_major_fact($pid);
			print "</span>\n";
		}
		else if ($type=="SOUR") {
			$name = get_source_descriptor($pid);
			print "\n<span class=\"list_item\">$name";
			print "</span>\n";
		}
		else if ($type=="FAM") {
			$name = get_family_descriptor($pid);
			print "\n<span class=\"list_item\">$name";
			print "</span>\n";
		}
		else print "$type $pid";
	}
	else {
		print "<span class=\"error\">";
		if ($outputVar == 1) {
			print $gm_lang["unable_to_find_privacy_indi"];
			print "<br />[" . $checkVar . "]";
		}
		if ($outputVar == 2) {
			print $gm_lang["unable_to_find_privacy_indi"];
		}
		print "</span><br /><br />";
	}
}


if (empty($action)) $action="";
print_header($gm_lang["privacy_header"]);
?>
<table class="facts_table <?php print $TEXT_DIRECTION ?>">
	<tr>
		<td colspan="2" class="facts_label"><?php
			print "<h2>".$gm_lang["edit_privacy_title"]." - ".$GEDCOMS[$ged]["title"]. "</h2>";
//			print "(".$PRIVACY_MODULE . ")";
			print "<br /><br /><a href=\"editgedcoms.php\"><b>";
			print $gm_lang["lang_back_manage_gedcoms"];
			print "</b></a><br /><br />";?>
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
	print $gm_lang["performing_update"];
	print "<br />";
	$settings = array();
	$settings["gedcom"] = $GEDCOM;
	$settings["priv_user"] = $PRIV_USER;
	$settings["priv_none"] = $PRIV_NONE;
	$settings["priv_hide"] = $PRIV_HIDE;
	$settings["priv_public"] = $PRIV_PUBLIC;
	$settings["privacy_version"] = $PRIVACY_VERSION;
	print $gm_lang["config_file_read"];
	print "</td></tr></table>\n";
	$settings["show_dead_people"] = $_POST["v_SHOW_DEAD_PEOPLE"];
	$settings["show_living_names"] = $_POST["v_SHOW_LIVING_NAMES"];
	$settings["show_sources"] = $_POST["v_SHOW_SOURCES"];
	$settings["max_alive_age"] = $_POST["v_MAX_ALIVE_AGE"];
	if ($MAX_ALIVE_AGE!=$_POST["v_MAX_ALIVE_AGE"]) reset_isdead();
	$settings["enable_clippings_cart"] = $_POST["v_ENABLE_CLIPPINGS_CART"];
	$settings["privacy_by_year"] = $boolarray[$_POST["v_PRIVACY_BY_YEAR"]];
	$settings["privacy_by_resn"] = $boolarray[$_POST["v_PRIVACY_BY_RESN"]];
	$settings["use_relationship_privacy"] = $boolarray[$_POST["v_USE_RELATIONSHIP_PRIVACY"]];
	$settings["max_relation_path_length"] = $_POST["v_MAX_RELATION_PATH_LENGTH"];
	$settings["check_marriage_relations"] = $boolarray[$_POST["v_CHECK_MARRIAGE_RELATIONS"]];
	
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
		$gedobj = new GedcomRecord(find_gedcom_record($v_new_person_privacy_access_ID));
		$v_new_person_privacy_access_ID = $gedobj->getXref();
		if (!empty($v_new_person_privacy_access_ID)) $person_privacy[$v_new_person_privacy_access_ID] = $v_new_person_privacy_acess_option;
	}
	$settings["person_privacy"] = $person_privacy;	
	
	if (!isset($v_user_privacy_del)) $v_user_privacy_del = array();
	if (!is_array($v_user_privacy_del)) $v_user_privacy_del = array();
	if (!isset($v_user_privacy)) $v_user_privacy = array();
	if (!is_array($v_user_privacy)) $v_user_privacy = array();
	foreach($user_privacy as $key=>$value) {
		foreach($value as $id=>$setting) {
			if (!isset($v_user_privacy_del[$key][$id])) {
				if (isset($v_user_privacy[$key][$id])) $user_privacy[$key][$id] = $v_user_privacy[$key][$id];
				else $user_privacy[$key][$id] = $PRIVACY_CONSTANTS[$setting];
			}
			else unset($user_privacy[$key][$id]);
		}
	}
	if ((!empty($v_new_user_privacy_username))&&(!empty($v_new_user_privacy_access_ID))&&(!empty($v_new_user_privacy_acess_option))) {
		$gedobj = new GedcomRecord(find_gedcom_record($v_new_user_privacy_access_ID));
		$v_new_user_privacy_access_ID = $gedobj->getXref();
		if (!empty($v_new_user_privacy_access_ID)) $user_privacy[$v_new_user_privacy_username][$v_new_user_privacy_access_ID] = $v_new_user_privacy_acess_option;
	}
	$settings["user_privacy"] = $user_privacy;	

		
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
	if ((!empty($v_new_global_facts_abbr))&&(!empty($v_new_global_facts_choice))&&(!empty($v_new_global_facts_access_option))) {
		$global_facts[$v_new_global_facts_abbr][$v_new_global_facts_choice] = $v_new_global_facts_access_option;
	}
	$settings["global_facts"] = $global_facts;
	
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
	if ((!empty($v_new_person_facts_access_ID))&&(!empty($v_new_person_facts_abbr))&&(!empty($v_new_global_facts_choice))&&(!empty($v_new_global_facts_acess_option))) {
		$gedobj = new GedcomRecord(find_gedcom_record($v_new_person_facts_access_ID));
		$v_new_person_facts_access_ID = $gedobj->getXref();
		if (!empty($v_new_person_facts_access_ID)) $person_facts[$v_new_person_facts_access_ID][$v_new_person_facts_abbr][$v_new_person_facts_choice] = $v_new_person_facts_acess_option;
	}
	$settings["person_facts"] = $person_facts;	
	StorePrivacy($settings);
	
	// NOTE: load the new variables
	ReadPrivacy($GEDCOM);
	WriteToLog("Privacy file updated", "I", "G", $GEDCOM);
	
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
    <?php print "<input type=\"hidden\" name=\"ged\" value=\"".$GEDCOM."\" />\n";
    
    // NOTE: General Privacy Settings header bar
    ?>
	<table class="facts_table">
    	<tr>
    		<td class="topbottombar <?php print $TEXT_DIRECTION;?>">
		<?php
    		print "<a href=\"javascript: ".$gm_lang["general_privacy"]."\" onclick=\"expand_layer('general-privacy-options');return false\"><img id=\"general-privacy-options_img\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["minus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a> ";
		print_help_link("general_privacy_help", "qm", "general_privacy");
    		?>
        	<a href="javascript: <?php print $gm_lang["general_privacy"]; ?>" onclick="expand_layer('general-privacy-options');return false"><b><?php print $gm_lang["general_privacy"]; ?></b></a>
        	</td>
	</tr>
	</table>
    
    <?php // NOTE: General Privacy Settings options
    ?>
    <div id="general-privacy-options" style="display: block">
    <table class="facts_table">
      <tr>
        <td class="shade2 wrap width20 <?php print $TEXT_DIRECTION;?>"><?php print_help_link("SHOW_DEAD_PEOPLE_help", "qm", "SHOW_DEAD_PEOPLE"); print $gm_lang["SHOW_DEAD_PEOPLE"]; ?>
        </td>
        <td class="shade1">
          <select size="1" name="v_SHOW_DEAD_PEOPLE"><?php write_access_option($SHOW_DEAD_PEOPLE); ?></select>
        </td>
      </tr>
      <tr>
        <td class="shade2 wrap"><?php print_help_link("SHOW_LIVING_NAMES_help", "qm", "SHOW_LIVING_NAMES"); print $gm_lang["SHOW_LIVING_NAMES"]; ?>
        </td>
        <td class="shade1">
          <select size="1" name="v_SHOW_LIVING_NAMES"><?php write_access_option($SHOW_LIVING_NAMES); ?></select>
        </td>
      </tr>
      <tr>
        <td class="shade2 wrap"><?php print_help_link("SHOW_SOURCES_help", "qm", "SHOW_SOURCES"); print $gm_lang["SHOW_SOURCES"]; ?>
        </td>
        <td class="shade1">
          <select size="1" name="v_SHOW_SOURCES"><?php write_access_option($SHOW_SOURCES); ?></select>
        </td>
      </tr>
      <tr>
        <td class="shade2 wrap"><?php print_help_link("ENABLE_CLIPPINGS_CART_help", "qm", "ENABLE_CLIPPINGS_CART"); print $gm_lang["ENABLE_CLIPPINGS_CART"]; ?>
        </td>
        <td class="shade1">
          <select size="1" name="v_ENABLE_CLIPPINGS_CART"><?php write_access_option($ENABLE_CLIPPINGS_CART); ?></select>
        </td>
      </tr>
      <tr>
        <td class="shade2 wrap"><?php print_help_link("PRIVACY_BY_YEAR_help", "qm", "PRIVACY_BY_YEAR"); print $gm_lang["PRIVACY_BY_YEAR"]; ?>
        </td>
        <td class="shade1">
          <select size="1" name="v_PRIVACY_BY_YEAR"><?php write_yes_no($PRIVACY_BY_YEAR); ?></select>
        </td>
      </tr>
      
      <tr>
        <td class="shade2 wrap"><?php print_help_link("PRIVACY_BY_RESN_help", "qm", "PRIVACY_BY_RESN"); print $gm_lang["PRIVACY_BY_RESN"]; ?>
        </td>
        <td class="shade1">
          <select size="1" name="v_PRIVACY_BY_RESN"><?php write_yes_no($PRIVACY_BY_RESN); ?></select>
        </td>
      </tr>
      
      <tr>
        <td class="shade2 wrap"><?php print_help_link("USE_RELATIONSHIP_PRIVACY_help", "qm", "USE_RELATIONSHIP_PRIVACY"); print $gm_lang["USE_RELATIONSHIP_PRIVACY"]; ?>
        </td>
        <td class="shade1">
          <select size="1" name="v_USE_RELATIONSHIP_PRIVACY"><?php write_yes_no($USE_RELATIONSHIP_PRIVACY); ?></select>
        </td>
      </tr>

      <tr>
        <td class="shade2 wrap"><?php print_help_link("MAX_RELATION_PATH_LENGTH_help", "qm", "MAX_RELATION_PATH_LENGTH"); print $gm_lang["MAX_RELATION_PATH_LENGTH"]; ?>
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
        <td class="shade2 wrap"><?php print_help_link("CHECK_MARRIAGE_RELATIONS_help", "qm", "CHECK_MARRIAGE_RELATIONS"); print $gm_lang["CHECK_MARRIAGE_RELATIONS"]; ?>
        </td>
        <td class="shade1">
          <select size="1" name="v_CHECK_MARRIAGE_RELATIONS"><?php write_yes_no($CHECK_MARRIAGE_RELATIONS); ?></select>
        </td>
      </tr>
      
	  <tr>
		<td class="shade2 wrap"><?php print_help_link("MAX_ALIVE_AGE_help", "qm", "MAX_ALIVE_AGE"); print $gm_lang["MAX_ALIVE_AGE"]?>
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
		print "<a href=\"javascript: ".$gm_lang["person_privacy"]."\" onclick=\"expand_layer('person-privacy-options');return false\"><img id=\"person-privacy-options_img\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a> ";?><?php print_help_link("person_privacy_help", "qm", "person_privacy");?>
        	<a href="javascript: <?php print $gm_lang["person_privacy"]; ?>" onclick="expand_layer('person-privacy-options');return false"><b><?php print $gm_lang["person_privacy"]; ?></b></a>
        	</td>
		</tr>
	</table>
    
    <?php // NOTE: General Privacy Settings options
    ?>
    <div id="person-privacy-options" style="display: none">
    	<table class="facts_table">
        	<tr>
            	<td class="topbottombar" colspan="2"><b><?php print $gm_lang["add_new_pp_setting"]; ?></b>
            	</td>
            </tr>
            
            <tr>
              <td class="shade2"><?php print $gm_lang["id"]; ?></td>
              <td class="shade2"><?php print $gm_lang["accessible_by"]; ?></td>
            </tr>
            
            <tr>
              <td class="shade1 width20">
                <input type="text" class="pedigree_form" name="v_new_person_privacy_access_ID" id="v_new_person_privacy_access_ID" size="4" />
                <?php
			 print_findindi_link("v_new_person_privacy_access_ID");
			 print_findfamily_link("v_new_person_privacy_access_ID");
			 print_findsource_link("v_new_person_privacy_access_ID");
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
              <td class="topbottombar" colspan="4"><?php print $gm_lang["edit_exist_person_privacy_settings"]; ?>
              </td>
            </tr>
            
            <tr>
              <td class="shade2"><?php print $gm_lang["delete"]; ?></td>
              <td class="shade2"><?php print $gm_lang["id"]; ?></td>
              <td class="shade2"><?php print $gm_lang["full_name"]; ?></td>
              <td class="shade2"><?php print $gm_lang["accessible_by"]; ?></td>
            </tr>
            <?php
            foreach($person_privacy as $key=>$value) {
            ?>
            <tr>
              <td class="shade1">
              <input type="checkbox" name="v_person_privacy_del[<?php print $key; ?>]" value="1" />
              </td>
              <td class="shade1"><?php print $key; ?></td>
              <td class="shade1"><?php search_ID_details($key, 1); ?></td>
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
    		print "<a href=\"javascript: ".$gm_lang["user_privacy"]."\" onclick=\"expand_layer('user-privacy-options');return false\"><img id=\"user-privacy-options_img\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a> ";?><?php print_help_link("user_privacy_help", "qm", "user_privacy");?>
		<a href="javascript: <?php print $gm_lang["user_privacy"]; ?>" onclick="expand_layer('user-privacy-options');return false"><b><?php print $gm_lang["user_privacy"]; ?></b></a>
        	</td>
		</tr>
	</table>
    
    <?php // User Privacy Settings options
    ?>
    <div id="user-privacy-options" style="display: none">
          <table class="facts_table">
            <tr>
              <td class="topbottombar" colspan="3"><b><?php print $gm_lang["add_new_up_setting"]; ?></b>
              </td>
            </tr>
            
            <tr>
              <td class="shade2"><?php print $gm_lang["user_name"]; ?></td>
              <td class="shade2"><?php print $gm_lang["id"]; ?></td>
              <td class="shade2"><?php print $gm_lang["show_question"]; ?></td>
            </tr>
            
            <tr class="<?php print $TEXT_DIRECTION; ?>">
              <td class="shade1 width20">
                <select size="1" name="v_new_user_privacy_username">
                <?php
                $users = getUsers();
                foreach($users as $username => $user)
                {
                  print "<option";
                  print " value=\"";
                  print $username;
                  print "\">";
                  print $user["firstname"]." ".$user["lastname"];
                  print "</option>";
                }
                ?>
                </select>
              </td>
              <td class="shade1">
                <input type="text" class="pedigree_form" name="v_new_user_privacy_access_ID" id="v_new_user_privacy_access_ID" size="4" />
                <?php
			 print_findindi_link("v_new_user_privacy_access_ID","");
			 print_findfamily_link("v_new_user_privacy_access_ID");
			 print_findsource_link("v_new_user_privacy_access_ID");
                ?>
              </td>
              <td class="shade1">
                <select size="1" name="v_new_user_privacy_acess_option"><?php write_access_option(""); ?></select>
              </td>
            </tr>
          </table>
       <?php
          if (count($user_privacy) > 0) {
          ?>
          <table class="facts_table">
            <tr>
              <td class="topbottombar" colspan="5"><?php print $gm_lang["edit_exist_user_privacy_settings"]; ?>
              </td>
            </tr>
            <tr>
              <td class="shade2"><?php print $gm_lang["delete"]; ?></td>
              <td class="shade2"><?php print $gm_lang["user_name"]; ?></td>
              <td class="shade2"><?php print $gm_lang["id"]; ?></td>
		    <td class="shade2"><?php print $gm_lang["full_name"]; ?></td>
              <td class="shade2"><?php print $gm_lang["show_question"]; ?></td>
            </tr>
            
            <?php
            foreach($user_privacy as $key=>$value) {
	            foreach($value as $id=>$setting) {
            ?>
            <tr class="<?php print $TEXT_DIRECTION; ?>">
              <td class="shade1">
              <input type="checkbox" name="v_user_privacy_del[<?php print $key; ?>][<?php print $id; ?>]" value="1" />
              </td>
              <td class="shade1"><?php print $key; ?></td>
	      <td class="shade1"><?php print $id; ?></td>
              <td class="shade1"><?php search_ID_details($id, 2); ?>
              </td>
              <td class="shade1">
                <select size="1" name="v_user_privacy[<?php print $key; ?>][<?php print $id; ?>]"><?php write_access_option($setting); ?></select>
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
    print "<a href=\"javascript: ".$gm_lang["global_facts"]."\" onclick=\"expand_layer('global-facts-options');return false\"><img id=\"global-facts-options_img\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a> ";?>
    	   <?php print_help_link("global_facts_help", "qm", "global_facts");?>
	   <a href="javascript: <?php print $gm_lang["global_facts"]; ?>" onclick="expand_layer('global-facts-options');return false"><b><?php print $gm_lang["global_facts"]; ?></b></a></td>
      </tr>
    </table>
    
    <?php // NOTE: General User Privacy Settings options
    ?>
    <div id="global-facts-options" style="display: none">
          <table class="facts_table">
            <tr>
              <td class="topbottombar" colspan="3"><b><?php print $gm_lang["add_new_gf_setting"]; ?></b></td>
            </tr>
            <tr>
              <td class="shade2"><?php print $gm_lang["name_of_fact"]; ?></td>
              <td class="shade2"><?php print $gm_lang["choice"]; ?></td>
              <td class="shade2"><?php print $gm_lang["accessible_by"]; ?></td>
            </tr>
            <tr class="<?php print $TEXT_DIRECTION; ?>">
              <td class="shade1">
                <select size="1" name="v_new_global_facts_abbr">
                <?php
                print "<option value=\"\">".$gm_lang["choice"]."</option>";
                foreach($factarray as $tag=>$label) {
                  print "<option";
                  print " value=\"";
                  print $tag;
                  print "\">";
                  print $tag . " - " . str_replace("<br />", " ", $label);
                  print "</option>";
                }
                ?>
                </select>
              </td>
              <td class="shade1">
                <select size="1" name="v_new_global_facts_choice">
                  <option value="details"><?php print $gm_lang["fact_details"]; ?></option>
                  <option value="show"><?php print $gm_lang["fact_show"]; ?></option>
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
              <td class="topbottombar" colspan="4"><b><?php print $gm_lang["edit_exist_global_facts_settings"]; ?></b></td>
            </tr>
            <tr>
              <td class="shade2"><?php print $gm_lang["delete"]; ?></td>
              <td class="shade2"><?php print $gm_lang["name_of_fact"]; ?></td>
              <td class="shade2"><?php print $gm_lang["choice"]; ?></td>
              <td class="shade2"><?php print $gm_lang["accessible_by"]; ?></td>
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
                if (isset($factarray[$tag])) print $factarray[$tag];
                else print $tag;
                ?>
              </td>
              <td class="shade1"><?php
              if ($key == "show") print $gm_lang["fact_show"];
              if ($key == "details") print $gm_lang["fact_details"];
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
    print "<a href=\"javascript: ".$gm_lang["person_facts"]."\" onclick=\"expand_layer('person-facts-options');return false\"><img id=\"person-facts-options_img\" src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["plus"]["other"]."\" border=\"0\" width=\"11\" height=\"11\" alt=\"\" /></a> ";?>
    	   <?php print_help_link("person_facts_help", "qm", "person_facts");?>
	   <a href="javascript: <?php print $gm_lang["person_facts"]; ?>" onclick="expand_layer('person-facts-options');return false"><b><?php print $gm_lang["person_facts"]; ?></b></a></td>
      </tr>
    </table>
    
    <?php // NOTE: Person Facts options
    ?>
    <div id="person-facts-options" style="display: none">
          <table class="facts_table">
            <?php //--Start--add person_facts for individuals----------------------------------------------- 
            ?>
            <tr>
              <td class="topbottombar" colspan="4"><b><?php print $gm_lang["add_new_pf_setting_indi"]; ?></b></td>
            </tr>
            <tr>
              <td class="shade2"><?php print $gm_lang["privacy_indi_id"]; ?></td>
              <td class="shade2"><?php print $gm_lang["name_of_fact"]; ?></td>
              <td class="shade2"><?php print $gm_lang["choice"]; ?></td>
              <td class="shade2"><?php print $gm_lang["accessible_by"]; ?></td>
            </tr>
            <tr class="<?php print $TEXT_DIRECTION; ?>">
              <td class="shade1">
                <input type="text" class="pedigree_form" name="v_new_person_facts_access_ID" id="v_new_person_facts_access_ID" size="4" />
                <?php
                print_findindi_link("v_new_person_facts_access_ID","");
			 print_findfamily_link("v_new_person_facts_access_ID");
			 print_findsource_link("v_new_person_facts_access_ID");
                ?>
              </td>
              <td class="shade1">
                <select size="1" name="v_new_person_facts_abbr">
                <?php
                foreach($factarray as $tag=>$label) {
                  print "<option";
                  print " value=\"";
                  print $tag;
                  print "\">";
                  print $tag . " - " . str_replace("<br />", " ", $label);
                  print "</option>";
                }
                ?>
                </select>
              </td>
              <td class="shade1">
                <select size="1" name="v_new_person_facts_choice">
                  <option value="details"><?php print $gm_lang["fact_details"]; ?></option>
                  <option value="show"><?php print $gm_lang["fact_show"]; ?></option>
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
          <td class="topbottombar" colspan="6"><b><?php print $gm_lang["edit_exist_person_facts_settings"]; ?></b></td>
        </tr>
        <tr>
          <td class="shade2"><?php print $gm_lang["delete"]; ?></td>
          <td class="shade2"><?php print $gm_lang["id"]; ?></td>
		<td class="shade2"><?php print $gm_lang["full_name"]; ?></td>
          <td class="shade2"><?php print $gm_lang["name_of_fact"]; ?></td>
          <td class="shade2"><?php print $gm_lang["choice"]; ?></td>
          <td class="shade2"><?php print $gm_lang["accessible_by"]; ?></td>
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
            print $tag. " - ".$factarray[$tag];
          ?></td>
          <td class="shade1"><?php
          if ($key == "show") print $gm_lang["fact_show"];
          if ($key == "details") print $gm_lang["fact_details"];
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
	<input type="submit" tabindex="<?php $i++; print $i?>" value="<?php print $gm_lang["save_config"]?>" onclick="closeHelp();" />
	&nbsp;&nbsp;
	<input type="reset" tabindex="<?php $i++; print $i?>" value="<?php print $gm_lang["reset"]?>" /><br />
	</td></tr>
	</table>
    </form>
<?php
print_footer();

?>