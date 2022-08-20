<?php
/**
 * Database layout file
 *
 * This file descibes the layout of the Genmod database. Any change in this file will be applied 
 * when a user logs in to Genmod.
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
 * @version $Id: db_layout.php 29 2022-07-17 13:18:20Z Boudewijn $
 * @package Genmod
 * @subpackage DB
 */
 
if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

if (isset($_POST["TBLPREFIX"])) define('TBLPREFIX', $_POST["TBLPREFIX"]);

/*
	The tables are designed to fit UTF-8 and non-utf8 database definitions.
	Rules for field definitions:
	* ID's for INDI's, FAM's etc (combikey and non-combikey)	:	VARCHAR(64)
	* Fact tags													:	VARCHAR(15)
	* Gedcom ID (max 255)										:	TINYINT UNSIGNED
	* Gedcom name												:	VARCHAR(64)
	* 0/1 fields												:	TINYINT(1)
	* Order type fields (max 255)								:	TINYINT UNSIGNED
	* Auto increment fields										:	INT
	* Username													:	VARBINARY(30) NOT NULL DEFAULT ''
	* Fact tag													:	VARCHAR(15)
	
	Field naming rules:
	* Gedcom id													: <table shortcut>_file
	* Gedcom records											: <table shortcut>_gedrec
*/

$db_original[TBLPREFIX."actions"]["row"]["a_id"]["details"] = "INT NOT NULL AUTO_INCREMENT";
$db_original[TBLPREFIX."actions"]["row"]["a_pid"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."actions"]["row"]["a_type"]["details"] = "VARCHAR(4) CHARACTER SET #charset# COLLATE #collate# "; // Added in 2.0
$db_original[TBLPREFIX."actions"]["row"]["a_repo"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."actions"]["row"]["a_file"]["details"] = "TINYINT UNSIGNED"; // Renamed in 2.0
$db_original[TBLPREFIX."actions"]["row"]["a_text"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."actions"]["row"]["a_status"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."actions"]["key"]["primary"] = "PRIMARY KEY  (`a_id`)";
$db_original[TBLPREFIX."actions"]["key"]["a_pid"] = "KEY `a_pid` (`a_pid`)";
$db_original[TBLPREFIX."actions"]["key"]["a_repo"] = "KEY `a_repo` (`a_repo`)";
$db_original[TBLPREFIX."actions"]["key"]["a_file"] = "KEY `a_file` (`a_file`)"; // Renamed in 2.0
$db_original[TBLPREFIX."actions"]["key"]["a_status"] = "KEY `a_status` (`a_status`)";

$db_original[TBLPREFIX."asso"]["row"]["as_id"]["details"] = "INT NOT NULL AUTO_INCREMENT";
$db_original[TBLPREFIX."asso"]["row"]["as_pid"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."asso"]["row"]["as_of"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."asso"]["row"]["as_type"]["details"] = "ENUM('I','F') NOT NULL";
$db_original[TBLPREFIX."asso"]["row"]["as_fact"]["details"] = "VARCHAR(15) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."asso"]["row"]["as_rela"]["details"] = "VARCHAR(128) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."asso"]["row"]["as_resn"]["details"] = "ENUM('','n','l','p','c') NOT NULL";
$db_original[TBLPREFIX."asso"]["row"]["as_file"]["details"] = "TINYINT UNSIGNED";
$db_original[TBLPREFIX."asso"]["key"]["primary"] = "PRIMARY KEY  (`as_id`)";
$db_original[TBLPREFIX."asso"]["key"]["as_pid"] = "KEY `as_pid` (`as_pid`)";
$db_original[TBLPREFIX."asso"]["key"]["as_of"] = "KEY `as_of` (`as_of`)";
$db_original[TBLPREFIX."asso"]["key"]["as_rel"] = "KEY `as_rel` (`as_of`, `as_type`)";

$db_original[TBLPREFIX."blocks"]["row"]["b_id"]["details"] = "INT NOT NULL AUTO_INCREMENT"; // Changed to auto increment in 2.0
$db_original[TBLPREFIX."blocks"]["row"]["b_username"]["details"] = "VARBINARY(30) NOT NULL DEFAULT ''"; // Def changed in 2.0
$db_original[TBLPREFIX."blocks"]["row"]["b_location"]["details"] = "VARCHAR(30) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."blocks"]["row"]["b_order"]["details"] = "TINYINT UNSIGNED";
$db_original[TBLPREFIX."blocks"]["row"]["b_name"]["details"] = "VARCHAR(255) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."blocks"]["row"]["b_config"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."blocks"]["row"]["b_file"]["details"] = "TINYINT UNSIGNED"; // Added in 2.0
$db_original[TBLPREFIX."blocks"]["key"]["primary"] = "PRIMARY KEY  (`b_id`)";
$db_original[TBLPREFIX."blocks"]["key"]["b_user"] = "KEY `b_user` (`b_username`)"; // Added in 2.0
$db_original[TBLPREFIX."blocks"]["key"]["b_file"] = "KEY `b_file` (`b_file`)"; // Added in 2.0
             
$db_original[TBLPREFIX."changes"]["row"]["ch_id"]["details"] = "INT NOT NULL AUTO_INCREMENT";
$db_original[TBLPREFIX."changes"]["row"]["ch_cid"]["details"] = "INT(11) NOT NULL DEFAULT '0'";
$db_original[TBLPREFIX."changes"]["row"]["ch_gid"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."changes"]["row"]["ch_file"]["details"] = "TINYINT UNSIGNED"; // Renamed in 2.0
$db_original[TBLPREFIX."changes"]["row"]["ch_type"]["details"] = "VARCHAR(25) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."changes"]["row"]["ch_user"]["details"] = "VARCHAR(30) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."changes"]["row"]["ch_time"]["details"] = "INT(11) NOT NULL DEFAULT '0'";
$db_original[TBLPREFIX."changes"]["row"]["ch_fact"]["details"] = "VARCHAR(15) CHARACTER SET #charset# COLLATE #collate#  DEFAULT NULL";
$db_original[TBLPREFIX."changes"]["row"]["ch_old"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."changes"]["row"]["ch_new"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."changes"]["row"]["ch_delete"]["details"] = "TINYINT(1) NOT NULL DEFAULT '0'";
$db_original[TBLPREFIX."changes"]["row"]["ch_gid_type"]["details"] = "ENUM('INDI','FAM', 'SOUR', 'REPO', 'NOTE', 'OBJE', 'HEAD', 'SUBM') CHARACTER SET #charset# COLLATE #collate# NOT NULL"; // Added in 2.0
$db_original[TBLPREFIX."changes"]["key"]["primary"] = "PRIMARY KEY  (`ch_id`)";
$db_original[TBLPREFIX."changes"]["key"]["ch_gid"] = "KEY `ch_gid` (`ch_gid`)";
             
$db_original[TBLPREFIX."counters"]["row"]["c_id"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."counters"]["row"]["c_number"]["details"] = "INT NOT NULL DEFAULT '0'";
$db_original[TBLPREFIX."counters"]["row"]["c_bot_number"]["details"] = "INT NOT NULL DEFAULT '0'";
$db_original[TBLPREFIX."counters"]["row"]["c_file"]["details"] = "TINYINT UNSIGNED"; // Added in 2.0
$db_original[TBLPREFIX."counters"]["row"]["c_type"]["details"] = "ENUM('INDI','FAM','REPO','SOUR','OBJE','NOTE','') NOT NULL"; // Added in 2.0
$db_original[TBLPREFIX."counters"]["key"]["primary"] = "PRIMARY KEY  (`c_id`)";
             
$db_original[TBLPREFIX."dates"]["row"]["d_day"]["details"] = "INT";
$db_original[TBLPREFIX."dates"]["row"]["d_month"]["details"] = "VARCHAR(5) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."dates"]["row"]["d_year"]["details"] = "INT";
$db_original[TBLPREFIX."dates"]["row"]["d_fact"]["details"] = "VARCHAR(15) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."dates"]["row"]["d_gid"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."dates"]["row"]["d_key"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."dates"]["row"]["d_file"]["details"] = "TINYINT UNSIGNED";
$db_original[TBLPREFIX."dates"]["row"]["d_type"]["details"] = "VARCHAR(13) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."dates"]["row"]["d_ext"]["details"] = "VARCHAR(32) CHARACTER SET #charset# COLLATE #collate# ";
// d_ext removed in 2.0
$db_original[TBLPREFIX."dates"]["key"]["date_day"] = "KEY `date_day` (`d_day`)";
$db_original[TBLPREFIX."dates"]["key"]["date_month"] = "KEY `date_month` (`d_month`)";
$db_original[TBLPREFIX."dates"]["key"]["date_year"] = "KEY `date_year` (`d_year`)";
$db_original[TBLPREFIX."dates"]["key"]["date_fact"] = "KEY `date_fact` (`d_fact`)";
$db_original[TBLPREFIX."dates"]["key"]["date_file_gid"] = "KEY `date_file_gid` (`d_file`, `d_gid`)";
$db_original[TBLPREFIX."dates"]["key"]["date_type"] = "KEY `date_type` (`d_type`)";
$db_original[TBLPREFIX."dates"]["key"]["date_key"] = "KEY `date_key` (`d_key`)";
             
$db_original[TBLPREFIX."eventcache"]["row"]["ge_order"]["details"] = "INT NOT NULL AUTO_INCREMENT";
$db_original[TBLPREFIX."eventcache"]["row"]["ge_file"]["details"] = "TINYINT UNSIGNED"; // Renamed and def changed in 2.0
$db_original[TBLPREFIX."eventcache"]["row"]["ge_cache"]["details"] = "VARCHAR(10) CHARACTER SET #charset# COLLATE #collate#  NOT NULL";
$db_original[TBLPREFIX."eventcache"]["row"]["ge_gid"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate#  NOT NULL";
$db_original[TBLPREFIX."eventcache"]["row"]["ge_isdead"]["details"] = "TINYINT(1) NOT NULL DEFAULT '0'";
$db_original[TBLPREFIX."eventcache"]["row"]["ge_fact"]["details"] = "VARCHAR(15) CHARACTER SET #charset# COLLATE #collate#  NOT NULL";
$db_original[TBLPREFIX."eventcache"]["row"]["ge_factrec"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate#  NOT NULL";
$db_original[TBLPREFIX."eventcache"]["row"]["ge_type"]["details"] = "VARCHAR(5) CHARACTER SET #charset# COLLATE #collate#  NOT NULL";
$db_original[TBLPREFIX."eventcache"]["row"]["ge_datestamp"]["details"] = "INT(11) NOT NULL DEFAULT '0'";
$db_original[TBLPREFIX."eventcache"]["row"]["ge_name"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate#  NOT NULL";
$db_original[TBLPREFIX."eventcache"]["row"]["ge_gender"]["details"] = "VARCHAR(2) CHARACTER SET #charset# COLLATE #collate#  NOT NULL";
$db_original[TBLPREFIX."eventcache"]["key"]["ge_file_key"] = "KEY `ge_file_key` (`ge_file`)"; // Renamed in 2.0
$db_original[TBLPREFIX."eventcache"]["key"]["ge_order_key"] = "KEY `ge_order_key` (`ge_order`)"; // Renamed in 2.0
$db_original[TBLPREFIX."eventcache"]["key"]["ge_cache_key"] = "KEY `ge_cache_key` (`ge_cache`)"; // Renamed in 2.0

// FAQ's are added in 2.0
$db_original[TBLPREFIX."faqs"]["row"]["fa_id"]["details"] = "INT NOT NULL AUTO_INCREMENT";
$db_original[TBLPREFIX."faqs"]["row"]["fa_order"]["details"] = "TINYINT UNSIGNED";
$db_original[TBLPREFIX."faqs"]["row"]["fa_header"]["details"] = "VARCHAR(255) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."faqs"]["row"]["fa_body"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."faqs"]["row"]["fa_file"]["details"] = "TINYINT UNSIGNED";
$db_original[TBLPREFIX."faqs"]["key"]["primary"] = "PRIMARY KEY  (`fa_id`)";
$db_original[TBLPREFIX."faqs"]["key"]["fa_file"] = "KEY `fa_file` (`fa_file`)";

             
$db_original[TBLPREFIX."families"]["row"]["f_key"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."families"]["row"]["f_id"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."families"]["row"]["f_file"]["details"] = "TINYINT UNSIGNED";
$db_original[TBLPREFIX."families"]["row"]["f_husb"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."families"]["row"]["f_wife"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."families"]["row"]["f_chil"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."families"]["row"]["f_gedrec"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# "; // Renamed in 2.0
$db_original[TBLPREFIX."families"]["row"]["f_numchil"]["details"] = "TINYINT UNSIGNED";
$db_original[TBLPREFIX."families"]["key"]["primary"] = "PRIMARY KEY  (`f_key`)";
$db_original[TBLPREFIX."families"]["key"]["fam_id_file"] = "UNIQUE KEY `fam_id_file` (`f_id`, `f_file`)";
$db_original[TBLPREFIX."families"]["key"]["fam_file"] = "KEY `fam_file` (`f_file`)";
$db_original[TBLPREFIX."families"]["key"]["fam_gedrec"] = "FULLTEXT `fam_gedrec` (`f_gedrec`)"; // Renamed in 2.0

$db_original[TBLPREFIX."favorites"]["row"]["fv_id"]["details"] = "INT NOT NULL AUTO_INCREMENT";       
$db_original[TBLPREFIX."favorites"]["row"]["fv_username"]["details"] = "VARBINARY(30) NOT NULL DEFAULT ''"; // Def changed in 2.0
$db_original[TBLPREFIX."favorites"]["row"]["fv_gid"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."favorites"]["row"]["fv_type"]["details"] = "VARCHAR(10) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."favorites"]["row"]["fv_file"]["details"] = "TINYINT UNSIGNED"; // Def changed in 2.0
$db_original[TBLPREFIX."favorites"]["row"]["fv_url"]["details"] = "VARCHAR(255) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."favorites"]["row"]["fv_title"]["details"] = "VARCHAR(255) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."favorites"]["row"]["fv_note"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."favorites"]["key"]["primary"] = "PRIMARY KEY  (`fv_id`)";

// g_id, g_config and g_privacy removed in 2.0
$db_original[TBLPREFIX."gedcoms"]["row"]["g_file"]["details"] = "TINYINT UNSIGNED"; // Added in 2.0
$db_original[TBLPREFIX."gedcoms"]["row"]["g_gedcom"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."gedcoms"]["row"]["g_title"]["details"] = "VARCHAR(50) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedcoms"]["row"]["g_path"]["details"] = "VARCHAR(255) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedcoms"]["row"]["g_commonsurnames"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedcoms"]["row"]["g_isdefault"]["details"] = "ENUM('N','Y') NOT NULL DEFAULT 'N'"; // Changed in 2.0
$db_original[TBLPREFIX."gedcoms"]["key"]["primary"] = "PRIMARY KEY  (`g_gedcom`)";
$db_original[TBLPREFIX."gedcoms"]["key"]["g_file"] = "KEY `g_file` (`g_file`)"; // Renamed in 2.0
                                                
$db_original[TBLPREFIX."gedconf"]["row"]["gc_gedcomid"]["details"] = "TINYINT UNSIGNED"; // Added in 2.0
$db_original[TBLPREFIX."gedconf"]["row"]["gc_gedcom"]["details"] = "VARCHAR (64) CHARACTER SET #charset# COLLATE #collate#  NOT NULL default ''";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_gedcomlang"]["details"] = "VARCHAR (64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_calendar_format"]["details"] = "VARCHAR (21) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_display_jewish_thousands"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_display_jewish_gereshayim"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_jewish_ashkenaz_pronunciation"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_use_rtl_functions"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_character_set"]["details"] = "VARCHAR (6) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_enable_multi_language"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_default_pedigree_generations"]["details"] = "TINYINT UNSIGNED";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_max_pedigree_generations"]["details"] = "TINYINT UNSIGNED";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_max_descendancy_generations"]["details"] = "TINYINT UNSIGNED";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_use_rin"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_pedigree_root_id"]["details"] = "VARCHAR (64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_gedcom_id_prefix"]["details"] = "VARCHAR (6) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_source_id_prefix"]["details"] = "VARCHAR (6) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_repo_id_prefix"]["details"] = "VARCHAR (6) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_fam_id_prefix"]["details"] = "VARCHAR (6) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_media_id_prefix"]["details"] = "VARCHAR (6) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_note_id_prefix"]["details"] = "VARCHAR (6) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_keep_actions"]["details"] = "TINYINT(1)"; // Added in 2.0
$db_original[TBLPREFIX."gedconf"]["row"]["gc_pedigree_full_details"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_pedigree_layout"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_show_empty_boxes"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_zoom_boxes"]["details"] = "VARCHAR (10) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_link_icons"]["details"] = "VARCHAR (10) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_abbreviate_chart_labels"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_show_parents_age"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_require_authentication"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_exclude_require_authentication"]["details"] = "VARCHAR (128) CHARACTER SET #charset# COLLATE #collate# "; // Added in 2.0
$db_original[TBLPREFIX."gedconf"]["row"]["gc_welcome_text_auth_mode"]["details"] = "INT (2)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_welcome_text_auth_mode_4"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_welcome_text_cust_head"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_show_gedcom_record"]["details"] = "TINYINT(1) DEFAULT '1'";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_edit_gedcom_record"]["details"] = "TINYINT(1) DEFAULT '2'";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_allow_edit_gedcom"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_postal_code"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_indi_ext_fam_facts"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_alpha_index_lists"]["details"] = "SMALLINT";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_lists_all"]["details"] = "TINYINT(1) DEFAULT '1'";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_name_from_gedcom"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_show_married_names"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_show_id_numbers"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_show_fam_id_numbers"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_show_pedigree_places"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_show_nick"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_nick_delim"]["details"] = "VARCHAR (2) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_media_external"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_media_directory"]["details"] = "VARCHAR (64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_media_directory_levels"]["details"] = "INT (2)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_media_directory_hide"]["details"] = "VARCHAR (64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_show_highlight_images"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_use_thumbs_main"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_thumbnail_width"]["details"] = "INT (5)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_auto_generate_thumbs"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_merge_double_media"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_hide_gedcom_errors"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_word_wrapped_notes"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_gedcom_default_tab"]["details"] = "TINYINT UNSIGNED";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_show_context_help"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_contact_email"]["details"] = "VARCHAR (64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_contact_method"]["details"] = "VARCHAR (15) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_webmaster_email"]["details"] = "VARCHAR (64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_support_method"]["details"] = "VARCHAR (15) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_bcc_webmaster"]["details"] = "TINYINT(1) DEFAULT '0'";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_home_site_url"]["details"] = "VARCHAR (64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_home_site_text"]["details"] = "VARCHAR (64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_favicon"]["details"] = "VARCHAR (64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_theme_dir"]["details"] = "VARCHAR (64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_allow_theme_dropdown"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_show_stats"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_show_counter"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_days_to_show_limit"]["details"] = "INT (4)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_common_names_threshold"]["details"] = "INT (4)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_common_names_add"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_common_names_remove"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_meta_author"]["details"] = "VARCHAR (64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_meta_publisher"]["details"] = "VARCHAR (64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_meta_copyright"]["details"] = "VARCHAR (64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_meta_description"]["details"] = "VARCHAR (64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_meta_page_topic"]["details"] = "VARCHAR (64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_meta_audience"]["details"] = "VARCHAR (64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_meta_page_type"]["details"] = "VARCHAR (64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_meta_robots"]["details"] = "VARCHAR (64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_meta_robots_DENY"]["details"] = "VARCHAR (64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_meta_revisit"]["details"] = "VARCHAR (64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_meta_keywords"]["details"] = "VARCHAR (128) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_meta_title"]["details"] = "VARCHAR (64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_meta_surname_keywords"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_include_in_sitemap"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_chart_box_tags"]["details"] = "VARCHAR (64) CHARACTER SET #charset# COLLATE #collate# ";
// gc_use_quick_update, gc_show_quick_resn, gc_quick_add_facts, gc_quick_required_facts, gc_quick_add_famfacts, gc_quick_required_famfacts removed in 2.0
$db_original[TBLPREFIX."gedconf"]["row"]["gc_show_lds_at_glance"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_underline_name_quotes"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_split_places"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_show_relatives_events"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate#  NOT NULL";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_expand_relatives_events"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_edit_autoclose"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_sour_facts_unique"]["details"] = "VARCHAR (255) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_sour_facts_add"]["details"] = "VARCHAR (255) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_sour_quick_addfacts"]["details"] = "VARCHAR (255) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_repo_facts_unique"]["details"] = "VARCHAR (255) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_repo_facts_add"]["details"] = "VARCHAR (255) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_repo_quick_addfacts"]["details"] = "VARCHAR (255) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_indi_facts_unique"]["details"] = "VARCHAR (255) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_indi_facts_add"]["details"] = "VARCHAR (255) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_indi_quick_addfacts"]["details"] = "VARCHAR (255) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_fam_facts_unique"]["details"] = "VARCHAR (255) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_fam_facts_add"]["details"] = "VARCHAR (255) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_fam_quick_addfacts"]["details"] = "VARCHAR (255) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_media_facts_unique"]["details"] = "VARCHAR (255) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_media_facts_add"]["details"] = "VARCHAR (255) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_media_quick_addfacts"]["details"] = "VARCHAR (255) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_note_facts_unique"]["details"] = "VARCHAR (255) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_note_facts_add"]["details"] = "VARCHAR (255) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_note_quick_addfacts"]["details"] = "VARCHAR (255) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_rss_format"]["details"] = "VARCHAR (10) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_time_limit"]["details"] = "INT (4)";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_last_change_email"]["details"] = "INT (11) DEFAULT '0'";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_last_upcoming"]["details"] = "INT (11) DEFAULT '0'";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_last_today"]["details"] = "INT (11) DEFAULT '0'";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_last_stats"]["details"] = "INT (11) DEFAULT '0'";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_display_pinyin"]["details"] = "TINYINT (1) DEFAULT '0'";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_display_transliterate"]["details"] = "TINYINT (1) DEFAULT '0'";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_last_plotdata"]["details"] = "TINYINT (1) DEFAULT '0'";
$db_original[TBLPREFIX."gedconf"]["row"]["gc_show_external_search"]["details"] = "TINYINT (1) DEFAULT '5'"; // Added in 2.0
$db_original[TBLPREFIX."gedconf"]["key"]["primary"] = "PRIMARY KEY  (`gc_gedcomid`)";

$db_original[TBLPREFIX."individual_family"]["row"]["ID"]["details"] = "INT NOT NULL AUTO_INCREMENT";
$db_original[TBLPREFIX."individual_family"]["row"]["if_pkey"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."individual_family"]["row"]["if_fkey"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."individual_family"]["row"]["if_order"]["details"] = "TINYINT UNSIGNED NOT NULL DEFAULT '0'";
$db_original[TBLPREFIX."individual_family"]["row"]["if_role"]["details"] = "VARCHAR(1) CHARACTER SET #charset# COLLATE #collate#  DEFAULT NULL";
$db_original[TBLPREFIX."individual_family"]["row"]["if_prim"]["details"] = "VARCHAR(1) CHARACTER SET #charset# COLLATE #collate#  DEFAULT NULL";
$db_original[TBLPREFIX."individual_family"]["row"]["if_pedi"]["details"] = "VARCHAR(1) CHARACTER SET #charset# COLLATE #collate#  DEFAULT NULL";
$db_original[TBLPREFIX."individual_family"]["row"]["if_stat"]["details"] = "VARCHAR(1) CHARACTER SET #charset# COLLATE #collate#  DEFAULT NULL";
$db_original[TBLPREFIX."individual_family"]["row"]["if_file"]["details"] = "TINYINT UNSIGNED NOT NULL DEFAULT '0'";
$db_original[TBLPREFIX."individual_family"]["key"]["primary"] = "PRIMARY KEY  (`ID`)";
$db_original[TBLPREFIX."individual_family"]["key"]["pid_key"] = "KEY `pid_key`  (`if_pkey`)";
$db_original[TBLPREFIX."individual_family"]["key"]["fam_key"] = "KEY `fam_key`  (`if_fkey`)";
$db_original[TBLPREFIX."individual_family"]["key"]["role_key"] = "KEY `role_key`  (`if_role`)";
$db_original[TBLPREFIX."individual_family"]["key"]["p_f_key"] = "UNIQUE KEY `p_f_key` (`if_pkey`, `if_fkey`)";
             
$db_original[TBLPREFIX."individuals"]["row"]["i_key"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."individuals"]["row"]["i_id"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."individuals"]["row"]["i_file"]["details"] = "TINYINT UNSIGNED";
$db_original[TBLPREFIX."individuals"]["row"]["i_rin"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."individuals"]["row"]["i_isdead"]["details"] = "TINYINT(1) DEFAULT 1";
$db_original[TBLPREFIX."individuals"]["row"]["i_gedrec"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."individuals"]["row"]["i_gender"]["details"] = "CHAR(1) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."individuals"]["key"]["primary"] = "PRIMARY KEY  (`i_key`)";
$db_original[TBLPREFIX."individuals"]["key"]["indi_id_file"] = "UNIQUE KEY `indi_id_file` (`i_id`, `i_file`)";
$db_original[TBLPREFIX."individuals"]["key"]["indi_file"] = "KEY `indi_file` (`i_file`)";
$db_original[TBLPREFIX."individuals"]["key"]["indi_gedrec"] = "FULLTEXT `indi_gedrec` (`i_gedrec`)";
             
$db_original[TBLPREFIX."language"]["row"]["lg_string"]["details"] = "VARCHAR(255) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."language"]["row"]["lg_english"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate#  NOT NULL";
$db_original[TBLPREFIX."language"]["row"]["lg_hebrew"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate#  NOT NULL";
$db_original[TBLPREFIX."language"]["row"]["lg_dutch"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate#  NOT NULL";
$db_original[TBLPREFIX."language"]["row"]["lg_portuguese_br"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate#  NOT NULL";
$db_original[TBLPREFIX."language"]["row"]["lg_czech"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate#  NOT NULL";
$db_original[TBLPREFIX."language"]["row"]["lg_german"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate#  NOT NULL";
$db_original[TBLPREFIX."language"]["row"]["lg_spanish"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate#  NOT NULL";
$db_original[TBLPREFIX."language"]["row"]["lg_french"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate#  NOT NULL";
$db_original[TBLPREFIX."language"]["row"]["lg_italian"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate#  NOT NULL";
$db_original[TBLPREFIX."language"]["row"]["lg_hungarian"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate#  NOT NULL";
$db_original[TBLPREFIX."language"]["row"]["lg_norwegian"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate#  NOT NULL";
$db_original[TBLPREFIX."language"]["row"]["lg_polish"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate#  NOT NULL";
$db_original[TBLPREFIX."language"]["row"]["lg_finnish"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate#  NOT NULL";
$db_original[TBLPREFIX."language"]["row"]["lg_swedish"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate#  NOT NULL";
$db_original[TBLPREFIX."language"]["row"]["lg_turkish"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate#  NOT NULL";
$db_original[TBLPREFIX."language"]["row"]["lg_chinese"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate#  NOT NULL";
$db_original[TBLPREFIX."language"]["row"]["lg_russian"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate#  NOT NULL";
$db_original[TBLPREFIX."language"]["row"]["lg_greek"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate#  NOT NULL";
$db_original[TBLPREFIX."language"]["row"]["lg_arabic"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate#  NOT NULL";
$db_original[TBLPREFIX."language"]["row"]["lg_lithuanian"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate#  NOT NULL";
$db_original[TBLPREFIX."language"]["row"]["lg_danish"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate#  NOT NULL";
$db_original[TBLPREFIX."language"]["row"]["lg_vietnamese"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate#  NOT NULL";
$db_original[TBLPREFIX."language"]["row"]["lg_spanish_ar"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate#  NOT NULL";
$db_original[TBLPREFIX."language"]["row"]["lg_last_update_date"]["details"] = "INT(11) NOT NULL DEFAULT '0'";
$db_original[TBLPREFIX."language"]["row"]["lg_last_update_by"]["details"] = "VARCHAR(100) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."language"]["key"]["primary"] = "PRIMARY KEY  (`lg_string`)";

$db_original[TBLPREFIX."language_help"]["row"]["lg_string"]["details"] = "VARCHAR(255) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."language_help"]["row"]["lg_english"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."language_help"]["row"]["lg_hebrew"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."language_help"]["row"]["lg_dutch"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."language_help"]["row"]["lg_portuguese_br"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."language_help"]["row"]["lg_czech"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."language_help"]["row"]["lg_german"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."language_help"]["row"]["lg_spanish"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."language_help"]["row"]["lg_french"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."language_help"]["row"]["lg_italian"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."language_help"]["row"]["lg_hungarian"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."language_help"]["row"]["lg_norwegian"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."language_help"]["row"]["lg_polish"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."language_help"]["row"]["lg_finnish"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."language_help"]["row"]["lg_swedish"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."language_help"]["row"]["lg_turkish"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."language_help"]["row"]["lg_chinese"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."language_help"]["row"]["lg_russian"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."language_help"]["row"]["lg_greek"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."language_help"]["row"]["lg_arabic"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."language_help"]["row"]["lg_lithuanian"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."language_help"]["row"]["lg_danish"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."language_help"]["row"]["lg_vietnamese"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."language_help"]["row"]["lg_spanish_ar"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."language_help"]["row"]["lg_last_update_date"]["details"] = "INT(11) NOT NULL DEFAULT '' DEFAULT '0'";
$db_original[TBLPREFIX."language_help"]["row"]["lg_last_update_by"]["details"] = "VARCHAR(100) CHARACTER SET #charset# COLLATE #collate# NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."language_help"]["key"]["primary"] = "PRIMARY KEY  (`lg_string`)";

$db_original[TBLPREFIX."lang_settings"]["row"]["ls_gm_langname"]["details"] = "VARCHAR(32) CHARACTER SET #charset# COLLATE #collate#  NOT NULL";
$db_original[TBLPREFIX."lang_settings"]["row"]["ls_translated"]["details"] = "TINYINT(1) NOT NULL DEFAULT '0'";
$db_original[TBLPREFIX."lang_settings"]["row"]["ls_md5_lang"]["details"] = "VARCHAR(32) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."lang_settings"]["row"]["ls_md5_help"]["details"] = "VARCHAR(32) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."lang_settings"]["row"]["ls_md5_facts"]["details"] = "VARCHAR(32) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."lang_settings"]["row"]["ls_gm_lang_use"]["details"] = "TINYINT(1) NOT NULL DEFAULT '0'";
$db_original[TBLPREFIX."lang_settings"]["row"]["ls_gm_lang"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."lang_settings"]["row"]["ls_lang_short_cut"]["details"] = "VARCHAR(10) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."lang_settings"]["row"]["ls_langcode"]["details"] = "VARCHAR(164) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."lang_settings"]["row"]["ls_gm_language"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."lang_settings"]["row"]["ls_confighelpfile"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."lang_settings"]["row"]["ls_helptextfile"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."lang_settings"]["row"]["ls_flagsfile"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."lang_settings"]["row"]["ls_factsfile"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."lang_settings"]["row"]["ls_DATE_FORMAT"]["details"] = "VARCHAR(32) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."lang_settings"]["row"]["ls_TIME_FORMAT"]["details"] = "VARCHAR(32) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."lang_settings"]["row"]["ls_WEEK_START"]["details"] = "TINYINT(1) NOT NULL DEFAULT '0'";
$db_original[TBLPREFIX."lang_settings"]["row"]["ls_TEXT_DIRECTION"]["details"] = "VARCHAR(3) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."lang_settings"]["row"]["ls_NAME_REVERSE"]["details"] = "TINYINT(1) NOT NULL DEFAULT '0'";
$db_original[TBLPREFIX."lang_settings"]["row"]["ls_ALPHABET_upper"]["details"] = "VARCHAR(255) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."lang_settings"]["row"]["ls_ALPHABET_lower"]["details"] = "VARCHAR(255) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."lang_settings"]["row"]["ls_MON_SHORT"]["details"] = "VARCHAR(150) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."lang_settings"]["key"]["primary"] = "PRIMARY KEY  (`ls_gm_langname`)";
 
$db_original[TBLPREFIX."facts"]["row"]["lg_string"]["details"] = "VARCHAR(15) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."facts"]["row"]["lg_english"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."facts"]["row"]["lg_hebrew"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."facts"]["row"]["lg_dutch"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."facts"]["row"]["lg_portuguese_br"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."facts"]["row"]["lg_czech"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."facts"]["row"]["lg_german"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."facts"]["row"]["lg_spanish"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."facts"]["row"]["lg_french"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."facts"]["row"]["lg_italian"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."facts"]["row"]["lg_hungarian"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."facts"]["row"]["lg_norwegian"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."facts"]["row"]["lg_polish"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."facts"]["row"]["lg_finnish"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."facts"]["row"]["lg_swedish"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."facts"]["row"]["lg_turkish"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."facts"]["row"]["lg_chinese"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."facts"]["row"]["lg_russian"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."facts"]["row"]["lg_greek"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."facts"]["row"]["lg_arabic"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."facts"]["row"]["lg_lithuanian"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."facts"]["row"]["lg_danish"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."facts"]["row"]["lg_vietnamese"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."facts"]["row"]["lg_spanish_ar"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# NOT NULL";
$db_original[TBLPREFIX."facts"]["row"]["lg_last_update_date"]["details"] = "INT(11) NOT NULL DEFAULT '0'";
$db_original[TBLPREFIX."facts"]["row"]["lg_last_update_by"]["details"] = "VARCHAR(100) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."facts"]["key"]["primary"] = "PRIMARY KEY  (`lg_string`)";

$db_original[TBLPREFIX."lockout"]["row"]["lo_ip"]["details"] = "VARCHAR(16) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."lockout"]["row"]["lo_timestamp"]["details"] = "INT(11)";
$db_original[TBLPREFIX."lockout"]["row"]["lo_release"]["details"] = "INT(11)";
$db_original[TBLPREFIX."lockout"]["row"]["lo_username"]["details"] = "VARBINARY(30) NOT NULL DEFAULT ''"; // Def changed in 2.0
$db_original[TBLPREFIX."lockout"]["key"]["primary"] = "PRIMARY KEY (`lo_ip`, `lo_username`)";
            
$db_original[TBLPREFIX."log"]["row"]["l_num"]["details"] = "INT AUTO_INCREMENT";
$db_original[TBLPREFIX."log"]["row"]["l_type"]["details"] = "VARCHAR(1) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."log"]["row"]["l_category"]["details"] = "VARCHAR(1) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."log"]["row"]["l_timestamp"]["details"] = "INT(11) NOT NULL DEFAULT '0'";
$db_original[TBLPREFIX."log"]["row"]["l_ip"]["details"] = "VARCHAR(15) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."log"]["row"]["l_user"]["details"] = "VARCHAR(30) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."log"]["row"]["l_text"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."log"]["row"]["l_file"]["details"] = "TINYINT UNSIGNED DEFAULT NULL"; // Changed in 2.0
$db_original[TBLPREFIX."log"]["row"]["l_new"]["details"] = "TINYINT(1) NOT NULL DEFAULT '0'";
$db_original[TBLPREFIX."log"]["key"]["primary"] = "PRIMARY KEY (`l_num`)";
$db_original[TBLPREFIX."log"]["key"]["category_letter"] = "KEY `category_letter` (`l_category`)";
$db_original[TBLPREFIX."log"]["key"]["time_order"] = "KEY `time_order` (`l_timestamp`)";
             
$db_original[TBLPREFIX."media"]["row"]["m_id"]["details"] = "INT AUTO_INCREMENT";
$db_original[TBLPREFIX."media"]["row"]["m_media"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."media"]["row"]["m_ext"]["details"] = "VARCHAR(6) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."media"]["row"]["m_titl"]["details"] = "VARCHAR(255) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."media"]["row"]["m_mfile"]["details"] = "VARCHAR(255) CHARACTER SET #charset# COLLATE #collate# ";  
$db_original[TBLPREFIX."media"]["row"]["m_file"]["details"] = "TINYINT UNSIGNED"; // m_gedfile removed, m_file is gedid in 2.0
$db_original[TBLPREFIX."media"]["row"]["m_gedrec"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."media"]["key"]["primary"] = "PRIMARY KEY  (`m_id`)";
$db_original[TBLPREFIX."media"]["key"]["m_mfile"] = "KEY `m_mfile` (`m_mfile`)";
$db_original[TBLPREFIX."media"]["key"]["m_media_file"] = "UNIQUE KEY `m_media_file` (`m_media` , `m_file`)";
$db_original[TBLPREFIX."media"]["key"]["media_gedrec"] = "FULLTEXT `media_gedrec` (`m_gedrec`)";
                                       
$db_original[TBLPREFIX."media_mapping"]["row"]["mm_id"]["details"] = "INT AUTO_INCREMENT";
$db_original[TBLPREFIX."media_mapping"]["row"]["mm_media"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."media_mapping"]["row"]["mm_gid"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."media_mapping"]["row"]["mm_order"]["details"] = "TINYINT UNSIGNED NOT NULL DEFAULT '0'";
$db_original[TBLPREFIX."media_mapping"]["row"]["mm_file"]["details"] = "TINYINT UNSIGNED DEFAULT NULL"; // Was mm_gedfile, changed in 2.0
$db_original[TBLPREFIX."media_mapping"]["row"]["mm_gedrec"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."media_mapping"]["row"]["mm_type"]["details"] = "VARCHAR(4) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."media_mapping"]["key"]["primary"] = "PRIMARY KEY  (`mm_id`)";
$db_original[TBLPREFIX."media_mapping"]["key"]["mm_media"] = "KEY `mm_media` (`mm_media` , `mm_file`)";
$db_original[TBLPREFIX."media_mapping"]["key"]["mm_type"] = "KEY `mm_type` (`mm_media` ,  `mm_type` , `mm_file` )";
$db_original[TBLPREFIX."media_mapping"]["key"]["mm_gid"] = "KEY `mm_gid` (`mm_media` ,  `mm_gid` , `mm_file` )";

$db_original[TBLPREFIX."media_files"]["row"]["mf_id"]["details"] = "INT AUTO_INCREMENT";
$db_original[TBLPREFIX."media_files"]["row"]["mf_file"]["details"] = "VARCHAR(255) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."media_files"]["row"]["mf_path"]["details"] = "VARCHAR(200) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."media_files"]["row"]["mf_fname"]["details"] = "VARCHAR(100) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."media_files"]["row"]["mf_is_image"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."media_files"]["row"]["mf_width"]["details"] = "INT(11) NOT NULL DEFAULT '0'";
$db_original[TBLPREFIX."media_files"]["row"]["mf_height"]["details"] = "INT(11) NOT NULL DEFAULT '0'";
$db_original[TBLPREFIX."media_files"]["row"]["mf_bits"]["details"] = "INT(11) NOT NULL DEFAULT '0'"; // Added in 2.0
$db_original[TBLPREFIX."media_files"]["row"]["mf_channels"]["details"] = "INT(11) NOT NULL DEFAULT '0'"; // Added in 2.0
$db_original[TBLPREFIX."media_files"]["row"]["mf_size"]["details"] = "INT NOT NULL DEFAULT '0'";
$db_original[TBLPREFIX."media_files"]["row"]["mf_twidth"]["details"] = "INT(11) NOT NULL DEFAULT '0'";
$db_original[TBLPREFIX."media_files"]["row"]["mf_theight"]["details"] = "INT(11) NOT NULL DEFAULT '0'";
$db_original[TBLPREFIX."media_files"]["row"]["mf_tsize"]["details"] = "INT NOT NULL DEFAULT '0'";
$db_original[TBLPREFIX."media_files"]["row"]["mf_mimetype"]["details"] = "VARCHAR(255) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."media_files"]["row"]["mf_mimedesc"]["details"] = "VARCHAR(255) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."media_files"]["row"]["mf_link"]["details"] = "VARCHAR(255) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."media_files"]["key"]["primary"] = "PRIMARY KEY  (`mf_id`)";
$db_original[TBLPREFIX."media_files"]["key"]["mf_file"] = "KEY `mf_file` (`mf_file`)";
$db_original[TBLPREFIX."media_files"]["key"]["mf_path"] = "KEY `mf_path` (`mf_path`)";
$db_original[TBLPREFIX."media_files"]["key"]["mf_fullfile"] = "UNIQUE KEY `mf_fullfile` (`mf_path`, `mf_fname`)";

$db_original[TBLPREFIX."media_datafiles"]["row"]["mdf_id"]["details"] = "INT AUTO_INCREMENT";
$db_original[TBLPREFIX."media_datafiles"]["row"]["mdf_file"]["details"] = "VARCHAR(255) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."media_datafiles"]["row"]["mdf_data"]["details"] = "BLOB NOT NULL";
$db_original[TBLPREFIX."media_datafiles"]["key"]["primary"] = "PRIMARY KEY  (`mdf_id`)";
$db_original[TBLPREFIX."media_datafiles"]["key"]["mdf_file"] = "KEY `mdf_file` (`mdf_file`)";

$db_original[TBLPREFIX."media_thumbfiles"]["row"]["mtf_id"]["details"] = "INT AUTO_INCREMENT";
$db_original[TBLPREFIX."media_thumbfiles"]["row"]["mtf_file"]["details"] = "VARCHAR(255) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."media_thumbfiles"]["row"]["mtf_data"]["details"] = "BLOB NOT NULL";
$db_original[TBLPREFIX."media_thumbfiles"]["key"]["primary"] = "PRIMARY KEY  (`mtf_id`)";
$db_original[TBLPREFIX."media_thumbfiles"]["key"]["mtf_file"] = "KEY `mtf_file` (`mtf_file`)";
             
$db_original[TBLPREFIX."messages"]["row"]["m_id"]["details"] = "INT NOT NULL AUTO_INCREMENT";
$db_original[TBLPREFIX."messages"]["row"]["m_from"]["details"] = "VARCHAR(255) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."messages"]["row"]["m_to"]["details"] = "VARCHAR(30) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."messages"]["row"]["m_subject"]["details"] = "VARCHAR(255) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."messages"]["row"]["m_body"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."messages"]["row"]["m_created"]["details"] = "VARCHAR(255) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."messages"]["key"]["primary"] = "PRIMARY KEY  (`m_id`)";
             
$db_original[TBLPREFIX."names"]["row"]["n_id"]["details"] = "INT AUTO_INCREMENT";
$db_original[TBLPREFIX."names"]["row"]["n_key"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."names"]["row"]["n_gid"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."names"]["row"]["n_file"]["details"] = "TINYINT UNSIGNED";
$db_original[TBLPREFIX."names"]["row"]["n_name"]["details"] = "VARCHAR(255) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."names"]["row"]["n_letter"]["details"] = "VARCHAR(5) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."names"]["row"]["n_fletter"]["details"] = "VARCHAR(5) CHARACTER SET #charset# COLLATE #collate# "; // Added in 2.0
$db_original[TBLPREFIX."names"]["row"]["n_surname"]["details"] = "VARCHAR(100) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."names"]["row"]["n_nick"]["details"] = "VARCHAR(100) CHARACTER SET #charset# COLLATE #collate# "; // Added in 2.0
$db_original[TBLPREFIX."names"]["row"]["n_type"]["details"] = "VARCHAR(10) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."names"]["key"]["primary"] = "PRIMARY KEY  (`n_id`)";
$db_original[TBLPREFIX."names"]["key"]["name_key"] = "KEY `name_key` (`n_key`)";
$db_original[TBLPREFIX."names"]["key"]["name_gid"] = "KEY `name_gid` (`n_gid`)";
$db_original[TBLPREFIX."names"]["key"]["name_name"] = "KEY `name_name` (`n_name`)";
$db_original[TBLPREFIX."names"]["key"]["name_letter_file"] = "KEY `name_letter_file` (`n_letter`, `n_file`)";
$db_original[TBLPREFIX."names"]["key"]["name_type"] = "KEY `name_type` (`n_type`)";
$db_original[TBLPREFIX."names"]["key"]["name_surn_file"] = "KEY `name_surn_file` (`n_surname`, `n_file`)";
$db_original[TBLPREFIX."names"]["key"]["name_file"] = "KEY `name_file` (`n_file`)";
             
$db_original[TBLPREFIX."news"]["row"]["n_id"]["details"] = "INT AUTO_INCREMENT";
$db_original[TBLPREFIX."news"]["row"]["n_username"]["details"] = "VARBINARY(30) NOT NULL DEFAULT ''"; // Def changed in 2.0
$db_original[TBLPREFIX."news"]["row"]["n_date"]["details"] = "INT";
$db_original[TBLPREFIX."news"]["row"]["n_title"]["details"] = "VARCHAR(255) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."news"]["row"]["n_text"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."news"]["key"]["primary"] = "PRIMARY KEY  (`n_id`)";
             
$db_original[TBLPREFIX."other_mapping"]["row"]["om_id"]["details"] = "INT AUTO_INCREMENT";
$db_original[TBLPREFIX."other_mapping"]["row"]["om_oid"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."other_mapping"]["row"]["om_gid"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."other_mapping"]["row"]["om_type"]["details"] = "VARCHAR(4) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."other_mapping"]["row"]["om_file"]["details"] = "TINYINT UNSIGNED DEFAULT NULL"; // Changed from om_gedfile in 2.0
$db_original[TBLPREFIX."other_mapping"]["key"]["primary"] = "PRIMARY KEY  (`om_id`)";
$db_original[TBLPREFIX."other_mapping"]["key"]["om_oid"] = "KEY `om_oid` (`om_oid` , `om_file` )";
$db_original[TBLPREFIX."other_mapping"]["key"]["om_gid"] = "KEY `om_gid` ( `om_gid` , `om_file` )";

$db_original[TBLPREFIX."other"]["row"]["o_key"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''"; // Added in 2.0
$db_original[TBLPREFIX."other"]["row"]["o_id"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."other"]["row"]["o_file"]["details"] = "TINYINT UNSIGNED";
$db_original[TBLPREFIX."other"]["row"]["o_type"]["details"] = "VARCHAR(20) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."other"]["row"]["o_gedrec"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# "; // Renamed from o_gedcom in 2.0
$db_original[TBLPREFIX."other"]["key"]["primary"] = "PRIMARY KEY (`o_id`, `o_file`)";
$db_original[TBLPREFIX."other"]["key"]["o_key"] = "UNIQUE KEY `o_key` (`o_key`)"; // Added in 2.0
$db_original[TBLPREFIX."other"]["key"]["other_type"] = "KEY `other_type` (`o_type`)";
$db_original[TBLPREFIX."other"]["key"]["other_file"] = "KEY `other_file` (`o_file`)";
$db_original[TBLPREFIX."other"]["key"]["other_gedrec"] = "FULLTEXT `other_gedrec` (`o_gedrec`)";
             
$db_original[TBLPREFIX."pages"]["row"]["pag_id"]["details"] = "INT NOT NULL AUTO_INCREMENT";
$db_original[TBLPREFIX."pages"]["row"]["pag_content"]["details"] = "LONGTEXT CHARACTER SET #charset# COLLATE #collate#   NOT NULL";
$db_original[TBLPREFIX."pages"]["row"]["pag_title"]["details"] = "VARCHAR(40) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."pages"]["key"]["primary"] = "PRIMARY KEY  (`pag_id`)";
             
$db_original[TBLPREFIX."pdata"]["row"]["pd_id"]["details"] = "INT AUTO_INCREMENT";
$db_original[TBLPREFIX."pdata"]["row"]["pd_file"]["details"] = "TINYINT UNSIGNED";
$db_original[TBLPREFIX."pdata"]["row"]["pd_data"]["details"] = "BLOB NOT NULL";
$db_original[TBLPREFIX."pdata"]["key"]["primary"] = "PRIMARY KEY  (`pd_id`)";
$db_original[TBLPREFIX."pdata"]["key"]["pd_file"] = "KEY `pd_file` (`pd_file`)";

$db_original[TBLPREFIX."placelinks"]["row"]["pl_p_id"]["details"] = "INT";
$db_original[TBLPREFIX."placelinks"]["row"]["pl_gid"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."placelinks"]["row"]["pl_type"]["details"] = "ENUM('INDI','FAM','REPO','SOUR','OBJE','NOTE') NOT NULL"; // Added in 2.0
$db_original[TBLPREFIX."placelinks"]["row"]["pl_file"]["details"] = "TINYINT UNSIGNED";
$db_original[TBLPREFIX."placelinks"]["key"]["plindex_place"] = "KEY `plindex_place` (`pl_p_id`)";
$db_original[TBLPREFIX."placelinks"]["key"]["plindex_gid"] = "KEY `plindex_gid` (`pl_gid`)";
$db_original[TBLPREFIX."placelinks"]["key"]["plindex_file"] = "KEY `plindex_file` (`pl_file`)";
             
$db_original[TBLPREFIX."places"]["row"]["p_id"]["details"] = "INT NOT NULL";
$db_original[TBLPREFIX."places"]["row"]["p_place"]["details"] = "VARCHAR(150) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."places"]["row"]["p_level"]["details"] = "INT";
$db_original[TBLPREFIX."places"]["row"]["p_parent_id"]["details"] = "INT";
$db_original[TBLPREFIX."places"]["row"]["p_file"]["details"] = "TINYINT UNSIGNED";
$db_original[TBLPREFIX."places"]["key"]["primary"] = "PRIMARY KEY  (`p_id`)";
$db_original[TBLPREFIX."places"]["key"]["place_place"] = "KEY `place_place` (`p_place`)";
$db_original[TBLPREFIX."places"]["key"]["place_level"] = "KEY `place_level` (`p_level`)";
$db_original[TBLPREFIX."places"]["key"]["place_parent"] = "KEY `place_parent` (`p_parent_id`)";
$db_original[TBLPREFIX."places"]["key"]["place_file"] = "KEY `place_file` (`p_file`)";
             
$db_original[TBLPREFIX."privacy"]["row"]["p_gedcom"]["details"] = "VARCHAR (64) CHARACTER SET #charset# COLLATE #collate#  NOT NULL default ''";
$db_original[TBLPREFIX."privacy"]["row"]["p_gedcomid"]["details"] = "TINYINT UNSIGNED";
$db_original[TBLPREFIX."privacy"]["row"]["p_privacy_version"]["details"] = "VARCHAR (6) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."privacy"]["row"]["p_priv_hide"]["details"] = "TINYINT";
$db_original[TBLPREFIX."privacy"]["row"]["p_priv_public"]["details"] = "TINYINT";
$db_original[TBLPREFIX."privacy"]["row"]["p_priv_user"]["details"] = "TINYINT";
$db_original[TBLPREFIX."privacy"]["row"]["p_priv_none"]["details"] = "TINYINT";
$db_original[TBLPREFIX."privacy"]["row"]["p_hide_live_people"]["details"] = "VARCHAR (12) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."privacy"]["row"]["p_show_dead_people"]["details"] = "VARCHAR (12) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."privacy"]["row"]["p_show_living_names"]["details"] = "VARCHAR (12) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."privacy"]["row"]["p_show_sources"]["details"] = "VARCHAR (12) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."privacy"]["row"]["p_max_alive_age"]["details"] = "TINYINT UNSIGNED";
$db_original[TBLPREFIX."privacy"]["row"]["p_enable_clippings_cart"]["details"] = "VARCHAR (12) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."privacy"]["row"]["p_show_action_list"]["details"] = "VARCHAR (12) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."privacy"]["row"]["p_link_privacy"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."privacy"]["row"]["p_use_relationship_privacy"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."privacy"]["row"]["p_max_relation_path_length"]["details"] = "TINYINT UNSIGNED";
$db_original[TBLPREFIX."privacy"]["row"]["p_check_marriage_relations"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."privacy"]["row"]["p_check_child_dates"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."privacy"]["row"]["p_privacy_by_year"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."privacy"]["row"]["p_privacy_by_resn"]["details"] = "TINYINT(1)";
$db_original[TBLPREFIX."privacy"]["row"]["p_person_privacy"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."privacy"]["row"]["p_user_privacy"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."privacy"]["row"]["p_global_facts"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."privacy"]["row"]["p_person_facts"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."privacy"]["key"]["primary"] = "PRIMARY KEY  (`p_gedcom`)";
$db_original[TBLPREFIX."privacy"]["key"]["p_gedcomid"] = "KEY `p_gedcomid` (`p_gedcomid`)";
             
$db_original[TBLPREFIX."sources"]["row"]["s_key"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate# "; // Added in 2.0
$db_original[TBLPREFIX."sources"]["row"]["s_id"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."sources"]["row"]["s_file"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."sources"]["row"]["s_name"]["details"] = "VARCHAR(255) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."sources"]["row"]["s_gedrec"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."sources"]["key"]["primary"] = "PRIMARY KEY  (`s_key`)";
$db_original[TBLPREFIX."sources"]["key"]["sour_id_file"] = "UNIQUE KEY `sour_id_file` (`s_id`, `s_file`)"; // Added in 2.0, was primary
$db_original[TBLPREFIX."sources"]["key"]["sour_name"] = "KEY `sour_name` (`s_name`)";
$db_original[TBLPREFIX."sources"]["key"]["sour_file"] = "KEY `sour_file` (`s_file`)";
$db_original[TBLPREFIX."sources"]["key"]["sour_gedrec"] = "FULLTEXT `sour_gedrec` (`s_gedrec`)";

$db_original[TBLPREFIX."source_mapping"]["row"]["sm_id"]["details"] = "INT AUTO_INCREMENT";
$db_original[TBLPREFIX."source_mapping"]["row"]["sm_key"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate# "; // Added in 2.0
$db_original[TBLPREFIX."source_mapping"]["row"]["sm_sid"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."source_mapping"]["row"]["sm_type"]["details"] = "VARCHAR(4) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."source_mapping"]["row"]["sm_gid"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."source_mapping"]["row"]["sm_file"]["details"] = "TINYINT UNSIGNED DEFAULT NULL"; // Renamed from sm_gedfile in 2.0 
$db_original[TBLPREFIX."source_mapping"]["row"]["sm_gedrec"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."source_mapping"]["key"]["primary"] = "PRIMARY KEY  (`sm_id`)";
$db_original[TBLPREFIX."source_mapping"]["key"]["sm_key"] = "KEY `sm_key` (`sm_key` , `sm_file` )"; // Changed from sm_sid and def in 2.0
$db_original[TBLPREFIX."source_mapping"]["key"]["sm_gid"] = "KEY `sm_gid` ( `sm_gid` , `sm_file` )";
$db_original[TBLPREFIX."source_mapping"]["key"]["sm_type"] = "KEY `sm_type` (`sm_sid` ,  `sm_type` , `sm_file` )";

$db_original[TBLPREFIX."soundex"]["row"]["s_id"]["details"] = "INT NOT NULL AUTO_INCREMENT";
$db_original[TBLPREFIX."soundex"]["row"]["s_gid"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT ''";;
$db_original[TBLPREFIX."soundex"]["row"]["s_file"]["details"] = "TINYINT UNSIGNED";
$db_original[TBLPREFIX."soundex"]["row"]["s_type"]["details"] = "ENUM('R','D') NOT NULL";
$db_original[TBLPREFIX."soundex"]["row"]["s_nametype"]["details"] = "ENUM('F','L','P') NOT NULL";
$db_original[TBLPREFIX."soundex"]["row"]["s_code"]["details"] = "VARCHAR(8) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."soundex"]["key"]["primary"] = "PRIMARY KEY  (`s_id`)";
$db_original[TBLPREFIX."soundex"]["key"]["s_gid"] = "KEY `s_gid` (`s_gid`, `s_file`)";
$db_original[TBLPREFIX."soundex"]["key"]["s_file"] = "KEY `s_file` (`s_file`)";
$db_original[TBLPREFIX."soundex"]["key"]["s_code"] = "KEY `s_code` (`s_code`, `s_type`, `s_nametype`)";

$db_original[TBLPREFIX."statscache"]["row"]["gs_file"]["details"] = "TINYINT UNSIGNED"; // Changed from gs_gedcom in 2.0
$db_original[TBLPREFIX."statscache"]["row"]["gs_nr_surnames"]["details"] = "INT NOT NULL DEFAULT '0'";
$db_original[TBLPREFIX."statscache"]["row"]["gs_nr_fams"]["details"] = "INT NOT NULL DEFAULT '0'";
$db_original[TBLPREFIX."statscache"]["row"]["gs_nr_sources"]["details"] = "INT NOT NULL DEFAULT '0'";
$db_original[TBLPREFIX."statscache"]["row"]["gs_nr_media"]["details"] = "INT NOT NULL DEFAULT '0'";
$db_original[TBLPREFIX."statscache"]["row"]["gs_nr_other"]["details"] = "INT NOT NULL DEFAULT '0'";
$db_original[TBLPREFIX."statscache"]["row"]["gs_nr_events"]["details"] = "INT NOT NULL DEFAULT '0'";
$db_original[TBLPREFIX."statscache"]["row"]["gs_earliest_birth_year"]["details"] = "INT(5) NOT NULL DEFAULT '0'";
$db_original[TBLPREFIX."statscache"]["row"]["gs_earliest_birth_gid"]["details"] = "VARCHAR(10) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT '0'";
$db_original[TBLPREFIX."statscache"]["row"]["gs_latest_birth_year"]["details"] = "INT(5) NOT NULL DEFAULT '0'";
$db_original[TBLPREFIX."statscache"]["row"]["gs_latest_birth_gid"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT '0'";
$db_original[TBLPREFIX."statscache"]["row"]["gs_longest_live_years"]["details"] = "INT(5) NOT NULL DEFAULT '0'";
$db_original[TBLPREFIX."statscache"]["row"]["gs_longest_live_gid"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT '0'";
$db_original[TBLPREFIX."statscache"]["row"]["gs_avg_age"]["details"] = "INT(4) NOT NULL DEFAULT '0'";
$db_original[TBLPREFIX."statscache"]["row"]["gs_most_children_gid"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate#  NOT NULL DEFAULT '0'";
$db_original[TBLPREFIX."statscache"]["row"]["gs_most_children_nr"]["details"] = "TINYINT NOT NULL DEFAULT '0'";
$db_original[TBLPREFIX."statscache"]["row"]["gs_avg_children"]["details"] = "FLOAT NOT NULL DEFAULT '0'";
$db_original[TBLPREFIX."statscache"]["key"]["primary"] = "PRIMARY KEY  (`gs_file`)";

$db_original[TBLPREFIX."sysconf"]["row"]["s_name"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate#  NOT NULL";
$db_original[TBLPREFIX."sysconf"]["row"]["s_value"]["details"] = "VARCHAR(255) CHARACTER SET #charset# COLLATE #collate#  NOT NULL";
$db_original[TBLPREFIX."sysconf"]["key"]["primary"] = "PRIMARY KEY  (`s_name`)";

$db_original[TBLPREFIX."users"]["row"]["u_username"]["details"] = "VARBINARY(30) NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."users"]["row"]["u_password"]["details"] = "VARCHAR(255) CHARACTER SET #charset# COLLATE #collate#  DEFAULT NULL";
$db_original[TBLPREFIX."users"]["row"]["u_firstname"]["details"] = "VARCHAR(255) CHARACTER SET #charset# COLLATE #collate#  DEFAULT NULL";
$db_original[TBLPREFIX."users"]["row"]["u_lastname"]["details"] = "VARCHAR(255) CHARACTER SET #charset# COLLATE #collate#  DEFAULT NULL";
$db_original[TBLPREFIX."users"]["row"]["u_canadmin"]["details"] = "VARCHAR(2) CHARACTER SET #charset# COLLATE #collate#  DEFAULT NULL";
$db_original[TBLPREFIX."users"]["row"]["u_email"]["details"] = "TEXT CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."users"]["row"]["u_verified"]["details"] = "CHAR(1) CHARACTER SET #charset# COLLATE #collate#  DEFAULT NULL"; // Def changed in 2.0
$db_original[TBLPREFIX."users"]["row"]["u_verified_by_admin"]["details"] = "CHAR(1) CHARACTER SET #charset# COLLATE #collate#  DEFAULT NULL"; // Def changed in 2.0
$db_original[TBLPREFIX."users"]["row"]["u_language"]["details"] = "VARCHAR(50) CHARACTER SET #charset# COLLATE #collate#  DEFAULT NULL";
// u_pwrequested removed in 2.0
$db_original[TBLPREFIX."users"]["row"]["u_reg_timestamp"]["details"] = "VARCHAR(50) CHARACTER SET #charset# COLLATE #collate#  DEFAULT NULL";
$db_original[TBLPREFIX."users"]["row"]["u_reg_hashcode"]["details"] = "VARCHAR(255) CHARACTER SET #charset# COLLATE #collate#  DEFAULT NULL";
$db_original[TBLPREFIX."users"]["row"]["u_theme"]["details"] = "VARCHAR(50) CHARACTER SET #charset# COLLATE #collate#  DEFAULT NULL";
$db_original[TBLPREFIX."users"]["row"]["u_loggedin"]["details"] = "CHAR(2) CHARACTER SET #charset# COLLATE #collate#  DEFAULT NULL";
$db_original[TBLPREFIX."users"]["row"]["u_sessiontime"]["details"] = "INT(11) DEFAULT NULL";
$db_original[TBLPREFIX."users"]["row"]["u_contactmethod"]["details"] = "VARCHAR(20) CHARACTER SET #charset# COLLATE #collate#  DEFAULT NULL";
$db_original[TBLPREFIX."users"]["row"]["u_visibleonline"]["details"] = "CHAR(2) CHARACTER SET #charset# COLLATE #collate#  DEFAULT NULL";
$db_original[TBLPREFIX."users"]["row"]["u_editaccount"]["details"] = "CHAR(2) CHARACTER SET #charset# COLLATE #collate#  DEFAULT NULL";
$db_original[TBLPREFIX."users"]["row"]["u_defaulttab"]["details"] = "INT(11) DEFAULT NULL";
$db_original[TBLPREFIX."users"]["row"]["u_comment"]["details"] = "VARCHAR(255) CHARACTER SET #charset# COLLATE #collate#  DEFAULT NULL";
$db_original[TBLPREFIX."users"]["row"]["u_comment_exp"]["details"] = "VARCHAR(20) CHARACTER SET #charset# COLLATE #collate#  DEFAULT NULL";
$db_original[TBLPREFIX."users"]["row"]["u_sync_gedcom"]["details"] = "CHAR(2) CHARACTER SET #charset# COLLATE #collate#  DEFAULT NULL";
$db_original[TBLPREFIX."users"]["row"]["u_auto_accept"]["details"] = "CHAR(2) CHARACTER SET #charset# COLLATE #collate#  DEFAULT NULL";
$db_original[TBLPREFIX."users"]["key"]["primary"] = "PRIMARY KEY  (`u_username`)";

$db_original[TBLPREFIX."users_gedcoms"]["row"]["ug_ID"]["details"] = "INT NOT NULL AUTO_INCREMENT";
$db_original[TBLPREFIX."users_gedcoms"]["row"]["ug_username"]["details"] = "VARBINARY(30) NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."users_gedcoms"]["row"]["ug_file"]["details"] = "TINYINT UNSIGNED NOT NULL DEFAULT '0'"; // Changed from ug_gedfile in 2.0
$db_original[TBLPREFIX."users_gedcoms"]["row"]["ug_gedcomid"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."users_gedcoms"]["row"]["ug_rootid"]["details"] = "VARCHAR(64) CHARACTER SET #charset# COLLATE #collate# ";
$db_original[TBLPREFIX."users_gedcoms"]["row"]["ug_canedit"]["details"] = "VARCHAR(7) CHARACTER SET #charset# COLLATE #collate# DEFAULT 'none'";
$db_original[TBLPREFIX."users_gedcoms"]["row"]["ug_gedcomadmin"]["details"] = "ENUM('N','Y','') NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."users_gedcoms"]["row"]["ug_privgroup"]["details"] = "VARCHAR(7) CHARACTER SET #charset# COLLATE #collate# DEFAULT 'access'";
$db_original[TBLPREFIX."users_gedcoms"]["row"]["ug_relationship_privacy"]["details"] = "ENUM('N','Y','') NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."users_gedcoms"]["row"]["ug_max_relation_path"]["details"] = "TINYINT UNSIGNED DEFAULT 0";
$db_original[TBLPREFIX."users_gedcoms"]["row"]["ug_check_marriage_relations"]["details"] = "ENUM('N','Y','') NOT NULL DEFAULT ''"; // Added in 2.0
$db_original[TBLPREFIX."users_gedcoms"]["row"]["ug_hide_live_people"]["details"] = "ENUM('N','Y','') NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."users_gedcoms"]["row"]["ug_show_living_names"]["details"] = "ENUM('N','Y','') NOT NULL DEFAULT ''";
$db_original[TBLPREFIX."users_gedcoms"]["key"]["primary"] = "PRIMARY KEY  (`ug_ID`)";
$db_original[TBLPREFIX."users_gedcoms"]["key"]["ug_user"] = "KEY `ug_user` (`ug_username`)";
$db_original[TBLPREFIX."users_gedcoms"]["key"]["ug_gedid"] = "KEY `ug_gedid` (`ug_gedcomid` ,  `ug_file`)";

ksort($db_original);
?>