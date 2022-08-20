/**
 * Common javascript functions for editing
 *
 * Genmod: Genealogy Viewer
 * Copyright (C) 2005 - 2007 Genmod Development Team
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
 * @subpackage Display
 * @version $Id: genmod_edit.js 29 2022-07-17 13:18:20Z Boudewijn $
 */
 
 
/**
 * Opens a window to edit a name. Used in the edit this person menu
 */
function edit_name(pid, fact, count, change_type) {
	if (!count) count = 1;
	window.open('edit_interface.php?action=editname&pid='+pid+'&fact='+fact+'&count='+count+'&change_type='+change_type+'&pid_type=INDI&'+sessionname+'='+sessionid, '', 'top=50,left=50,width=800,height=500,resizable=1,scrollbars=1');
	return false;
}

/**
 * Copy, used in editing
*/
function copy_record(pid, fact, count, change_type, pid_type) {
	window.open('edit_interface.php?action=copy&pid='+pid+'&fact='+fact+'&count='+count+'&pid_type='+pid_type+'&change_type='+change_type+"&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=800,height=500,resizable=1,scrollbars=1');
	return false;
}

/**
 * Opens a window to link a spouse to a family. Used in the edit this person menu
 */
function linkspouse(pid, famtag, change_type) {
	window.open('edit_interface.php?action=linkspouse&pid='+pid+'&change_type='+change_type+'&pid_type=INDI&famtag='+famtag+'&famid=new&'+sessionname+"="+sessionid, '', 'top=50,left=50,width=800,height=500,resizable=1,scrollbars=1');
	return false;
}

/**
 * Editing links that opens an edit window via edit_interface.php
 */
/**
 * Delete
 */
function deleteperson(pid, change_type) {
	window.open('edit_interface.php?action=deleteperson&pid='+pid+'&change_type='+change_type+"&pid_type=INDI&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=800,height=500,resizable=1,scrollbars=1');
	return false;
}

function delete_family(famid, change_type) {
	window.open('edit_interface.php?action=deletefamily&famid='+famid+'&change_type='+change_type+"&pid_type=FAM&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=800,height=600,resizable=1,scrollbars=1');
	return false;
}

function deletesource(pid, change_type) {
	 window.open('edit_interface.php?action=deletesource&pid='+pid+'&change_type='+change_type+"&pid_type=SOUR&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=800,height=500,resizable=1,scrollbars=1');
	 return false;
}

function deletegnote(pid, change_type) {
	 window.open('edit_interface.php?action=deletegnote&pid='+pid+'&change_type='+change_type+"&pid_type=NOTE&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=800,height=500,resizable=1,scrollbars=1');
	 return false;
}

function deletemedia(mid, change_type) {
	 window.open('edit_interface.php?action=deletemedia&pid='+mid+'&change_type='+change_type+"&pid_type=OBJE&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=800,height=500,resizable=1,scrollbars=1');
	 return false;
}

function deleterepository(pid, change_type) {
	 window.open('edit_interface.php?action=deleterepo&pid='+pid+'&change_type='+change_type+"&pid_type=REPO&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=800,height=500,resizable=1,scrollbars=1');
	 return false;
}

function delete_record(pid, fact, count, change_type, pid_type) {
	if (!count) count = 1;
	window.open('edit_interface.php?action=delete&pid='+pid+'&pid_type='+pid_type+'&change_type='+change_type+'&fact='+fact+'&count='+count+"&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=800,height=500,resizable=1,scrollbars=1');
	return false;
}

/**
 * Reorder
 */
function reorder_children(famid, change_type) {
	window.open('edit_interface.php?action=reorder_children&pid='+famid+'&change_type='+change_type+"&pid_type=FAM&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=800,height=500,resizable=1,scrollbars=1');
	return false;
}

function reorder_media(famid, change_type, pid_type) {
	window.open('edit_interface.php?action=reorder_media&pid='+famid+'&pid_type='+pid_type+'&change_type='+change_type+"&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=800,height=500,resizable=1,scrollbars=1');
	return false;
}

function reorder_families(pid, change_type) {
	window.open('edit_interface.php?action=reorder_fams&pid='+pid+'&change_type='+change_type+"&pid_type=INDI&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=800,height=500,resizable=1,scrollbars=1');
	return false;
}

function relation_families(pid, change_type) {
	window.open('edit_interface.php?action=relation_fams&pid='+pid+'&change_type='+change_type+"&pid_type=INDI&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=800,height=500,resizable=1,scrollbars=1');
	return false;
}

function change_family_members(famid, change_type) {
	window.open('edit_interface.php?action=changefamily&famid='+famid+'&change_type='+change_type+"&pid_type=FAM&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=800,height=600,resizable=1,scrollbars=1');
	return false;
}

/**
 * Add
 */
function add_record(pid, fact, change_type, pid_type) {
	factfield = document.getElementById(fact);
	if (factfield) {
		factvalue = factfield.options[factfield.selectedIndex].value;
		if (factvalue.substr(0, 10)=="clipboard_") window.open('edit_interface.php?action=paste&pid='+pid+'&pid_type='+pid_type+'&change_type='+change_type+'&fact='+factvalue.substr(10)+"&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=800,height=500,resizable=1,scrollbars=1');
		else window.open('edit_interface.php?action=add&pid='+pid+'&pid_type='+pid_type+'&change_type='+change_type+'&fact='+factvalue+"&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=800,height=500,resizable=1,scrollbars=1');
	}
	return false;
}

function add_new_record(pid, fact, change_type, pid_type) {
	window.open('edit_interface.php?action=add&pid='+pid+'&pid_type='+pid_type+'&change_type='+change_type+'&fact='+fact+"&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=800,height=500,resizable=1,scrollbars=1');
	return false;
}

function addnewchild(famid, change_type) {
	window.open('edit_interface.php?action=addchild&famid='+famid+'&change_type='+change_type+"&pid_type=FAM&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=800,height=500,resizable=1,scrollbars=1');
	return false;
}

function addspouse(pid, famtag, change_type) {
	window.open('edit_interface.php?action=addspouse&pid_type=INDI&&pid='+pid+'&change_type='+change_type+'&famtag='+famtag+'&famid=new&'+sessionname+"="+sessionid, '', 'top=50,left=50,width=800,height=500,resizable=1,scrollbars=1');
	return false;
}

function add_famc(pid, change_type) {
	 window.open('edit_interface.php?action=addfamlink&pid='+pid+'&pid_type=INDI&change_type='+change_type+'&famtag=CHIL'+"&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=800,height=500,resizable=1,scrollbars=1');
	return false;
}

function add_newfamc(pid, change_type) {
	 window.open('edit_interface.php?action=addnewfamlink&pid='+pid+'&pid_type=INDI&change_type='+change_type+'&famtag=CHIL'+"&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=800,height=500,resizable=1,scrollbars=1');
	return false;
}

function add_fams(pid, famtag, change_type) {
	window.open('edit_interface.php?action=addfamlink&pid='+pid+'&pid_type=INDI&change_type='+change_type+'&famtag='+famtag+"&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=800,height=500,resizable=1,scrollbars=1');
	return false;
}

function add_newfams(pid, famtag, change_type) {
	window.open('edit_interface.php?action=addnewfamlink&pid='+pid+'&pid_type=INDI&change_type='+change_type+'&famtag='+famtag+"&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=800,height=500,resizable=1,scrollbars=1');
	return false;
}

function add_name(pid, change_type) {
	window.open('edit_interface.php?action=addname&pid='+pid+'&pid_type=INDI&change_type='+change_type+"&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=800,height=500,resizable=1,scrollbars=1');
	return false;
}

function addnewparent(pid, famtag, change_type) {
	window.open('edit_interface.php?action=addnewparent&pid='+pid+'&pid_type=INDI&change_type='+change_type+'&famtag='+famtag+'&famid=new'+"&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=800,height=500,resizable=1,scrollbars=1');
	return false;
}

function addnewparentfamily(pid, famtag, famid, change_type) {
	window.open('edit_interface.php?action=addnewparent&pid='+pid+'&pid_type=FAM&change_type='+change_type+'&famtag='+famtag+'&famid='+famid+"&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=800,height=500,resizable=1,scrollbars=1');
	return false;
}

function addnewsource(field, change_type) {
	pastefield = field;
	window.open('edit_interface.php?action=addnewsource&pid=newsour&pid_type=SOUR&change_type='+change_type, '', 'top=70,left=70,width=800,height=500,resizable=1,scrollbars=1');
	return false;
}

function addnewrepository(field, change_type) {
	pastefield = field;
	window.open('edit_interface.php?action=addnewrepository&pid=newrepo&pid_type=REPO&change_type='+change_type, '', 'top=70,left=70,width=800,height=500,resizable=1,scrollbars=1');
	return false;
}

function addnewmedia(field, change_type) {
	pastefield = field;
	window.open('edit_interface.php?action=add&pid=newmedia&fact=OBJE&pid_type=OBJE&change_type='+change_type, '' ,'top=70,left=70,width=800,height=500,resizable=1,scrollbars=1');
	return false;
}
function addnewgnote(field, change_type) {
	pastefield = field;
	window.open('edit_interface.php?action=addnewgnote&pid=newgnote&pid_type=NOTE&change_type='+change_type, '' ,'top=70,left=70,width=800,height=500,resizable=1,scrollbars=1');
	return false;
}

/**
 * Edit
 */
function edit_record(pid, fact, count, change_type, pid_type) {
	window.open('edit_interface.php?action=edit&pid='+pid+'&pid_type='+pid_type+'&fact='+fact+'&count='+count+'&change_type='+change_type+"&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=900,height=650,resizable=1,scrollbars=1');
	return false;
}

/**
 * Opens a window to raw edit a record. Used in the edit menus of the various record types.
 */

function edit_raw(pid, change_type, pid_type) {
	window.open('edit_interface.php?action=editraw&pid='+pid+'&pid_type='+pid_type+'&change_type='+change_type+"&"+sessionname+"="+sessionid, '', 'top=50,left=50,width=800,height=550,resizable=1,scrollbars=1');
	return false;
}

