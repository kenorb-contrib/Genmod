<?php
/**
 * Customizable FAQ page
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
 * This Page Is Valid XHTML 1.0 Transitional! > 01 September 2005
 *
 * @package Genmod
 * @subpackage Charts
 * @version $Id: faq.php,v 1.7 2006/04/09 15:53:27 roland-d Exp $
 */
 
/**
 * Inclusion of the configuration file
*/
require("config.php");

/**
 * Inclusion of the language files
*/
/*
require $GM_BASE_DIRECTORY.$confighelpfile["english"];
if (file_exists($GM_BASE_DIRECTORY.$confighelpfile[$LANGUAGE])) require $GM_BASE_DIRECTORY.$confighelpfile[$LANGUAGE];
*/
global $GM_IMAGES, $faqs;

if (userGedcomAdmin($gm_username)) $canconfig = true;
else $canconfig = false;
if (!isset($action)) $action = "show";
if (!isset($adminedit) && $canconfig) $adminedit = true;
else if (!isset($adminedit)) $adminedit = false;

// -- print html header information
$gm_lang["faq_page"] = "Frequently Asked Questions";
print_header($gm_lang["faq_page"]);

// NOTE: Commit the faq data to the DB
if ($action=="commit") {
	if ($type == "update") {
		$faqs = get_faq_data();
		if (isset($faqs[$order])) {
			foreach ($faqs as $key => $item) {
				if ($key >= $order) {
					$sql = "UPDATE ".$TBLPREFIX."blocks SET b_order='".($key+1)."' WHERE b_id='".$faqs[$key]["header"]["pid"]."' and b_location='header'";;
					$res = dbquery($sql);
					$sql = "UPDATE ".$TBLPREFIX."blocks SET b_order='".($key+1)."' WHERE b_id='".$faqs[$key]["body"]["pid"]."' and b_location='body'";
					$res = dbquery($sql);
				}
			}
		}
		$sql = "UPDATE ".$TBLPREFIX."blocks SET b_order='".$order."', b_config='".$DBCONN->escapeSimple(serialize($header))."' WHERE b_id='".$pidh."' and b_username='".$GEDCOM."' and b_location='header'";
		$res = dbquery($sql);
		$sql = "UPDATE ".$TBLPREFIX."blocks SET b_order='".$order."', b_config='".$DBCONN->escapeSimple(serialize($body))."' WHERE b_id='".$pidb."'  and b_username='".$GEDCOM."' and b_location='body'";
		$res = dbquery($sql);
		WriteToLog("FAQ item has been edited.<br />Header ID: ".$pidh.".<br />Body ID: ".$pidb, "I", "G", $GEDCOM);
		$action = "show";
	}
	else if ($type == "delete") {
		$sql = "DELETE FROM ".$TBLPREFIX."blocks WHERE b_order='".$id."' AND b_name='faq' AND b_username='".$GEDCOM."'";
		$res = dbquery($sql);
		WriteToLog("FAQ item has been deleted.<br />Header ID: ".$pidh.".<br />Body ID: ".$pidb, "I", "G", $GEDCOM);
		$action = "show";
	}
	else if ($type == "add") {
		$faqs = get_faq_data();
		if (isset($faqs[$order])) {
			foreach ($faqs as $key => $item) {
				if ($key >= $order) {
					$sql = "UPDATE ".$TBLPREFIX."blocks SET b_order='".($key+1)."' WHERE b_id='".$faqs[$key]["header"]["pid"]."' and b_location='header'";;
					$res = dbquery($sql);
					$sql = "UPDATE ".$TBLPREFIX."blocks SET b_order='".($key+1)."' WHERE b_id='".$faqs[$key]["body"]["pid"]."' and b_location='body'";
					$res = dbquery($sql);
				}
			}
		}
		$newid = get_next_id("blocks", "b_id");
		$sql = "INSERT INTO ".$TBLPREFIX."blocks VALUES ($newid, '".$GEDCOM."', 'header', '$order', 'faq', '".$DBCONN->escapeSimple(serialize($header))."')";
		$res = dbquery($sql);
		$sql = "INSERT INTO ".$TBLPREFIX."blocks VALUES (".($newid+1).", '".$GEDCOM."', 'body', '".$order."', 'faq', '".$DBCONN->escapeSimple(serialize($body))."')";
		$res = dbquery($sql);
		WriteToLog("FAQ item has been added.<br />Header ID: ".$newid.".<br />Body ID: ".($newid+1), "I", "G", $GEDCOM);
		$action = "show";
	}
	else if ($type == "moveup") {
		$faqs = get_faq_data();
		if (isset($faqs[$id-1])) {
			$sql = "UPDATE ".$TBLPREFIX."blocks SET b_order='".($id)."' WHERE b_id='".$faqs[$id-1]["header"]["pid"]."' and b_location='header'";;
			$res = dbquery($sql);
			$sql = "UPDATE ".$TBLPREFIX."blocks SET b_order='".($id)."' WHERE b_id='".$faqs[$id-1]["body"]["pid"]."' and b_location='body'";
			$res = dbquery($sql);
		}
		$sql = "UPDATE ".$TBLPREFIX."blocks SET b_order='".($id-1)."' WHERE b_id='".$pidh."' and b_location='header'";;
		$res = dbquery($sql);
		$sql = "UPDATE ".$TBLPREFIX."blocks SET b_order='".($id-1)."' WHERE b_id='".$pidb."' and b_location='body'";
		$res = dbquery($sql);
		WriteToLog("FAQ item has been moved up.<br />Header ID: ".$pidh.".<br />Body ID: ".$pidb, "I", "G", $GEDCOM);
		$action = "show";
	}
	else if ($type == "movedown") {
		$faqs = get_faq_data();
		if (isset($faqs[$id+1])) {
			$sql = "UPDATE ".$TBLPREFIX."blocks SET b_order='".($id)."' WHERE b_id='".$faqs[$id+1]["header"]["pid"]."' and b_location='header'";;
			$res = dbquery($sql);
			$sql = "UPDATE ".$TBLPREFIX."blocks SET b_order='".($id)."' WHERE b_id='".$faqs[$id+1]["body"]["pid"]."' and b_location='body'";
			$res = dbquery($sql);
		}
		
		$sql = "UPDATE ".$TBLPREFIX."blocks SET b_order='".($id+1)."' WHERE b_id='".$pidh."' and b_location='header'";;
		$res = dbquery($sql);
		$sql = "UPDATE ".$TBLPREFIX."blocks SET b_order='".($id+1)."' WHERE b_id='".$pidb."' and b_location='body'";
		$res = dbquery($sql);
		WriteToLog("FAQ item has been moved down.<br />Header ID: ".$pidh.".<br />Body ID: ".$pidb, "I", "G", $GEDCOM);
		$action = "show";
	}	
	$action = "show";
}

if ($action=="add") {
	$i=1;
	print "<form name=\"addfaq\" method=\"post\" action=\"faq.php\">";
	print "<input type=\"hidden\" name=\"action\" value=\"commit\" />";
	print "<input type=\"hidden\" name=\"type\" value=\"add\" />";
	print "<table class=\"center list_table $TEXT_DIRECTION\">";
	print "<tr><td class=\"topbottombar\">";
	print_help_link("add_faq_item_help","qm","add_faq_item");
	print $gm_lang["add_faq_item"]."</td></tr>";
	print "<tr><td class=\"shade2\">";
	print_help_link("add_faq_header_help","qm","add_faq_header");
	print $gm_lang["add_faq_header"]."</td></tr>";
	print "<tr><td class=\"shade1\"><input type=\"text\" name=\"header\" size=\"90\" tabindex=\"".$i++."\" /></td></tr>";
	print "<tr><td class=\"shade2\">";
	print_help_link("add_faq_body_help","qm","add_faq_body");
	print $gm_lang["add_faq_body"]."</td></tr>";
	print "<tr><td class=\"shade1\"><textarea name=\"body\" rows=\"10\" cols=\"90\" tabindex=\"".$i++."\"></textarea></td></tr>";
	print "<tr><td class=\"shade2\">";
	print_help_link("add_faq_order_help","qm","add_faq_order");
	print $gm_lang["add_faq_order"]."</td></tr>";
	print "<tr><td class=\"shade1\"><input type=\"text\" name=\"order\" size=\"3\" tabindex=\"".$i++."\" /></td></tr>";
	print "<tr><td class=\"topbottombar\"><input type=\"submit\" value=\"".$gm_lang["save"]."\" tabindex=\"".$i++."\" />";
	print "&nbsp;<input type=\"button\" value=\"".$gm_lang["cancel"]."\" onclick=window.location=\"faq.php\"; tabindex=\"".$i++."\" /></td></tr>";
	print "</table>";
	print "</form>";
}

if ($action == "edit") {
	if (!isset($id)) {
		$error = true;
		$error_message =  $gm_lang["no_id"];
		$action = "show";
	}
	else {
		$faqs = get_faq_data($id);
		
		$i=1;
		print "<form name=\"editfaq\" method=\"post\" action=\"faq.php\">";
		print "<input type=\"hidden\" name=\"action\" value=\"commit\" />";
		print "<input type=\"hidden\" name=\"type\" value=\"update\" />";
		print "<input type=\"hidden\" name=\"id\" value=\"".$id."\" />";
		print "<table class=\"center list_table $TEXT_DIRECTION\">";
		print "<tr><td class=\"topbottombar\">";
		print_help_link("edit_faq_item_help","qm","edit_faq_item");
		print $gm_lang["edit_faq_item"]."</td></tr>";
		foreach ($faqs as $id => $data) {
			print "<input type=\"hidden\" name=\"pidh\" value=\"".$data["header"]["pid"]."\" />";
			print "<input type=\"hidden\" name=\"pidb\" value=\"".$data["body"]["pid"]."\" />";
			print "<tr><td class=\"shade2\">";
			print_help_link("add_faq_header_help","qm","add_faq_header");
			print $gm_lang["add_faq_header"]."</td></tr>";
			print "<tr><td class=\"shade1\"><input type=\"text\" name=\"header\" size=\"90\" tabindex=\"".$i++."\" value=\"".$data["header"]["text"]."\" /></td></tr>";
			print "<tr><td class=\"shade2\">";
			print_help_link("add_faq_body_help","qm","add_faq_body");
			print $gm_lang["add_faq_body"]."</td></tr>";
			print "<tr><td class=\"shade1\"><textarea name=\"body\" rows=\"10\" cols=\"90\" tabindex=\"".$i++."\">".html_entity_decode(stripslashes($data["body"]["text"]))."</textarea></td></tr>";
			print "<tr><td class=\"shade2\">";
			print_help_link("add_faq_order_help","qm","add_faq_order");
			print $gm_lang["add_faq_order"]."</td></tr>";
			print "<tr><td class=\"shade1\"><input type=\"text\" name=\"order\" size=\"3\" tabindex=\"".$i++."\" value=\"".$id."\" /></td></tr>";
		}
		print "<tr><td class=\"topbottombar\"><input type=\"submit\" value=\"".$gm_lang["save"]."\" tabindex=\"".$i++."\" />";
		print "&nbsp;<input type=\"button\" value=\"".$gm_lang["cancel"]."\" onclick=window.location=\"faq.php\"; tabindex=\"".$i++."\" /></td></tr>";
		print "</table>";
		print "</form>";
	}
}

if ($action == "show") {
	$faqs = get_faq_data();
	print "<table class=\"list_table width100\">";
	if (count($faqs) == 0 && $canconfig) {
		print "<tr><td class=\"width20 list_label\">";
		print_help_link("add_faq_item_help","qm","add_faq_item");
		print "<a href=\"faq.php?action=add\">".$gm_lang["add_faq_item"]."</a>";
		print "</td></tr>";
	}
	else if (count($faqs) == 0 && !$canconfig) print "<tr><td class=\"error center\">".$gm_lang["no_faq_items"]."</td></tr>";
	else {
		// NOTE: Add and preview link
		if ($canconfig) print "<tr>";
		if ($canconfig && $adminedit) {
			print "<td class=\"shade2 center\" colspan=\"2\">";
			print_help_link("add_faq_item_help","qm","add_faq_item");
			print "<a href=\"faq.php?action=add\">".$gm_lang["add"]."</a></td>";
		}
		if ($canconfig) print "<td class=\"shade2 center\" colspan=\"2\">";
		
		if ($canconfig && $adminedit) {
			print_help_link("preview_faq_item_help","qm","preview_faq_item");
			print "<a href=\"faq.php?adminedit=0\">".$gm_lang["preview"]."</a>";
		}
		else if ($canconfig && !$adminedit) {
			print_help_link("restore_faq_edits_help","qm","restore_faq_edits");
			print "<a href=\"faq.php?adminedit=1\">".$gm_lang["edit"]."</a>";
		}
		print "</td>";
		
		if ($canconfig && $adminedit) {
			if (isset($error)) print "<td class=\"topbottombar red\">".$error_message."</td></tr>";
			else print "<td class=\"topbottombar\">&nbsp;</td></tr>";
		}
		else print "</tr>";
		
		foreach($faqs as $id => $data) {
			if ($data["header"] && $data["body"]) {
				print "<tr>";
				// NOTE: Print the position of the current item
				if ($canconfig && $adminedit) {
					print "<td class=\"shade2 width20 $TEXT_DIRECTION\" colspan=\"4\">";
					print $gm_lang["position_item"].": ".$id;
					print "</td>";
				}
				// NOTE: Print the header of the current item
				print "<td class=\"list_label wrap\">".html_entity_decode($data["header"]["text"])."</td></tr>";
				print "<tr>";
				// NOTE: Print the edit options op the current item
				if ($canconfig && $adminedit) {
					print "<td class=\"shade1 center\">";
					print_help_link("moveup_faq_item_help","qm","moveup_faq_item");
					print "<a href=\"faq.php?action=commit&amp;type=moveup&amp;id=".$id."&amp;pidh=".$data["header"]["pid"]."&amp;pidb=".$data["body"]["pid"]."\"><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["uarrow"]["other"]."\" border=\"0\" alt=\"\" /></a>\n</td>";
					print "\n<td class=\"shade1 center\">";
					print_help_link("movedown_faq_item_help","qm","movedown_faq_item");
					print "<a href=\"faq.php?action=commit&amp;type=movedown&amp;id=".$id."&amp;pidh=".$data["header"]["pid"]."&amp;pidb=".$data["body"]["pid"]."\"><img src=\"".$GM_IMAGE_DIR."/".$GM_IMAGES["darrow"]["other"]."\" border=\"0\" alt=\"\" /></a>";
					print "\n</td>\n<td class=\"shade1 center\">";					
					print_help_link("edit_faq_item_help","qm","edit_faq_item");
					print "<a href=\"faq.php?action=edit&amp;id=".$id."\">".$gm_lang["edit"]."</a>";
					print "\n</td><td class=\"shade1 center\">";
					print_help_link("delete_faq_item_help","qm","delete_faq_item");
					print "<a href=\"faq.php?action=commit&amp;type=delete&amp;id=".$id."&amp;pidh=".$data["header"]["pid"]."&amp;pidb=".$data["body"]["pid"]."\" onclick=\"return confirm('".$gm_lang["confirm_faq_delete"]."');\">".$gm_lang["delete"]."</a>\n";
					print "</td>";
				}
				// NOTE: Print the body text op the current item
				print "<td class=\"list_value wrap\">".nl2br(html_entity_decode(stripslashes($data["body"]["text"])))."</td></tr>";				
			}
		}
	}
	print "</table>";
}
if ($action != "show") {
	?>
	<script language="JavaScript" type="text/javascript">
	<!--
		document.<?php print $action;?>faq.header.focus();
	//-->
	</script>
	<?php
}
print_footer();
?>
