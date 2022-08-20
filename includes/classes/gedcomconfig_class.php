<?php
/**
 * Class file for gedcom config
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
 * @package Genmod
 * @subpackage Admin
 * @version $Id: gedcomconfig_class.php 29 2022-07-17 13:18:20Z Boudewijn $
 */

if (stristr($_SERVER["SCRIPT_NAME"],basename(__FILE__))) {
	require "../../intrusion.php";
}

abstract class GedcomConfig {

	public $classname = "GedcomConfig";
	
	public static $GEDCOMID = null;							// Gedcom ID of the gedcom
	public static $GEDCOM = null;							// Gedcom filename of the gedcom
	public static $GEDCOMLANG = "english";					// Assign the default language.  
															// User can override this setting if $ENABLE_MULTI_LANGUAGE = true
	public static $CALENDAR_FORMAT = "gregorian";			// Translate dates to the specified Calendar
															// Options are gregorian, julian, french, jewish, jewish_and_gregorian,
															// hebrew, hebrew_and_gregorian
	public static $DISPLAY_JEWISH_THOUSANDS = false;		// Show Alafim in Jewish dates Similar to php 5.0 CAL_JEWISH_ADD_ALAFIM
	public static $DISPLAY_JEWISH_GERESHAYIM = true;		// Show single and double quotes in Hebrew dates. 
															// Similar to php 5.0 CAL_JEWISH_ADD_GERESHAYIM
	public static $JEWISH_ASHKENAZ_PRONUNCIATION = true;	// Jewish pronunciation option
	public static $USE_RTL_FUNCTIONS = false;				// Use processing to properly display GEDCOM data in RTL languages
	public static $CHARACTER_SET = "UTF-8";					// This is the character set of your gedcom file.  UTF-8 is the default and should work
															// for almost all sites.  If you export your gedcom using ibm windows encoding, then you
															// should put WINDOWS here.
															// NOTE: PHP does NOT support UNICODE so don't try it :-)
	public static $ENABLE_MULTI_LANGUAGE = true;			// Set to true to give users the option of selecting a different language from
															// a dropdown list in the footer and default to the language they have set in their
															// browser settings
	public static $DEFAULT_PEDIGREE_GENERATIONS = "4";		// Set the default number of generations to display on the pedigree charts
	public static $MAX_PEDIGREE_GENERATIONS = "10";			// Set the max number of generations to display on the pedigree charts
	public static $MAX_DESCENDANCY_GENERATIONS = "15";		// Set the max number of generations to display on the descendancy charts
	public static $USE_RIN = false;							// Use the RIN # instead of the regular GEDCOM ID for places where you are asked 
															// to enter an ID
	public static $PEDIGREE_ROOT_ID = "I1";					// Use this line to change the default person who appears on the Pedigree tree
	public static $GEDCOM_ID_PREFIX = "I";					// This is the prefix prepend to newly generated individual records
	public static $SOURCE_ID_PREFIX = "S";					// This is the prefix prepend to newly generated source records
	public static $REPO_ID_PREFIX = "R";					// This is the prefix prepend to newly generated repository records
	public static $FAM_ID_PREFIX = "F";						// This is the prefix prepend to newly generated family records
	public static $MEDIA_ID_PREFIX = "M";					// This is the prefix prepend to newly generated media records
	public static $NOTE_ID_PREFIX = "N";					// This is the prefix prepend to newly generated note records
	public static $KEEP_ACTIONS = 0;						// If 0, actions will be deleted on re-import and actions will be imported. If 1, contrary.
	public static $PEDIGREE_FULL_DETAILS = true;			// Show the birth and death details of an individual on the pedigree tree
	public static $PEDIGREE_LAYOUT = true;					// Set to true for Landscape mode, false for portrait mode
	public static $SHOW_EMPTY_BOXES = true;					// Show empty boxes on charts if the person is unknown
	public static $ZOOM_BOXES = "click";					// When should pedigree boxes zoom.  Values are "disabled", "mouseover", "click"
	public static $LINK_ICONS = "mouseover";				// When should pedigree box extra links show up.  
															// Values are "disabled", "mouseover", "click"
	public static $ABBREVIATE_CHART_LABELS = false;			// Should chart labels like "Birth" be abbreviated as "B"
	public static $SHOW_PARENTS_AGE = true;					// Show age of parents on charts next to the birth date
	public static $REQUIRE_AUTHENTICATION = false;			// Set this to true to force all visitors to login before they can view anything 
															// on the site
	public static $EXCLUDE_REQUIRE_AUTHENTICATION = "";		// Range of IP addresses and/or hostnames to exclude from the above:
															// If require_authentication is true, it will give IP's in this list instant visitors
															// access. If false, it will force these IP's to login and will hide the visitors level
															// data for them.
	public static $WELCOME_TEXT_AUTH_MODE = "1";			// Sets which predefined of custom welcome message will be displayed on the 
															// welcome page in authentication mode
	public static $WELCOME_TEXT_AUTH_MODE_4 = "";			// Customized welcome text to display on login screen if that option is chosen
	public static $WELCOME_TEXT_CUST_HEAD = false;			// Use standard GM header to display with custom welcome text
	public static $SHOW_GEDCOM_RECORD = 1;					// This will provide a link on detail pages that will allow the users with that 
															// rights to view the actual lines from the gedcom file
	public static $EDIT_GEDCOM_RECORD = true;				// Allow users with canEdit privileges to edit the gedcom
	public static $ALLOW_EDIT_GEDCOM = 5;					// This will provide a link on detail pages that will allow the users with that 
															// rights to view the actual lines from the gedcom file
	public static $POSTAL_CODE = false;						// Allow users to choose where to print the postal code. 
															// True is after the city name, false is before the city name
	public static $INDI_EXT_FAM_FACTS = false;				// Show note and object information for family facts in the individuals facts list
	public static $ALPHA_INDEX_LISTS = 500;					// for very long individual and family lists, set this to the value above which 
															// lists must be split in pages by the first letter of their last name.
	public static $LISTS_ALL = true;						// Should the "ALL" option show up in the indilist and famlist
	public static $NAME_FROM_GEDCOM = false;				// By default Genmod uses the name stored in the indexes to get a person's name
															// With some gedcom formats the sortable name stored in the indexes does not get
															// displayed properly and the best way to get the correct display name is from the gedcom
															// However, retrieving the name from the gedcom will slow the program down.
	public static $SHOW_MARRIED_NAMES = false;				// Option to show the married name for females in the indilist
	public static $SHOW_ID_NUMBERS = true;					// Show gedcom id numbers next to individual names
	public static $SHOW_FAM_ID_NUMBERS = true;				// Show gedcom id numbers next to family names
	public static $SHOW_PEDIGREE_PLACES = "9";				// What level to show the birth and death places next to the birth and death dates 
															// on the pedigree and descendency charts.
	public static $SHOW_EXTERNAL_SEARCH = 5;				// Access level for external search
	public static $SHOW_NICK = false;						// Whether or not to show the nickname in parenthesis between first and last name
	public static $NICK_DELIM = "()";						// If nicks are shown, they are contained between these two characters
	public static $MEDIA_EXTERNAL = true;					// Set whether or not to change links starting with http, ftp etc.
	public static $MEDIA_DIRECTORY = "media/";				// Directory where media files are stored
	public static $MEDIA_DIRECTORY_LEVELS = "0";			// The number of sub-directories to keep when getting names of media files
	public static $MEDIA_DIRECTORY_HIDE = ".svn, _svn, ., .., CVS, @eaDir"; // Directories to exclude from mediatrees, at any level.
	public static $SHOW_HIGHLIGHT_IMAGES = true;			// Show highlighted photos on pedigree tree and individual pages.
	public static $USE_THUMBS_MAIN = false;					// For the main image on the individual page, whether or not to use the full res 
															// image or the thumbnail
	public static $THUMBNAIL_WIDTH = "100";					// The width to use when automatically generating thumbnails
	public static $AUTO_GENERATE_THUMBS = true;				// Whether GM should try to automatically generate thumbnails
	public static $MERGE_DOUBLE_MEDIA = 1;					// Option to merge similar embedded media references to one media object
	public static $HIDE_GEDCOM_ERRORS = false;				// A true value will disable error messages for undefined GEDCOM codes.  See the
															// non-standard gedcom codes section of the readme file for more information.
	public static $WORD_WRAPPED_NOTES = false;				// Some programs wrap notes at word boundaries while others wrap notes anywhere. Setting 
															// this to true will add a space between words where they are wrapped in the gedcom
	public static $GEDCOM_DEFAULT_TAB = "0";				// This setting controls which tab on the individual page should first be 
															// displayed to visitors
	public static $SHOW_CONTEXT_HELP = true;				// Show ? links on the page for contextual popup help
	public static $CONTACT_EMAIL = "you@yourdomain.com";	// This is who the user should contact for more information
	public static $CONTACT_METHOD = "messaging2";			// The method to allow users to contact you. options are: mailto, messaging, messaging2
	public static $WEBMASTER_EMAIL = "webmaster@yourdomain.com"; 
															// This is who the user should contact in case of errors
	public static $SUPPORT_METHOD = "messaging2";			// The method to allow users to contact you. options are: mailto, messaging, messaging2
	public static $BCC_WEBMASTER = false;					// Send a Bcc of system generated messages to the webmaster
	public static $HOME_SITE_URL = "https://www.sourceforge.net/projects/genmod";	// Url for your home page
	public static $HOME_SITE_TEXT = "About Genmod";			// Name of your site
	public static $FAVICON = "images/favicon.ico";			// Change to point to your favicon, either relative or absolute
	public static $THEME_DIR = "themes/standard/";			// Directory where display theme files are kept
	public static $ALLOW_THEME_DROPDOWN = false;			// Allows the themes to display theme change dropdown
	public static $SHOW_STATS = false;						// Show execution stats at the bottom of the page
	public static $SHOW_COUNTER = false;					// Show hit counters on portal and individual pages
	public static $DAYS_TO_SHOW_LIMIT = "30";				// Maximum number of days in Upcoming Events block
	public static $COMMON_NAMES_THRESHOLD = "40";			// The minimum number of times a surname must appear before it is shown on the most common surnames list
	public static $COMMON_NAMES_ADD = "";					// A comma seperated list of surnames the admin can add to the common surnames list
	public static $COMMON_NAMES_REMOVE = "";				// A comma seperated list of surnames to ignore in the common surnames list
	public static $META_AUTHOR = "";						// The author of the webpage leave empty to use gedcom contact user name
	public static $META_PUBLISHER = "";						// The publisher of the web page, leave empty to use gedcom contact
	public static $META_COPYRIGHT = "";						// The copyright statement, leave empty to use gedcom contact
	public static $META_DESCRIPTION = "";					// The page description, leave empty to use the gedcom title
	public static $META_PAGE_TOPIC = "";					// The page topic, leave empty to use the gedcom title
	public static $META_AUDIENCE = "All";					// The intended audience
	public static $META_PAGE_TYPE = "Private Homepage";		// The type of page
	public static $META_ROBOTS = "index,follow";			// Instructions for robots
	public static $META_ROBOTS_DENY = "none";					// Instructions for robots if access is denied by robots.txt
	public static $META_REVISIT = "10 days";				// How often crawlers should reindex the site
	public static $META_KEYWORDS = "ancestry, genealogy, pedigree tree";	
															// any aditional keywords, the most common surnames list will be appended to 
															// anything you put in
	public static $META_TITLE = "";							// Optional text that can be added to the html page <title></title> line
	public static $META_SURNAME_KEYWORDS = true;			// Option to use the most common surnames in the keywords field
	public static $CHART_BOX_TAGS = "";						// Optional comma seperated gedcom tags to show in chart boxes
	public static $INCLUDE_IN_SITEMAP = true;				// Whether or not to include in sitemaps (with respect to privacy)
	public static $SHOW_LDS_AT_GLANCE = false;				// Show status of LDS ordinances in chart boxes
	public static $UNDERLINE_NAME_QUOTES = false;			// Convert double quotes in names to underlines
	public static $SPLIT_PLACES = false;					// Split PLAC tag into subtags (town, county, state...) in edit mode
	public static $SHOW_RELATIVES_EVENTS = "_DEAT_SPOU,_BIRT_CHIL,_DEAT_CHIL,_BIRT_GCHI,_DEAT_GCHI,_DEAT_FATH,_DEAT_MOTH,_BIRT_SIBL,_DEAT_SIBL,_BIRT_HSIB,_DEAT_HSIB,_DEAT_GPAR,_BIRT_FSIB,_DEAT_FSIB,_BIRT_MSIB,_DEAT_MSIB";
															// Show birth and death of relatives on individual page
	public static $EXPAND_RELATIVES_EVENTS = false;			// Auto-expand the relatives facts on the individual page
	public static $EDIT_AUTOCLOSE = false;					// Autoclose edit window when update successful
	public static $SOUR_FACTS_UNIQUE = "AUTH,ABBR,TITL,PUBL,TEXT,RESN";
															// Facts for sources of which there can only be one
	public static $SOUR_FACTS_ADD = "NOTE,OBJE,REPO,GNOTE";	// Facts for sources that can be added more than once
	public static $SOUR_QUICK_ADDFACTS = "OBJE";			// Done
	public static $REPO_FACTS_UNIQUE = "NAME,ADDR,RESN";	// Facts for repositories of which there can only be one.
	public static $REPO_FACTS_ADD = "PHON,EMAIL,FAX,WWW,NOTE,GNOTE";
															// Facts for repositories that can be added more than once
	public static $REPO_QUICK_ADDFACTS = "";				// A link to add these facts will display next to the dropdown for add facts
	public static $INDI_FACTS_UNIQUE = "SEX,BIRT,DEAT,CREM,RESN";
															// Facts for individuals of which there can only be one.
	public static $INDI_FACTS_ADD = "ADDR,AFN,CHR,BURI,ADOP,BAPM,BARM,BASM,BLES,CHRA,CONF,EMAIL,FAX,PHON,FCOM,ORDN,NATU,EMIG,IMMI,CENS,PROB,WILL,GRAD,RETI,CAST,DSCR,EDUC,IDNO,NATI,NCHI,NMR,OCCU,PROP,RELI,RESI,SSN,TITL,BAPL,CONL,ENDL,SLGC,_MILI";
															// Facts for individuals that can be added more than once
	public static $INDI_QUICK_ADDFACTS = "BIRT,DEAT,OCCU,RESI";
															// A link to add these facts will display next to the dropdown for add facts
	public static $FAM_FACTS_UNIQUE = "NCHI,MARL,DIV,ANUL,DIVF,ENGA,MARB,MARC,MARS,HUSB,WIFE,RESN";
															// Facts for families of which there can only be one.
	public static $FAM_FACTS_ADD = "CENS,MARR,RESI,SLGS,MARR_CIVIL,MARR_RELIGIOUS,MARR_PARTNERS";
															// Facts for families that can be added more than once
	public static $FAM_QUICK_ADDFACTS = "MARR";				// A link to add these facts will display next to the dropdown for add facts
	public static $MEDIA_FACTS_UNIQUE = "TITL,RESN";		// Facts for media items of which there can only be one.
	public static $MEDIA_FACTS_ADD = "FILE,REFN,NOTE,SOUR,GNOTE";
															// Facts for media items that can be added more than once
	public static $MEDIA_QUICK_ADDFACTS = "";				// A link to add these facts will display next to the dropdown for add facts
	public static $NOTE_FACTS_UNIQUE = "RIN,RESN";			// Facts for notes of which there can only be one.
	public static $NOTE_FACTS_ADD = "";						// Facts for notes that can be added more than once
	public static $NOTE_QUICK_ADDFACTS = "";				// A link to add these facts will display next to the dropdown for add facts
	public static $RSS_FORMAT = "RSS1.0";					// format of RSS to use
	public static $TIME_LIMIT = "60";						// Amount of time to execute before quitting in seconds
															// Set this to 0 to remove all time limits
	public static $DISPLAY_PINYIN = false;					// Option to add pinyin translation to chinese names and places
	public static $DISPLAY_TRANSLITERATE = true;			// Option to add transliteration to Russian names and places
	public static $defaults = null;							// Holder for the default settings
	
	public static $LAST_CHANGE_EMAIL = "0";					// Date stamp when the last notification for changes was sent
	public static $LAST_UPCOMING = null;					// Date stamp when the last cache for upcoming events was built
	public static $LAST_TODAY = null;						// Date stamp when the last cache for todays events was built
	public static $LAST_STATS = null;						// Date stamp when the last cache for gedcom stats was built
	public static $LAST_PLOTDATA = null;					// Date stamp when the last cache for plots was built
	
	public static $MUST_AUTHENTICATE = null;				// Holder for the calculated value of REQUIRE_AUTHENTICATION and
															// EXCLUDE_REQUIRE_AUTHENTICATION
	public static $GEDCONF = array();						// Holder of all stored configurations
	private static $lastmail = null;						// Holder for all LAST_CHANGE_EMAIL values of the gedcoms
	private static $cachenames = array("upcoming", "today", "stats", "plotdata");
															// Array of possible cachenames, used for checking
	
	/** Read the Gedcom configuration settings from the database
	 *
	 * The function reads the GEDCOM configuration settings from the database.
	 * It also sets the max execution time to the new value, if it's the current GEDCOM.
	 *
	 * @author	Genmod Development Team
	 * @param		string	$gedcom		GEDCOM name for which the values are to be retrieved.
	 * @return 	boolean		true if success, false if failed
	**/
	public function ReadGedcomConfig($gedcomid=0) {
		global $GEDCOMID;

		// Save the default settings to assign to unknown gedcoms
		if (is_null(self::$defaults)) {
			self::$defaults = get_class_vars(__CLASS__);
			unset(self::$defaults["classname"]);
			unset(self::$defaults["GEDCOMID"]);
			unset(self::$defaults["defaults"]);
			unset(self::$defaults["GEDCONF"]);
			unset(self::$defaults["MUST_AUTHENTICATE"]);
		}
			
		if (isset(self::$GEDCONF[$gedcomid])) {
			foreach (self::$GEDCONF[$gedcomid] as $var => $value) {
				if ($var == "GEDCOMID") {
					global $$var;
					$$var = $value;
				}					
				if (isset(self::$$var) || is_null(self::$$var)) self::$$var = $value;
			}
		}
		else {
			$found = false;
			$sql = "SELECT * FROM ".TBLPREFIX."gedconf WHERE (gc_gedcomid='".$gedcomid."')";
			$res = NewQuery($sql);
			if ($res) {
				$ct = $res->NumRows($res->result);
				if ($ct != "0") {
					$found = true;
					$gc = array();
					while($row = $res->FetchAssoc($res->result)){
						foreach ($row as $key => $value) {
							$var = strtoupper(substr($key, 3));
							if ($var == "GEDCOMID") {
								global $$var;
								$$var = $value;
							}
							if (property_exists("GedcomConfig", $var)) self::$$var = $value;
							$gc[$var] = $value;
						}
					}
					self::$GEDCONF[$gedcomid] = $gc;
					$res->FreeResult($res->result);
				}
			}
			if (!$found) {
				foreach (self::$defaults as $var => $value) {
					self::$$var = $value;
				}
				self::$GEDCOMID = self::GetNextGedcomId();
				global $GEDCOMID;
				$GEDCOMID = self::$GEDCOMID;
			}
		}
		
		// Set the MUST_AUTHENTICATE variable
		if (SystemFunctions::IPInRange($_SERVER["REMOTE_ADDR"], self::$EXCLUDE_REQUIRE_AUTHENTICATION)) self::$MUST_AUTHENTICATE = !self::$REQUIRE_AUTHENTICATION;
		else self::$MUST_AUTHENTICATE = self::$REQUIRE_AUTHENTICATION;
		
		// If the pinyin table wasn't previously loaded and is required, load it now
		if (self::$DISPLAY_PINYIN) {
			global $pinyin;
			require_once(SystemConfig::$GM_BASE_DIRECTORY."includes/values/pinyin.php");
		}
		//-- This is copied from the config_gedcom.php
		if ($gedcomid == self::$GEDCOMID) @set_time_limit(self::$TIME_LIMIT);
		return true;
	}
	
	/** Store the Gedcom configuration settings in the database
	 *
	 * The function stores all GEDCOM configuration settings in the database.
	 * It also sets the execution time limit to the new value.
	 *
	 * @author	Genmod Development Team
	 * @param		array	$settings	Array with GEDCOM settings
	**/
	public function SetGedcomConfig($settings) {
		global $GEDCOMID;
		
		// Clear the cache
		self::$GEDCONF = array();
	
		// -- First see if the settings already exist
		$sql = "SELECT gc_gedcom FROM ".TBLPREFIX."gedconf WHERE (gc_gedcomid='".$settings["gedcomid"]."')";
		$res = NewQuery($sql);
		$ct = $res->NumRows($res->result);
		if ($ct == "0") {
			// -- New config. We will insert it in the database.
			$col = "(";
			$val = "(";
			$i = "0";
			foreach ($settings as $key => $value) {
				if ($i > 0) {
					$col .= ", ";
					$val .= ", ";
				}
				$col .= "gc_";
				$col .= $key;
				$val .= "'".$value."'";
				$i++;
			}
			$col .= ")";
			$val .= ")";
			$sql = "INSERT INTO ".TBLPREFIX."gedconf ".$col." VALUES ".$val;
	  		$res = NewQuery($sql);
		}
		else {
			$i = "0";
			$str = "";
			foreach ($settings as $key => $value) {
				if ($i > 0) $str .= ", ";
				$str .= "gc_".$key."='".$value."'";
				$i++;
			}
			$sql = "UPDATE ".TBLPREFIX."gedconf SET ".$str." WHERE gc_gedcomid='".$settings["gedcomid"]."'";
	  		$res = NewQuery($sql);
		}
		//-- This is copied from the config_gedcom.php. Added: only re-set the limit 
		//-- when it's the current gedcom.
		if ($settings["gedcom"] == get_gedcom_from_id(self::$GEDCOMID)) @set_time_limit(self::$TIME_LIMIT);
		
		return;
	}
		
	/** Delete Gedcom configuration settings from the database
	 *
	 * The function deletes GEDCOM configuration settings from the database.
	 *
	 * @author	Genmod Development Team
	 * @param		string	$gedcom		GEDCOM name for which the values are to be deleted.
	**/
	public function DeleteGedcomConfig($gedcomid) {
		global $DBCONN;
	
		if (!$DBCONN->connected) return false;
		unset(self::$GEDCONF[$gedcomid]);
		$sql = "DELETE FROM ".TBLPREFIX."gedconf WHERE gc_gedcomid='".$gedcomid."'";
		$res = NewQuery($sql);
		return;
	}
	
	public function GetHighMaxTime() {

		// Retrieve the maximum maximum execution time from the gedcom settings
		$sql = "SELECT max(gc_time_limit) FROM ".TBLPREFIX."gedconf";
		$res = NewQuery($sql);
		if ($res) while($row = $res->FetchRow()) return $row["0"];
		else return self::$TIME_LIMIT;
	}

	public function GetLastNotifMail() {
		
		if (is_null(self::$lastmail)) {
			$sql = "SELECT gc_gedcom, gc_last_change_email from ".TBLPREFIX."gedconf";
			$res = NewQuery($sql);
			if ($res) {
				while ($row = $res->FetchAssoc()) {
					self::$lastmail[$row["gc_gedcom"]] = $row["gc_last_change_email"];
				}
			}
		}
		return self::$lastmail;
	}
	
	public function SetLastNotifMail($ged) {
		
		$time = time();
		$sql = "UPDATE ".TBLPREFIX."gedconf SET gc_last_change_email='".$time."' WHERE gc_gedcomid='".$ged."'";
		$res = NewQuery($sql);
		self::$lastmail[$ged] = $time;
		return true;
	}
	
	public function SetPedigreeRootId($id, $ged) {
	
		$sql = "UPDATE ".TBLPREFIX."gedconf SET gc_pedigree_root_id='".$id."' WHERE gc_gedcomid='".$ged."'";
		$res = NewQuery($sql);
		return true;
	}

	public function GetLastCacheDate($cache, $ged) {

		if (!in_array($cache, self::$cachenames)) return false;
		$sql = "SELECT gc_gedcom, gc_last_".$cache." FROM ".TBLPREFIX."gedconf WHERE gc_gedcomid='".$ged."'";
		$res = NewQuery($sql);
		if ($res) {
			if ($res->NumRows() == 0) return false;
			else {
				while ($row = $res->FetchAssoc()) {
					return $row["gc_last_".$cache];
				}
			}
		}
		else return false;
	}
	
	public function GetAllLastCacheDates($gedid="") {
		global $GEDCOMID;
	
		if (empty($gedid)) $gedid = $GEDCOMID;
		$sql = "SELECT gc_last_upcoming, gc_last_today, gc_last_stats FROM ".TBLPREFIX."gedconf WHERE gc_gedcomid='".$gedid."'";
		$res = NewQuery($sql);
		if ($res) {
			$row = $res->fetchAssoc();
			return $row;
		}
		else return false;
	}
	
	public function SetLastCacheDate($cache, $value, $ged) {
		
		if (!in_array($cache, self::$cachenames)) return false;
		$sql = "UPDATE ".TBLPREFIX."gedconf SET gc_last_".$cache."='".$value."' WHERE gc_gedcomid='".$ged."'";
		$res = NewQuery($sql);
		return true;
	}

	public function ResetCaches($gedid="") {
	
		// Reset todays events cache
		$sql = "UPDATE ".TBLPREFIX."gedconf SET gc_last_today='0', gc_last_upcoming='0', gc_last_stats='0', gc_last_plotdata='0' ";
		if (!empty($ged)) $sql .= "WHERE gc_gedcomid='".$gedid."'";
		$res = NewQuery($sql);
		return true;
	}
	
	public function GetGedcomLanguage($gedid) {
	
		$sql = "SELECT gc_gedcomlang FROM ".TBLPREFIX."gedconf WHERE gc_gedcomid='".$gedid."'";
		$res = NewQuery($sql);
		if ($res->NumRows() == 0) return false;
		$lang = $res->FetchAssoc();
		return $lang["gc_gedcomlang"];
	}
	
	public function AnyGedcomHasAuth() {
		
		$sql = "SELECT count(gc_require_authentication) as number FROM ".TBLPREFIX."gedconf WHERE gc_require_authentication=1";
		$res = NewQuery($sql);
		if ($res->NumRows() == 0) return false;
		$row = $res->FetchAssoc();
		return $row["number"];
	}
	
	private function GetNextGedcomId() {
		
		$sql = "SELECT MAX(gc_gedcomid) as number FROM ".TBLPREFIX."gedconf WHERE 1";
		$res = NewQuery($sql);
		if ($res->NumRows() == 0) return 1;
		$row = $res->FetchAssoc();
		return $row["number"] + 1;
	}
}
?>