<?php
/*=================================================
   charset=utf-8
   Project:	Genmod
   File:	facts.it.php
   Author:	Fabio Parri
   Comments:	Defines an array of GEDCOM codes and the Italian name facts that they represent.
   Change Log:	21/01/2003 - File Created
   2005.02.19 "Genmod" and "GEDCOM" made consistent across all language files  G.Kroll (canajun2eh)
===================================================*/
# $Id: facts.it.php,v 1.1 2005/10/23 21:54:42 roland-d Exp $
if (preg_match("/facts\...\.php$/", $_SERVER["SCRIPT_NAME"])>0) {
	print "You cannot access a language file directly.";
	exit;
}
// -- Define a fact array to map GEDCOM tags with their Italian values
$factarray["ABBR"] = "Abbreviazione";
$factarray["ADDR"] = "Indirizzo";
$factarray["ADR1"] = "Indirizzo 1";
$factarray["ADR2"] = "Indirizzo 2";
$factarray["ADOP"] = "Adozione";
$factarray["AFN"] = "Ancestral File Number (AFN)";
$factarray["AGE"] = "Età";
$factarray["AGNC"] = "Istituzione";
$factarray["ALIA"] = "Alias";
$factarray["ANCE"] = "Antenati";
$factarray["ANCI"] = "Interesse Antenati";
$factarray["ANUL"] = "Annullamento";
$factarray["ASSO"] = "Associati";
$factarray["AUTH"] = "Autore";
$factarray["BAPL"] = "Battesimo Mormone";
$factarray["BAPM"] = "Battesimo";
$factarray["BARM"] = "Bar Mitzvah";
$factarray["BASM"] = "Bas Mitzvah";
$factarray["BIRT"] = "Nascita";
$factarray["BLES"] = "Benedizione";
$factarray["BLOB"] = "Oggetto binario contenente i dati";
$factarray["BURI"] = "Sepoltura";
$factarray["CALN"] = "Numero";
$factarray["CAST"] = "Casta / Stato Sociale";
$factarray["CAUS"] = "Causa della morte";
$factarray["CAUS"] = "Cauda della morte";
$factarray["CENS"] = "Censimento";
$factarray["CHAN"] = "Ultima modifica";
$factarray["CHAR"] = "Set di caratteri";
$factarray["CHIL"] = "Bambino";
$factarray["CHR"] = "Cresima";
$factarray["CHRA"] = "Cresima da Adulto";
$factarray["CITY"] = "Città";
$factarray["CONF"] = "Comunione";
$factarray["CONL"] = "Comunione Mormone";
$factarray["COPR"] = "Copyright";
$factarray["CORP"] = "Compagnia / Società";
$factarray["CREM"] = "Cremazione";
$factarray["CTRY"] = "Nazione";
$factarray["DATA"] = "Dati";
$factarray["DEAT"] = "Morte";
$factarray["DESC"] = "Discendenti";
$factarray["DESI"] = "Interesse Discendenti";
$factarray["DEST"] = "Destinazione";
$factarray["DIV"] = "Divorzio";
$factarray["DIVF"] = "Dossier Divorzio";
$factarray["DSCR"] = "Descrizione";
$factarray["EDUC"] = "Educazione";
$factarray["EMIG"] = "Emigrazione";
$factarray["ENDL"] = "Costituzione Mormone di dote";
$factarray["ENGA"] = "Fidanzamento";
$factarray["EVEN"] = "Evento";
$factarray["FAM"] = "Famiglia";
$factarray["FAMC"] = "Famiglia da bambino";
$factarray["FAMF"] = "Dossier familiare";
$factarray["FAMS"] = "Famiglia da coniuge";
$factarray["FCOM"] = "Prima comunione";
$factarray["FILE"] = "Dossier esterno";
$factarray["FORM"] = "Formato";
$factarray["GIVN"] = "Nome proprio";
$factarray["GRAD"] = "Laurea";
$factarray["IDNO"] = "Identificativo";
$factarray["IMMI"] = "Immigrazione";
$factarray["LEGA"] = "Legatario";
$factarray["MARB"] = "Pubblicazioni matrimoniali";
$factarray["MARC"] = "Contratto di matrimonio";
$factarray["MARL"] = "Licenza di matrimonio";
$factarray["MARR"] = "Matrimonio";
$factarray["MARS"] = "Accordo pre-matrimoniale";
$factarray["NAME"] = "Nome";
$factarray["NATI"] = "Nazionalità";
$factarray["NATU"] = "Naturalizzazione";
$factarray["NCHI"] = "Numero di bambini";
$factarray["NICK"] = "Soprannome";
$factarray["NMR"] = "Numero di matrimoni";
$factarray["NOTE"] = "Note";
$factarray["NPFX"] = "Prefisso";
$factarray["NSFX"] = "Suffisso";
$factarray["OBJE"] = "Oggetto multimediale";
$factarray["OCCU"] = "Occupazione";
$factarray["ORDI"] = "Cerimonia";
$factarray["ORDN"] = "Ordinazione";
$factarray["PAGE"] = "Dettagli";
$factarray["PEDI"] = "Antenati";
$factarray["PLAC"] = "Posto";
$factarray["PHON"] = "Telefono";
$factarray["POST"] = "C.A.P.";
$factarray["PROB"] = "Probate";
$factarray["PROP"] = "Proprietà";
$factarray["PUBL"] = "Pubblicazione";
$factarray["QUAY"] = "Qualità  dei dati";
$factarray["REFN"] = "Numero di riferimento";
$factarray["RELI"] = "Religione";
$factarray["RESI"] = "Residenza";
$factarray["RESN"] = "Restrizione";
$factarray["RETI"] = "Pensionamento";
$factarray["RFN"] = "Numero di archivio del registor";
$factarray["RIN"] = "Numero ID";
$factarray["ROLE"] = "Ruolo";
$factarray["SEX"] = "Sesso";
$factarray["SLGC"] = "Suggellatura del Bambino (Chiesa Mormone)";
$factarray["SLGS"] = "Suggellatura al Coniuge (Chiesa Mormone)";
$factarray["SOUR"] = "Origine";
$factarray["SPFX"] = "Prefisso del Cognome";
$factarray["SSN"] = "Numero di Previdenza Sociale";
$factarray["STAE"] = "Stato";
$factarray["STAT"] = "Stato";
$factarray["SUBM"] = "Inviato da:";
$factarray["SUBN"] = "Dati da trattare";
$factarray["SURN"] = "Cognome";
$factarray["TEMP"] = "Tempio";
$factarray["TEXT"] = "Testo";
$factarray["TIME"] = "Time";
$factarray["TITL"] = "Titolo";
$factarray["WILL"] = "Testamento";
$factarray["_EMAIL"] = "Indirizzo e-mail";
$factarray["EMAIL"] = "Indirizzo E-mail";
$factarray["_TODO"] = "Item Da Fare";
$factarray["_UID"] = "Identificatore Universale";

// These facts are specific to GEDCOM exports from Family Tree Maker
$factarray["_MDCL"]	= "Medical";
$factarray["_SEPR"] = "Separato";
$factarray["_DETS"] = "Morte di un coniuge";
$factarray["CITN"] = "Cittadinanza";

// Other common customized facts
$factarray["_ADPF"] = "Adottato dal padre";
$factarray["_ADPM"] = "Adottato dalla madre";
$factarray["_AKAN"] = "Soprannominato";
$factarray["_EYEC"] = "Colore degli occhi";
$factarray["_FNRL"] = "Funerale";
$factarray["_HAIR"] = "Colore dei capelli";
$factarray["_HEIG"] = "Altezza";
$factarray["_NMR"] = "Non sposato";
$factarray["_NLIV"] = "Non in vita";
$factarray["_NMAR"] = "Mai sposato";
$factarray["_WEIG"] = "Peso";

if (file_exists($GM_BASE_DIRECTORY . "languages/facts.it.extra.php")) require $GM_BASE_DIRECTORY . "languages/facts.it.extra.php";

?>