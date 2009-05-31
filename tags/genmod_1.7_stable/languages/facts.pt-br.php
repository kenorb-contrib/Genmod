<?php
/*=================================================
   charset=utf-8
   Project:	Genmod
   File:	facts.pt-br.php
   Author:	John Finlay
   Translator:	Maurício Menegazzo Rosa
   Comments:	Defines an array of GEDCOM codes and the brasilian portugese name facts
			that they represent.
   Change Log:	8/5/02 - File Created
   2005.02.19 "Genmod" and "GEDCOM" made consistent across all language files  G.Kroll (canajun2eh)
===================================================*/
# $Id: facts.pt-br.php,v 1.1 2005/10/23 21:54:42 roland-d Exp $
if (preg_match("/facts\..+\.php$/", $_SERVER["SCRIPT_NAME"])>0) {
	print "You cannot access a language file directly.";
	exit;
}
// -- Define a fact array to map GEDCOM tags with their brasilian portugese values
$factarray["ABBR"] = "Abreviação";
$factarray["ADDR"] = "Endereço";
$factarray["ADR1"] = "Endereço 1";
$factarray["ADR2"] = "Endereço 2";
$factarray["ADOP"] = "Adoção";
$factarray["AFN"] = "Ancestral File Number (AFN)";
$factarray["AGE"] = "Idade";
$factarray["AGNC"]	= "Agência";
$factarray["ALIA"] = "Apelido";
$factarray["ANCE"] = "Ancestrais";
$factarray["ANCI"]	= "Interesses Ancestrais";
$factarray["ANUL"]	= "Anulação";
$factarray["ASSO"]	= "Associado";
$factarray["AUTH"] = "Autor";
$factarray["BAPL"]	= "Batismo LDS";
$factarray["BAPM"] = "Batismo";
$factarray["BARM"] = "Bar Mitzvah";
$factarray["BASM"] = "Bas Mitzvah";
$factarray["BIRT"] = "Nascimento";
$factarray["BLES"]	= "Bênção";
$factarray["BLOB"]	= "Objeto Binário";
$factarray["BURI"]	= "Sepultamento";
$factarray["CALN"]	= "Número de Chamada";
$factarray["CAST"]	= "Casta / Status Social";
$factarray["CAUS"]	= "Causa do Falecimento";
$factarray["CENS"]	= "Censo";
$factarray["CHAN"]	= "Atualizado em";
$factarray["CHAR"]	= "Conjunto de caracteres";
$factarray["CHIL"] = "Filho";
$factarray["CHR"] = "Batizado";
$factarray["CHRA"]	= "Batizado Adulto";
$factarray["CITY"] = "Cidade";
$factarray["CONF"] = "Confirmação";
$factarray["CONL"]	= "Confirmação LDS";
$factarray["COPR"]	= "Direitos Autorais";
$factarray["CORP"]	= "Corporação / Companhia";
$factarray["CREM"] = "Cremação";
$factarray["CTRY"] = "País";
$factarray["DATA"] = "Dados";
$factarray["DATE"] = "Data";
$factarray["DEAT"]	= "Falecimento";
$factarray["DESC"] = "Descendentes";
$factarray["DESI"]	= "Interesses Descendentes";
$factarray["DEST"] = "Destinação";
$factarray["DIV"] = "Divórcio";
$factarray["DIVF"]	= "Divórcio Arquivado";
$factarray["DSCR"] = "Descrição";
$factarray["EDUC"] = "Educação";
$factarray["EMIG"] = "Emigração";
$factarray["ENDL"]	= "Fundação LDS";
$factarray["ENGA"]	= "Noivado";
$factarray["EVEN"] = "Evento";
$factarray["FAM"] = "Família";
$factarray["FAMC"]	= "Family as a Child";			// ??
$factarray["FAMF"]	= "Arquivo de Família";
$factarray["FAMS"]	= "Family as a Spouse";			// ??
$factarray["FCOM"] = "Primeira Comunhão";
$factarray["FILE"]	= "Arquivo Externo";
$factarray["FORM"]	= "Formato";
$factarray["GIVN"] = "Nome(s)";
$factarray["GRAD"]	= "Graduação";
$factarray["IDNO"]	= "Número de Identificação";
$factarray["IMMI"]	= "Imigração";
$factarray["LEGA"]	= "Herdeiro Testamentário";
$factarray["MARB"]	= "Marriage Bann";			// ??
$factarray["MARC"]	= "Contrato Matrimonial";
$factarray["MARL"] = "Licença de Casamento";
$factarray["MARR"] = "Casamento";
$factarray["MARS"]	= "Acordo Matrimonial";			// ??
$factarray["MEDI"]	= "Media Type";
$factarray["NAME"] = "Nome";
$factarray["NATI"]	= "Nacionalidade";
$factarray["NATU"] = "Naturalização";
$factarray["NCHI"] = "Número de Filhos";
$factarray["NICK"] = "Apelido";
$factarray["NMR"] = "Número de Casamentos";
$factarray["NOTE"] = "Nota";
$factarray["NPFX"] = "Prefixo";
$factarray["NSFX"] = "Sufixo";
$factarray["OBJE"]	= "Objeto Multimídia";
$factarray["OCCU"]	= "Ocupação";
$factarray["ORDI"]	= "Mandato";
$factarray["ORDN"]	= "Ordenação";
$factarray["PAGE"]	= "Intimação Judicial";			// ??
$factarray["PEDI"] = "Árvore Genealógica";
$factarray["PLAC"]	= "Local";
$factarray["PHON"] = "Telefone";
$factarray["POST"] = "CEP";
$factarray["PROB"]	= "Comprovação de Legitimidade";
$factarray["PROP"] = "Propriedade";
$factarray["PUBL"] = "Publicação";
$factarray["QUAY"]	= "Qualificação de dados";
$factarray["REPO"]	= "Repositório ";
$factarray["REFN"]	= "Número de Referência";
$factarray["RELA"]	= "Relacionamento";
$factarray["RELI"] = "Religião";
$factarray["RESI"] = "Residência";
$factarray["RESN"] = "Restrição";
$factarray["RETI"]	= "Retirada";
$factarray["RFN"]		= "Número de Registros";
$factarray["RIN"]		= "Número de Identificação do Registro";
$factarray["ROLE"]	= "Cargo";
$factarray["SEX"] = "Sexo";
$factarray["SLGC"]	= "LDS Child Sealing";			// ??
$factarray["SLGS"]	= "LDS Spouse Sealing";			// ??
$factarray["SOUR"] = "Fonte";
$factarray["SPFX"] = "Prefixo do Sobrenome";
$factarray["SSN"]		= "Número do Seguro Social";
$factarray["STAE"] = "Estado";
$factarray["STAT"] = "Status";
$factarray["SUBM"]	= "Submitter";				// ??
$factarray["SUBN"]	= "Submission";				// ??
$factarray["SURN"] = "Sobrenome";
$factarray["TEMP"] = "Templo";
$factarray["TEXT"] = "Texto";
$factarray["TIME"] = "Tempo";
$factarray["TITL"] = "Title";
$factarray["TYPE"] = "Tipo";
$factarray["WILL"]	= "Testamento";
$factarray["_EMAIL"]	= "Endereço de E-mail";
$factarray["EMAIL"] = "E-mail";
$factarray["_TODO"]	= "Ítem por fazer";
$factarray["_UID"]	= "Identificador Universal";
$factarray["_GMU"]	= "Última Alteração por";

// These facts are specific to GEDCOM exports from Family Tree Maker
$factarray["_MDCL"]	= "Medical";				// ??
$factarray["_DEG"]	= "Graduação";
$factarray["_MILT"] = "Serviço Militar";
$factarray["_SEPR"]	= "Separado";
$factarray["_DETS"]	= "Falecimento de um Cônjuge";
$factarray["CITN"] = "Cidadania";
$factarray["_FA1"]	= "Fato 1";
$factarray["_FA2"]	= "Fato 2";
$factarray["_FA3"]	= "Fato 3";
$factarray["_FA4"]	= "Fato 4";
$factarray["_FA5"]	= "Fato 5";
$factarray["_FA6"]	= "Fato 6";
$factarray["_FA7"]	= "Fato 7";
$factarray["_FA8"]	= "Fato 8";
$factarray["_FA9"]	= "Fato 9";
$factarray["_FA10"]	= "Fato 10";
$factarray["_FA11"]	= "Fato 11";
$factarray["_FA12"]	= "Fato 12";
$factarray["_FA13"]	= "Fato 13";
$factarray["_MREL"]	= "Relacionado com a Mãe";
$factarray["_FREL"]	= "Relacionado com o Pai";
$factarray["_MSTAT"]	= "Marriage Beginning Status";	// ??
$factarray["_MEND"]	= "Marriage Ending Status";		// ??

// GEDCOM 5.5.1 related facts
$factarray["FAX"]		= "FAX";
$factarray["FACT"]	= "Fato";
$factarray["WWW"]		= "Web Home Page";
$factarray["MAP"]		= "Mapa";
$factarray["LATI"]	= "Latitude";
$factarray["LONG"]	= "Longitude";
$factarray["FONE"]	= "Fonético";
$factarray["ROMN"]	= "Convertido ao Catolicismo";

// PAF related facts
$factarray["_NAME"]	= "Mailing Name";				// ??
$factarray["URL"]		= "Web URL";
$factarray["_HEB"]	= "Hebreu";

// Rootsmagic
$factarray["_SUBQ"]	= "Versão Reduzida";
$factarray["_BIBL"]	= "Bibliografia";

// Other common customized facts
$factarray["_ADPF"]	= "Adotado pelo pai";
$factarray["_ADPM"]	= "Adotado pela mãe";
$factarray["_AKAN"]	= "Também conhecido como";
$factarray["_AKA"] 	= "Também conhecido como";
$factarray["_BRTM"] = "Brit mila";
$factarray["_COML"]	= "Common Law marriage";		// ??
$factarray["_EYEC"]	= "Cor dos olhos";
$factarray["_FNRL"] = "Funeral";
$factarray["_HAIR"]	= "Cor do cabelo";
$factarray["_HEIG"]	= "Altura";
$factarray["_INTE"]	= "Sepultado";
$factarray["_MARI"]	= "Marriage intention";			// ??
$factarray["_MBON"]	= "Ligação Familiar";
$factarray["_MEDC"] = "Medical condition";
$factarray["_MILI"]	= "Militar";
$factarray["_NMR "]	= "Não Casados";
$factarray["_NLIV"]	= "Não Vivos";
$factarray["_NMAR"]	= "Nunca foi casado";
$factarray["_PRMN"]	= "Número permanente";
$factarray["_WEIG"] = "Peso";
$factarray["_YART"]	= "Yartzeit";				// ??
$factarray["_MARNM"]	= "Nome de casada";
$factarray["_STAT"]	= "Estado Civil";
$factarray["COMM"]	= "Comentário";

if (file_exists($GM_BASE_DIRECTORY . "languages/facts.pt-br.extra.php")) require $GM_BASE_DIRECTORY . "languages/facts.pt-br.extra.php";

?>