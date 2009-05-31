<?php
/**
 * Standard file of language_settings.php
 *
 * -> NEVER manually delete or edit this file <-
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
 * $Id: lang_settings_std.php,v 1.1 2005/10/23 21:48:42 roland-d Exp $
 *
 * @package Genmod
 * @subpackage Languages
 */

//-- NEVER manually delete or edit this entry and every line below this entry! --START--//

// Array definition of language_settings
$language_settings = array();

//-- settings for czech
$lang = array();
$lang["gm_langname"]		= "czech";
$lang["gm_lang_use"]		= false;
$lang["gm_lang"]		= "Čeština";
$lang["lang_short_cut"]		= "cz";
$lang["langcode"]		= "cs;cz;";
$lang["gm_language"]		= "languages/lang.cz.php";
$lang["confighelpfile"]		= "languages/configure_help.cz.php";
$lang["helptextfile"]		= "languages/help_text.cz.php";
$lang["flagsfile"]		= "images/flags/czech republic.gif";
$lang["factsfile"]		= "languages/facts.cz.php";
$lang["DATE_FORMAT"]		= "D. M Y";
$lang["TIME_FORMAT"]		= "G:i:s";
$lang["WEEK_START"]		= "1";
$lang["TEXT_DIRECTION"]		= "ltr";
$lang["NAME_REVERSE"]		= false;
$lang["ALPHABET_upper"]		= "AÁBCČDĎEĚÉFGHIÍJKLMNŇOÓPQRŘSŠTŤUÚŮVWXYÝZŽ";
$lang["ALPHABET_lower"]		= "aábcčdďeěéfghiíjklmnňoópqrřsštťuúůvwxyýzž";
$language_settings["czech"]	= $lang;

//-- settings for german
$lang = array();
$lang["gm_langname"]		= "german";
$lang["gm_lang_use"]		= false;
$lang["gm_lang"]		= "Deutsch";
$lang["lang_short_cut"]		= "de";
$lang["langcode"]		= "de;de-de;de-at;de-li;de-lu;de-ch;";
$lang["gm_language"]		= "languages/lang.de.php";
$lang["confighelpfile"]		= "languages/configure_help.de.php";
$lang["helptextfile"]		= "languages/help_text.de.php";
$lang["flagsfile"]		= "images/flags/germany.gif";
$lang["factsfile"]		= "languages/facts.de.php";
$lang["DATE_FORMAT"]		= "D. M Y";
$lang["TIME_FORMAT"]		= "H:i:s";
$lang["WEEK_START"]		= "1";
$lang["TEXT_DIRECTION"]		= "ltr";
$lang["NAME_REVERSE"]		= false;
$lang["ALPHABET_upper"]		= "ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÜ";
$lang["ALPHABET_lower"]		= "abcdefghijklmnopqrstuvwxyzäöüß";
$language_settings["german"]	= $lang;

//-- settings for english
$lang = array();
$lang["gm_langname"]		= "english";
$lang["gm_lang_use"]		= true;
$lang["gm_lang"]		= "English";
$lang["lang_short_cut"]		= "en";
$lang["langcode"]		= "en;en-us;en-au;en-bz;en-ca;en-ie;en-jm;en-nz;en-ph;en-za;en-tt;en-gb;en-zw;";
$lang["gm_language"]		= "languages/lang.en.php";
$lang["confighelpfile"]		= "languages/configure_help.en.php";
$lang["helptextfile"]		= "languages/help_text.en.php";
$lang["flagsfile"]		= "images/flags/usa.gif";
$lang["factsfile"]		= "languages/facts.en.php";
$lang["DATE_FORMAT"]		= "D M Y";
$lang["TIME_FORMAT"]		= "g:i:sa";
$lang["WEEK_START"]		= "0";
$lang["TEXT_DIRECTION"]		= "ltr";
$lang["NAME_REVERSE"]		= false;
$lang["ALPHABET_upper"]		= "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
$lang["ALPHABET_lower"]		= "abcdefghijklmnopqrstuvwxyz";
$language_settings["english"]	= $lang;

//-- settings for spanish
$lang = array();
$lang["gm_langname"]		= "spanish";
$lang["gm_lang_use"]		= false;
$lang["gm_lang"]		= "Español";
$lang["lang_short_cut"]		= "es";
$lang["langcode"]		= "es;es-bo;es-cl;es-co;es-cr;es-do;es-ec;es-sv;es-gt;es-hn;es-mx;es-ni;es-pa;es-py;es-pe;es-pr;es-us;es-uy;es-ve;";
$lang["gm_language"]		= "languages/lang.es.php";
$lang["confighelpfile"]		= "languages/configure_help.es.php";
$lang["helptextfile"]		= "languages/help_text.es.php";
$lang["flagsfile"]		= "images/flags/spain.gif";
$lang["factsfile"]		= "languages/facts.es.php";
$lang["DATE_FORMAT"]		= "D M Y";
$lang["TIME_FORMAT"]		= "g:i:sa";
$lang["WEEK_START"]		= "0";
$lang["TEXT_DIRECTION"]		= "ltr";
$lang["NAME_REVERSE"]		= false;
$lang["ALPHABET_upper"]		= "ABCDEFGHIJKLMNÑOPQRSTUVWXYZ";
$lang["ALPHABET_lower"]		= "abcdefghijklmnñopqrstuvwxyz";
$language_settings["spanish"]	= $lang;

//-- settings for spanish-ar
$lang = array();
$lang["gm_langname"]		= "spanish-ar";
$lang["gm_lang_use"]		= false;
$lang["gm_lang"]		= "Español Latinoamericano";
$lang["lang_short_cut"]		= "es-ar";
$lang["langcode"]		= "es-ar;";
$lang["gm_language"]		= "languages/lang.es-ar.php";
$lang["confighelpfile"]		= "languages/configure_help.es-ar.php";
$lang["helptextfile"]		= "languages/help_text.es-ar.php";
$lang["flagsfile"]		= "images/flags/argentina.gif";
$lang["factsfile"]		= "languages/facts.es-ar.php";
$lang["DATE_FORMAT"]		= "D M Y";
$lang["TIME_FORMAT"]		= "g:i:sa";
$lang["WEEK_START"]		= "0";
$lang["TEXT_DIRECTION"]		= "ltr";
$lang["NAME_REVERSE"]		= false;
$lang["ALPHABET_upper"]		= "ABCDEFGHIJKLMNÑOPQRSTUVWXYZ";
$lang["ALPHABET_lower"]		= "abcdefghijklmnñopqrstuvwxyz";
$language_settings["spanish-ar"]	= $lang;

//-- settings for french
$lang = array();
$lang["gm_langname"]		= "french";
$lang["gm_lang_use"]		= false;
$lang["gm_lang"]		= "Français";
$lang["lang_short_cut"]		= "fr";
$lang["langcode"]		= "fr;fr-be;fr-ca;fr-lu;fr-mc;fr-ch;";
$lang["gm_language"]		= "languages/lang.fr.php";
$lang["confighelpfile"]		= "languages/configure_help.fr.php";
$lang["helptextfile"]		= "languages/help_text.fr.php";
$lang["flagsfile"]		= "images/flags/france.gif";
$lang["factsfile"]		= "languages/facts.fr.php";
$lang["DATE_FORMAT"]		= "D j F Y";
$lang["TIME_FORMAT"]		= "H:i:s";
$lang["WEEK_START"]		= "0";
$lang["TEXT_DIRECTION"]		= "ltr";
$lang["NAME_REVERSE"]		= false;
$lang["ALPHABET_upper"]		= "AÀÂÆBCÇDEÉÈËÊFGHIÏÎJKLMNOÔŒPQRSTUÙÛVWXYZ";
$lang["ALPHABET_lower"]		= "aàâæbcçdeéèëêfghiïîjklmnoôœpqrstuùûvwxyz";

$language_settings["french"]	= $lang;

//-- settings for italian
$lang = array();
$lang["gm_langname"]		= "italian";
$lang["gm_lang_use"]		= false;
$lang["gm_lang"]		= "Italiano";
$lang["lang_short_cut"]		= "it";
$lang["langcode"]		= "it;it-ch;";
$lang["gm_language"]		= "languages/lang.it.php";
$lang["confighelpfile"]		= "languages/configure_help.it.php";
$lang["helptextfile"]		= "languages/help_text.it.php";
$lang["flagsfile"]		= "images/flags/italy.gif";
$lang["factsfile"]		= "languages/facts.it.php";
$lang["DATE_FORMAT"]		= "D M Y";
$lang["TIME_FORMAT"]		= "g:i:sa";
$lang["WEEK_START"]		= "0";
$lang["TEXT_DIRECTION"]		= "ltr";
$lang["NAME_REVERSE"]		= false;
$lang["ALPHABET_upper"]		= "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
$lang["ALPHABET_lower"]		= "abcdefghijklmnopqrstuvwxyz";
$language_settings["italian"]	= $lang;

//-- settings for hungarian
$lang = array();
$lang["gm_langname"]		= "hungarian";
$lang["gm_lang_use"]		= false;
$lang["gm_lang"]		= "Magyar";
$lang["lang_short_cut"]		= "hu";
$lang["langcode"]		= "hu;";
$lang["gm_language"]		= "languages/lang.hu.php";
$lang["confighelpfile"]		= "languages/configure_help.hu.php";
$lang["helptextfile"]		= "languages/help_text.hu.php";
$lang["flagsfile"]		= "images/flags/hungary.gif";
$lang["factsfile"]		= "languages/facts.hu.php";
$lang["DATE_FORMAT"]		= "Y. M D.";
$lang["TIME_FORMAT"]		= "g:i:sa";
$lang["WEEK_START"]		= "1";
$lang["TEXT_DIRECTION"]		= "ltr";
$lang["NAME_REVERSE"]		= true;
$lang["ALPHABET_upper"]		= "AÁBCDEÉFGHIÍJKLMNOÓÖŐPQRSTUÚÜŰVWXYZ";
$lang["ALPHABET_lower"]		= "aábcdeéfghiíjklmnoóöőpqrstuúüűvwxyz";
$language_settings["hungarian"]	= $lang;

//-- settings for dutch
$lang = array();
$lang["gm_langname"]		= "dutch";
$lang["gm_lang_use"]		= false;
$lang["gm_lang"]		= "Nederlands";
$lang["lang_short_cut"]		= "nl";
$lang["langcode"]		= "nl;nl-be;";
$lang["gm_language"]		= "languages/lang.nl.php";
$lang["confighelpfile"]		= "languages/configure_help.nl.php";
$lang["helptextfile"]		= "languages/help_text.nl.php";
$lang["flagsfile"]		= "images/flags/netherlands.gif";
$lang["factsfile"]		= "languages/facts.nl.php";
$lang["DATE_FORMAT"]		= "D M Y";
$lang["TIME_FORMAT"]		= "G:i:s";
$lang["WEEK_START"]		= "1";
$lang["TEXT_DIRECTION"]		= "ltr";
$lang["NAME_REVERSE"]		= false;
$lang["ALPHABET_upper"]		= "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
$lang["ALPHABET_lower"]		= "abcdefghijklmnopqrstuvwxyz";
$language_settings["dutch"]	= $lang;

//-- settings for norwegian
$lang = array();
$lang["gm_langname"]		= "norwegian";
$lang["gm_lang_use"]		= false;
$lang["gm_lang"]		= "Norsk";
$lang["lang_short_cut"]		= "no";
$lang["langcode"]		= "no;nb;nn;";
$lang["gm_language"]		= "languages/lang.no.php";
$lang["confighelpfile"]		= "languages/configure_help.no.php";
$lang["helptextfile"]		= "languages/help_text.no.php";
$lang["flagsfile"]		= "images/flags/norway.gif";
$lang["factsfile"]		= "languages/facts.no.php";
$lang["DATE_FORMAT"]		= "D. M Y";
$lang["TIME_FORMAT"]		= "H:i:s";
$lang["WEEK_START"]		= "1";
$lang["TEXT_DIRECTION"]		= "ltr";
$lang["NAME_REVERSE"]		= false;
$lang["ALPHABET_upper"]		= "ABCDEFGHIJKLMNOPQRSTUVWXYZÆØÅ";
$lang["ALPHABET_lower"]		= "abcdefghijklmnopqrstuvwxyzæøå";
$language_settings["norwegian"]	= $lang;

//-- settings for polish
$lang = array();
$lang["gm_langname"]		= "polish";
$lang["gm_lang_use"]		= false;
$lang["gm_lang"]		= "Polski";
$lang["lang_short_cut"]		= "pl";
$lang["langcode"]		= "pl;";
$lang["gm_language"]		= "languages/lang.pl.php";
$lang["confighelpfile"]		= "languages/configure_help.pl.php";
$lang["helptextfile"]		= "languages/help_text.pl.php";
$lang["flagsfile"]		= "images/flags/poland.gif";
$lang["factsfile"]		= "languages/facts.pl.php";
$lang["DATE_FORMAT"]		= "D M Y";
$lang["TIME_FORMAT"]		= "G:i:s";
$lang["WEEK_START"]		= "1";
$lang["TEXT_DIRECTION"]		= "ltr";
$lang["NAME_REVERSE"]		= false;
$lang["ALPHABET_upper"]		= "AĄBCĆDEĘFGHIJKLŁMNŃOÓPQRSŚTUVWXYZŹŻ";
$lang["ALPHABET_lower"]		= "aąbcćdeęfghijklłmnńoópqrsśtuvwxyzźż";
$language_settings["polish"]	= $lang;

//-- settings for portuguese-br
$lang = array();
$lang["gm_langname"]		= "portuguese-br";
$lang["gm_lang_use"]		= false;
$lang["gm_lang"]		= "Português";
$lang["lang_short_cut"]		= "pt-br";
$lang["langcode"]		= "pt;pt-br;";
$lang["gm_language"]		= "languages/lang.pt-br.php";
$lang["confighelpfile"]		= "languages/configure_help.pt-br.php";
$lang["helptextfile"]		= "languages/help_text.pt-br.php";
$lang["flagsfile"]		= "images/flags/brazil.gif";
$lang["factsfile"]		= "languages/facts.pt-br.php";
$lang["DATE_FORMAT"]		= "D M Y";
$lang["TIME_FORMAT"]		= "g:i:sa";
$lang["WEEK_START"]		= "0";
$lang["TEXT_DIRECTION"]		= "ltr";
$lang["NAME_REVERSE"]		= false;
$lang["ALPHABET_upper"]		= "ABCDEFGHIJKLMNÑOPQRSTUVWXYZ";
$lang["ALPHABET_lower"]		= "abcdefghijklmnñopqrstuvwxyz";
$language_settings["portuguese-br"]	= $lang;

//-- settings for finnish
$lang = array();
$lang["gm_langname"]		= "finnish";
$lang["gm_lang_use"]		= false;
$lang["gm_lang"]		= "Suomi";
$lang["lang_short_cut"]		= "fi";
$lang["langcode"]		= "fi;";
$lang["gm_language"]		= "languages/lang.fi.php";
$lang["confighelpfile"]		= "languages/configure_help.fi.php";
$lang["helptextfile"]		= "languages/help_text.fi.php";
$lang["flagsfile"]		= "images/flags/finland.gif";
$lang["factsfile"]		= "languages/facts.fi.php";
$lang["DATE_FORMAT"]		= "D. M Y";
$lang["TIME_FORMAT"]		= "H:i:s";
$lang["WEEK_START"]		= "1";
$lang["TEXT_DIRECTION"]		= "ltr";
$lang["NAME_REVERSE"]		= false;
$lang["ALPHABET_upper"]		= "ABCDEFGHIJKLMNOPQRSTUVWXYZÅÄÖ";
$lang["ALPHABET_lower"]		= "abcdefghijklmnopqrstuvwxyzåäö";
$language_settings["finnish"]	= $lang;

//-- settings for swedish
$lang = array();
$lang["gm_langname"]		= "swedish";
$lang["gm_lang_use"]		= false;
$lang["gm_lang"]		= "Svenska";
$lang["lang_short_cut"]		= "sv";
$lang["langcode"]		= "sv;sv-fi;";
$lang["gm_language"]		= "languages/lang.sv.php";
$lang["confighelpfile"]		= "languages/configure_help.sv.php";
$lang["helptextfile"]		= "languages/help_text.sv.php";
$lang["flagsfile"]		= "images/flags/sweden.gif";
$lang["factsfile"]		= "languages/facts.sv.php";
$lang["DATE_FORMAT"]		= "D M Y";
$lang["TIME_FORMAT"]		= "H:i:s";
$lang["WEEK_START"]		= "1";
$lang["TEXT_DIRECTION"]		= "ltr";
$lang["NAME_REVERSE"]		= false;
$lang["ALPHABET_upper"]		= "ABCDEFGHIJKLMNOPQRSTUVWXYZÅÄÖ";
$lang["ALPHABET_lower"]		= "abcdefghijklmnopqrstuvwxyzåäö";
$language_settings["swedish"]	= $lang;

//-- settings for turkish
$lang = array();
$lang["gm_langname"]		= "turkish";
$lang["gm_lang_use"]		= false;
$lang["gm_lang"]		= "Türkçe";
$lang["lang_short_cut"]		= "tr";
$lang["langcode"]		= "tr;";
$lang["gm_language"]		= "languages/lang.tr.php";
$lang["confighelpfile"]		= "languages/configure_help.tr.php";
$lang["helptextfile"]		= "languages/help_text.tr.php";
$lang["flagsfile"]		= "images/flags/turkey.gif";
$lang["factsfile"]		= "languages/facts.tr.php";
$lang["DATE_FORMAT"]		= "D M Y";
$lang["TIME_FORMAT"]		= "G:i:s";
$lang["WEEK_START"]		= "1";
$lang["TEXT_DIRECTION"]		= "ltr";
$lang["NAME_REVERSE"]		= false;
$lang["ALPHABET_upper"]		= "ABCÇDEFGĞHIİJKLMNOÖPRSŞTUÜVYZ";
$lang["ALPHABET_lower"]		= "abcçdefgğhıijklmnoöprsştuüvyz";
$language_settings["turkish"]	= $lang;

//-- settings for chinese
$lang = array();
$lang["gm_langname"]		= "chinese";
$lang["gm_lang_use"]		= false;
$lang["gm_lang"]		= "繁體中文";
$lang["lang_short_cut"]		= "zh";
$lang["langcode"]		= "zh;zh-cn;zh-hk;zh-mo;zh-sg;zh-tw;";
$lang["gm_language"]		= "languages/lang.zh.php";
$lang["confighelpfile"]		= "languages/configure_help.zh.php";
$lang["helptextfile"]		= "languages/help_text.zh.php";
$lang["flagsfile"]		= "images/flags/china.gif";
$lang["factsfile"]		= "languages/facts.zh.php";
$lang["DATE_FORMAT"]		= "Y年 M D日";
$lang["TIME_FORMAT"]		= "g:i:sa";
$lang["WEEK_START"]		= "0";
$lang["TEXT_DIRECTION"]		= "ltr";
$lang["NAME_REVERSE"]		= true;
$lang["ALPHABET_upper"]		= "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
$lang["ALPHABET_lower"]		= "abcdefghijklmnopqrstuvwxyz";
$language_settings["chinese"]	= $lang;

//-- settings for hebrew
$lang = array();
$lang["gm_langname"]		= "hebrew";
$lang["gm_lang_use"]		= false;
$lang["gm_lang"]		= "‏עברית";
$lang["lang_short_cut"]		= "he";
$lang["langcode"]		= "he;";
$lang["gm_language"]		= "languages/lang.he.php";
$lang["confighelpfile"]		= "languages/configure_help.he.php";
$lang["helptextfile"]		= "languages/help_text.he.php";
$lang["flagsfile"]		= "images/flags/israel.gif";
$lang["factsfile"]		= "languages/facts.he.php";
$lang["DATE_FORMAT"]		= "D M Y";
$lang["TIME_FORMAT"]		= "G:i:s";
$lang["WEEK_START"]		= "0";
$lang["TEXT_DIRECTION"]		= "rtl";
$lang["NAME_REVERSE"]		= false;
$lang["ALPHABET_upper"]		= "אבגדהוזחטיכךלמםנןסעפףצץקרשת";
$lang["ALPHABET_lower"]		= "אבגדהוזחטיכךלמםנןסעפףצץקרשת";
$language_settings["hebrew"]	= $lang;

//-- settings for russian
$lang = array();
$lang["gm_langname"]		= "russian";
$lang["gm_lang_use"]		= false;
$lang["gm_lang"]		= "русский";
$lang["lang_short_cut"]		= "ru";
$lang["langcode"]		= "ru;ru-md;";
$lang["gm_language"]		= "languages/lang.ru.php";
$lang["confighelpfile"]		= "languages/configure_help.ru.php";
$lang["helptextfile"]		= "languages/help_text.ru.php";
$lang["flagsfile"]		= "images/flags/russia.gif";
$lang["factsfile"]		= "languages/facts.ru.php";
$lang["DATE_FORMAT"]		= "D M Y";
$lang["TIME_FORMAT"]		= "g:i:sa";
$lang["WEEK_START"]		= "0";
$lang["TEXT_DIRECTION"]		= "ltr";
$lang["NAME_REVERSE"]		= false;
$lang["ALPHABET_upper"]		= "АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯ";
$lang["ALPHABET_lower"]		= "абвгдеёжзийклмнопрстуфхцчшщъыьэюя";
$language_settings["russian"]	= $lang;

//-- settings for greek
$lang = array();
$lang["gm_langname"]		= "greek";
$lang["gm_lang_use"]		= false;
$lang["gm_lang"]		= "Ελληνικά";
$lang["lang_short_cut"]		= "el";
$lang["langcode"]		= "el;";
$lang["gm_language"]		= "languages/lang.el.php";
$lang["confighelpfile"]		= "languages/configure_help.el.php";
$lang["helptextfile"]		= "languages/help_text.el.php";
$lang["flagsfile"]		= "images/flags/greece.gif";
$lang["factsfile"]		= "languages/facts.el.php";
$lang["DATE_FORMAT"]		= "D M Y";
$lang["TIME_FORMAT"]		= "g:i:sa";
$lang["WEEK_START"]		= "0";
$lang["TEXT_DIRECTION"]		= "ltr";
$lang["NAME_REVERSE"]		= false;
$lang["ALPHABET_upper"]		= "ΆΑΒΓΔΈΕΖΗΘΊΪΪΙΚΛΜΝΞΌΟΠΡΣΣΤΎΫΫΥΦΧΨΏΩ";
$lang["ALPHABET_lower"]		= "άαβγδέεζηθίϊΐικλμνξόοπρσςτύϋΰυφχψώω";
$language_settings["greek"]	= $lang;

//-- settings for arabic
$lang = array();
$lang["gm_langname"]		= "arabic";
$lang["gm_lang_use"]		= false;
$lang["gm_lang"]		= "عربي";
$lang["lang_short_cut"]		= "ar";
$lang["langcode"]		= "ar;ar-ae;ar-bh;ar-dz;ar-eg;ar-iq;ar-jo;ar-kw;ar-lb;ar-ly;ar-ma;ar-om;ar-qa;ar-sa;ar-sy;ar-tn;ar-ye;";
$lang["gm_language"]		= "languages/lang.ar.php";
$lang["confighelpfile"]		= "languages/configure_help.ar.php";
$lang["helptextfile"]		= "languages/help_text.ar.php";
$lang["flagsfile"]		= "images/flags/arab league.gif";
$lang["factsfile"]		= "languages/facts.ar.php";
$lang["DATE_FORMAT"]		= "D M Y";
$lang["TIME_FORMAT"]		= "h:i:sA";
$lang["WEEK_START"]		= "0";
$lang["TEXT_DIRECTION"]		= "rtl";
$lang["NAME_REVERSE"]		= false;
$lang["ALPHABET_upper"]		= "ابتثجحخدذرزسشصضطظعغفقكلمنهويآةىی";
$lang["ALPHABET_lower"]		= "ابتثجحخدذرزسشصضطظعغفقكلمنهويآةىی";
// arabian numbers                    "٠١٢٣٤٥٦٧٨٩"
// iranian/pakistani/indian numbers   "۰۱۲۳۴۵۶۷۸۹"; 
// 
$language_settings["arabic"]	= $lang;

//-- settings for lithuanian
$lang = array();
$lang["gm_langname"]		= "lithuanian";
$lang["gm_lang_use"]		= false;
$lang["gm_lang"]		= "Lietuvių";
$lang["lang_short_cut"]		= "lt";
$lang["langcode"]		= "lt;";
$lang["gm_language"]		= "languages/lang.lt.php";
$lang["confighelpfile"]		= "languages/configure_help.lt.php";
$lang["helptextfile"]		= "languages/help_text.lt.php";
$lang["flagsfile"]		= "images/flags/lithuania.gif";
$lang["factsfile"]		= "languages/facts.lt.php";
$lang["DATE_FORMAT"]		= "Y M D";
$lang["TIME_FORMAT"]		= "g:i:sa";
$lang["WEEK_START"]		= "1";
$lang["TEXT_DIRECTION"]		= "ltr";
$lang["NAME_REVERSE"]		= false;
$lang["ALPHABET_upper"]		= "AĄBCČDEĘĖFGHIYĮJKLMNOPRSŠTUŲŪVZŽ";
$lang["ALPHABET_lower"]		= "aąbcčdeęėfghiyįjklmnoprsštuųūvzž";
$language_settings["lithuanian"]	= $lang;

//-- settings for danish
$lang = array();
$lang["gm_langname"]		= "danish";
$lang["gm_lang_use"]		= false;
$lang["gm_lang"]		= "Dansk";
$lang["lang_short_cut"]		= "da";
$lang["langcode"]		= "da;";
$lang["gm_language"]		= "languages/lang.da.php";
$lang["confighelpfile"]		= "languages/configure_help.da.php";
$lang["helptextfile"]		= "languages/help_text.da.php";
$lang["flagsfile"]		= "images/flags/denmark.gif";
$lang["factsfile"]		= "languages/facts.da.php";
$lang["DATE_FORMAT"]		= "D M Y";
$lang["TIME_FORMAT"]		= "G:i:s";
$lang["WEEK_START"]		= "1";
$lang["TEXT_DIRECTION"]		= "ltr";
$lang["NAME_REVERSE"]		= false;
$lang["ALPHABET_upper"]		= "ABCDEFGHIJKLMNOPQRSTUVWXYZÆØÅ";
$lang["ALPHABET_lower"]		= "abcdefghijklmnopqrstuvwxyzæøå";
$language_settings["danish"]	= $lang;

//-- settings for Vietnamese
$lang = array();
$lang["gm_langname"]		= "vietnamese";
$lang["gm_lang_use"]		= false;
$lang["gm_lang"]		= "Tiếng Việt";
$lang["lang_short_cut"]		= "vi";
$lang["langcode"]		= "vi;";
$lang["gm_language"]		= "languages/lang.vi.php";
$lang["confighelpfile"]		= "languages/configure_help.vi.php";
$lang["helptextfile"]		= "languages/help_text.vi.php";
$lang["flagsfile"]		= "images/flags/vietnam.gif";
$lang["factsfile"]		= "languages/facts.vi.php";
$lang["DATE_FORMAT"]		= "D M Y";
$lang["TIME_FORMAT"]		= "g:i:sa";
$lang["WEEK_START"]		= "0";
$lang["TEXT_DIRECTION"]		= "ltr";
$lang["NAME_REVERSE"]		= true;
$lang["ALPHABET_upper"]		= "AÀẢÃÁẠĂẰẲẴẮẶÂẦẨẪẤẬBCDĐEÈẺẼÉẸÊỀỂỄẾỆFGHIÌỈĨÍỊJKLMNOÒỎÕÓỌÔỒỔỖỐỘƠỜỞỠỚỢPQRSTUÙỦŨÚỤƯỪỬỮỨỰVWXYỲỶỸÝỴZ";
$lang["ALPHABET_lower"]		= "aàảãáạăằẳẵắặâầẩẫấậbcdđeèẻẽéẹêềểễếệfghiìỉĩíịjklmnoòỏõóọôồổỗốộơờởỡớợpqrstuùủũúụưừửữứựvwxyỳỷỹýỵz";
$language_settings["vietnamese"]	= $lang;

//-- NEVER manually delete or edit this entry and every line above this entry! --END--//

?>