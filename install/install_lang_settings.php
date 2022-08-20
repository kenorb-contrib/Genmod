<?php
/**
 * Standard file of language_settings.php
 *
 * -> NEVER manually delete or edit this file <-
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
 * $Id: install_lang_settings.php 29 2022-07-17 13:18:20Z Boudewijn $
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
$lang["gm_language"]		= "languages/lang.cz.txt";
$lang["confighelpfile"]		= "";
$lang["helptextfile"]		= "languages/help_text.cz.txt";
$lang["flagsfile"]		= "images/flags/czech republic.gif";
$lang["factsfile"]		= "languages/facts.cz.txt";
$lang["DATE_FORMAT"]		= "D. M Y";
$lang["TIME_FORMAT"]		= "G:i:s";
$lang["WEEK_START"]		= "1";
$lang["TEXT_DIRECTION"]		= "ltr";
$lang["NAME_REVERSE"]		= false;
$lang["ALPHABET_upper"]		= "AÁBCČDĎEĚÉFGHIÍJKLMNŇOÓPQRŘSŠTŤUÚŮVWXYÝZŽ";
$lang["ALPHABET_lower"]		= "aábcčdďeěéfghiíjklmnňoópqrřsštťuúůvwxyýzž";
$lang["MON_SHORT"]		= "Led,Ún,Bře,Dub,Kvě,Červen,Červenec,Srp,Zář,Říj,List,Pros";

$language_settings["czech"]	= $lang;

//-- settings for german
$lang = array();
$lang["gm_langname"]		= "german";
$lang["gm_lang_use"]		= false;
$lang["gm_lang"]		= "Deutsch";
$lang["lang_short_cut"]		= "de";
$lang["langcode"]		= "de;de-de;de-at;de-li;de-lu;de-ch;";
$lang["gm_language"]		= "languages/lang.de.txt";
$lang["confighelpfile"]		= "";
$lang["helptextfile"]		= "languages/help_text.de.txt";
$lang["flagsfile"]		= "images/flags/germany.gif";
$lang["factsfile"]		= "languages/facts.de.txt";
$lang["DATE_FORMAT"]		= "D. M Y";
$lang["TIME_FORMAT"]		= "H:i:s";
$lang["WEEK_START"]		= "1";
$lang["TEXT_DIRECTION"]		= "ltr";
$lang["NAME_REVERSE"]		= false;
$lang["ALPHABET_upper"]		= "ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÜ";
$lang["ALPHABET_lower"]		= "abcdefghijklmnopqrstuvwxyzäöüß";
$lang["MON_SHORT"]		= "Jan,Feb,Mär,Mai,Jun,Jul,Aug,Sep,Okt,Nov,Dez";
$language_settings["german"]	= $lang;

//-- settings for english
$lang = array();
$lang["gm_langname"]		= "english";
$lang["gm_lang_use"]		= true;
$lang["gm_lang"]		= "English";
$lang["lang_short_cut"]		= "en";
$lang["langcode"]		= "en;en-us;en-au;en-bz;en-ca;en-ie;en-jm;en-nz;en-ph;en-za;en-tt;en-gb;en-zw;";
$lang["gm_language"]		= "languages/lang.en.txt";
$lang["confighelpfile"]		= "";
$lang["helptextfile"]		= "languages/help_text.en.txt";
$lang["flagsfile"]		= "images/flags/usa.gif";
$lang["factsfile"]		= "languages/facts.en.txt";
$lang["DATE_FORMAT"]		= "D M Y";
$lang["TIME_FORMAT"]		= "g:i:sa";
$lang["WEEK_START"]		= "0";
$lang["TEXT_DIRECTION"]		= "ltr";
$lang["NAME_REVERSE"]		= false;
$lang["ALPHABET_upper"]		= "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
$lang["ALPHABET_lower"]		= "abcdefghijklmnopqrstuvwxyz";
$lang["MON_SHORT"]		= "Jan,Feb,Mar,Apr,May,Jun,Jul,Aug,Sep,Oct,Nov,Dec";
$language_settings["english"]	= $lang;

//-- settings for spanish
$lang = array();
$lang["gm_langname"]		= "spanish";
$lang["gm_lang_use"]		= false;
$lang["gm_lang"]		= "Español";
$lang["lang_short_cut"]		= "es";
$lang["langcode"]		= "es;es-bo;es-cl;es-co;es-cr;es-do;es-ec;es-sv;es-gt;es-hn;es-mx;es-ni;es-pa;es-py;es-pe;es-pr;es-us;es-uy;es-ve;";
$lang["gm_language"]		= "languages/lang.es.txt";
$lang["confighelpfile"]		= "";
$lang["helptextfile"]		= "languages/help_text.es.txt";
$lang["flagsfile"]		= "images/flags/spain.gif";
$lang["factsfile"]		= "languages/facts.es.txt";
$lang["DATE_FORMAT"]		= "D M Y";
$lang["TIME_FORMAT"]		= "g:i:sa";
$lang["WEEK_START"]		= "0";
$lang["TEXT_DIRECTION"]		= "ltr";
$lang["NAME_REVERSE"]		= false;
$lang["ALPHABET_upper"]		= "ABCDEFGHIJKLMNÑOPQRSTUVWXYZ";
$lang["ALPHABET_lower"]		= "abcdefghijklmnñopqrstuvwxyz";
$lang["MON_SHORT"]		= "Ene,Feb,Mar,Abr,May,Jun,Jul,Ago,Sep,Oct,Nov,Dic";
$language_settings["spanish"]	= $lang;

//-- settings for spanish-ar
$lang = array();
$lang["gm_langname"]		= "spanish_ar";
$lang["gm_lang_use"]		= false;
$lang["gm_lang"]		= "Español Latinoamericano";
$lang["lang_short_cut"]		= "es-ar";
$lang["langcode"]		= "es-ar;";
$lang["gm_language"]		= "languages/lang.es-ar.txt";
$lang["confighelpfile"]		= "";
$lang["helptextfile"]		= "languages/help_text.es-ar.txt";
$lang["flagsfile"]		= "images/flags/argentina.gif";
$lang["factsfile"]		= "languages/facts.es-ar.txt";
$lang["DATE_FORMAT"]		= "D M Y";
$lang["TIME_FORMAT"]		= "g:i:sa";
$lang["WEEK_START"]		= "0";
$lang["TEXT_DIRECTION"]		= "ltr";
$lang["NAME_REVERSE"]		= false;
$lang["ALPHABET_upper"]		= "ABCDEFGHIJKLMNÑOPQRSTUVWXYZ";
$lang["ALPHABET_lower"]		= "abcdefghijklmnñopqrstuvwxyz";
$lang["MON_SHORT"]		= "Ene,Feb,Mar,Abr,May,Jun,Jul,Ago,Sep,Oct,Nov,Dic";
$language_settings["spanish_ar"]	= $lang;

//-- settings for french
$lang = array();
$lang["gm_langname"]		= "french";
$lang["gm_lang_use"]		= false;
$lang["gm_lang"]		= "Français";
$lang["lang_short_cut"]		= "fr";
$lang["langcode"]		= "fr;fr-be;fr-ca;fr-lu;fr-mc;fr-ch;";
$lang["gm_language"]		= "languages/lang.fr.txt";
$lang["confighelpfile"]		= "";
$lang["helptextfile"]		= "languages/help_text.fr.txt";
$lang["flagsfile"]		= "images/flags/france.gif";
$lang["factsfile"]		= "languages/facts.fr.txt";
$lang["DATE_FORMAT"]		= "D j F Y";
$lang["TIME_FORMAT"]		= "H:i:s";
$lang["WEEK_START"]		= "0";
$lang["TEXT_DIRECTION"]		= "ltr";
$lang["NAME_REVERSE"]		= false;
$lang["ALPHABET_upper"]		= "AÀÂÆBCÇDEÉÈËÊFGHIÏÎJKLMNOÔŒPQRSTUÙÛVWXYZ";
$lang["ALPHABET_lower"]		= "aàâæbcçdeéèëêfghiïîjklmnoôœpqrstuùûvwxyz";
$lang["MON_SHORT"]		= "jan,fév,mar,avr,mai,juin,juil,aoû,sep,oct,nov,dec";
$language_settings["french"]	= $lang;

//-- settings for italian
$lang = array();
$lang["gm_langname"]		= "italian";
$lang["gm_lang_use"]		= false;
$lang["gm_lang"]		= "Italiano";
$lang["lang_short_cut"]		= "it";
$lang["langcode"]		= "it;it-ch;";
$lang["gm_language"]		= "languages/lang.it.txt";
$lang["confighelpfile"]		= "";
$lang["helptextfile"]		= "languages/help_text.it.txt";
$lang["flagsfile"]		= "images/flags/italy.gif";
$lang["factsfile"]		= "languages/facts.it.txt";
$lang["DATE_FORMAT"]		= "D M Y";
$lang["TIME_FORMAT"]		= "g:i:sa";
$lang["WEEK_START"]		= "0";
$lang["TEXT_DIRECTION"]		= "ltr";
$lang["NAME_REVERSE"]		= false;
$lang["ALPHABET_upper"]		= "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
$lang["ALPHABET_lower"]		= "abcdefghijklmnopqrstuvwxyz";
$lang["MON_SHORT"]		= "Gen,Feb,Mar,Apr,Mag,Giu,Lug,Ago,Set,Ott,Nov,Dic";
$language_settings["italian"]	= $lang;

//-- settings for hungarian
$lang = array();
$lang["gm_langname"]		= "hungarian";
$lang["gm_lang_use"]		= false;
$lang["gm_lang"]		= "Magyar";
$lang["lang_short_cut"]		= "hu";
$lang["langcode"]		= "hu;";
$lang["gm_language"]		= "languages/lang.hu.txt";
$lang["confighelpfile"]		= "";
$lang["helptextfile"]		= "languages/help_text.hu.txt";
$lang["flagsfile"]		= "images/flags/hungary.gif";
$lang["factsfile"]		= "languages/facts.hu.txt";
$lang["DATE_FORMAT"]		= "Y. M D.";
$lang["TIME_FORMAT"]		= "g:i:sa";
$lang["WEEK_START"]		= "1";
$lang["TEXT_DIRECTION"]		= "ltr";
$lang["NAME_REVERSE"]		= true;
$lang["ALPHABET_upper"]		= "AÁBCDEÉFGHIÍJKLMNOÓÖŐPQRSTUÚÜŰVWXYZ";
$lang["ALPHABET_lower"]		= "aábcdeéfghiíjklmnoóöőpqrstuúüűvwxyz";
$lang["MON_SHORT"]		= "";
$language_settings["hungarian"]	= $lang;

//-- settings for dutch
$lang = array();
$lang["gm_langname"]		= "dutch";
$lang["gm_lang_use"]		= false;
$lang["gm_lang"]		= "Nederlands";
$lang["lang_short_cut"]		= "nl";
$lang["langcode"]		= "nl;nl-be;";
$lang["gm_language"]		= "languages/lang.nl.txt";
$lang["confighelpfile"]		= "";
$lang["helptextfile"]		= "languages/help_text.nl.txt";
$lang["flagsfile"]		= "images/flags/netherlands.gif";
$lang["factsfile"]		= "languages/facts.nl.txt";
$lang["DATE_FORMAT"]		= "D M Y";
$lang["TIME_FORMAT"]		= "G:i:s";
$lang["WEEK_START"]		= "1";
$lang["TEXT_DIRECTION"]		= "ltr";
$lang["NAME_REVERSE"]		= false;
$lang["ALPHABET_upper"]		= "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
$lang["ALPHABET_lower"]		= "abcdefghijklmnopqrstuvwxyz";
$lang["MON_SHORT"]		= "jan,feb,mrt,apr,mei,jun,jul,aug,sep,okt,nov,dec";
$language_settings["dutch"]	= $lang;

//-- settings for norwegian
$lang = array();
$lang["gm_langname"]		= "norwegian";
$lang["gm_lang_use"]		= false;
$lang["gm_lang"]		= "Norsk";
$lang["lang_short_cut"]		= "no";
$lang["langcode"]		= "no;nb;nn;";
$lang["gm_language"]		= "languages/lang.no.txt";
$lang["confighelpfile"]		= "";
$lang["helptextfile"]		= "languages/help_text.no.txt";
$lang["flagsfile"]		= "images/flags/norway.gif";
$lang["factsfile"]		= "languages/facts.no.txt";
$lang["DATE_FORMAT"]		= "D. M Y";
$lang["TIME_FORMAT"]		= "H:i:s";
$lang["WEEK_START"]		= "1";
$lang["TEXT_DIRECTION"]		= "ltr";
$lang["NAME_REVERSE"]		= false;
$lang["ALPHABET_upper"]		= "ABCDEFGHIJKLMNOPQRSTUVWXYZÆØÅ";
$lang["ALPHABET_lower"]		= "abcdefghijklmnopqrstuvwxyzæøå";
$lang["MON_SHORT"]		= "";
$language_settings["norwegian"]	= $lang;

//-- settings for polish
$lang = array();
$lang["gm_langname"]		= "polish";
$lang["gm_lang_use"]		= false;
$lang["gm_lang"]		= "Polski";
$lang["lang_short_cut"]		= "pl";
$lang["langcode"]		= "pl;";
$lang["gm_language"]		= "languages/lang.pl.txt";
$lang["confighelpfile"]		= "";
$lang["helptextfile"]		= "languages/help_text.pl.txt";
$lang["flagsfile"]		= "images/flags/poland.gif";
$lang["factsfile"]		= "languages/facts.pl.txt";
$lang["DATE_FORMAT"]		= "D M Y";
$lang["TIME_FORMAT"]		= "G:i:s";
$lang["WEEK_START"]		= "1";
$lang["TEXT_DIRECTION"]		= "ltr";
$lang["NAME_REVERSE"]		= false;
$lang["ALPHABET_upper"]		= "AĄBCĆDEĘFGHIJKLŁMNŃOÓPQRSŚTUVWXYZŹŻ";
$lang["ALPHABET_lower"]		= "aąbcćdeęfghijklłmnńoópqrsśtuvwxyzźż";
$lang["MON_SHORT"]		= "sty,lut,mar,kwi,maj,cze,lip,sie,wrz,paź,lis,gru";
$language_settings["polish"]	= $lang;

//-- settings for portuguese-br
$lang = array();
$lang["gm_langname"]		= "portuguese_br";
$lang["gm_lang_use"]		= false;
$lang["gm_lang"]		= "Português";
$lang["lang_short_cut"]		= "pt-br";
$lang["langcode"]		= "pt;pt-br;";
$lang["gm_language"]		= "languages/lang.pt-br.txt";
$lang["confighelpfile"]		= "";
$lang["helptextfile"]		= "languages/help_text.pt-br.txt";
$lang["flagsfile"]		= "images/flags/brazil.gif";
$lang["factsfile"]		= "languages/facts.pt-br.txt";
$lang["DATE_FORMAT"]		= "D M Y";
$lang["TIME_FORMAT"]		= "g:i:sa";
$lang["WEEK_START"]		= "0";
$lang["TEXT_DIRECTION"]		= "ltr";
$lang["NAME_REVERSE"]		= false;
$lang["ALPHABET_upper"]		= "ABCDEFGHIJKLMNÑOPQRSTUVWXYZ";
$lang["ALPHABET_lower"]		= "abcdefghijklmnñopqrstuvwxyz";
$lang["MON_SHORT"]		= "Jan,Fev,Mar,Abr,Mai,Jun,Jul,Ago,Set,Out,Nov,Dez";
$language_settings["portuguese_br"]	= $lang;

//-- settings for finnish
$lang = array();
$lang["gm_langname"]		= "finnish";
$lang["gm_lang_use"]		= false;
$lang["gm_lang"]		= "Suomi";
$lang["lang_short_cut"]		= "fi";
$lang["langcode"]		= "fi;";
$lang["gm_language"]		= "languages/lang.fi.txt";
$lang["confighelpfile"]		= "";
$lang["helptextfile"]		= "languages/help_text.fi.txt";
$lang["flagsfile"]		= "images/flags/finland.gif";
$lang["factsfile"]		= "languages/facts.fi.txt";
$lang["DATE_FORMAT"]		= "D. M Y";
$lang["TIME_FORMAT"]		= "H:i:s";
$lang["WEEK_START"]		= "1";
$lang["TEXT_DIRECTION"]		= "ltr";
$lang["NAME_REVERSE"]		= false;
$lang["ALPHABET_upper"]		= "ABCDEFGHIJKLMNOPQRSTUVWXYZÅÄÖ";
$lang["ALPHABET_lower"]		= "abcdefghijklmnopqrstuvwxyzåäö";
$lang["MON_SHORT"]		= "Tam,Hel,Maa,Huh,Tou,Kes,Hei,Elo,Syy,Lok,Mar,Jou";
$language_settings["finnish"]	= $lang;

//-- settings for swedish
$lang = array();
$lang["gm_langname"]		= "swedish";
$lang["gm_lang_use"]		= false;
$lang["gm_lang"]		= "Svenska";
$lang["lang_short_cut"]		= "sv";
$lang["langcode"]		= "sv;sv-fi;";
$lang["gm_language"]		= "languages/lang.sv.txt";
$lang["confighelpfile"]		= "";
$lang["helptextfile"]		= "languages/help_text.sv.txt";
$lang["flagsfile"]		= "images/flags/sweden.gif";
$lang["factsfile"]		= "languages/facts.sv.txt";
$lang["DATE_FORMAT"]		= "D M Y";
$lang["TIME_FORMAT"]		= "H:i:s";
$lang["WEEK_START"]		= "1";
$lang["TEXT_DIRECTION"]		= "ltr";
$lang["NAME_REVERSE"]		= false;
$lang["ALPHABET_upper"]		= "ABCDEFGHIJKLMNOPQRSTUVWXYZÅÄÖ";
$lang["ALPHABET_lower"]		= "abcdefghijklmnopqrstuvwxyzåäö";
$lang["MON_SHORT"]		= "Jan,Feb,Mar,Apr,Maj,Jun,Jul,Aug,Sep,Okt,Nov,Dec";
$language_settings["swedish"]	= $lang;

//-- settings for turkish
$lang = array();
$lang["gm_langname"]		= "turkish";
$lang["gm_lang_use"]		= false;
$lang["gm_lang"]		= "Türkçe";
$lang["lang_short_cut"]		= "tr";
$lang["langcode"]		= "tr;";
$lang["gm_language"]		= "languages/lang.tr.txt";
$lang["confighelpfile"]		= "";
$lang["helptextfile"]		= "languages/help_text.tr.txt";
$lang["flagsfile"]		= "images/flags/turkey.gif";
$lang["factsfile"]		= "languages/facts.tr.txt";
$lang["DATE_FORMAT"]		= "D M Y";
$lang["TIME_FORMAT"]		= "G:i:s";
$lang["WEEK_START"]		= "1";
$lang["TEXT_DIRECTION"]		= "ltr";
$lang["NAME_REVERSE"]		= false;
$lang["ALPHABET_upper"]		= "ABCÇDEFGĞHIİJKLMNOÖPRSŞTUÜVYZ";
$lang["ALPHABET_lower"]		= "abcçdefgğhıijklmnoöprsştuüvyz";
$lang["MON_SHORT"]		= "";
$language_settings["turkish"]	= $lang;

//-- settings for chinese
$lang = array();
$lang["gm_langname"]		= "chinese";
$lang["gm_lang_use"]		= false;
$lang["gm_lang"]		= "繁體中文";
$lang["lang_short_cut"]		= "zh";
$lang["langcode"]		= "zh;zh-cn;zh-hk;zh-mo;zh-sg;zh-tw;";
$lang["gm_language"]		= "languages/lang.zh.txt";
$lang["confighelpfile"]		= "";
$lang["helptextfile"]		= "languages/help_text.zh.txt";
$lang["flagsfile"]		= "images/flags/china.gif";
$lang["factsfile"]		= "languages/facts.zh.txt";
$lang["DATE_FORMAT"]		= "Y年 M D日";
$lang["TIME_FORMAT"]		= "g:i:sa";
$lang["WEEK_START"]		= "0";
$lang["TEXT_DIRECTION"]		= "ltr";
$lang["NAME_REVERSE"]		= true;
$lang["ALPHABET_upper"]		= "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
$lang["ALPHABET_lower"]		= "abcdefghijklmnopqrstuvwxyz";
$lang["MON_SHORT"]		= "";
$language_settings["chinese"]	= $lang;

//-- settings for hebrew
$lang = array();
$lang["gm_langname"]		= "hebrew";
$lang["gm_lang_use"]		= false;
$lang["gm_lang"]		= "‏עברית";
$lang["lang_short_cut"]		= "he";
$lang["langcode"]		= "he;";
$lang["gm_language"]		= "languages/lang.he.txt";
$lang["confighelpfile"]		= "";
$lang["helptextfile"]		= "languages/help_text.he.txt";
$lang["flagsfile"]		= "images/flags/israel.gif";
$lang["factsfile"]		= "languages/facts.he.txt";
$lang["DATE_FORMAT"]		= "D M Y";
$lang["TIME_FORMAT"]		= "G:i:s";
$lang["WEEK_START"]		= "0";
$lang["TEXT_DIRECTION"]		= "rtl";
$lang["NAME_REVERSE"]		= false;
$lang["ALPHABET_upper"]		= "אבגדהוזחטיכךלמםנןסעפףצץקרשת";
$lang["ALPHABET_lower"]		= "אבגדהוזחטיכךלמםנןסעפףצץקרשת";
$lang["MON_SHORT"]		= "ינו',פבר',מרץ,אפר',מאי,יוני,יולי,אוג',ספט',אוק',נוב',דצמ'";
$language_settings["hebrew"]	= $lang;

//-- settings for russian
$lang = array();
$lang["gm_langname"]		= "russian";
$lang["gm_lang_use"]		= false;
$lang["gm_lang"]		= "русский";
$lang["lang_short_cut"]		= "ru";
$lang["langcode"]		= "ru;ru-md;";
$lang["gm_language"]		= "languages/lang.ru.txt";
$lang["confighelpfile"]		= "";
$lang["helptextfile"]		= "languages/help_text.ru.txt";
$lang["flagsfile"]		= "images/flags/russia.gif";
$lang["factsfile"]		= "languages/facts.ru.txt";
$lang["DATE_FORMAT"]		= "D M Y";
$lang["TIME_FORMAT"]		= "g:i:sa";
$lang["WEEK_START"]		= "0";
$lang["TEXT_DIRECTION"]		= "ltr";
$lang["NAME_REVERSE"]		= false;
$lang["ALPHABET_upper"]		= "АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯ";
$lang["ALPHABET_lower"]		= "абвгдеёжзийклмнопрстуфхцчшщъыьэюя";
$lang["MON_SHORT"]		= "Янв,Фев,Мар,Апр,Май,Июн,Июл,Авг,Сен,Окт,Ноя,Дек";
$language_settings["russian"]	= $lang;

//-- settings for greek
$lang = array();
$lang["gm_langname"]		= "greek";
$lang["gm_lang_use"]		= false;
$lang["gm_lang"]		= "Ελληνικά";
$lang["lang_short_cut"]		= "el";
$lang["langcode"]		= "el;";
$lang["gm_language"]		= "languages/lang.el.txt";
$lang["confighelpfile"]		= "";
$lang["helptextfile"]		= "languages/help_text.el.txt";
$lang["flagsfile"]		= "images/flags/greece.gif";
$lang["factsfile"]		= "languages/facts.el.txt";
$lang["DATE_FORMAT"]		= "D M Y";
$lang["TIME_FORMAT"]		= "g:i:sa";
$lang["WEEK_START"]		= "0";
$lang["TEXT_DIRECTION"]		= "ltr";
$lang["NAME_REVERSE"]		= false;
$lang["ALPHABET_upper"]		= "ΆΑΒΓΔΈΕΖΗΘΊΪΪΙΚΛΜΝΞΌΟΠΡΣΣΤΎΫΫΥΦΧΨΏΩ";
$lang["ALPHABET_lower"]		= "άαβγδέεζηθίϊΐικλμνξόοπρσςτύϋΰυφχψώω";
$lang["MON_SHORT"]		= "Ιαν,Φεβ,Μαρ,Απρ,Μάι,Ιούν,Ιούλ,Αυγ,Σεπ,Οκτ,Νοέ,Δεκ";
$language_settings["greek"]	= $lang;

//-- settings for arabic
$lang = array();
$lang["gm_langname"]		= "arabic";
$lang["gm_lang_use"]		= false;
$lang["gm_lang"]		= "عربي";
$lang["lang_short_cut"]		= "ar";
$lang["langcode"]		= "ar;ar-ae;ar-bh;ar-dz;ar-eg;ar-iq;ar-jo;ar-kw;ar-lb;ar-ly;ar-ma;ar-om;ar-qa;ar-sa;ar-sy;ar-tn;ar-ye;";
$lang["gm_language"]		= "languages/lang.ar.txt";
$lang["confighelpfile"]		= "";
$lang["helptextfile"]		= "languages/help_text.ar.txt";
$lang["flagsfile"]		= "images/flags/arab league.gif";
$lang["factsfile"]		= "languages/facts.ar.txt";
$lang["DATE_FORMAT"]		= "D M Y";
$lang["TIME_FORMAT"]		= "h:i:sA";
$lang["WEEK_START"]		= "0";
$lang["TEXT_DIRECTION"]		= "rtl";
$lang["NAME_REVERSE"]		= false;
$lang["ALPHABET_upper"]		= "ابتثجحخدذرزسشصضطظعغفقكلمنهويآةىی";
$lang["ALPHABET_lower"]		= "ابتثجحخدذرزسشصضطظعغفقكلمنهويآةىی";
$lang["MON_SHORT"]		= "";
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
$lang["gm_language"]		= "languages/lang.lt.txt";
$lang["confighelpfile"]		= "";
$lang["helptextfile"]		= "languages/help_text.lt.txt";
$lang["flagsfile"]		= "images/flags/lithuania.gif";
$lang["factsfile"]		= "languages/facts.lt.txt";
$lang["DATE_FORMAT"]		= "Y M D";
$lang["TIME_FORMAT"]		= "g:i:sa";
$lang["WEEK_START"]		= "1";
$lang["TEXT_DIRECTION"]		= "ltr";
$lang["NAME_REVERSE"]		= false;
$lang["ALPHABET_upper"]		= "AĄBCČDEĘĖFGHIYĮJKLMNOPRSŠTUŲŪVZŽ";
$lang["ALPHABET_lower"]		= "aąbcčdeęėfghiyįjklmnoprsštuųūvzž";
$lang["MON_SHORT"]		= "";
$language_settings["lithuanian"]	= $lang;

//-- settings for danish
$lang = array();
$lang["gm_langname"]		= "danish";
$lang["gm_lang_use"]		= false;
$lang["gm_lang"]		= "Dansk";
$lang["lang_short_cut"]		= "da";
$lang["langcode"]		= "da;";
$lang["gm_language"]		= "languages/lang.da.txt";
$lang["confighelpfile"]		= "";
$lang["helptextfile"]		= "languages/help_text.da.txt";
$lang["flagsfile"]		= "images/flags/denmark.gif";
$lang["factsfile"]		= "languages/facts.da.txt";
$lang["DATE_FORMAT"]		= "D M Y";
$lang["TIME_FORMAT"]		= "G:i:s";
$lang["WEEK_START"]		= "1";
$lang["TEXT_DIRECTION"]		= "ltr";
$lang["NAME_REVERSE"]		= false;
$lang["ALPHABET_upper"]		= "ABCDEFGHIJKLMNOPQRSTUVWXYZÆØÅ";
$lang["ALPHABET_lower"]		= "abcdefghijklmnopqrstuvwxyzæøå";
$lang["MON_SHORT"]		= "jan,feb,mar,apr,maj,jun,jul,aug,sep,okt,nov,dec";
$language_settings["danish"]	= $lang;

//-- settings for Vietnamese
$lang = array();
$lang["gm_langname"]		= "vietnamese";
$lang["gm_lang_use"]		= false;
$lang["gm_lang"]		= "Tiếng Việt";
$lang["lang_short_cut"]		= "vi";
$lang["langcode"]		= "vi;";
$lang["gm_language"]		= "languages/lang.vi.txt";
$lang["confighelpfile"]		= "";
$lang["helptextfile"]		= "languages/help_text.vi.txt";
$lang["flagsfile"]		= "images/flags/vietnam.gif";
$lang["factsfile"]		= "languages/facts.vi.txt";
$lang["DATE_FORMAT"]		= "D M Y";
$lang["TIME_FORMAT"]		= "g:i:sa";
$lang["WEEK_START"]		= "0";
$lang["TEXT_DIRECTION"]		= "ltr";
$lang["NAME_REVERSE"]		= true;
$lang["ALPHABET_upper"]		= "AÀẢÃÁẠĂẰẲẴẮẶÂẦẨẪẤẬBCDĐEÈẺẼÉẸÊỀỂỄẾỆFGHIÌỈĨÍỊJKLMNOÒỎÕÓỌÔỒỔỖỐỘƠỜỞỠỚỢPQRSTUÙỦŨÚỤƯỪỬỮỨỰVWXYỲỶỸÝỴZ";
$lang["ALPHABET_lower"]		= "aàảãáạăằẳẵắặâầẩẫấậbcdđeèẻẽéẹêềểễếệfghiìỉĩíịjklmnoòỏõóọôồổỗốộơờởỡớợpqrstuùủũúụưừửữứựvwxyỳỷỹýỵz";
$lang["MON_SHORT"]		= "";
$language_settings["vietnamese"]	= $lang;

//-- NEVER manually delete or edit this entry and every line above this entry! --END--//

?>