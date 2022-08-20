<?php
/**
 * File contains var's to glue Help_text for Genmod together
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
 * @author Genmod Development Team
 * @package Genmod
 * @subpackage Help
 * @version $Id: helptext_vars.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

// The variables in this file are used to glue together other var's in the help_text.xx.php
// Do NOT put any var's, that need to be translated, in this file
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "intrusion.php";
}

define("GM_LANG_edit_RESN_help", "#gm_lang[RESN_help]#");

define("GM_LANG_help_aliveinyear.php", "#gm_lang[alive_in_year_help]#");

//General
define("GM_LANG_start_ahelp", "<div class=\"list_value_wrap\"><center class=\"Error\">#gm_lang[start_admin_help]#</center>");
define("GM_LANG_end_ahelp", "<center class=\"Error\">#gm_lang[end_admin_help]#</center></div>");
define("GM_LANG_redast", "<span class=\"Error\"<b>*</b></span>");

// lower to UPPER conversions
define("GM_LANG_upper_mygenmod", Str2Upper(GM_LANG_mygenmod));

// Header
define("GM_LANG_header_help_items", "<a name=\"header\">&nbsp;</a>#gm_lang[header_help]#<br /><a name=\"header_search\"></a><a href=\"#header\">$UpArrow </a>#gm_lang[header_search_help]#<br /><a name=\"header_lang_select\"></a><a href=\"#header\">$UpArrow </a>#gm_lang[header_lang_select_help]#<br /><a name=\"header_user_links\"></a><a href=\"#header\">$UpArrow </a>#gm_lang[header_user_links_help]#<br /><a name=\"header_favorites\"></a><a href=\"#header\">$UpArrow </a>#gm_lang[header_favorites_help]#<br /><a name=\"header_theme\"></a><a href=\"#header\">$UpArrow </a>#gm_lang[header_theme_help]#<br />");
define("GM_LANG_menu_help_items", "<a name=\"menu\">&nbsp;</a>#gm_lang[helpmenu]#<a name=\"menu_fam\"></a><a href=\"#menu\">$UpArrow </a>#gm_lang[menu_famtree_help]#<br /><a name=\"menu_myged\"></a><a href=\"#menu\">$UpArrow </a>#gm_lang[menu_myged_help]#<a name=\"menu_charts\"></a><a href=\"#menu\">$UpArrow </a>#gm_lang[menu_charts_help]#<a name=\"menu_lists\"></a><a href=\"#menu\">$UpArrow </a>#gm_lang[menu_lists_help]#<a name=\"menu_annical\"></a><a href=\"#menu\">$UpArrow </a>#gm_lang[menu_annical_help]#<a name=\"menu_clip\"></a><a href=\"#menu\">$UpArrow </a>#gm_lang[menu_clip_help]#<a name=\"menu_search\"></a><a href=\"#menu\">$UpArrow </a>#gm_lang[menu_search_help]#<a name=\"menu_rslog\"></a><a name=\"menu_help\"></a><a href=\"#menu\">$UpArrow </a>#gm_lang[menu_help_help]#<br />");
define("GM_LANG_index_portal_help_blocks", "<a href=\"#top\">$UpArrow </a><a name=\"index_portal\">&nbsp;</a>#gm_lang[index_portal_head_help]##gm_lang[index_portal_help]#<br /><a name=\"index_welcome\"></a><a href=\"#index_portal\">$UpArrow </a>#gm_lang[index_welcome_help]#<br /><a name=\"index_login\"></a><a href=\"#index_portal\">$UpArrow </a>#gm_lang[index_login_help]#<br /><a name=\"index_events\"></a><a href=\"#index_portal\">$UpArrow </a>#gm_lang[index_events_help]#<br /><a name=\"index_onthisday\"></a><a href=\"#index_portal\">$UpArrow </a>#gm_lang[index_onthisday_help]#<br /><a name=\"index_favorites\"></a><a href=\"#index_portal\">$UpArrow </a>#gm_lang[index_favorites_help]#<br /><a name=\"index_stats\"></a><a href=\"#index_portal\">$UpArrow </a>#gm_lang[index_stats_help]#<br /><a name=\"index_common_surnames\"></a><a href=\"#index_portal\">$UpArrow </a>#gm_lang[index_common_names_help]#<br /><a name=\"index_media\"></a><a href=\"#index_portal\">$UpArrow </a>#gm_lang[index_media_help]#<br /><a name=\"index_loggedin\"></a><a href=\"#index_portal\">$UpArrow </a>#gm_lang[index_loggedin_help]#<br /><a name=\"recent_changes\"></a><a href=\"#index_portal\">$UpArrow </a>#gm_lang[recent_changes_help]#<br />");

//Help
define("GM_LANG_help_help_items", "#gm_lang[help_help]#<br />#gm_lang[help_page_help]##gm_lang[help_content_help]##gm_lang[help_faq_help]##gm_lang[help_HS_help]##gm_lang[help_qm_help]#");
define("GM_LANG_def_help_items", "<a name=\"def\">&nbsp;</a>#gm_lang[def_help]#<br /><a name=\"def_gedcom\"></a><a href=\"#def\">$UpArrow </a>#gm_lang[def_gedcom_help]#<br /><a name=\"def_gedcom_date\"></a><a href=\"#def\">$UpArrow </a>#gm_lang[def_gedcom_date_help]#<br /><a name=\"def_pdf_format\"></a><a href=\"#def\">$UpArrow </a>#gm_lang[def_pdf_format_help]#<br /><a name=\"def_gm\"></a><a href=\"#def\">$UpArrow </a>#gm_lang[def_gm_help]#<br /><a name=\"def_portal\"></a><a href=\"#def\">$UpArrow </a>#gm_lang[def_portal_help]#<br /><a name=\"def_theme\"></a><a href=\"#def\">$UpArrow </a>#gm_lang[def_theme_help]#<br />");

// edit_user.php (My account)
define("GM_LANG_edituser_user_contact_help", "#gm_lang[edituser_contact_meth_help]#<br /><br /><b>#gm_lang[messaging]#</b><br />#gm_lang[mail_option1_help]#<br /><b>#gm_lang[messaging2]#</b><br />#gm_lang[mail_option2_help]#<br /><b>#gm_lang[mailto]#</b><br />#gm_lang[mail_option3_help]#<br /><b>#gm_lang[no_messaging]#</b><br />#gm_lang[mail_option4_help]#<br />");
define("GM_LANG_help_edituser.php", "~".Str2Upper(GM_LANG_myuserdata)."~<br /><br />#gm_lang[edituser_my_account_help]#<br />#gm_lang[more_help]#");

// user_admin.php
define("GM_LANG_help_useradmin.php", "#gm_lang[useradmin_help]#<br /><br />#gm_lang[is_user_help]#<br />#gm_lang[more_help]#");
define("GM_LANG_useradmin_user_contact_help", "#gm_lang[is_user_help]#<br /><br />#gm_lang[edituser_user_contact_help]#");
define("GM_LANG_useradmin_change_lang_help", "#gm_lang[is_user_help]#<br /><br />#gm_lang[edituser_change_lang_help]#");
define("GM_LANG_useradmin_email_help", "#gm_lang[is_user_help]#<br /><br />#gm_lang[edituser_email_help]#");
define("GM_LANG_useradmin_user_theme_help", "#gm_lang[is_user_help]#<br /><br />#gm_lang[edituser_user_theme_help]#");
// these need to be checked and maybe moved to the help_text.en.php
define("GM_LANG_useradmin_username_help", "#gm_lang[is_user_help]#<br /><br />#gm_lang[edituser_username_help]#");
define("GM_LANG_useradmin_firstname_help", "#gm_lang[is_user_help]#<br /><br />#gm_lang[edituser_firstname_help]#");
define("GM_LANG_useradmin_lastname_help", "#gm_lang[is_user_help]#<br /><br />#gm_lang[edituser_lastname_help]#");
define("GM_LANG_useradmin_password_help", "#gm_lang[is_user_help]#<br /><br />#gm_lang[edituser_password_help]#");
define("GM_LANG_useradmin_conf_password_help", "#gm_lang[is_user_help]#<br /><br />#gm_lang[edituser_conf_password_help]#");
define("GM_LANG_edit_useradmin_help", "#gm_lang[useradmin_edit_user_help]#<br />#gm_lang[more_help]#");

// general help items used in help welcome page
define("GM_LANG_general_help", "<a name=\"header_general\">&nbsp;</a>#gm_lang[header_general_help]##gm_lang[best_display_help]#<br />#gm_lang[preview_help]#<br />");

// page help for the Welcome page
define("GM_LANG_help_index.php", "#gm_lang[index_help]#<br />#gm_lang[index_portal_help_blocks]#<br /><a href=\"#top\">$UpArrow </a>#gm_lang[header_help_items]#<br /><br /><a href=\"#top\">$UpArrow </a>#gm_lang[menu_help_items]#<br /><a href=\"#top\">$UpArrow </a>#gm_lang[general_help]#<br /><a href=\"#top\">$UpArrow </a>#gm_lang[def_help_items]#<br />");

// page help for the MyGenmod page
define("GM_LANG_mygenmod_portal_help_blocks", "<a name=\"mygenmod_portal\"></a>#gm_lang[mygenmod_portal_help]#<br /><a href=\"#mygenmod_portal\">$UpArrow </a><a name=\"mygenmod_welcome\"></a>#gm_lang[mygenmod_welcome_help]#<br /><a href=\"#mygenmod_portal\">$UpArrow </a><a name=\"mygenmod_customize\"></a>#gm_lang[mygenmod_customize_help]#<br /><a href=\"#mygenmod_portal\">$UpArrow </a><a name=\"mygenmod_message\"></a>#gm_lang[mygenmod_message_help]#<br /><a href=\"#mygenmod_portal\">$UpArrow </a><a name=\"mygenmod_events\"></a>#gm_lang[index_events_help]#<br /><a href=\"#mygenmod_portal\">$UpArrow </a><a name=\"mygenmod_onthisday\"></a>#gm_lang[index_onthisday_help]#<br /><a href=\"#mygenmod_portal\">$UpArrow </a><a name=\"mygenmod_favorites\"></a>#gm_lang[mygenmod_favorites_help]#<br /><a href=\"#mygenmod_portal\">$UpArrow </a><a name=\"mygenmod_stats\"></a>#gm_lang[index_stats_help]#<br /><a href=\"#mygenmod_portal\">$UpArrow </a><a name=\"mygenmod_myjournal\"></a>#gm_lang[mygenmod_myjournal_help]#<br /><a href=\"#mygenmod_portal\">$UpArrow </a><a name=\"mygenmod_media\"></a>#gm_lang[index_media_help]#<br /><a href=\"#mygenmod_portal\">$UpArrow </a><a name=\"mygenmod_loggedin\"></a>#gm_lang[index_loggedin_help]#<br /><a name=\"mygenmod_recent_changes\"></a><a href=\"#mygenmod_portal\">$UpArrow </a>#gm_lang[recent_changes_help]#<br />");
define("GM_LANG_index_myged_help", "#gm_lang[mygenmod_portal_help_blocks]#<br />");


//Login
define("GM_LANG_help_login.php", "#gm_lang[login_page_help]#<br />#gm_lang[mygenmod_login_help]#");
define("GM_LANG_help_login_register.php", "~#gm_lang[requestaccount]#~<br /><br />#gm_lang[register_info_01]#");
define("GM_LANG_help_login_lost_pw.php", "~#gm_lang[lost_pw_reset]#~<br /><br />#gm_lang[pls_note11]#");
define("GM_LANG_index_login_register_help", "#gm_lang[index_login_help]#<br />#gm_lang[new_user_help]#<br /><br />#gm_lang[new_password_help]#<br />");

//Add Facts
define("GM_LANG_add_new_facts_help", "#gm_lang[multiple_help]#<br />#gm_lang[add_facts_help]#<br />#gm_lang[add_custom_facts_help]#<br />#gm_lang[add_from_clipboard_help]#<br />#gm_lang[def_gedcom_date_help]#<br />#gm_lang[add_facts_general_help]#");

//Admin Help News Block
define("GM_LANG_index_gedcom_news_ahelp", "#gm_lang[start_ahelp]#<br />#gm_lang[index_gedcom_news_adm_help]#<br />#gm_lang[end_ahelp]#<br />#gm_lang[index_gedcom_news_help]#");

//Upgrade Utility
define("GM_LANG_help_upgrade.php", "#gm_lang[how_upgrade_help]#<br /><br />#gm_lang[readme_help]#");

//-- Admin
define("GM_LANG_help_admin.php", "~#gm_lang[administration]#~</b><br /><br />#gm_lang[admin_help]#<br /><br />#gm_lang[readme_help]#");

//-- Language editor and configuration
define("GM_LANG_help_editlang.php", "#gm_lang[lang_edit_help]#<br /><br />#gm_lang[translation_forum_help]#<br /><br />#gm_lang[bom_check_help]#<br /><br />#gm_lang[edit_lang_utility_help]#<br /><br />#gm_lang[export_lang_utility_help]#<br /><br />#gm_lang[compare_lang_utility_help]#<br /><br />#gm_lang[add_new_language_help]#<br /><br />#gm_lang[more_help]#");
define("GM_LANG_help_changelanguage.php", "#gm_lang[config_lang_utility_help]##gm_lang[more_help]#");

//-- FAQ List editing tool
define("GM_LANG_faq_page_help",	"#gm_lang[help_faq.php]#<br /><a href=\"#top\">$UpArrow </a>#gm_lang[preview_faq_item_help]#<br /><a href=\"#top\">$UpArrow </a>#gm_lang[restore_faq_edits_help]#<br /><a href=\"#top\">$UpArrow </a>#gm_lang[add_faq_item_help]#<br /><a href=\"#top\">$UpArrow </a>#gm_lang[edit_faq_item_help]#<br /><a href=\"#top\">$UpArrow </a>#gm_lang[delete_faq_item_help]#<br /><a href=\"#top\">$UpArrow </a>#gm_lang[moveup_faq_item_help]#<br /><a href=\"#top\">$UpArrow </a>#gm_lang[movedown_faq_item_help]#<br /><a href=\"#top\">$UpArrow </a>#gm_lang[add_faq_header_help]#<br /><a href=\"#top\">$UpArrow </a>#gm_lang[add_faq_body_help]#<br /><a href=\"#top\">$UpArrow </a>#gm_lang[add_faq_order_help]#");

//-- 	G E D C O M
//-- Gedcom Info
define("GM_LANG_gedcom_info_help", "<div class=\"PageTitleName\"><b>#gm_lang[help_contents_gedcom_info]#</b></div><br />#gm_lang[def_gedcom_help]#<br /><a href=\"#top\">$UpArrow </a>#gm_lang[def_gedcom_date_help]#<br /><a href=\"#top\">$UpArrow </a>#gm_lang[ppp_levels_help]#");

//-- Add Gedcom
define("GM_LANG_help_addgedcom.php", "#gm_lang[add_gedcom_help]#<br /><br />#gm_lang[add_upload_gedcom_help]#<br />#gm_lang[readme_help]#");
//-- Add new Gedcom
define("GM_LANG_help_addnewgedcom.php", "#gm_lang[add_new_gedcom_help]#<br /><br />#gm_lang[readme_help]#");
//-- Download Gedcom
define("GM_LANG_help_downloadgedcom.php", "#gm_lang[download_gedcom_help]#");
//-- Edit Gedcoms
define("GM_LANG_help_editgedcoms.php", "#gm_lang[edit_gedcoms_help]#");
//-- Edit Config Gedcoms
define("GM_LANG_help_editconfig_gedcom.php", "#gm_lang[edit_config_gedcom_help]##gm_lang[more_config_hjaelp]#<br /><br />#gm_lang[readme_help]#");
//-- Import Gedcom
define("GM_LANG_help_importgedcom.php", "#gm_lang[import_gedcom_help]#");
//-- Upload Gedcom
define("GM_LANG_help_uploadgedcom.php", "#gm_lang[upload_gedcom_help]#<br /><br />#gm_lang[add_upload_gedcom_help]#<br />#gm_lang[readme_help]#");
//-- Validate Gedcom
define("GM_LANG_help_validategedcom.php", "#gm_lang[validate_gedcom_help]#");
//-- Edit Privacy
define("GM_LANG_help_edit_privacy.php", "~".Str2Upper(GM_LANG_edit_privacy_title)."~<br /><br />#gm_lang[edit_privacy_help]##gm_lang[more_config_hjaelp]#<br />#gm_lang[readme_help]#");

//Specials for contents
$vpos = strpos(GM_LANG_enter_terms, ":", 0);
if ($vpos>0) $enter_terms = substr(GM_LANG_enter_terms, 0, $vpos);
else $enter_terms = GM_LANG_enter_terms;
$vpos = strpos(GM_LANG_soundex_search, ":", 0);
if ($vpos>0) $soundex_search = substr(GM_LANG_soundex_search, 0, $vpos);
else $soundex_search = GM_LANG_soundex_search;

define("GM_LANG_help_used_in_contents", "<div class=\"PageTitleName\"><b>#gm_lang[page_help]#</b></div><br />#gm_lang[help_help_items]#");
define("GM_LANG_search_used_in_contents", "<div class=\"PageTitleName\"><b>#gm_lang[search]#</b></div><ul><li><a href=\"#header_search\">#gm_lang[header]#</a><li><a href=\"#menu_search\">#gm_lang[menu]#</a><li><a href=\"#help_search\">#gm_lang[search]#<li><a href=\"#search_enter_terms\">$enter_terms</a><li><a href=\"#soundex_search\">$soundex_search</ul><br /><br /><a href=\"#top\">$UpArrow </a><a name=\"header_search\"></a>#gm_lang[header_search_help]#<br /><br /><a href=\"#top\">$UpArrow </a><a name=\"menu_search\"></a>#gm_lang[menu_search_help]#<br /><a href=\"#top\">$UpArrow </a><a name=\"help_search\"></a>#gm_lang[help_search.php]#<br /><a href=\"#top\">$UpArrow </a><a name=\"search_enter_terms\"></a>#gm_lang[search_enter_terms_help]#<br /><br /><br /><a name=\"soundex_search\"></a><a href=\"#top\">$UpArrow </a>#gm_lang[soundex_search_help]#");


/*-- Var's for Menu Item: Help contents
	The var define("help_contents_help"] contains all the vars below.
	example: define("h1"] >>> help_index.php will be the var define("help_index.php"],
	to be displayed if the text of define("welcome_page"] is clicked in the Help Contents
*/
define("GM_LANG_h1", "help_index.php,welcome_page");
define("GM_LANG_h2", "index_myged_help,mygenmod");
define("GM_LANG_h3", "help_calendar.php,anniversary_calendar");
define("GM_LANG_h4", "help_clippings.php,clip_cart");
define("GM_LANG_h5", "help_descendancy.php,descend_chart");
define("GM_LANG_h6", "help_edituser.php,editowndata");
define("GM_LANG_h7", "gedcom_info_help,help_contents_gedcom_info");
define("GM_LANG_h8", "help_family.php,family_info");
define("GM_LANG_h9", "help_famlist.php,family_list");
define("GM_LANG_h10", "header_help_items,header");
define("GM_LANG_h11", "help_individual.php,indi_info");
define("GM_LANG_h12", "help_indilist.php,individual_list");
define("GM_LANG_h13", "help_login.php,login");
define("GM_LANG_h14", "menu_help_items,menu");
define("GM_LANG_h15", "help_medialist.php,media_list");
define("GM_LANG_h16", "help_relationship.php,relationship_chart");
define("GM_LANG_h17", "best_display_help,resolution");
define("GM_LANG_h18", "search_used_in_contents,search");
define("GM_LANG_h19", "help_source.php,source");
define("GM_LANG_h20", "help_sourcelist.php,source_list");
define("GM_LANG_h21", "help_pedigree.php,index_header");
define("GM_LANG_h22", "preview_help,print_preview");
define("GM_LANG_h23", "help_placelist.php,place_list");
define("GM_LANG_h24", "help_timeline.php,timeline_chart");
define("GM_LANG_h25", "help_used_in_contents,page_help");
define("GM_LANG_h26", "edituser_password_help,password");
define("GM_LANG_h27", "edituser_username_help,username");
define("GM_LANG_h28", "add_media_help,add_media_lbl");
define("GM_LANG_h29", "help_login_register.php,requestaccount");
define("GM_LANG_h30", "help_login_lost_pw.php,lost_pw_reset");
define("GM_LANG_h31", "help_ancestry.php,ancestry_chart");
define("GM_LANG_h32", "help_fanchart.php,fan_chart");
define("GM_LANG_h33", "help_reportengine.php,reports");
define("GM_LANG_h34", "def_help_items,definitions");
define("GM_LANG_h35", "accesskey_viewing_advice_help,accesskeys");
define("GM_LANG_h36", "help_faq.php,faq_list");
define("GM_LANG_h37", "hs_title_help,hs_title");

$help_contents_help = "";
$i=1;
while (defined("GM_LANG_h$i")) {
	$help_contents_help .= "#gm_lang[h$i]#";
	$i++;
}
define("GM_LANG_help_contents_help", $help_contents_help);



//-- Help Contents for admin

define("GM_LANG_ah1", "how_upgrade_help,ah1_help");
define("GM_LANG_ah2", "help_editconfig.php,ah2_help");
define("GM_LANG_ah3", "add_upload_gedcom_help,ah3_help");
define("GM_LANG_ah4", "gedcom_configfile_help,ah4_help");
define("GM_LANG_ah5", "default_gedcom_help,ah5_help");
define("GM_LANG_ah6", "delete_gedcom_help,ah6_help");
define("GM_LANG_ah7", "add_gedcom_help,ah7_help");
define("GM_LANG_ah8", "add_new_gedcom_help,ah8_help");
define("GM_LANG_ah9", "download_gedcom_help,ah9_help");
define("GM_LANG_ah10", "edit_gedcoms_help,ah10_help");
define("GM_LANG_ah11", "edit_config_gedcom_help,ah11_help");
define("GM_LANG_ah12", "import_gedcom_help,ah12_help");
define("GM_LANG_ah13", "upload_gedcom_help,ah13_help");
define("GM_LANG_ah14", "validate_gedcom_help,ah14_help");
define("GM_LANG_ah15", "convert_ansi2utf_help,ah15_help");
define("GM_LANG_ah16", "help_edit_privacy.php,ah16_help");
define("GM_LANG_ah17", "help_useradmin.php,ah17_help");
define("GM_LANG_ah18", "help_admin.php,ah18_help");
define("GM_LANG_ah19", "addmedia_tool_help,ah19_help");
define("GM_LANG_ah20", "change_indi2id_help,ah20_help");
define("GM_LANG_ah21", "help_editlang.php,ah21_help");
define("GM_LANG_ah22_help", "_Readme.txt");
define("GM_LANG_GM_LANG_ah22_help", "_Readme.txt");
define("GM_LANG_ah22", "readme_help,ah22_help");
define("GM_LANG_ah23", "help_changelanguage.php,ah23_help");
define("GM_LANG_ah24", "um_bu_help,ah24_help");
define("GM_LANG_ah25", "faq_page_help,ah25_help");

$a_help_contents_help = "";
$i=1;
while (defined("GM_LANG_ah".$i)) {
	$a_help_contents_help .= "#gm_lang[ah$i]#";
	$i++;
}
define("GM_LANG_a_help_contents_help", $a_help_contents_help);

define("GM_LANG_admin_help_contents_help", GM_LANG_help_contents_help.GM_LANG_a_help_contents_help);

?>
