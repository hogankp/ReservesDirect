<?
/*******************************************************************************
Reserves Direct 2.0

Copyright (c) 2004 Emory University General Libraries

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
\"Software\"), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be included
in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED \"AS IS\", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

Created by Kathy A. Washington (kawashi@emory.edu)

Reserves Direct 2.0 is located at:
http://coursecontrol.sourceforge.net/

*******************************************************************************/
require_once("common.inc.php");

class noteDisplayer 
{
	/**
	* @return void
	* @param $user, $reserveID
	* @desc display Add Note form
	*/
	function displayAddNoteScreen($user, $hidden_fields)
	{
		global $g_permission;
		
		//$reserve = new reserve($reserveID);
		//$reserve->getItem();
		
		echo "<form name=\"addNote\" action=\"index.php?cmd=addNote\" method=\"post\">\n";

		if (is_array($hidden_fields)){
			$keys = array_keys($hidden_fields);
			foreach($keys as $key){
				if (is_array($hidden_fields[$key])){
					foreach ($hidden_fields[$key] as $field){
						echo "<input type=\"hidden\" name=\"".$key."[]\" value=\"". $field ."\">\n";	
					}
				} else {
					echo "<input type=\"hidden\" name=\"$key\" value=\"". $hidden_fields[$key] ."\">\n";
				}
			}
		}

		
		//echo '<table width="410" border="0" cellspacing="0" cellpadding="0">';
		//echo '	<tr>';
		//echo '		<td width="10">&nbsp;</td>';
		//echo '		<td width = "400">';
		
		echo '<center>';
		echo '<table width="400" border="0" cellspacing="0" cellpadding="0">';
  		echo '	<tr><td align="left" valign="top"><h1>Add Note</h1></td></tr>';
  		echo '	<tr><td align="left" valign="top">&nbsp;</td></tr>';
  		echo '	<tr><td align="left" valign="top">&nbsp;</td></tr>';
  		if ($user->dfltRole >= $g_permission['staff']) {
  			echo '	<tr>';
  			echo '  	<td align="left" valign="top">';
  			echo '			<table width="100%" border="0" cellspacing="0" cellpadding="0">';
  			echo '  			<tr>';
  			echo '  				<td width="50%" class="headingCell1">Note Options</td>';
  			echo '      			<td>&nbsp;</td>';
  			echo '				</tr>';
  			echo '			</table>';
  			echo '		</td>';
  			echo '	</tr>';
  			echo '	<tr>';
  			echo '  	<td align="left" valign="top">';
  			echo '			<table width="100%" border="0" cellpadding="3" cellspacing="0" class="borders">';
  			echo '    			<tr align="left" valign="top" bgcolor="#CCCCCC">';
  			echo '      			<td width="22%" valign="top"><p class="strong">Note Type:<br>';
  			echo '	    			<span class="small-x">(This will show as the title of the note for editing';
			echo '				    purposes.)</span></p>';
			echo '					</td>';
        	echo '					<td width="78%" align="left"><p>';        
       		echo '					<label><input type="radio" name="noteType" value="Content" checked>Content Note</label><br>';
       		echo '					<label><input type="radio" name="noteType" value="Instructor">Instructor Note</label><br>';
       		echo '					<label><input type="radio" name="noteType" value="Staff">Staff Note</label><br>';
			echo '					<label><input type="radio" name="noteType" value="Copyright">Copyright Note</label><br>';
			echo '					</p></td>';
			echo '				</tr>';
			/*
      		echo '				<tr align="left" valign="top" bgcolor="#CCCCCC">';
        	echo '					<td class="strong">Permanency:</td>';
        	echo '					<td><p>';
        	echo '						<label><input name="Permanency" type="radio" value="radio" checked>Permanent</label>';
        	echo '							<span class="small-x">(until deleted)</span>';
        	echo '						<label></label><br>';
        	echo '						<label><input type="radio" name="Permanency" value="radio">Temporary</label>';
        	echo '							<span class="small-x">(lasts only for the current semester)</span><br>';
        	echo ' 					</p></td>';
      		echo '				</tr>';
      		*/
			echo '			</table>';
			echo '		</td>';
  			echo '	</tr>';
		} else {
			echo '					<input type="hidden" name="noteType" value="Instructor">';
		}

  		echo '	<tr>';
  		echo '		<td align="left" valign="top">&nbsp;</td>';
  		echo '	</tr>';
  		echo '	<tr>';
    	echo '		<td align="left" valign="top">';
    	echo '			<table width="100%" border="0" cellspacing="0" cellpadding="0">';
      	echo '				<tr>';
        echo '					<td width="50%" class="headingCell1">Note Text</td>';
        echo '					<td>&nbsp;</td>';
      	echo '				</tr>';
    	echo '			</table>';
    	echo '		</td>';
  		echo '	</tr>';
  		echo '	<tr>';
    	echo '		<td align="left" valign="top" class="borders">';
    	echo '			<table width="100%" border="0" cellspacing="0" cellpadding="3">';
      	echo '				<tr>';
        echo '					<td align="center" valign="top"><textarea name="noteText" cols="45"></textarea></td>';
      	echo '				</tr>';
    	echo '			</table>';
    	echo '		</td>';
  		echo '	</tr>';
  		
  		echo "    <tr><td><img src=\../images/spacer.gif\" width=\"1\" height=\"15\"></td></tr>\n";
  		echo "    <tr>\n";
		echo "    	<td align=\"center\"><input type=\"submit\" value=\"Save Note\"></td>\n";
		echo "	</tr>\n";
		echo "    <tr><td><img src=\../images/spacer.gif\" width=\"1\" height=\"15\"></td></tr>\n";
		echo '</table>';
		
		echo '</td>';
		echo '</tr>';
		echo '</table>';
		echo "</form>\n";
		echo "</center>";
	}
	
	
	function displaySuccess($noteID)
	{
		echo "<script language=\"JavaScript\">this.window.opener.newWindow_returnValue='$noteID';</script>\n"; //pass value to parent window
		
		echo "<table width=\"90%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">\n"
		.	 "	<tbody>\n"
		.	 "		<tr><td width=\"140%\"><img src=\../images/spacer.gif\" width=\"1\" height=\"5\"> </td></tr>\n"
		.	 "		<tr>\n"
	    .	 "			<td align=\"left\" valign=\"top\" class=\"borders\">\n"
	    .	 "				<table width=\"50%\" border=\"0\" align=\"center\" cellpadding=\"0\" cellspacing=\"5\">\n"
		.	 "					<tr><td align=\"center\"><strong>You have successfully added a note.</strong></td></tr>\n"
		.	 "					<tr><td align=\"center\">\n"
		.	 "						Please close this window to Continue<p\>\n"
		.	 "						<input type=\"button\" value=\"Close Window\" onClick=\"window.close();\">\n"
		.	 "					</td></tr>\n"
		.	 "				</table>\n"
		.	 "			</td>\n"
		.	 "		</tr>\n"
		.	 "		<tr><td><img src=\../images/spacer.gif\" width=\"1\" height=\"15\"></td></tr>\n"
		.	 "	</tbody>\n"
		.	 "</table>\n"
		;
	}
}
?>