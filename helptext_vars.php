<?php
/**
 * File contains var's to glue Help_text for Genmod together
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
 * @author Genmod Development Team
 * @package Genmod
 * @subpackage Help
 * @version $Id$
 */

// The variables in this file are used to glue together other var's in the help_text.xx.php
// Do NOT put any var's, that need to be translated, in this file
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "intrusion.php";
}

$gm_lang["edit_RESN_help"]			= "#gm_lang[RESN_help]#";

$gm_lang["help_aliveinyear.php"]	= "#gm_lang[alive_in_year_help]#";

//General
$gm_lang["start_ahelp"]			= "<div class=\"list_value_wrap\"><center class=\"error\">#gm_lang[start_admin_help]#</center>";
$gm_lang["end_ahelp"]				= "<center class=\"error\">#gm_lang[end_admin_help]#</center></div>";
$gm_lang["redast"]				= "<span class=\"error\"<b>*</b></span>";

// lower to UPPER conversions
$gm_lang["upper_mygedview"]			= Str2Upper($gm_lang["mygedview"]);

// Header
$gm_lang["header_help_items"]			= "<a name=\"header\">&nbsp;</a>#gm_lang[header_help]#<br /><a name=\"header_search\"></a><a href=\"#header\">$UpArrow </a>#gm_lang[header_search_help]#<br /><a name=\"header_lang_select\"></a><a href=\"#header\">$UpArrow </a>#gm_lang[header_lang_select_help]#<br /><a name=\"header_user_links\"></a><a href=\"#header\">$UpArrow </a>#gm_lang[header_user_links_help]#<br /><a name=\"header_favorites\"></a><a href=\"#header\">$UpArrow </a>#gm_lang[header_favorites_help]#<br /><a name=\"header_theme\"></a><a href=\"#header\">$UpArrow </a>#gm_lang[header_theme_help]#<br />";
$gm_lang["menu_help_items"]			= "<a name=\"menu\">&nbsp;</a>#gm_lang[helpmenu]#<a name=\"menu_fam\"></a><a href=\"#menu\">$UpArrow </a>#gm_lang[menu_famtree_help]#<br /><a name=\"menu_myged\"></a><a href=\"#menu\">$UpArrow </a>#gm_lang[menu_myged_help]#<a name=\"menu_charts\"></a><a href=\"#menu\">$UpArrow </a>#gm_lang[menu_charts_help]#<a name=\"menu_lists\"></a><a href=\"#menu\">$UpArrow </a>#gm_lang[menu_lists_help]#<a name=\"menu_annical\"></a><a href=\"#menu\">$UpArrow </a>#gm_lang[menu_annical_help]#<a name=\"menu_clip\"></a><a href=\"#menu\">$UpArrow </a>#gm_lang[menu_clip_help]#<a name=\"menu_search\"></a><a href=\"#menu\">$UpArrow </a>#gm_lang[menu_search_help]#<a name=\"menu_rslog\"></a><a name=\"menu_help\"></a><a href=\"#menu\">$UpArrow </a>#gm_lang[menu_help_help]#<br />";
$gm_lang["index_portal_help_blocks"]		= "<a href=\"#top\">$UpArrow </a><a name=\"index_portal\">&nbsp;</a>#gm_lang[index_portal_head_help]##gm_lang[index_portal_help]#<br /><a name=\"index_welcome\"></a><a href=\"#index_portal\">$UpArrow </a>#gm_lang[index_welcome_help]#<br /><a name=\"index_login\"></a><a href=\"#index_portal\">$UpArrow </a>#gm_lang[index_login_help]#<br /><a name=\"index_events\"></a><a href=\"#index_portal\">$UpArrow </a>#gm_lang[index_events_help]#<br /><a name=\"index_onthisday\"></a><a href=\"#index_portal\">$UpArrow </a>#gm_lang[index_onthisday_help]#<br /><a name=\"index_favorites\"></a><a href=\"#index_portal\">$UpArrow </a>#gm_lang[index_favorites_help]#<br /><a name=\"index_stats\"></a><a href=\"#index_portal\">$UpArrow </a>#gm_lang[index_stats_help]#<br /><a name=\"index_common_surnames\"></a><a href=\"#index_portal\">$UpArrow </a>#gm_lang[index_common_names_help]#<br /><a name=\"index_media\"></a><a href=\"#index_portal\">$UpArrow </a>#gm_lang[index_media_help]#<br /><a name=\"index_loggedin\"></a><a href=\"#index_portal\">$UpArrow </a>#gm_lang[index_loggedin_help]#<br /><a name=\"recent_changes\"></a><a href=\"#index_portal\">$UpArrow </a>#gm_lang[recent_changes_help]#<br />";

//Help
$gm_lang["help_help_items"]			= "#gm_lang[help_help]#<br />#gm_lang[help_page_help]##gm_lang[help_content_help]##gm_lang[help_faq_help]##gm_lang[help_HS_help]##gm_lang[help_qm_help]#";
$gm_lang["def_help_items"]			= "<a name=\"def\">&nbsp;</a>#gm_lang[def_help]#<br /><a name=\"def_gedcom\"></a><a href=\"#def\">$UpArrow </a>#gm_lang[def_gedcom_help]#<br /><a name=\"def_gedcom_date\"></a><a href=\"#def\">$UpArrow </a>#gm_lang[def_gedcom_date_help]#<br /><a name=\"def_pdf_format\"></a><a href=\"#def\">$UpArrow </a>#gm_lang[def_pdf_format_help]#<br /><a name=\"def_gm\"></a><a href=\"#def\">$UpArrow </a>#gm_lang[def_gm_help]#<br /><a name=\"def_portal\"></a><a href=\"#def\">$UpArrow </a>#gm_lang[def_portal_help]#<br /><a name=\"def_theme\"></a><a href=\"#def\">$UpArrow </a>#gm_lang[def_theme_help]#<br />";

// edit_user.php (My account)
$gm_lang["edituser_user_contact_help"]		= "#gm_lang[edituser_contact_meth_help]#<br /><br /><b>#gm_lang[messaging]#</b><br />#gm_lang[mail_option1_help]#<br /><b>#gm_lang[messaging2]#</b><br />#gm_lang[mail_option2_help]#<br /><b>#gm_lang[mailto]#</b><br />#gm_lang[mail_option3_help]#<br /><b>#gm_lang[no_messaging]#</b><br />#gm_lang[mail_option4_help]#<br />";
$gm_lang["help_edituser.php"]			= "~".Str2Upper($gm_lang["myuserdata"])."~<br /><br />#gm_lang[edituser_my_account_help]#<br />#gm_lang[more_help]#";

// user_admin.php
$gm_lang["help_useradmin.php"]			= "#gm_lang[useradmin_help]#<br /><br />#gm_lang[is_user_help]#<br />#gm_lang[more_help]#";
$gm_lang["useradmin_user_contact_help"]	= "#gm_lang[is_user_help]#<br /><br />#gm_lang[edituser_user_contact_help]#";
$gm_lang["useradmin_change_lang_help"]		= "#gm_lang[is_user_help]#<br /><br />#gm_lang[edituser_change_lang_help]#";
$gm_lang["useradmin_email_help"]		= "#gm_lang[is_user_help]#<br /><br />#gm_lang[edituser_email_help]#";
$gm_lang["useradmin_user_theme_help"]		= "#gm_lang[is_user_help]#<br /><br />#gm_lang[edituser_user_theme_help]#";
// these need to be checked and maybe moved to the help_text.en.php
$gm_lang["useradmin_username_help"]		= "#gm_lang[is_user_help]#<br /><br />#gm_lang[edituser_username_help]#";
$gm_lang["useradmin_firstname_help"]		= "#gm_lang[is_user_help]#<br /><br />#gm_lang[edituser_firstname_help]#";
$gm_lang["useradmin_lastname_help"]		= "#gm_lang[is_user_help]#<br /><br />#gm_lang[edituser_lastname_help]#";
$gm_lang["useradmin_password_help"]		= "#gm_lang[is_user_help]#<br /><br />#gm_lang[edituser_password_help]#";
$gm_lang["useradmin_conf_password_help"]	= "#gm_lang[is_user_help]#<br /><br />#gm_lang[edituser_conf_password_help]#";
$gm_lang["edit_useradmin_help"]		= "#gm_lang[useradmin_edit_user_help]#<br />#gm_lang[more_help]#";

// general help items used in help welcome page
$gm_lang["general_help"]			= "<a name=\"header_general\">&nbsp;</a>#gm_lang[header_general_help]##gm_lang[best_display_help]#<br />#gm_lang[preview_help]#<br />";

// page help for the Welcome page
$gm_lang["help_index.php"]			= "#gm_lang[index_help]#<br />#gm_lang[index_portal_help_blocks]#<br /><a href=\"#top\">$UpArrow </a>#gm_lang[header_help_items]#<br /><br /><a href=\"#top\">$UpArrow </a>#gm_lang[menu_help_items]#<br /><a href=\"#top\">$UpArrow </a>#gm_lang[general_help]#<br /><a href=\"#top\">$UpArrow </a>#gm_lang[def_help_items]#<br />";

// page help for the MyGedView page
$gm_lang["mygedview_portal_help_blocks"]	= "<a name=\"mygedview_portal\"></a>#gm_lang[mygedview_portal_help]#<br /><a href=\"#mygedview_portal\">$UpArrow </a><a name=\"mygedview_welcome\"></a>#gm_lang[mygedview_welcome_help]#<br /><a href=\"#mygedview_portal\">$UpArrow </a><a name=\"mygedview_customize\"></a>#gm_lang[mygedview_customize_help]#<br /><a href=\"#mygedview_portal\">$UpArrow </a><a name=\"mygedview_message\"></a>#gm_lang[mygedview_message_help]#<br /><a href=\"#mygedview_portal\">$UpArrow </a><a name=\"mygedview_events\"></a>#gm_lang[index_events_help]#<br /><a href=\"#mygedview_portal\">$UpArrow </a><a name=\"mygedview_onthisday\"></a>#gm_lang[index_onthisday_help]#<br /><a href=\"#mygedview_portal\">$UpArrow </a><a name=\"mygedview_favorites\"></a>#gm_lang[mygedview_favorites_help]#<br /><a href=\"#mygedview_portal\">$UpArrow </a><a name=\"mygedview_stats\"></a>#gm_lang[index_stats_help]#<br /><a href=\"#mygedview_portal\">$UpArrow </a><a name=\"mygedview_myjournal\"></a>#gm_lang[mygedview_myjournal_help]#<br /><a href=\"#mygedview_portal\">$UpArrow </a><a name=\"mygedview_media\"></a>#gm_lang[index_media_help]#<br /><a href=\"#mygedview_portal\">$UpArrow </a><a name=\"mygedview_loggedin\"></a>#gm_lang[index_loggedin_help]#<br /><a name=\"mygedview_recent_changes\"></a><a href=\"#mygedview_portal\">$UpArrow </a>#gm_lang[recent_changes_help]#<br />";
$gm_lang["index_myged_help"]			= "#gm_lang[mygedview_portal_help_blocks]#<br />";


//Login
$gm_lang["help_login.php"]			= "#gm_lang[login_page_help]#<br />#gm_lang[mygedview_login_help]#";
$gm_lang["help_login_register.php"]		= "~#gm_lang[requestaccount]#~<br /><br />#gm_lang[register_info_01]#";
$gm_lang["help_login_lost_pw.php"]		= "~#gm_lang[lost_pw_reset]#~<br /><br />#gm_lang[pls_note11]#";
$gm_lang["index_login_register_help"]		= "#gm_lang[index_login_help]#<br />#gm_lang[new_user_help]#<br /><br />#gm_lang[new_password_help]#<br />";

//Add Facts
$gm_lang["add_new_facts_help"]			= "#gm_lang[multiple_help]#<br />#gm_lang[add_facts_help]#<br />#gm_lang[add_custom_facts_help]#<br />#gm_lang[add_from_clipboard_help]#<br />#gm_lang[def_gedcom_date_help]#<br />#gm_lang[add_facts_general_help]#";

//Admin Help News Block
$gm_lang["index_gedcom_news_ahelp"]		= "#gm_lang[start_ahelp]#<br />#gm_lang[index_gedcom_news_adm_help]#<br />#gm_lang[end_ahelp]#<br />#gm_lang[index_gedcom_news_help]#";

//Upgrade Utility
$gm_lang["help_upgrade.php"]			="#gm_lang[how_upgrade_help]#<br /><br />#gm_lang[readme_help]#";

//-- Admin
$gm_lang["help_admin.php"]			="~#gm_lang[administration]#~</b><br /><br />#gm_lang[admin_help]#<br /><br />#gm_lang[readme_help]#";

//-- Language editor and configuration
$gm_lang["help_editlang.php"]			="#gm_lang[lang_edit_help]#<br /><br />#gm_lang[translation_forum_help]#<br /><br />#gm_lang[bom_check_help]#<br /><br />#gm_lang[edit_lang_utility_help]#<br /><br />#gm_lang[export_lang_utility_help]#<br /><br />#gm_lang[compare_lang_utility_help]#<br /><br />#gm_lang[add_new_language_help]#<br /><br />#gm_lang[more_help]#";
$gm_lang["help_changelanguage.php"]			="#gm_lang[config_lang_utility_help]##gm_lang[more_help]#";

//-- FAQ List editing tool
$gm_lang["faq_page_help"]	=	"#gm_lang[help_faq.php]#<br /><a href=\"#top\">$UpArrow </a>#gm_lang[preview_faq_item_help]#<br /><a href=\"#top\">$UpArrow </a>#gm_lang[restore_faq_edits_help]#<br /><a href=\"#top\">$UpArrow </a>#gm_lang[add_faq_item_help]#<br /><a href=\"#top\">$UpArrow </a>#gm_lang[edit_faq_item_help]#<br /><a href=\"#top\">$UpArrow </a>#gm_lang[delete_faq_item_help]#<br /><a href=\"#top\">$UpArrow </a>#gm_lang[moveup_faq_item_help]#<br /><a href=\"#top\">$UpArrow </a>#gm_lang[movedown_faq_item_help]#<br /><a href=\"#top\">$UpArrow </a>#gm_lang[add_faq_header_help]#<br /><a href=\"#top\">$UpArrow </a>#gm_lang[add_faq_body_help]#<br /><a href=\"#top\">$UpArrow </a>#gm_lang[add_faq_order_help]#";

//--				G E D C O M
//-- Gedcom Info
$gm_lang["gedcom_info_help"]			= "<div class=\"name_head center\"><b>#gm_lang[help_contents_gedcom_info]#</b></div><br />#gm_lang[def_gedcom_help]#<br /><a href=\"#top\">$UpArrow </a>#gm_lang[def_gedcom_date_help]#<br /><a href=\"#top\">$UpArrow </a>#gm_lang[ppp_levels_help]#";

//-- Add Gedcom
$gm_lang["help_addgedcom.php"]			="#gm_lang[add_gedcom_help]#<br /><br />#gm_lang[add_upload_gedcom_help]#<br />#gm_lang[readme_help]#";
//-- Add new Gedcom
$gm_lang["help_addnewgedcom.php"]		="#gm_lang[add_new_gedcom_help]#<br /><br />#gm_lang[readme_help]#";
//-- Download Gedcom
$gm_lang["help_downloadgedcom.php"]		="#gm_lang[download_gedcom_help]#";
//-- Edit Gedcoms
$gm_lang["help_editgedcoms.php"]		="#gm_lang[edit_gedcoms_help]#";
//-- Edit Config Gedcoms
$gm_lang["help_editconfig_gedcom.php"]		="#gm_lang[edit_config_gedcom_help]##gm_lang[more_config_hjaelp]#<br /><br />#gm_lang[readme_help]#";
//-- Import Gedcom
$gm_lang["help_importgedcom.php"]		="#gm_lang[import_gedcom_help]#";
//-- Upload Gedcom
$gm_lang["help_uploadgedcom.php"]		="#gm_lang[upload_gedcom_help]#<br /><br />#gm_lang[add_upload_gedcom_help]#<br />#gm_lang[readme_help]#";
//-- Validate Gedcom
$gm_lang["help_validategedcom.php"]		="#gm_lang[validate_gedcom_help]#";
//-- Edit Privacy
$gm_lang["help_edit_privacy.php"]		="~".Str2Upper($gm_lang["edit_privacy_title"])."~<br /><br />#gm_lang[edit_privacy_help]##gm_lang[more_config_hjaelp]#<br />#gm_lang[readme_help]#";

//Specials for contents
$vpos = strpos($gm_lang["enter_terms"], ":", 0);
if ($vpos>0) $enter_terms = substr($gm_lang["enter_terms"], 0, $vpos);
else $enter_terms = $gm_lang["enter_terms"];
$vpos = strpos($gm_lang["soundex_search"], ":", 0);
if ($vpos>0) $soundex_search = substr($gm_lang["soundex_search"], 0, $vpos);
else $soundex_search = $gm_lang["soundex_search"];

$gm_lang["help_used_in_contents"]		= "<div class=\"name_head center\"><b>#gm_lang[page_help]#</b></div><br />#gm_lang[help_help_items]#";
$gm_lang["search_used_in_contents"]		= "<div class=\"name_head center\"><b>#gm_lang[search]#</b></div><ul><li><a href=\"#header_search\">#gm_lang[header]#</a><li><a href=\"#menu_search\">#gm_lang[menu]#</a><li><a href=\"#help_search\">#gm_lang[search]#<li><a href=\"#search_enter_terms\">$enter_terms</a><li><a href=\"#soundex_search\">$soundex_search</ul><br /><br /><a href=\"#top\">$UpArrow </a><a name=\"header_search\"></a>#gm_lang[header_search_help]#<br /><br /><a href=\"#top\">$UpArrow </a><a name=\"menu_search\"></a>#gm_lang[menu_search_help]#<br /><a href=\"#top\">$UpArrow </a><a name=\"help_search\"></a>#gm_lang[help_search.php]#<br /><a href=\"#top\">$UpArrow </a><a name=\"search_enter_terms\"></a>#gm_lang[search_enter_terms_help]#<br /><br /><br /><a name=\"soundex_search\"></a><a href=\"#top\">$UpArrow </a>#gm_lang[soundex_search_help]#";


/*-- Var's for Menu Item: Help contents
	The var $gm_lang["help_contents_help"] contains all the vars below.
	example: $gm_lang["h1"] >>> help_index.php will be the var $gm_lang["help_index.php"],
	to be displayed if the text of $gm_lang["welcome_page"] is clicked in the Help Contents
*/
$gm_lang["h1"]		= "help_index.php,welcome_page";
$gm_lang["h2"]		= "index_myged_help,mygedview";
$gm_lang["h3"]		= "help_calendar.php,anniversary_calendar";
$gm_lang["h4"]		= "help_clippings.php,clip_cart";
$gm_lang["h5"]		= "help_descendancy.php,descend_chart";
$gm_lang["h6"]		= "help_edituser.php,editowndata";
$gm_lang["h7"]		= "gedcom_info_help,help_contents_gedcom_info";
$gm_lang["h8"]		= "help_family.php,family_info";
$gm_lang["h9"]		= "help_famlist.php,family_list";
$gm_lang["h10"]	= "header_help_items,header";
$gm_lang["h11"]	= "help_individual.php,indi_info";
$gm_lang["h12"]	= "help_indilist.php,individual_list";
$gm_lang["h13"]	= "help_login.php,login";
$gm_lang["h14"]	= "menu_help_items,menu";
$gm_lang["h15"]	= "help_medialist.php,media_list";
$gm_lang["h16"]	= "help_relationship.php,relationship_chart";
$gm_lang["h17"]	= "best_display_help,resolution";
$gm_lang["h18"]	= "search_used_in_contents,search";
$gm_lang["h19"]	= "help_source.php,source";
$gm_lang["h20"]	= "help_sourcelist.php,source_list";
$gm_lang["h21"]	= "help_pedigree.php,index_header";
$gm_lang["h22"]	= "preview_help,print_preview";
$gm_lang["h23"]	= "help_placelist.php,place_list";
$gm_lang["h24"]	= "help_timeline.php,timeline_chart";
$gm_lang["h25"]	= "help_used_in_contents,page_help";
$gm_lang["h26"]	= "edituser_password_help,password";
$gm_lang["h27"]	= "edituser_username_help,username";
$gm_lang["h28"]	= "add_media_help,add_media_lbl";
$gm_lang["h29"]	= "help_login_register.php,requestaccount";
$gm_lang["h30"]	= "help_login_lost_pw.php,lost_pw_reset";
$gm_lang["h31"]	= "help_ancestry.php,ancestry_chart";
$gm_lang["h32"]	= "help_fanchart.php,fan_chart";
$gm_lang["h33"]	= "help_reportengine.php,reports";
$gm_lang["h34"]	= "def_help_items,definitions";
$gm_lang["h35"]	= "accesskey_viewing_advice_help,accesskeys";
$gm_lang["h36"]	= "help_faq.php,faq_list";
$gm_lang["h37"]	= "hs_title_help,hs_title";

$gm_lang["help_contents_help"] = "";
$i=1;
while (isset($gm_lang["h$i"])) {
	$gm_lang["help_contents_help"] .= "#gm_lang[h$i]#";
	$i++;
}




//-- Help Contents for admin

$gm_lang["ah1"]	= "how_upgrade_help,ah1_help";
$gm_lang["ah2"]	= "help_editconfig.php,ah2_help";
$gm_lang["ah3"]	= "add_upload_gedcom_help,ah3_help";
$gm_lang["ah4"]	= "gedcom_configfile_help,ah4_help";
$gm_lang["ah5"]	= "default_gedcom_help,ah5_help";
$gm_lang["ah6"]	= "delete_gedcom_help,ah6_help";
$gm_lang["ah7"]	= "add_gedcom_help,ah7_help";
$gm_lang["ah8"]	= "add_new_gedcom_help,ah8_help";
$gm_lang["ah9"]	= "download_gedcom_help,ah9_help";
$gm_lang["ah10"]	= "edit_gedcoms_help,ah10_help";
$gm_lang["ah11"]	= "edit_config_gedcom_help,ah11_help";
$gm_lang["ah12"]	= "import_gedcom_help,ah12_help";
$gm_lang["ah13"]	= "upload_gedcom_help,ah13_help";
$gm_lang["ah14"]	= "validate_gedcom_help,ah14_help";
$gm_lang["ah15"]	= "convert_ansi2utf_help,ah15_help";
$gm_lang["ah16"]	= "help_edit_privacy.php,ah16_help";
$gm_lang["ah17"]	= "help_useradmin.php,ah17_help";
$gm_lang["ah18"]	= "help_admin.php,ah18_help";
$gm_lang["ah19"]	= "addmedia_tool_help,ah19_help";
$gm_lang["ah20"]	= "change_indi2id_help,ah20_help";
$gm_lang["ah21"]	= "help_editlang.php,ah21_help";
$gm_lang["ah22_help"]	= "_Readme.txt";
$gm_lang["ah22"]	= "readme_help,ah22_help";
$gm_lang["ah23"]	= "help_changelanguage.php,ah23_help";
$gm_lang["ah24"]	= "um_bu_help,ah24_help";
$gm_lang["ah25"]	= "faq_page_help,ah25_help";

$gm_lang["a_help_contents_help"] = "";
$i=1;
while (isset($gm_lang["ah$i"])) {
	$gm_lang["a_help_contents_help"] .= "#gm_lang[ah$i]#";
	$i++;
}

$gm_lang["admin_help_contents_help"]		=$gm_lang["help_contents_help"].$gm_lang["a_help_contents_help"];

?>
