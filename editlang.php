<?php
/**
 * Display a diff between two language files to help in translating.
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
 * @subpackage Languages
 * @version $Id: editlang.php,v 1.11 2006/02/27 22:07:26 roland-d Exp $
 */

/**
 * Inclusion of the configuration file
*/
require "config.php";

/**
 * Inclusion of the language files
*/
if (file_exists($GM_BASE_DIRECTORY . $confighelpfile[$LANGUAGE])) require $GM_BASE_DIRECTORY . $confighelpfile[$LANGUAGE];



/**
 * Inclusion of the language editing functions
*/
require $GM_BASE_DIRECTORY . "includes/functions_editlang.php";

if (!isset($action)) $action="";
if (!isset($hide_translated)) $hide_translated=false;
if (!isset($language2)) $language2 = $LANGUAGE;
if (!isset($file_type)) $file_type = "lang";
if (!isset($language1)) $language1 = "english";
$lang_shortcut = $language_settings[$language2]["lang_short_cut"];

//-- make sure that they have admin status before they can use this page
//-- otherwise have them login again
$uname = $gm_username;
if (empty($uname)) {
	header("Location: login.php?url=editlang.php");
	exit;
}

switch ($action){
  case "edit": 
  	print_header($gm_lang["edit_lang_utility"]);
	break;
  case "export": 
  	print_header($gm_lang["export_lang_utility"]);
	break;
  case "compare": 
  	print_header($gm_lang["compare_lang_utility"]); 
	break;
  default	: 
  	print_header($gm_lang["edit_langdiff"]); 
	break;
}
if (isset($execute) && $action == "debug") {
	if (isset($_POST["DEBUG_LANG"])) $_SESSION["DEBUG_LANG"] = $_POST["DEBUG_LANG"];
	else $_SESSION["DEBUG_LANG"] = "no";
	$DEBUG_LANG = $_SESSION["DEBUG_LANG"];
}

$QUERY_STRING = preg_replace("/&amp;/", "&", $QUERY_STRING);
$QUERY_STRING = preg_replace("/&&/", "&", $QUERY_STRING);
if (strpos($QUERY_STRING,"&dv="))$QUERY_STRING = substr($QUERY_STRING,0,strpos($QUERY_STRING,"&dv="));

print "<script language=\"JavaScript\" type=\"text/javascript\">\n";
print "<!--\n";
print "var helpWin;\n";
print "function helpPopup00(which) {\n";
print "if ((!helpWin)||(helpWin.closed)){helpWin = window.open('editlang_edit.php?' + which, '' , 'left=50, top=30, width=700, height=600, resizable=1, scrollbars=1'); helpWin.focus();}\n";
print "else helpWin.location = 'editlang_edit.php?' + which;\n";
print "return false;\n";
print "}\n";
print "function showchanges(which2) {\n";
print "\twindow.location = '$SCRIPT_NAME?$QUERY_STRING'+which2;\n";
print "}\n";
print "//-->\n";
print "</script>\n";

print "<div class=\"center\">";

// Sort the Language table into localized language name order
foreach ($gm_language as $key => $value){
	$d_LangName = "lang_name_".$key;
	$Sorted_Langs[$key] = $gm_lang[$d_LangName];
}
asort($Sorted_Langs);

/* Language File Edit Mask */

switch ($action) {
     case "loadenglish" :
          print "<table class=\"center $TEXT_DIRECTION\">";
		print "<tr><td class=\"topbottombar\" colspan=\"2\">";
		print $gm_lang["load_english"];
		print "</td></tr>";		
		print "<tr><td class=\"shade1 center\">";
		if (storeEnglish()) {
               loadEnglish();
               WriteToLog($gm_lang["english_loaded"]);
               print $gm_lang["english_loaded"];
          }
          else {
               loadEnglish();
               WriteToLog($gm_lang["english_not_loaded"], "E", "S");
               print $gm_lang["english_not_loaded"];
		}
		print "</td></tr>";
		print  "<tr><td class=\"center\"><a href=\"editlang.php\"><b>";
		print $gm_lang["lang_back"];
		print "</b></a></td></tr></table><br />";                         
		print "<a href=\"editlang.php\">".$gm_lang["lang_back"]."</a>";
		break;
	case "bom" :
		print "<table class=\"center $TEXT_DIRECTION\">";
		print "<tr><td class=\"topbottombar\" colspan=\"2\">";
		print $gm_lang["bom_check"];
		print "</td></tr>";		
		print "<tr><td class=\"shade1 center\">";
		check_bom();
		print "</td></tr>";
		print  "<tr><td class=\"center\"><a href=\"editlang.php\"><b>";
		print $gm_lang["lang_back"];
		print "</b></a></td></tr></table><br />";
		break;
	case "edit" :
		print "<form name=\"choose_form\" method=\"get\" action=\"$SCRIPT_NAME\">";
		print "<input type=\"hidden\" name=\"action\" value=\"edit\" />";
		print "<input type=\"hidden\" name=\"execute\" value=\"true\" />";
		print "<table class=\"center $TEXT_DIRECTION\">";
		print "<tr><td class=\"topbottombar\" colspan=\"4\">";
		print $gm_lang["edit_lang_utility"];
		print "</td></tr>";		
		print "<tr>";
		print "<td class=\"shade1\">";
		print_help_link("language_to_edit_help", "qm", "language_to_edit");
		print $gm_lang["language_to_edit"];
		print ":";
		print "<br />";
		print "<select name=\"language2\">";
		foreach ($Sorted_Langs as $key => $value){
			print "\n\t\t\t<option value=\"$key\"";
			if ($key == $language2) print " selected=\"selected\"";
			print ">".$gm_lang["lang_name_".$key]."</option>";
		}
		print "</select>";
		print "</td>";
		print "<td class=\"shade1\">";
		print_help_link("file_to_edit_help", "qm", "file_to_edit");
		print $gm_lang["file_to_edit"].":";
		print "<br />";
		print "<select name=\"file_type\">";
		print "\n\t\t\t<option value=\"lang\"";
		if ($file_type == "lang") print " selected=\"selected\"";
		print ">"."Main texts"."</option>";
		
		print "\n\t\t\t<option value=\"help_text\"";
		if ($file_type == "help_text") print " selected=\"selected\"";
		print ">" . "Help texts" . "</option>";
		
		print "\n\t\t\t<option value=\"facts\"";
		if ($file_type == "facts") print " selected=\"selected\"";
		print ">" . "Facts" . "</option>";
		print "</select>";
		print "</td>";
		
		print "<td class=\"shade1\">";
		print_help_link("hide_translated_help", "qm", "hide_translated");
		print $gm_lang["hide_translated"];
		print ":";
		print "<br />";
		print "<select name=\"hide_translated\">";
		print "<option";
		if (!$hide_translated) print " selected=\"selected\"";
		print " value=\"";
		print "0";
		print "\">";
		print $gm_lang["no"];
		print "</option>";
		print "<option";
		if ($hide_translated) print " selected=\"selected\"";
		print " value=\"";
		print "1";
		print "\">";
		print $gm_lang["yes"];
		print "</option>";
		print "</select>";
		print "</td>";
		print "<td class=\"shade1\" style=\"text-align: center; \">";
		print "<input type=\"submit\" value=\"" . $gm_lang["edit"] . "\" />";
		print "</td>";
		print "</tr>";
		print  "<tr><td class=\"center\" colspan=\"4\"><a href=\"editlang.php\"><b>";
		print $gm_lang["lang_back"];
		print "</b></a></td></tr>";
		print "</table><br />";
		print "</form>";
		if (isset($execute)) {
			print "<table class=\"center $TEXT_DIRECTION\" style=\"width:70%; \">";
			print "<tr><td class=\"topbottombar\" colspan=\"2\"><span class=\"subheaders\">" . $gm_lang["listing"] . ": \"";
			switch ($file_type) {
				case "lang":
					print $gm_lang["lang_name_english"] . "\" ";
					print $gm_lang["and"] . " \"";
					print $gm_lang["lang_name_".$language2];
					// read the english lang.en.php file into array
					$english_language_array = array();
					$english_language_array = loadEnglish(true);
					// read the chosen lang.xx.php file into array
					$new_language_array = array();
					$new_language_array = loadLanguage($language2, true);
					break;
				case "help_text":
		      		print $helptextfile["english"]."\" ";
					print $gm_lang["and"] . " \"";
					print $gm_lang["lang_name_".$language2];
					// read the english lang.en.php file into array
					$english_language_array = array();
					$english_language_array = loadEnglish(true, true);
					// read the chosen lang.xx.php file into array
					$new_language_array = array();
					$new_language_array = loadLanguage($language2, true, true);
					break;
				case "facts":
		      		print $factsfile["english"]."\" ";
					print $gm_lang["and"] . " \"";
					print $factsfile[$language2];
					// read the english lang.en.php file into array
					$english_language_array = array();
					$english_language_array = read_complete_file_into_array($factsfile["english"], "factarray[");
					// read the chosen lang.xx.php file into array
					$new_language_array = array();
					$new_language_array = read_complete_file_into_array($factsfile[$language2], "factarray[");
					break;					
			}
			print "\"</span><br /><br />\n";
			print "<span class=\"subheaders\">" . $gm_lang["contents"] . ":</span></td></tr>";
			$lastfound = (-1);
			$counter = 0;
			foreach ($english_language_array as $string => $value) {
				$dummy_output = "";
				$dummy_output .= "<tr>";
				$dummy_output .= "<td class=\"facts_label\" rowspan=\"2\" dir=\"ltr\">";
				$dummy_output .= $string;
				$dummy_output .= "</td>\n";
				$dummy_output .= "<td class=\"shade1 wrap\">";
				$dummy_output .= "\n<a name=\"a1_".$counter."\"></a>\n";
				if (stripslashes(mask_all($value)) == "") {
					$dummy_output .= "<strong style=\"color: #FF0000\">" . str_replace("#LANGUAGE_FILE#", $gm_language[$language1], $gm_lang["message_empty_warning"]) . "</strong>";
				}
				else $dummy_output .= "<i>" . stripslashes(mask_all($value)) . "</i>";
				$dummy_output .= "</td>";
				$dummy_output .= "</tr>\n";
				$dummy_output_02 = "";
				$dummy_output_02 .= "<tr>\n";
				$dummy_output_02 .= "<td class=\"shade1 wrap\">";
				$found = false;
				$new_counter = 0;
				foreach ($new_language_array as $new_string => $new_value) {
					if (strlen(trim($new_value)) != 0) {
						if ($new_string == $string) {
				        		$dDummy =  $new_value;
				        		$dummy_output_02 .= "<a href=\"#\" onclick=\"return helpPopup00('" . "ls01=" . $string . "&amp;ls02=" . $new_string . "&amp;language2=" . $language2 . "&amp;file_type=" . $file_type . "&amp;" . session_name() . "=" . session_id() . "&amp;anchor=a1_" . $counter . "');\">";
				        		$dummy_output_02 .= stripslashes(mask_all($dDummy));
				        		if (stripslashes(mask_all($dDummy)) == "") {
				          		$dummy_output_02 .= "<strong style=\"color: #FF0000\">" . str_replace("#LANGUAGE_FILE#", $gm_language[$language2], $gm_lang["message_empty_warning"]) . "</strong>";
				        		}
				        		$dummy_output_02 .= "</a>";
				        		$found = true;
				        		$lastfound = $new_counter;
				        		break;
				      	}
				    	}
					$new_counter++;
				}
				if ((($hide_translated) and (!$found)) or (!$hide_translated)) {
					print $dummy_output;
					print $dummy_output_02;
					if (!$found) {
						print "<a style=\"color: #FF0000\" href=\"#\" onclick=\"return helpPopup00('" . "ls01=" . $string . "&amp;ls02=" . (0 - intval($lastfound) - 1) . "&amp;language2=" . $language2 . "&amp;file_type=" . $file_type . "&amp;anchor=a1_" . $counter . "');\">";
						print "<i>";
						if (stripslashes(mask_all($value)) == "") print "&nbsp;";
						else print stripslashes(mask_all($value));
						print "</i>";
						print "</a>";
				  	}
					print "</td>";
					print "</tr>\n";
				}
				$counter++;
			}
			print "</table><br />";
		}
		break;
	case "debug" :
		print "<form name=\"debug_form\" method=\"post\" action=\"editlang.php\">";
		print "<input type=\"hidden\" name=\"action\" value=\"debug\" />";
		print "<input type=\"hidden\" name=\"execute\" value=\"true\" />";
		print "<table class=\"center $TEXT_DIRECTION\">";
		print "<tr><td class=\"topbottombar\" colspan=\"3\">";
		print $gm_lang["lang_debug"];
		print "</td></tr>";				
		print "<tr>";
		print "<td class=\"shade1\" >";
		print "<input type=\"checkbox\" name=\"DEBUG_LANG\" value=\"yes\" ";
		if (isset($_SESSION["DEBUG_LANG"])) {
			if (($_SESSION["DEBUG_LANG"]) == "yes") print "checked=\"checked\"";
		}
		print " />";
		print $gm_lang["lang_debug_use"]."&nbsp;&nbsp;</td>";
		print "<td class=\"shade1\" align=\"center\" ><input type=\"submit\" value=\"".$gm_lang["save"]."\" />";
		print "</td>";
		print "</tr>";
		print  "<tr><td class=\"center\" colspan=\"4\"><a href=\"editlang.php\"><b>";
		print $gm_lang["lang_back"];
		print "</b></a></td></tr>";
		print "</table><br />";
		print "</form>";
		break;
	case "export" :
		print "<form name=\"export_form\" method=\"get\" action=\"$SCRIPT_NAME\">";
		print "<input type=\"hidden\" name=\"action\" value=\"export\" />";
		print "<input type=\"hidden\" name=\"execute\" value=\"true\" />";
		print "<table class=\"center $TEXT_DIRECTION\">";
		print "<tr><td class=\"topbottombar\" colspan=\"3\">";
		print $gm_lang["export_lang_utility"];
		print "</td></tr>";
		print "<tr>";
		print "<td class=\"shade1\">";
		print_help_link("language_to_export_help", "qm", "language_to_export");
		print $gm_lang["language_to_export"];
		print ":";
		print "<br />";
		print "<select name=\"language2\">";
		foreach ($Sorted_Langs as $key => $value){
			print "\n\t\t\t<option value=\"$key\"";
			if ($key == $language2) print " selected=\"selected\"";
			print ">".$gm_lang["lang_name_".$key]."</option>";
		}
		print "</select>";
		print "</td>";
		
		print "<td class=\"shade1\" style=\"text-align: center; \">";
		print "<input type=\"submit\" value=\"" . $gm_lang["export"] . "\" />";
		print "</td></tr>";
		print  "<tr><td class=\"center\" colspan=\"4\"><a href=\"editlang.php\"><b>";
		print $gm_lang["lang_back"];
		print "</b></a></td></tr>";
		print "</table><br /></form>";
          if (isset($execute)) {
               $data = "";
	          $data_help = "";
               $storelang = loadLanguage($language2, true) + loadLanguage($language2, true, true);
               foreach($storelang as $string => $value) {
          		if (stristr($string, "_help")) $data_help .= "\"".$string."\";\"".$value."\"\r\n";
          		else $data .= "\"".$string."\";\"".$value."\"\r\n";
          	}
          	if (!$handle = fopen("languages/lang.".$lang_shortcut.".txt", "w")) {
                    print str_replace("#lang_filename#", "languages/lang.".$lang_shortcut.".txt", $gm_lang["no_open"])."<br />";
                    WriteToLog("Can't open file languages/lang.".$lang_shortcut.".txt", "E", "S");
               }
               else {
                    if (fwrite($handle, $data)) {
                         WriteToLog($gm_lang["editlang_lang_export_success"], "I", "S");
                         print $gm_lang["editlang_lang_export_success"]."<br />";
                    }
     	          else {
                         WriteToLog($gm_lang["editlang_lang_export_no_success"], "E", "S");
                         print $gm_lang["editlang_lang_export_success"]."<br />";
                    }
               	fclose($handle);            
               }
	          if (!$handle_help = fopen("languages/help_text.".$lang_shortcut.".txt", "w")) {
	               print str_replace("#lang_filename#", "languages/help_text.".$lang_shortcut.".txt", $gm_lang["no_open"])."<br />";
                    WriteToLog("Can't open file languages/help_text.".$lang_shortcut.".txt", "E", "S");
               }
	          else {
               	if (fwrite($handle_help, $data_help)) {
               	     WriteToLog($gm_lang["editlang_help_export_success"], "I", "S");
                         print $gm_lang["editlang_help_export_success"]."<br />";
                    }
               	else {
                         WriteToLog($gm_lang["editlang_help_no_export_success"], "E", "S");
                         print $gm_lang["editlang_lang_export_success"]."<br />";
                    }
               	fclose($handle_help);
               }
          }
		break;
	case "compare" :
		print "<form name=\"langdiff_form\" method=\"get\" action=\"$SCRIPT_NAME\">";
		print "<input type=\"hidden\" name=\"action\" value=\"compare\" />";
		print "<input type=\"hidden\" name=\"execute\" value=\"true\" />";
		print "<table class=\"center $TEXT_DIRECTION\">";
		print "<tr><td class=\"topbottombar\" colspan=\"3\">";
		print $gm_lang["compare_lang_utility"];
	     print "</td></tr>";
		print "<tr>";
		print "<td class=\"shade1\">";
		print $gm_lang["new_language"];
		print ":";
		print_help_link("new_language_help", "qm");
		print "<br />";
		print "<select name=\"language1\">";
		foreach ($Sorted_Langs as $key => $value){
			print "\n\t\t\t<option value=\"$key\"";
			if ($key == $language1) print " selected=\"selected\"";
			print ">".$gm_lang["lang_name_".$key]."</option>";
		}
		print "</select>";
		print "</td>";
		print "<td class=\"shade1\">";
		print $gm_lang["old_language"];
		print ":";
		print_help_link("old_language_help", "qm");
		print "<br />";
		print "<select name=\"language2\">";
		foreach ($Sorted_Langs as $key => $value){
			print "\n\t\t\t<option value=\"$key\"";
			if ($key == $language2) print " selected=\"selected\"";
			print ">".$gm_lang["lang_name_".$key]."</option>";
		}
		print "</select>";
		print "</td>";

		print "<td class=\"shade1 center\">";
		print "<input type=\"submit\" value=\"" . $gm_lang["compare"] . "\" />";
		print "</td>";
		print "</tr>";
	     print  "<tr><td class=\"center\" colspan=\"4\"><a href=\"editlang.php\"><b>";
	     print $gm_lang["lang_back"];
	     print "</b></a></td></tr>";
		print "</table>";
		print "</form>";
          if (isset($execute)) {
               $d_gm_lang["comparing"] = $gm_lang["comparing"];
               $d_gm_lang["no_additions"] = $gm_lang["no_additions"];
               $d_gm_lang["additions"] = $gm_lang["additions"];
               $d_gm_lang["subtractions"] = $gm_lang["subtractions"];
               $d_gm_lang["no_subtractions"] = $gm_lang["no_subtractions"];

               $lang1 = loadLanguage($language1, true);
               $lang2 = loadLanguage($language2, true);
		     print "<br /><span class=\"subheaders\">".$d_gm_lang["comparing"]."<br />\"".$gm_language[$language1]."\" <---> \"".$gm_language[$language2]."\"</span><br /><br />\n";
               print "<span class=\"subheaders\">".$d_gm_lang["additions"].":</span><table class=\"center $TEXT_DIRECTION\">\n";

               if (file_exists($GM_BASE_DIRECTORY.$gm_language[$language2])) require $GM_BASE_DIRECTORY.$gm_language[$language2];
		     $count=0;
		     foreach($lang1 as $key=>$value) {
                    if (!array_key_exists($key, $lang2)) {
                         print "<tr><td class=\"facts_label\">\$gm_lang[\"$key\"]</td>\n";
                         print "<td class=\"shade1 wrap\">\"$value\";</td></tr>\n";
                         $count++;
		          }
               }
               if ($count==0) {
                    print "<tr><td colspan=\"2\" class=\"shade1\">".$d_gm_lang["no_additions"]."</td></tr>\n";
               }
               print "</table><br /><br />\n";
               print "<span class=\"subheaders\">".$d_gm_lang["subtractions"].":</span><table class=\"facts_table $TEXT_DIRECTION\">\n";
               $count=0;
               foreach($lang2 as $key=>$value) {
                    if (!array_key_exists($key, $lang1)) {
                         print "<tr><td class=\"facts_label\">\$gm_lang[\"$key\"]</td>\n";
                         print "<td class=\"shade1 wrap\">\"$value\";</td></tr>\n";
                         $count++;
                    }
               }
               if ($count==0) {
                    print "<tr><td colspan=\"2\" class=\"shade1\">".$d_gm_lang["no_subtractions"]."</td></tr>\n";
               }
               print "</table><br /><br />\n";

               print "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["hline"]["other"]."\" width=\"100%\" height=\"6\" alt=\"\" /><br />\n";
               print "<span class=\"subheaders\">".$d_gm_lang["comparing"]."<br />\"".$factsfile[$language1]."\" <---> \"".$factsfile[$language2]."\"<br /><br /></span>\n";
               $factsarray=array();
               require $GM_BASE_DIRECTORY.$factsfile[$language1];
               $lang1 = $factarray;
               $factarray=array();
               if (file_exists($GM_BASE_DIRECTORY.$factsfile[$language2])) require $GM_BASE_DIRECTORY.$factsfile[$language2];
               print "<span class=\"subheaders\">".$d_gm_lang["additions"].":</span><table class=\"facts_table $TEXT_DIRECTION\">\n";
               $count=0;
               foreach($lang1 as $key=>$value) {
                    if (!array_key_exists($key, $factarray)) {
     		      	print "<tr><td class=\"facts_label\">\$factarray[\"$key\"]</td>\n";
     		      	print "<td class=\"shade1 wrap\">\"$value\";</td></tr>\n";
     		      	$count++;
                    }
               }
               if ($count==0) {
                    print "<tr><td colspan=\"2\" class=\"shade1\">".$d_gm_lang["no_additions"]."</td></tr>\n";
               }
               print "</table><br /><br />\n";
               print "<span class=\"subheaders\">".$d_gm_lang["subtractions"].":</span><table class=\"facts_table $TEXT_DIRECTION\">\n";
               $count=0;
               foreach($factarray as $key=>$value) {
                    if (!array_key_exists($key, $lang1)) {
     		      	print "<tr><td class=\"facts_label\">\$gm_lang[\"$key\"]</td>\n";
     		      	print "<td class=\"shade1 wrap\">\"$value\";</td></tr>\n";
     		      	$count++;
                    }
               }
               if ($count==0) {
                    print "<tr><td colspan=\"2\" class=\"shade1\">".$d_gm_lang["no_subtractions"]."</td></tr>\n";
               }
               print "</table><br /><br />\n";

               if (file_exists($confighelpfile[$language2])) {
                    print "<img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["hline"]["other"]."\" width=\"100%\" height=\"6\" alt=\"\" /><br />\n";
                    print "<span class=\"subheaders\">".$d_gm_lang["comparing"]."<br />\"".$confighelpfile[$language1]."\" <---> \"".$confighelpfile[$language2]."\"</span><br /><br />\n";
                    $lang1 = loadLanguage($language1, true, true);
                    $lang2 = loadLanguage($language2, true, true);
                    print "<span class=\"subheaders\">".$d_gm_lang["additions"].":</span><table class=\"facts_table $TEXT_DIRECTION\">\n";
                    $count=0;
                    foreach($lang1 as $key=>$value) {
                         if (!array_key_exists($key, $lang2)) {
                              print "<tr><td class=\"facts_label\">\$gm_lang[\"$key\"]</td>\n";
                              print "<td class=\"shade1 wrap\">\"$value\";</td></tr>\n";
                              $count++;
                         }
                    }
                    if ($count==0) {
                         print "<tr><td colspan=\"2\" class=\"shade1\">".$d_gm_lang["no_additions"]."</td></tr>\n";
                    }
                    
                    print "</table><br /><br />\n";
                    print "<span class=\"subheaders\">".$d_gm_lang["subtractions"].":</span><table class=\"facts_table $TEXT_DIRECTION\">\n";
                    $count=0;
                    foreach($lang2 as $key=>$value) {
     		      	if (!array_key_exists($key, $lang1)) {
                              print "<tr><td class=\"facts_label\">\$gm_lang[\"$key\"]</td>\n";
                              print "<td class=\"shade1 wrap\">\"$value\";</td></tr>\n";
                              $count++;
                         }
                    }
                    if ($count==0) {
                         print "<tr><td colspan=\"2\" class=\"shade1\">".$d_gm_lang["no_subtractions"]."</td></tr>\n";
                    }
                    print "</table><br /><br />\n";
		    }
          }
          print "<br />";
          break;
	default :?>
		<br />
		<table class="center <?php print $TEXT_DIRECTION ?>">
		<tr>
			<td class="topbottombar center" colspan="2">
				<?php print $gm_lang["edit_langdiff"]; ?>
			</td>
		</tr>
		<tr>
			<td class="shade1"><?php
				// BOM Check is no longer necessary for plain text files
				print_help_link("bom_check_help", "qm");
				print "<a href=\"editlang.php?action=bom\">".$gm_lang["bom_check"]."</a>";
	    	?></td>
	      	<td class="shade1"><?php
				print_help_link("edit_lang_utility_help", "qm");
	      		print "<a href=\"editlang.php?action=edit\">".$gm_lang["edit_lang_utility"]."</a>";
	    	?></td>
	    </tr>
	    <tr>
	    	<td class="shade1"><?php
	    		print_help_link("lang_debug_help", "qm");
	        	print "<a href=\"editlang.php?action=debug\">".$gm_lang["lang_debug"]."</a>";
	    	?></td>
		  	<td class="shade1"><?php
				print_help_link("export_lang_utility_help", "qm");
		  		print "<a href=\"editlang.php?action=export\">".$gm_lang["export_lang_utility"]."</a>";
			?></td>
		</tr>
		<tr>
			<td class="shade1"><?php
				print_help_link("translation_forum_desc", "qm"); ?>
				<a href="http://www.genmod.net/forum/viewforum.php?f=4" target="_blank" ><?php
				print $gm_lang["translation_forum"];
	      	?></td>
		  	<td class="shade1"><?php
				print_help_link("compare_lang_utility_help", "qm");		  	
	      		print "<a href=\"editlang.php?action=compare\">".$gm_lang["compare_lang_utility"]."</a>";
		  	?></td>
		</tr>
		<tr>
    	          <td class="shade1"><?php print_help_link("add_new_language_help", "qm"); ?><a href="changelanguage.php?action=addnew"><?php print $gm_lang["add_new_language"];?></a>
	 	    </td>
      		<td class="shade1"><?php print_help_link("help_changelanguage.php", "qm"); ?><a href="changelanguage.php?action=editold"><?php print $gm_lang["enable_disable_lang"];?></a>
	     	<?php
	     	if (!file_exists($INDEX_DIRECTORY . "lang_settings.php")) {
	     		print "<br /><span class=\"error\">";
	     		print $gm_lang["LANGUAGE_DEFAULT"];
	     		print "</span>";
         	}
	     	?>      
	  	</td>
	  </tr>
          <td class="shade1"><?php print_help_link("load_english_help", "qm", "load_english"); ?><a href="editlang.php?action=loadenglish"><?php print $gm_lang["load_english"];?></a>
          </td>
          <td class="shade1">&nbsp;</td> 
       </tr>	
		<tr>
		  	<td colspan="2">
		  	<div class="center">
				<a href="admin.php"><b><?php print $gm_lang["lang_back_admin"];?></a>
			</div>
			</td>
		</tr>
		</table>
		<br />
		<?php
}
?>
</div>
<?php

//-- load file for language settings
require($GM_BASE_DIRECTORY . "includes/lang_settings_std.php");
$Languages_Default = true;
if (file_exists($INDEX_DIRECTORY . "lang_settings.php")) {
	$DefaultSettings = $language_settings;		// Save default settings, so we can merge properly
	require($INDEX_DIRECTORY . "lang_settings.php");
	$ConfiguredSettings = $language_settings;	// Save configured settings, same reason
	$language_settings = array_merge($DefaultSettings, $ConfiguredSettings);	// Copy new langs into config
	unset($DefaultSettings);
	unset($ConfiguredSettings);		// We don't need these any more
	$Languages_Default = false;
}
	
/* Re-build the various language-related arrays
 *		Note:
 *		This code existed in both lang_settings_std.php and in lang_settings.php.
 *		It has been removed from both files and inserted here, where it belongs.
 */
$languages 				= array();
$gm_lang_use 			= array();
$gm_lang 				= array();
$lang_short_cut 		= array();
$lang_langcode 			= array();
$gm_language 			= array();
$confighelpfile 		= array();
$helptextfile 			= array();
$flagsfile 				= array();
$factsfile 				= array();
$factsarray 			= array();
$gm_lang_name 			= array();
$langcode				= array();
$ALPHABET_upper			= array();
$ALPHABET_lower			= array();
$DATE_FORMAT_array		= array();
$TIME_FORMAT_array		= array();
$WEEK_START_array		= array();
$TEXT_DIRECTION_array	= array();
$NAME_REVERSE_array		= array();

foreach ($language_settings as $key => $value) {
	$languages[$key] 			= $value["gm_langname"];
	$gm_lang_use[$key]			= $value["gm_lang_use"];
	$gm_lang[$key]				= $value["gm_lang"];
	$lang_short_cut[$key]		= $value["lang_short_cut"];
	$lang_langcode[$key]		= $value["langcode"];
	$gm_language[$key]			= $value["gm_language"];
	$confighelpfile[$key]		= $value["confighelpfile"];
	$helptextfile[$key]			= $value["helptextfile"];
	$flagsfile[$key]			= $value["flagsfile"];
	$factsfile[$key]			= $value["factsfile"];
	$ALPHABET_upper[$key]		= $value["ALPHABET_upper"];
	$ALPHABET_lower[$key]		= $value["ALPHABET_lower"];
	$DATE_FORMAT_array[$key]		= $value["DATE_FORMAT"];
	$TIME_FORMAT_array[$key]		= $value["TIME_FORMAT"];;
	$WEEK_START_array[$key]		= $value["WEEK_START"];
	$TEXT_DIRECTION_array[$key]	= $value["TEXT_DIRECTION"];
	$NAME_REVERSE_array[$key]	= $value["NAME_REVERSE"];
	
	$gm_lang["lang_name_$key"]	= $value["gm_lang"];
	
	$dDummy = $value["langcode"];
	$ct = strpos($dDummy, ";");
	while ($ct > 1) {
		$shrtcut = substr($dDummy,0,$ct);
		$dDummy = substr($dDummy,$ct+1);
		$langcode[$shrtcut]		= $key;
		$ct = strpos($dDummy, ";");
	}
}
	
loadEnglish();
loadLanguage($LANGUAGE);
print_footer();
?>
