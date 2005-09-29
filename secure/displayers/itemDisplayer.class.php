<?
/*******************************************************************************
itemDisplayer.class.php


Created by Kathy Washington (kawashi@emory.edu)

This file is part of ReservesDirect

Copyright (c) 2004-2005 Emory University, Atlanta, Georgia.

ReservesDirect is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

ReservesDirect is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with ReservesDirect; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

ReservesDirect is located at:
http://www.reservesdirect.org/

*******************************************************************************/
require_once("secure/common.inc.php");

class itemDisplayer
{
	/**
	* @return void
	* @param reserve current reserve object
	* @param user $user
	* @param docTypeIcons
	* @param new_reserve new reserve object if duplicating
	* @desc display edit course form
	*/
	function displayEditReserveScreen($reserve, $user, $docTypeIcons=null, $new_reserve=null)
	{

		global $g_permission;

		if (!is_a($reserve->item, "reserveItem")) $reserve->getItem();
		
		echo "
		<script language=\"JavaScript\">
		//<!--
			function validateForm(frm,physicalCopy)
			{			
				var alertMsg = \"\";

				if (frm.title.value == \"\")
					alertMsg = alertMsg + \"Title is required.<br>\";
				
				if (physicalCopy) {
					//make sure this physical copy is supposed to have a barcode
					//	if it is, there will be an input element for it in the form
					if( (document.getElementById('barcode') != null) && (document.getElementById('barcode').value == '') )
						alertMsg = alertMsg + \"Barcode is required.<br />\";
				}
				else {
					if (frm.url.value == \"\")
						alertMsg = alertMsg + \"URL is required.<br>\";				
				}
				
				
				if (!alertMsg == \"\") 
				{ 
					document.getElementById('alertMsg').innerHTML = alertMsg;
					return false;
				}
					
			}
		//-->
		</script>	
		";	
	
		if ($reserve->item->isPhysicalItem())
			echo "<form name=\"reservesMgr\" action=\"index.php?cmd=editReserve\" method=\"post\" onSubmit=\"return validateForm(this,true);\">\n";
		else
			echo "<form name=\"reservesMgr\" action=\"index.php?cmd=editReserve\" method=\"post\" onSubmit=\"return validateForm(this,false);\">\n";
		echo "<input type=\"hidden\" name=\"ci\" value=\"".$reserve->getCourseInstanceID()."\">\n";
		echo "<input type=\"hidden\" name=\"rID\" value=\"".$reserve->getReserveID()."\">\n";

		//pass destination reserveID for duplication
		if(!empty($new_reserve)) {
			echo "<input type=\"hidden\" name=\"new_rID\" value=\"".$new_reserve->getReserveID()."\">\n";
			echo "<input type=\"hidden\" name=\"selected_instr\" value=\"".$_REQUEST['selected_instr']."\">\n";
		}


		$activationDate = $reserve->getActivationDate();
		list($year, $month, $day) = split("-", $activationDate);

		$status = $reserve->getStatus();
		$todaysDate = date ("Y-m-d");

		$statusTag = common_getStatusStyleTag($status);

		$title = $reserve->item->getTitle();
		$author = $reserve->item->getAuthor();
		$url = $reserve->item->getURL();
		$performer = $reserve->item->getPerformer();
		$volTitle = $reserve->item->getVolumeTitle();
		$volEdition = $reserve->item->getVolumeEdition();
		$pagesTimes = $reserve->item->getPagesTimes();
		$source = $reserve->item->getSource();
		$contentNotes = $reserve->item->getContentNotes();
		$docTypeIcon = $reserve->item->getItemIcon();

		//pull notes from new reserve/item if duplicating
		if(!empty($new_reserve)) {
			$itemNotes = $new_reserve->item->getNotes(); //Valid note types, associated with an item, are content, copyright, and staff
			$instructorNotes = $new_reserve->getNotes();
		}
		else {	//not duplicating, get notes from original
			$itemNotes = $reserve->item->getNotes(); //Valid note types, associated with an item, are content, copyright, and staff
			$instructorNotes = $reserve->getNotes();
		}
		
		//output a message about duplicating
		if(!empty($new_reserve)) {
			echo '<div style="width:100%; text-align:center;"><strong>EDIT THE DUPLICATE</strong></div>';
		}

		echo "<table width=\"90%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">\n";
		echo "	<tr>\n";
		echo "    	<td width=\"140%\"><img src=\images/spacer.gif\" width=\"1\" height=\"5\"></td>\n";
		echo "	</tr>\n";
		echo "	<tr><td colspan=\"3\" align=\"right\"> <a href=\"index.php?cmd=editClass&ci=".$reserve->getCourseInstanceID()."\" class=\"strong\">Return to Class</a></div></td></tr>\n";
		echo "    <tr>\n";
		echo "    	<td>\n";
		echo "    	<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n";
		echo "        	<tr align=\"left\" valign=\"top\">\n";
		echo "            	<td class=\"headingCell1\"><div align=\"center\">ITEM DETAILS</div></td>\n";
		echo "            ";
		echo "			<!--The \"Show All Editable Item\" Links appears by default when this";
		echo "			page is loaded if some of the metadata fields for the document are blank.";
		echo "			Blank fields will be hidden upon page load. -->  ";
		echo "			  ";
		echo "				<td width=\"75%\"><!-- <div align=\"right\">[ <a href=\"link\" class=\"editlinks\">show all editable fields</a><a href=\"link\" class=\"editlinks\"></a> ]</div>--></td>\n";
		echo "			</tr>\n";
		echo "		</table>\n";
		echo "		</td>\n";
		echo "	</tr>\n";
		echo "    <tr>\n";
		echo "    	<td align=\"left\" valign=\"top\" class=\"borders\">\n";
		echo "    	<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\">\n";
		echo "        	<tr valign=\"middle\">\n";
		echo "            	<td colspan=\"2\" align=\"right\" bgcolor=\"#CCCCCC\" class=\"borders\">\n";
		echo "            	<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n";
		echo "                	<tr>\n";
		echo "                  		<td width=\"35%\" height=\"14\"><p><span class=\"strong\">Current Status: </span><strong><font color=\"".$statusTag."\">".$status."</font></strong>\n";

		if ($status == "ACTIVE") {
			if ($activationDate > $todaysDate) {
				echo "<span class=\"small\"> (hidden until ".$month."/".$day."/".$year.")</span>\n";
			}
			echo " | <input type=\"checkbox\" name=\"deactivateReserve\" value=\"".$reserve->getReserveID()."\"> Deactivate?";
			echo "						<td width=\"100%\"><span class=\"strong\">Activation Date:</span><strong></strong>&nbsp;<input name=\"month\" type=\"text\" size=\"2\" maxlength=\"2\" value=\"".$month."\"> / <input name=\"day\" type=\"text\" size=\"2\" maxlength=\"2\" value=\"".$day."\"> / <input name=\"year\" type=\"text\" size=\"4\" maxlength=\"4\" value=\"".$year."\"></td>\n";
		} elseif ($status == "INACTIVE") {
			echo " | <input type=\"checkbox\" name=\"activateReserve\" value=\"".$reserve->getReserveID()."\"> Activate?";
		} elseif (($status == "IN PROCESS") && ($user->dfltRole >= $g_permission['staff'])) { //only staff can change an in-process status
			echo " | <input type=\"checkbox\" name=\"activateReserve\" value=\"".$reserve->getReserveID()."\"> Activate?";
		}

		echo "					</tr>\n";
		echo "              	</table>\n";
		echo "              	</td>\n";
		echo "			</tr>\n";
		echo "            <tr valign=\"middle\">\n";
		echo "            	<td width=\"25%\" align=\"right\" bgcolor=\"#CCCCCC\"><div align=\"right\" class=\"strong\"><font color=\"#FF0000\"><strong>*</strong></font>&nbsp;Document Title:</div></td>\n";
		echo "				<td width=\"100%\" align=\"left\"><input name=\"title\" type=\"text\" id=\"title\" size=\"50\" value=\"$title\"></td>\n";
		echo "			</tr>\n";
		echo "            <tr valign=\"middle\">\n";
		echo "            	<td width=\"25%\" height=\"31\" align=\"right\" bgcolor=\"#CCCCCC\"><div align=\"right\" class=\"strong\">Author/Composer:</div></td>\n";
		echo "				<td width=\"100%\" align=\"left\"><input name=\"author\" type=\"text\" id=\"author\" size=\"50\" value=\"".$author."\"></td>\n";
		echo "			</tr>\n";
		if (!$reserve->item->isPhysicalItem()) {
			echo "            <tr valign=\"middle\">\n";
			echo "            	<td align=\"right\" bgcolor=\"#CCCCCC\"><div align=\"right\" class=\"strong\"><font color=\"#FF0000\"><strong>*</strong></font>URL:</div></td>\n";
			echo "				<td width=\"100%\" align=\"left\"><input name=\"url\" type=\"text\" size=\"50\" value=\"".urldecode($url)."\"></td>\n";
			echo "			</tr>\n";
		}
		echo "          <tr valign=\"middle\">\n";
		echo "            	<td width=\"25%\" align=\"right\" bgcolor=\"#CCCCCC\"><div align=\"right\"><span class=\"strong\">Performer </span><span class=\"strong\">:</span></div></td>\n";
		echo "				<td width=\"100%\" align=\"left\"><input name=\"performer\" type=\"text\" id=\"performer\" size=\"50\" value=\"".$performer."\"></td>\n";
		echo "			</tr>\n";
		
		if (!is_null($docTypeIcons))
		{
			echo "				<tr valign=\"middle\">\n";
			echo "					<td width=\"35%\" align=\"right\" bgcolor=\"#CCCCCC\"><span class=\"strong\">Document Type Icon:</span></td>\n";
			echo "					<td align=\"left\">";
			echo "						<select name=\"selectedDocIcon\" onChange=\"document.iconImg.src = this[this.selectedIndex].value;\">\n";
					
			for ($j = 0; $j<count($docTypeIcons); $j++)
			{
				$selectedIcon = ($docTypeIcon == $docTypeIcons[$j]['helper_app_icon']) ? " selected " : "";
				echo "							<option value=\"" . $docTypeIcons[$j]['helper_app_icon']  . "\" $selectedIcon>" . $docTypeIcons[$j]['helper_app_name'] . "</option>\n";
			}
				
			echo "						</select>\n";
			echo "					<img name=\"iconImg\" width=\"24\" height=\"20\" src=\"$docTypeIcon\">\n";
			echo "					</td>\n";
			echo "				</tr>\n";
		}	
		
		
		echo "            <tr valign=\"middle\">\n";
		echo "            	<td width=\"25%\" align=\"right\" bgcolor=\"#CCCCCC\"><div align=\"right\"><span class=\"strong\">Book/Journal/Work Title</span><span class=\"strong\">:</span></div></td>\n";
		echo "				<td width=\"100%\" align=\"left\"><input name=\"volumeTitle\" type=\"text\" id=\"volumeTitle\" size=\"50\" value=\"".$volTitle."\"></td>\n";
		echo "			</tr>\n";
		echo "            <tr valign=\"middle\">\n";
		echo "            	<td width=\"25%\" align=\"right\" bgcolor=\"#CCCCCC\"><div align=\"right\"><span class=\"strong\">Volume/ Edition</span><span class=\"strong\">:</span></div></td>\n";
		echo "				<td width=\"100%\" align=\"left\"><input name=\"volumeEdition\" type=\"text\" id=\"volumeEdition\" size=\"50\" value=\"".$volEdition."\"></td>\n";
		echo "			</tr>\n";
		echo "            <tr valign=\"middle\">\n";
		echo "            	<td width=\"25%\" align=\"right\" bgcolor=\"#CCCCCC\"><div align=\"right\"><span class=\"strong\">Pages/Time</span><span class=\"strong\">:</span></div></td>\n";
		echo "				<td width=\"100%\" align=\"left\"><input name=\"pagesTimes\" type=\"text\" id=\"pages\" size=\"50\" value=\"".$pagesTimes."\"></td>\n";
		echo "            </tr>\n";
		echo "            <tr valign=\"middle\">\n";
		echo "            	<td width=\"25%\" align=\"right\" bgcolor=\"#CCCCCC\"><div align=\"right\"><span class=\"strong\">Source/ Year</span><span class=\"strong\">:</span></div></td>\n";
		echo "				<td width=\"100%\" align=\"left\"><input name=\"source\" type=\"text\" id=\"source\" size=\"50\" value=\"".$source."\"></td>\n";
		echo "			</tr>\n";
		
		//items w/ ILS records require a barcode and optional call number
		//only allow this for staff
		if( ($user->dfltRole >= $g_permission['staff']) && $reserve->item->getPhysicalCopy()) {
			//set barcode to itself if editing, to '' if duplicating
			$barcode = ($new_reserve instanceof reserve) ? '' : $reserve->item->physicalCopy->getBarcode();
?>
			<tr valign="middle">		
				<td width="25%" align="right" bgcolor="#CCCCCC">
					<strong><font color="#FF0000">*</font>&nbsp;Barcode:</strong>
				</td>
				<td width="100%" align="left">
					<input name="barcode" type="text" id="barcode" size="20" value="<?=$barcode?>" />
				</td>
			</tr>
			<tr valign="middle">		
				<td width="25%" align="right" bgcolor="#CCCCCC">
					<strong>Call Number:</strong>
				</td>
				<td width="100%" align="left">
					<input name="call_num" type="text" id="call_num" size="30" value="<?=$reserve->item->physicalCopy->getCallNumber()?>" />
				</td>
			</tr>
<?php
		}
		
		if ($contentNotes) {

			echo "            <tr valign=\"middle\">\n";
			echo "            	<td width=\"25%\" align=\"right\" bgcolor=\"#CCCCCC\"><div align=\"right\"><span class=\"strong\">Content Note:<br></span></div></td>\n";
			echo "				<td width=\"100%\" align=\"left\"><textarea name=\"contentNotes\" cols=\"50\" rows=\"3\">".$contentNotes."</textarea></td>\n";
			echo "			</tr>\n";
		}
		if ($itemNotes) {

			for ($i=0; $i<count($itemNotes); $i++) {

				if ($user->dfltRole >= $g_permission['staff'] || $itemNotes[$i]->getType() == "Instructor" || $itemNotes[$i]->getType() == "Content") {
					echo "      <tr valign=\"middle\">\n";
					echo "			";
					echo "			<!-- On page load, by default, there is no blank \"Notes\" field showing, only ";
					echo "			previously created notes, if any, and the \"add Note\" button. Notes should";
					echo "			be added one after the other at the bottom of the table, but above the \"add Note\" button.-->\n";
					echo "            	<td align=\"right\" bgcolor=\"#CCCCCC\"><span class=\"strong\">".$itemNotes[$i]->getType()." Note:</span><br><a href=\"index.php?cmd=editReserve&reserveID=".$reserve->getReserveID()."&deleteNote=".$itemNotes[$i]->getID()."\">Delete this note</a></td>\n";
					echo "				<td align=\"left\"><textarea name=\"itemNotes[".$itemNotes[$i]->getID()."]\" cols=\"50\" rows=\"3\">".$itemNotes[$i]->getText()."</textarea></td>\n";
					echo "      </tr>\n";
				}
			}
		}
		if ($instructorNotes) {

			for ($i=0; $i<count($instructorNotes); $i++) {

				echo "      <tr valign=\"middle\">\n";
				echo "			";
				echo "			<!-- On page load, by default, there is no blank \"Notes\" field showing, only ";
				echo "			previously created notes, if any, and the \"add Note\" button. Notes should";
				echo "			be added one after the other at the bottom of the table, but above the \"add Note\" button.-->\n";
				echo "            	<td align=\"right\" bgcolor=\"#CCCCCC\"><span class=\"strong\">Instructor Note:</span><br><a href=\"index.php?cmd=editReserve&reserveID=".$reserve->getReserveID()."&deleteNote=".$instructorNotes[$i]->getID()."\">Delete this note</a></td>\n";
				echo "				<td align=\"left\"><textarea name=\"instructorNotes[".$instructorNotes[$i]->getID()."]\" cols=\"50\" rows=\"3\">".$instructorNotes[$i]->getText()."</textarea></td>\n";
				echo "      </tr>\n";
			}
		}
		echo "          <tr valign=\"middle\">\n";
		//echo "            	<td colspan=\"2\" align=\"left\" valign=\"top\" bgcolor=\"#CCCCCC\" class=\"borders\" align=\"center\"><a href='index.php?cmd=addNote&reserveID=".$reserve->getReserveID()."'><input type=\"button\" name=\"addNote\" value=\"Add Note\"></a></td>\n";
		echo "            	<td colspan=\"2\" valign=\"top\" bgcolor=\"#CCCCCC\" class=\"borders\" align=\"center\">\n";

		//if duplicating, add note to new reserve
		if(!empty($new_reserve)) {
			echo "					<input type=\"button\" name=\"addNote\" value=\"Add Note\" onClick=\"openWindow('&cmd=addNote&reserve_id=".$new_reserve->getReserveID()."');\">\n";
		}
		else {
			echo "					<input type=\"button\" name=\"addNote\" value=\"Add Note\" onClick=\"openWindow('&cmd=addNote&reserve_id=".$reserve->getReserveID()."');\">\n";
		}

		echo "				</td>\n";
		echo "			</tr>\n";
		echo "		</table>\n";
		echo "		</td>\n";
		echo "	</tr>\n";
		echo "    <tr>\n";
		echo "    	<td><strong><font color=\"#FF0000\">* </font></strong><span class=\"helperText\">=required fields</span></td>\n";
		echo "	</tr>\n";
		echo "    <tr>\n";
		echo "    	<td><div align=\"center\"><input type=\"submit\" name=\"Submit\" value=\"Save Changes\"\"></div></td>\n";
		echo "	</tr>\n";
		echo "	<tr><td colspan=\"3\">&nbsp;</td></tr>\n";
		echo "	<tr><td colspan=\"3\" align=\"center\"> <a href=\"index.php?cmd=editClass&ci=".$reserve->getCourseInstanceID()."\" class=\"strong\">Return to Class</a></div></td></tr>\n";
		echo "	<tr><td colspan=\"3\"><img src=\images/spacer.gif\" width=\"1\" height=\"15\"></td></tr>\n";
		echo "    <tr>\n";
		echo "    	<td><img src=\images/spacer.gif\" width=\"1\" height=\"15\"></td>\n";
		echo "	</tr>\n";
		echo "</table>\n";
		echo "</form>\n";
	}


	function displayEditHeadingScreen($ci, $heading)
	{
		$heading->getItem();
		
		$contentNotes = $heading->item->getContentNotes();
		$itemNotes = $heading->item->getNotes(); //Valid note types, associated with an item, are content, copyright, and staff
		$instructorNotes = $heading->getNotes();
		
		if ($heading->getSortOrder() == 0 || $heading->getSortOrder() == null)
			$currentSortOrder = "Not Yet Specified";
		else
			$currentSortOrder = ($heading->getSortOrder()+1);
		
		echo "<script language=\"javaScript\">
			function processHeading(form, nextAction)
			{
				form.nextAction.value = nextAction;
				form.submit();

			}
		</script>";
		
		echo "<form action=\"index.php\" method=\"post\" name=\"editHeading\">\n";
		
		echo "<input type=\"hidden\" name=\"cmd\" value=\"processHeading\">\n";
		echo "<input type=\"hidden\" name=\"nextAction\" value=\"\">\n";
		echo "<input type=\"hidden\" name=\"ci\" value=\"$ci\">\n";
		echo "<input type=\"hidden\" name=\"headingID\" value=\"$heading->itemID\">\n";
		
		echo '<table width="90%" border="0" cellspacing="0" cellpadding="0" align="center">';
    	echo ' 	<tr><td width="140%"><img src="/images/spacer.gif" width="1" height="5"> </td></tr>';
    	echo ' 	<tr>';
        echo ' 		<td>';
        echo '		<table width="100%" border="0" cellspacing="0" cellpadding="0">';
        echo ' 			<tr align="left" valign="top">';
        echo ' 				<td class="headingCell1"><div align="center">HEADING DETAILS</div></td>';
        echo ' 				<td width="75%">&nbsp;</td>';
        echo ' 			</tr>';
        echo '		</table>';
        echo '		</td>';
      	echo '	</tr>';
      	echo '	<tr>';
        echo '		<td align="left" valign="top" class="borders">';
        echo '		<table width="100%" border="0" cellspacing="0" cellpadding="3">';
        echo '		    <tr valign="middle">';
        echo '		      	<td colspan="2" align="right" bgcolor="#CCCCCC" class="headingCell1">&nbsp;</td>';
        echo '		    </tr>';
        echo '		    <tr valign="middle">';
        echo '      		<td width="30%" align="right" bgcolor="#CCCCCC"><div align="right" class="strong"><font color="#FF0000"><strong>*</strong></font> Heading Title:</div></td>';
        echo '				<td align="left"><input name="heading" type="text" size="60" value="'.$heading->item->getTitle().'"></td>';
        echo '			</tr>';
        echo '			<tr valign="middle">';
        echo '				<td width="30%" align="right" bgcolor="#CCCCCC"><strong>Current Sort Position: </strong></td>';
        //echo '				<td align="left"> &nbsp;'.$heading->getSortOrder().'&nbsp;<!-- <a href="link">change sort position &gt;&gt; </a>--></td>';
        echo '				<td align="left"> &nbsp;'.$currentSortOrder.'&nbsp;<!-- <a href="link">change sort position &gt;&gt; </a>--></td>';
        echo '			</tr>';
        
        //START
        if ($contentNotes) {

			echo "            <tr valign=\"middle\">\n";
			echo "            	<td width=\"25%\" align=\"right\" bgcolor=\"#CCCCCC\"><div align=\"right\"><span class=\"strong\">Content Note:<br></span></div></td>\n";
			echo "				<td width=\"100%\" align=\"left\"><textarea name=\"contentNotes\" cols=\"50\" rows=\"3\">".$contentNotes."</textarea></td>\n";
			echo "			</tr>\n";
		}
		if ($itemNotes) {

			for ($i=0; $i<count($itemNotes); $i++) {

				//if ($user->dfltRole >= $g_permission['staff'] || $itemNotes[$i]->getType() == "Instructor" || $itemNotes[$i]->getType() == "Content") {
					echo "      <tr valign=\"middle\">\n";
					echo "			";
					echo "            	<td align=\"right\" bgcolor=\"#CCCCCC\"><span class=\"strong\">".$itemNotes[$i]->getType()." Note:</span><br><a href=\"index.php?cmd=editHeading&ci=".$ci."&headingID=".$heading->getReserveID()."&deleteNote=".$itemNotes[$i]->getID()."\">Delete this note</a></td>\n";
					echo "				<td align=\"left\"><textarea name=\"itemNotes[".$itemNotes[$i]->getID()."]\" cols=\"50\" rows=\"3\">".$itemNotes[$i]->getText()."</textarea></td>\n";
					echo "      </tr>\n";
				//}
			}
		}
		if ($instructorNotes) {

			for ($i=0; $i<count($instructorNotes); $i++) {

				echo "      <tr valign=\"middle\">\n";
				echo "			";
				echo "			<!-- On page load, by default, there is no blank \"Notes\" field showing, only ";
				echo "			previously created notes, if any, and the \"add Note\" button. Notes should";
				echo "			be added one after the other at the bottom of the table, but above the \"add Note\" button.-->\n";
				echo "            	<td align=\"right\" bgcolor=\"#CCCCCC\"><span class=\"strong\">Instructor Note:</span><br><a href=\"index.php?cmd=editHeading&ci=".$ci."&headingID=".$heading->getReserveID()."&deleteNote=".$instructorNotes[$i]->getID()."\">Delete this note</a></td>\n";
				echo "				<td align=\"left\"><textarea name=\"instructorNotes[".$instructorNotes[$i]->getID()."]\" cols=\"50\" rows=\"3\">".$instructorNotes[$i]->getText()."</textarea></td>\n";
				echo "      </tr>\n";
			}
		}
		
		//END
        
        if ($heading->getReserveID())
        {
        echo '			<tr valign="middle">';
        echo '				<td colspan="2" align="left" valign="top" bgcolor="#CCCCCC" class="borders">';
        echo '					<div align="center"><input type="button" name="addNote" value="Add Note" onClick="openWindow(\'&cmd=addNote&reserve_id='.$heading->getReserveID().'\');"></div>';
        echo '				</td>';
        echo '			</tr>';
      	}
        echo '		</table>';
        echo '		</td>';
      	echo '	</tr>';
      	echo '	<tr>';
        echo '		<td>&nbsp;</td>';
      	echo '	</tr>';
      	/*
      	echo '	<tr>';
        echo '		<td><div align="left">';
        echo '			<p align="center">';
        echo '				<input type="submit" name="Submit" value="Save Changes">';
    	echo '				<br>';
    	echo '				&gt;&gt; Save changes and return to class';
    	echo '			</p>';
        echo '			<p align="center">';
        echo '				<input type="submit" name="Submit" value="Add another heading">';
        echo '				<br>';
        echo '				&gt;&gt; Save changes and create another heading';
        echo '			</p>';
        echo '		</div></td>';
      	echo '	</tr>';
      	*/
      	echo '	<tr><td align="left"><a href="javascript:processHeading(document.forms.editHeading,\'editClass\');">&gt;&gt;&nbsp;Save changes and return to class</a></td></tr>';
      	echo '	<tr><td align="left"><a href="javascript:processHeading(document.forms.editHeading,\'editHeading\');">&gt;&gt;&nbsp;Save changes and create another heading</a></td></tr>';
      	echo '	<tr><td align="left"><a href="javascript:processHeading(document.forms.editHeading,\'customSort\');">&gt;&gt;&nbsp;Save changes and change heading sort position</a></td></tr>';
      	echo '	<tr><td align="left">&nbsp;</td></tr>';
      	echo '	<tr><td align="left"><a href="index.php?cmd=editClass&ci='.$ci.'">&gt;&gt;&nbsp;Cancel and return to class</a></td></tr>';
      	echo '	<tr>';
        echo '		<td><img src="/images/spacer.gif" width="1" height="15"></td>';
      	echo '	</tr>';
    	echo '</table>';
    	echo '</form>';
	}
	
	function displayEditItemScreen($item,$user,$owner_list=null,$search_serial=null)
	{
		
		global $g_permission, $g_documentURL;
		
		$title = $item->getTitle();
		$author = $item->getAuthor();
		$url = $item->getURL();
		$performer = $item->getPerformer();
		$volTitle = $item->getVolumeTitle();
		$volEdition = $item->getVolumeEdition();
		$pagesTimes = $item->getPagesTimes();
		$source = $item->getSource();
		$contentNotes = $item->getContentNotes();
		$itemNotes = $item->getNotes(); //Valid note types, associated with an item, are content, copyright, and staff
		//private user
		if( !is_null($item->getPrivateUserID()) ) {
			$privateUserID = $item->getPrivateUserID();
			$item->getPrivateUser();
			$privateUser = $item->privateUser->getName(). ' ('.$item->privateUser->getUsername().')';
		}
?>
	<script language="JavaScript">
	//<!--
		function validateForm(frm)
		{			
			var alertMsg = "";

			if (frm.title.value == "")
				alertMsg = alertMsg + "Title is required.<br />";
				
			if (frm.documentType[1].checked)
			{ 
				if (frm.userFile.value == "")
					alertMsg = alertMsg + "File path is required.<br>"
			} else if (frm.documentType[2].checked) {
				if (frm.url.value == "")
					alertMsg = alertMsg + "URL is required.<br>";				
			} 
				
			if (!alertMsg == "") 
			{ 
				document.getElementById('alertMsg').innerHTML = alertMsg;
				return false;
			}
		}
		
		
		//shows/hides personal item elements; marks them as required or not
		function togglePersonal(enable) {
			if(enable) {
				document.getElementById('personal_item_yes').checked = true;
				document.getElementById('personal_item_owner_block').style.display ='';
				togglePersonalOwnerSearch();
			}
			else {
				document.getElementById('personal_item_no').checked = true;
				document.getElementById('personal_item_owner_block').style.display ='none';
			}
		}
	
		//shows/hides personal item owner search fields
		function togglePersonalOwnerSearch() {
			//if no personal owner set, 
			if(document.getElementById('personal_item_owner_curr').checked) {
				document.getElementById('personal_item_owner_search').style.visibility = 'hidden';
			}
			else if(document.getElementById('personal_item_owner_new').checked) {
				document.getElementById('personal_item_owner_search').style.visibility = 'visible';
			}	
		}
	//-->
	</script>			
<?php
		$formEncode = ($item->getItemGroup() == 'ELECTRONIC') ? "enctype=\"multipart/form-data\"" : "";
		echo "<form name=\"reservesMgr\" action=\"index.php?cmd=editItem\" method=\"post\" $formEncode onSubmit=\"return validateForm(this);\">\n";
		
		echo "<input type=\"hidden\" name=\"itemID\" value=\"".$item->getItemID()."\">\n";
		echo "<input type=\"hidden\" name=\"search\" value=\"".$search_serial."\">\n";		

		echo "<table width=\"90%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">\n";
		echo "	  <tr>\n";
		echo "     	<td width=\"140%\"><img src=\images/spacer.gif\" width=\"1\" height=\"5\"></td>\n";
		echo "	  </tr>\n";		
		
		if ($item->getItemGroup() == 'ELECTRONIC')
		{
			if (!isset($request['documentType'])) {
				$maintain_current = 'checked';
				$upload_checked  = '';
				$upload_disabled = 'disabled';
				
				$url_checked	 = ''; 
				$url_disabled	 = 'disabled';
			} elseif ($request['documentType'] == 'DOCUMENT') {
				$upload_checked  = 'checked';
				$upload_disabled = '';
				
				$url_checked	 = ''; 
				$url_disabled	 = 'disabled';
			} else {
				$upload_checked  = '';
				$upload_disabled = 'disabled';
				
				$url_checked	 = 'checked'; 
				$url_disabled	 = '';				
			}
			
			echo "  <tr>\n";
			echo "    	<td>\n";
			echo "    	<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n";
			echo "        	<tr align=\"left\" valign=\"top\">\n";
			echo "            	<td class=\"headingCell1\"><div align=\"center\">ITEM SOURCE</div></td>\n";
			echo "				<td width=\"75%\">&nbsp;</td>\n";
			echo "			</tr>\n";
			echo "		</table>\n";
			echo "		</td>\n";
			echo "	</tr>\n";
			echo "  <tr>\n";
			echo "    	<td align=\"left\" valign=\"top\" class=\"borders\">\n";
			echo "			<table width=\"100%\" border=\"0\" cellpadding=\"3\" cellspacing=\"0\" bgcolor=\"#CCCCCC\" align=\"center\">\n";
			echo "				<tr class=\"borders\">\n";
			echo "					<td align=\"left\" valign=\"top\" NOWRAP><strong>Current URL:</strong></td>\n";
			echo "					<td>$url &nbsp;&nbsp;&nbsp;<i><b>to overwrite set below</b></i></td>\n";
			echo "				</tr>\n";

			echo "				<tr class=\"borders\">\n";
			echo "					<td align=\"left\" valign=\"top\" NOWRAP colspan=\"2\">\n";
			echo "						<input type=\"radio\" name=\"documentType\" $maintain_current onClick=\"this.form.userFile.disabled = true; this.form.url.disabled = true;\">";
			echo "						&nbsp;<span class=\"strong\">Maintain current URL</span>\n";
			echo "					</td>\n";
			echo "				</tr>\n";			
			
			
			echo "				<tr class=\"borders\">\n";
			echo "					<td align=\"left\" valign=\"top\" NOWRAP>\n";
			echo "						<input type=\"radio\" name=\"documentType\" value=\"DOCUMENT\" $upload_checked onClick=\"this.form.userFile.disabled = !this.checked; this.form.url.disabled = this.checked;\">";
			echo "						&nbsp;<span class=\"strong\">Upload new file&gt;&gt;</span>\n";
			echo "					</td>\n";
			echo "					<td align=\"left\" valign=\"top\" colspan=\"2\"><input type=\"file\" name=\"userFile\" size=\"40\" $upload_disabled></td>\n";
			echo "				</tr>\n";
			echo "				<tr class=\"borders\">\n";
			echo "					<td align=\"left\" valign=\"top\">\n";
			echo "						<input type=\"radio\" name=\"documentType\" value=\"URL\" $url_checked onClick=\"this.form.url.disabled = !this.checked; this.form.userFile.disabled = this.checked;\">\n";
			echo "						<span class=\"strong\">Change URL&gt;&gt;</span>\n";
			echo "					</td>\n";
			echo "					<td align=\"left\" valign=\"top\">\n";
			echo "						<input name=\"url\" type=\"text\" size=\"50\" $url_disabled>\n";
			echo "					</td>\n";
			echo "					<td align=\"left\" valign=\"top\" width=\"80%\">";
			echo "						<input type=\"button\" onClick=\"openNewWindow(this.form.url.value, 500);\" value=\"Preview\">";
			echo "					</td>\n";			
			echo "				</tr>\n";
			echo "			</table>\n";		
			echo "		</td>\n";
			echo "	</tr>\n";
		}
		
		echo "    <tr>\n";
		echo "    	<td>\n";
		echo "    	<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n";
		echo "        	<tr align=\"left\" valign=\"top\">\n";
		echo "            	<td class=\"headingCell1\"><div align=\"center\">ITEM DETAILS</div></td>\n";
		echo "				<td width=\"75%\">&nbsp;</td>\n";
		echo "			</tr>\n";
		echo "		</table>\n";
		echo "		</td>\n";
		echo "	</tr>\n";
		echo "    <tr>\n";
		echo "    	<td align=\"left\" valign=\"top\" class=\"borders\">\n";
		echo "    	<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\">\n";
		echo "            <tr valign=\"middle\">\n";
		echo "            	<td width=\"25%\" align=\"right\" bgcolor=\"#CCCCCC\"><div align=\"right\" class=\"strong\"><font color=\"#FF0000\"><strong>*</strong></font>Document Title:</div></td>\n";
		echo "				<td width=\"100%\" align=\"left\"><input name=\"title\" type=\"text\" id=\"title\" size=\"50\" value=\"$title\"></td>\n";
		echo "			</tr>\n";
		echo "            <tr valign=\"middle\">\n";
		echo "            	<td width=\"25%\" height=\"31\" align=\"right\" bgcolor=\"#CCCCCC\"><div align=\"right\" class=\"strong\">Author/Composer:</div></td>\n";
		echo "				<td width=\"100%\" align=\"left\"><input name=\"author\" type=\"text\" id=\"author\" size=\"50\" value=\"".$author."\"></td>\n";
		echo "			</tr>\n";

		if ($item->getItemGroup() != 'ELECTRONIC')
		{
/*			
			echo "          <tr valign=\"middle\">\n";
			echo "            	<td align=\"right\" bgcolor=\"#CCCCCC\"><div align=\"right\" class=\"strong\"><font color=\"#FF0000\"><strong>*</strong></font>URL:</div></td>\n";
			echo "				<td width=\"100%\" align=\"left\">";
			//echo "					<input name=\"url\" type=\"text\" size=\"50\" value=\"".urldecode($url)."\">";
			//echo "					<input type=\"button\" onClick=\"openNewWindow(this.form.url.value, 500);\" value=\"Preview\">";
			//echo "					&nbsp;&nbsp;";
			//echo "					<input type=\"button\" onClick=\"openNewWindow(this.form.url.value, 500);\" value=\"Preview\">";


			
			
			
			echo "				</td>\n";
			echo "			</tr>\n";
		} else {
*/
			$pc = new physicalCopy();
			$pc->getByItemID($item->getItemID());
			echo "          <tr valign=\"middle\">\n";
			echo "            	<td align=\"right\" bgcolor=\"#CCCCCC\"><div align=\"right\" class=\"strong\"><font color=\"#FF0000\"><strong>*</strong></font>Barcode:</div></td>\n";
			echo "				<td width=\"100%\" align=\"left\"><input name=\"url\" type=\"text\" size=\"50\" value=\"". $pc->getBarcode() ."\"></td>\n";
			echo "			</tr>\n";
		}
		
		echo "          <tr valign=\"middle\">\n";
		echo "            	<td width=\"25%\" align=\"right\" bgcolor=\"#CCCCCC\"><div align=\"right\"><span class=\"strong\">Performer </span><span class=\"strong\">:</span></div></td>\n";
		echo "				<td width=\"100%\" align=\"left\"><input name=\"performer\" type=\"text\" id=\"performer\" size=\"50\" value=\"".$performer."\"></td>\n";
		echo "			</tr>\n";
		echo "            <tr valign=\"middle\">\n";
		echo "            	<td width=\"25%\" align=\"right\" bgcolor=\"#CCCCCC\"><div align=\"right\"><span class=\"strong\">Book/Journal/Work Title</span><span class=\"strong\">:</span></div></td>\n";
		echo "				<td width=\"100%\" align=\"left\"><input name=\"volumeTitle\" type=\"text\" id=\"volumeTitle\" size=\"50\" value=\"".$volTitle."\"></td>\n";
		echo "			</tr>\n";
		echo "            <tr valign=\"middle\">\n";
		echo "            	<td width=\"25%\" align=\"right\" bgcolor=\"#CCCCCC\"><div align=\"right\"><span class=\"strong\">Volume/ Edition</span><span class=\"strong\">:</span></div></td>\n";
		echo "				<td width=\"100%\" align=\"left\"><input name=\"volumeEdition\" type=\"text\" id=\"volumeEdition\" size=\"50\" value=\"".$volEdition."\"></td>\n";
		echo "			</tr>\n";
		echo "            <tr valign=\"middle\">\n";
		echo "            	<td width=\"25%\" align=\"right\" bgcolor=\"#CCCCCC\"><div align=\"right\"><span class=\"strong\">Pages/Time</span><span class=\"strong\">:</span></div></td>\n";
		echo "				<td width=\"100%\" align=\"left\"><input name=\"pagesTimes\" type=\"text\" id=\"pages\" size=\"50\" value=\"".$pagesTimes."\"></td>\n";
		echo "            </tr>\n";
		echo "            <tr valign=\"middle\">\n";
		echo "            	<td width=\"25%\" align=\"right\" bgcolor=\"#CCCCCC\"><div align=\"right\"><span class=\"strong\">Source/ Year</span><span class=\"strong\">:</span></div></td>\n";
		echo "				<td width=\"100%\" align=\"left\"><input name=\"source\" type=\"text\" id=\"source\" size=\"50\" value=\"".$source."\"></td>\n";
		echo "			</tr>\n";
		
		//personal copy/private user block
		
		//set search-by selected
		$username = "";
		$last_name = "";
		$selector = (isset($_REQUEST['select_owner_by'])) ? $_REQUEST['select_owner_by'] : "last_name";
		$$selector = 'selected="selected"';
		
		//set search term
		$owner_qryTerm = (isset($_REQUEST['owner_qryTerm'])) ? $_REQUEST['owner_qryTerm'] : "";

		//set name selected
		$inst_DISABLED = (is_null($owner_list)) ? 'disabled="disabled"' : '';

		//show the form elements
?>
			<tr align="left" valign="top" id="personal_item_row">
				<td align="right" bgcolor="#CCCCCC" class="strong">
					Personal Copy Owner:
				</td>
				<td>
					<div id="personal_item_choice">
						<input type="radio" name="personal_item" id="personal_item_no" value="no" onChange="togglePersonal(0);" /> No
						&nbsp;&nbsp;
						<input type="radio" name="personal_item" id="personal_item_yes" value="yes" onChange="togglePersonal(1);" /> Yes
					</div>
					<div id="personal_item_owner_block" style="margin-top:2px; margin-bottom:15px;">
<?php
	//if there is an existing owner, give a choice of picking new one
	if(isset($privateUser)):
?>
						<input type="radio" name="personal_item_owner" id="personal_item_owner_curr" value="old" checked="checked" onChange="togglePersonalOwnerSearch();" /> Current - <strong><?=$privateUser?></strong>
						<br />
						<input type="radio" name="personal_item_owner" id="personal_item_owner_new" value="new" onChange="togglePersonalOwnerSearch();" /> New &nbsp;
<?php
	else:	//if not, then just assume we are searching for a new one
?>
						<input type="hidden" name="personal_item_owner" id="personal_item_owner_new" value="new" />
<?php
	endif;
?>
						<span id="personal_item_owner_search">
							<select name="select_owner_by">
								<option value="last_name" <?=$last_name?>>Last Name</option>
								<option value="username" <?=$username?>>User Name</option>
							</select>
							&nbsp; <input id="owner_qryTerm" name="owner_qryTerm" type="text" value="<?=$owner_qryTerm?>" size="15"  onBlur="this.form.submit();">
							&nbsp; <input type="submit" name="owner_search" value="Search">
							&nbsp;
							<select name="selected_owner" <?=$inst_DISABLED?>>
								<option value="null">-- Choose Item Owner -- </option>
<?php
		for($i=0;$i<count($owner_list);$i++) {
			$inst_selector = ($_REQUEST['selected_owner'] == $owner_list[$i]->getUserID() || $search_results['personal_owner'] == $owner_list[$i]->getUserID()  ) ? 'selected="selected"' : '';
			echo "\t\t\t\t\t\t\t".'<option value="'. $owner_list[$i]->getUserID() .'" '.$owner_selector.'>'.$owner_list[$i]->getName().'</option>'."\n";
		}
?>
							</select>
						</span>
					</div>
				</td>
			</tr>
<?php
		//notes
		
		if ($contentNotes) {
		
			echo "            <tr valign=\"middle\">\n";
			echo "            	<td width=\"25%\" align=\"right\" bgcolor=\"#CCCCCC\"><div align=\"right\"><span class=\"strong\">Content Note:<br></span></div></td>\n";
			echo "				<td width=\"100%\" align=\"left\"><textarea name=\"contentNotes\" cols=\"50\" rows=\"3\">".$contentNotes."</textarea></td>\n";
			echo "			</tr>\n";
		}
		if ($itemNotes) {
			
			for ($i=0; $i<count($itemNotes); $i++) {
				
				if ($user->dfltRole >= $g_permission['staff'] || $itemNotes[$i]->getType() == "Instructor" || $itemNotes[$i]->getType() == "Content") {
					echo "      <tr valign=\"middle\">\n";
					echo "			";
					echo "			<!-- On page load, by default, there is no blank \"Notes\" field showing, only ";
					echo "			previously created notes, if any, and the \"add Note\" button. Notes should";
					echo "			be added one after the other at the bottom of the table, but above the \"add Note\" button.-->\n";
					echo "            	<td align=\"right\" bgcolor=\"#CCCCCC\"><span class=\"strong\">".$itemNotes[$i]->getType()." Note:</span><br><a href=\"index.php?cmd=editReserve&reserveID=".$reserve->getReserveID()."&deleteNote=".$itemNotes[$i]->getID()."\">Delete this note</a></td>\n";
					echo "				<td align=\"left\"><textarea name=\"itemNotes[".$itemNotes[$i]->getID()."]\" cols=\"50\" rows=\"3\">".$itemNotes[$i]->getText()."</textarea></td>\n";
					echo "      </tr>\n";
				}
			}
		}			
		echo "          <tr valign=\"middle\">\n";	
		echo "            	<td colspan=\"2\" valign=\"top\" bgcolor=\"#CCCCCC\" class=\"borders\" align=\"center\">\n";
		echo "					<input type=\"button\" name=\"addNote\" value=\"Add Note\" onClick=\"openWindow('&cmd=addNote&item_id=".$item->getitemID()."');\">\n";
		echo "				</td>\n";
		echo "			</tr>\n";
		echo "		</table>\n";
		echo "		</td>\n";
		echo "	</tr>\n";
		echo "    <tr>\n";
		echo "    	<td><strong><font color=\"#FF0000\">* </font></strong><span class=\"helperText\">=required fields</span></td>\n";
		echo "	</tr>\n";
		echo "    <tr>\n";
		echo "    	<td><div align=\"center\"><input type=\"submit\" name=\"Submit\" value=\"Save Changes\"></div></td>\n";
		echo "	</tr>\n";
		echo "	<tr><td colspan=\"3\">&nbsp;</td></tr>\n";

		echo "	<tr><td colspan=\"3\"><img src=\images/spacer.gif\" width=\"1\" height=\"15\"></td></tr>\n";
		echo "    <tr>\n";
		echo "    	<td><img src=\images/spacer.gif\" width=\"1\" height=\"15\"></td>\n";
		echo "	</tr>\n";
		echo "</table>\n";
		echo "</form>\n";
?>
	<script language="JavaScript">
		//set up some fields on load
		
		//if we are searching for a new owner
		if( document.getElementById('owner_qryTerm').value != '') {
			//select new owner
			document.getElementById('personal_item_owner_new').checked = true;
			//show private owner block
			togglePersonal(1);
		}
		else if( document.getElementById('personal_item_owner_curr') != null ) {	//if there is already a private owner
			//select current owner
			document.getElementById('personal_item_owner_curr').checked = true;
			//show private owner block
			togglePersonal(1);			
		}
		else {
			//default to no private owner
			togglePersonal(0);
		}
	</script>
<?php
	}

	function displayItemSuccessScreen($search_serial,$user)
	{
		
		global $g_permission;

		echo "	<table width=\"90%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">\n";
		echo "		<tr>\n";
		echo "	    	<td width=\"140%\"><img src=\"/images/spacer.gif\" width=\"1\" height=\"5\"> </td>\n";
		echo "	    </tr>\n";
		echo "	    <tr>\n";
		echo "	        <td align=\"left\" valign=\"top\" class=\"borders\">\n";
		echo "				<table width=\"50%\" border=\"0\" align=\"center\" cellpadding=\"0\" cellspacing=\"5\">\n";
		echo "	            	<tr>\n";
		echo "	                	<td><strong>Your item has been updated successfully.</strong></td>\n";
		echo "	                </tr>\n";
		echo "	                <tr>\n";
		echo "	                	<td align=\"left\" valign=\"top\">\n";
		echo "	                		<ul>\n";
		echo "	                			<li><a href=\"index.php?cmd=doSearch&search=". $search_serial ."\">Return to Search Results</a></li>\n";
		echo "	                			<li><a href=\"index.php\">Return to myReserves</a><br></li>\n";
		echo "	                		</ul>\n";
		echo "	                	</td>\n";
		echo "	                </tr>\n";
		echo "	            </table>\n";
		echo "			</td>\n";
		echo "		</tr>\n";
		echo "	</table>\n";				

	}	

}
?>
