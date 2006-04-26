<?php
/*******************************************************************************
baseDisplayer.class.php
Base Displayer abstract class

Created by Dmitriy Panteleyev (dpantel@emory.edu)

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

require_once('secure/displayers/noteDisplayer.class.php');
require_once('secure/classes/reserves.class.php');

/**
 * Base Displayer abstract class
 * - Contains functions common to many displayers
 * - To be extended by other displayer classes
 * - this is very layout-specific, since it will assume the general layout of
 *	its children's elements - ie. it will be using <table> often, as that is what
 *	its children currently use.
 */
abstract class baseDisplayer {
	
	/**
	 * @return void
	 * @param array $hidden_fields A reference to an array (may be two-dimensional) of keys/values
	 * @desc outputs hidden <input> fields for the array
	 */
	public function displayHiddenFields(&$hidden_fields) {
		if(empty($hidden_fields))
			return;
			
		foreach($hidden_fields as $key=>$val) {
			if(is_array($val)) {
				foreach($val as $subkey=>$val) {
					echo '<input type="hidden" name="'.$key.'['.$subkey.']" value="'.$val.'" />'."\n";
				}
			}
			else {		
				echo '<input type="hidden" name="'.$key.'" value="'.$val.'" />'."\n";
			}
		}
	}
	
	
	/**
	 * @return void
	 * @param reserve $reserve Reference to a reserve object
	 * @param string $block_style String added to the main <div> to style it
	 * @param boolean $edit_options If set to true, will display editing options. If false, will show student view
	 * @desc outputs HTML showing information about a reserve plus aditional links and info.  For use in class/reserve lists.
	 */
	public function displayReserveRow(&$reserve, $block_style='', $edit_options=false) {
		if(!($reserve->item instanceof reserveItem)) {
			$reserve->getItem();	//pull in item info
		}
		
		if($reserve->item->isHeading()) {	//style as heading
			$block_style ='class="headingCell2"';
		}
?>
	
	<div <?=$block_style?> >

<?php	if($reserve->hidden): ?>
		<div class="hiddenItem">
<?php	endif; ?>

		
		
<?php
		//are we editing?
		if($edit_options):	//yes, show editing info and options
			//if item is heading, warn on checkbox click
			$checkbox_onchange = $reserve->item->isHeading() ? 'onchange="javascript:alert(\'Checking this box will affect everything in this folder\')"' : '';
			$meta_style = 'metaBlock';
?>
		<div class="editOptions">
			<div class="itemNumber">
				<?=$reserve->counter?>
			</div>
			<div class="checkBox">
				<input type="checkbox" name="selected_reserves[]" value="<?=$reserve->getReserveID()?>" <?=$checkbox_onchange?> />
			</div>
			<div class="sortBox">
				<?=$reserve->sort_link?>&nbsp;
			</div>
			<div class="editBox">
				<?=$reserve->edit_link?>&nbsp;
			</div>
			<div class="statusBox">
				<span class="<?=common_getStatusStyleTag($reserve->status)?>"><?=$reserve->status?></span>
			</div>
		</div>
<?php	
		else:	//not editing -- "student view"
			//if item is hidden, mark it as such
			$checkbox_checked = ((isset($reserve->hidden) && $reserve->hidden) || (isset($reserve->selected) && $reserve->selected)) ? 'checked="checked"' : '';
			//if item is heading, warn on checkbox click
			$checkbox_onchange = $reserve->item->isHeading() ? 'onchange="javascript:alert(\'Checking/Unchecking this box will hide/unhide everything in this folder\')"' : '';
			$meta_style = 'metaBlock-wide';

			
?>

			<div class="checkBox-right">
				<input type="checkbox" <?=$checkbox_checked?> name="selected_reserves[]" value="<?=$reserve->getReserveID()?>" <?=$checkbox_onchange?> />
			</div>
		
<?php		endif; ?>
		
<?php	self::displayReserveInfo($reserve, 'class="'.$meta_style.'"'); ?>

<?php	if($reserve->hidden): ?>
		</div>
<?php	endif; ?>

		<!-- hack to clear floats -->
		<div style="clear:both;"></div>
		<!-- end hack -->
	</div>
<?php
	}
	
	
	/**
	 * @return void
	 * @param reserve $reserve Reference to a reserve object
	 * @param string $row_style styles the row
	 * @desc outputs HTML showing information about a reserve.  For use in class/reserve lists.
	 */
	public function displayReserveInfo(&$reserve, $meta_style) {
		global $u, $g_reservesViewer;
	
		//collect and set data	
		
		if(!($reserve->item instanceof reserveItem)) {
			$reserve->getItem();	//pull in item info
		}
		
		if(!$reserve->item->isHeading()) {	//if not heading/folder, assign all the pertinent info
			$title = $reserve->item->getTitle();
			$author = $reserve->item->getAuthor();
			$url = $reserve->item->getURL();
			$performer = $reserve->item->getPerformer();
			$volTitle = $reserve->item->getVolumeTitle();
			$volEdition = $reserve->item->getVolumeEdition();
			$pagesTimes = $reserve->item->getPagesTimes();
			$source = $reserve->item->getSource();
			$itemIcon = $reserve->item->getItemIcon();
			
			$reserve->item->getPhysicalCopy();	//get physical copy info
			$callNumber = $reserve->item->physicalCopy->getCallNumber();
			//get home library/reserve desk
			$lib = new library($reserve->item->getHomeLibraryID());
			$reserveDesk = $lib->getReserveDesk();
			
			if($reserve->item->isPhysicalItem()) {
				$viewReserveURL = $g_reservesViewer . $reserve->item->getLocalControlKey();
			}
			else {
				$viewReserveURL = "reservesViewer.php?reserve=" . $reserve->getReserveID();
			}
		}
		$itemNotes = $reserve->item->getNotes();
		$reserveNotes = $reserve->getNotes();	
		
		//begin display of data

		if($reserve->item->isHeading()):
?>

		<div class="headingText" style="border:0px solid red;">
<?php
			echo $reserve->item->getTitle();
			//show notes
			noteDisplayer::displayNotes($itemNotes);
			noteDisplayer::displayNotes($reserveNotes);
?>
		</div>
	
<?php	else: ?>

		<div class="iconBlock">
			<img src="<?=$itemIcon?>" alt="icon">&nbsp;
		</div>

		<div <?=$meta_style?>>
<?php		if($reserve->item->isPhysicalItem()): ?>

			
			<span class="itemTitleNoLink"><?=$title?></span>
			<br />
			<span class="itemAuthor"><?=$author?></span>
			<br />
			<span class="itemMeta"><?=$callNumber?></span>
			<br />
			<span class="itemMetaPre">On Reserve at:</span><span class="itemMeta"><?=$reserveDesk?></span> [<a href="<?=$viewReserveURL?>" target="_blank" class="strong">more info</a>]
	
<?php		else: ?>
		
		
			<a href="<?=$viewReserveURL?>" target="_blank" class="itemTitle" style="margin:0px; padding:0px;"><?=$title?></a>
		
		<br />
					
			<span class="itemAuthor"><?=$author?></span>
			
<?php		endif; ?>


<?php		if($performer): ?>

       		<br />
       		<span class="itemMetaPre">Performed by:</span><span class="itemMeta"><?=$performer?></span>
       		
<?php		endif; ?>
<?php		if($volTitle): ?>

       		<br />
       		<span class="itemMetaPre">From:</span><span class="itemMeta"><?=$volTitle?></span>
       		
<?php		endif; ?>
<?php		if($volEdition): ?>

       		<br />
       		<span class="itemMetaPre">Volume/Edition:</span><span class="itemMeta"><?=$volEdition?></span>
       		
<?php		endif; ?>
<?php		if($pagesTimes): ?>

       		<br />
       		<span class="itemMetaPre">Pages/Time:</span><span class="itemMeta"><?=$pagesTimes?></span>
       		
<?php		endif; ?>
<?php		if($source): ?>

       		<br />
       		<span class="itemMetaPre">Source/Year:</span><span class="itemMeta"><?=$source?></span>
       		
<?php		endif; ?>

<?php
			//show notes
			noteDisplayer::displayNotes($itemNotes);
			noteDisplayer::displayNotes($reserveNotes);
			
			//show additional info
			if(!empty($reserve->additional_info)) {
				echo $reserve->additional_info;
			}
?>

		</div>

<?php 	
		endif; 
	}
	
	
	/**
	 * @return void
	 * @param courseInstance $ci Reference to a Course Instance object
	 * @param mixed $default_heading (optional) Pre-select the option matching this value. null = no selection, 'root' = main list, <id> = folder id
	 * @param boolean $truncate_heading (optional) If true, will trunkate the heading to the first 30 chars.
	 * @desc displays a <select> box that shows all available folders (headings) for a given CI
	 */
	public function displayHeadingSelect(&$ci, $default_heading=null, $truncate_heading=false) {
		//get headings as a tree + recursive iterator
		$walker = $ci->getReservesAsTreeWalker('getHeadings');
		
		$select_none = empty($default_heading) ? ' selected="selected"' : '';
		$select_root = (strtolower($default_heading)=='root') ? ' selected="selected"' : '';
?>
	<select name="heading_select">
		<option value=""<?=$select_none?>>...</option>
		<option value="root"<?=$select_root?>>Main List</option>
<?php
		foreach($walker as $leaf):
			$heading = new reserve($leaf->getID());
			$heading->getItem();
			$label = str_repeat('&nbsp;&nbsp;', ($walker->getDepth()+1)).$heading->item->getTitle();
			if($truncate_heading) {
				$label = substr($label, 0, 30).'...';
			}
			//pre-select a heading
			$select_other = ($leaf->getID()==$default_heading) ? ' selected="selected"' : '';
?>	
			<option value="<?=$leaf->getID()?>"<?=$select_other?>><?=$label?></option>
<?php	endforeach; ?>
	</select>
<?php
	}
	
	
	/**
	 * @return void
	 * @param int $default_dept (optional) ID of department to pre-select
	 * @param boolean $abbreviation_only (optional) If true will only display the abbreviation, instead of ABBR+NAME
	 * @param string $field_name (optional) If set, then the select id and name are set to this string
	 * @desc displays a <select> box that shows all available departments
	 */
	public function displayDepartmentSelect($default_dept=null, $abbreviation_only=false, $field_name='department') {
		$department = new department();	//init a department object
?>
	<select name="<?=$field_name?>" id="<?=$field_name?>">
		<option value="">-- Select a Department --</option>
<?php
		foreach($department->getAllDepartments() as $dep):
			$selected = ($dep->getDepartmentID()==$default_dept) ? 'selected="selected"' : '';
			$label = $abbreviation_only ? $dep->getAbbr() : $dep->getAbbr().' '.$dep->getName();
?>
		<option value="<?=$dep->getDepartmentID()?>" <?=$selected?>><?=$label?></option>
<?php	endforeach; ?>			
	</select>
<?php
	}
	
	
	/**
	 * @return void
	 * @param int $default_term (optional) ID of term to pre-select
	 * @param boolean $show_dates (optional) If true, will show input fields for activation and expiration dates; else will include them as hidden fields
	 * @desc displays a <select> box of semesters and date fields
	 */
	public function displayTermSelect($default_term=null, $show_dates=false) {
		global $calendar;
		
		$termsObj = new terms();
		$terms = $termsObj->getTerms();
		
		if(empty($default_term)) {	//set default if none specified
			$default_term = $terms[0]->getTermID();
		}
		
		//must build a javascript array with term dates
		$term_dates_jscript = '';
		foreach($terms as $term) {
			$term_dates_jscript .= "term_dates[".$term->getTermID()."] = new Array();\n";
			$term_dates_jscript .= "term_dates[".$term->getTermID()."][0] = '".$term->getBeginDate()."';\n";
			$term_dates_jscript .= "term_dates[".$term->getTermID()."][1] = '".$term->getEndDate()."';\n";
		}
		
?>
	<script language="JavaScript">
		/*
			This date change could be accomiplished much easier if you could call
			the term_setActiveDates() function directly from <option onclick>. 
			Howerver, IE does not support that even, so we must do the workaround where
			we build a list of all possible dates ahead of time.
		*/
		
		function term_setTermDates(term_id) {
			var term_dates = new Array();			
			<?=$term_dates_jscript?>

			return term_setActiveDates(term_dates[term_id][0], term_dates[term_id][1]);
		}
		
		function term_setActiveDates(activateDate, expirationDate) {
			if(document.getElementById('activation_date')) {
				document.getElementById('activation_date').value = activateDate;
			}
			if(document.getElementById('expiration_date')) {
				document.getElementById('expiration_date').value = expirationDate;
			}
			return false;
		}
	</script>

	<select name="term" id="term" onchange="term_setTermDates(this.options[this.selectedIndex].value);">
<?php
		foreach($terms as $term):
			$selected = '';
			if($term->getTermID()==$default_term) {	//if the term matches default term
				$selected = 'selected="selected"';	//preselect the field
				//fetch the default dates
				$activation_date = $term->getBeginDate();
				$expiration_date = $term->getEndDate();
			}
?>
	<!--	<option value="<?=$term->getTermID()?>" <?=$selected?> onclick="term_setActiveDates('<?=$term->getBeginDate()?>','<?=$term->getEndDate()?>')"><?=$term->getTerm()?></option>	-->
		<option value="<?=$term->getTermID()?>" <?=$selected?>><?=$term->getTerm()?></option>
<?php	endforeach; ?>			
	</select>
	
<?php	if($show_dates): //show date fields ?>

	&mdash; <input type="text" id="activation_date" name="activation_date" size="10" maxlength="10" value="<?=$activation_date?>" /> <?=$calendar->getWidgetAndTrigger('activation_date', $activation_date)?> &raquo; <input type="text" id="expiration_date" name="expiration_date" size="10" maxlength="10" value="<?=$expiration_date?>" /> <?=$calendar->getWidgetAndTrigger('expiration_date', $expiration_date)?>
	
<?php 	else:	//include them as hidden fields ?>

	<input type="hidden" id="activation_date" name="activation_date" value="<?=$activation_date?>" />
	<input type="hidden" id="expiration_date" name="expiration_date" value="<?=$expiration_date?>" />
	
<?php						
		endif;
	}
	
	
	/**
	 * @return void
	 * @param string $default_enrollment (optional) Enrollment option to check by default
	 * @param boolean $show_descriptions (optional) If true, will show descriptions of each option on the line below.
	 * @desc displays enrollment options as radio options
	 */
	public function displayEnrollmentSelect($default_enrollment='OPEN', $show_descriptions=false) {
		//set default
		$checked = array();
		$options = array('OPEN', 'MODERATED', 'CLOSED');
		if(!in_array($default_enrollment, $options)) {	//if not a valid default, set it to OPEN
			$default_enrollment = 'OPEN';
		}
		//now set up the checks
		foreach($options as $option) {
			$checked[$option] = ($default_enrollment == $option) ? 'checked="checked"' : '';
		}
		
		if($show_descriptions):
?>
		<script language="JavaScript">
			function showEnrollmentOptionDescription(option) {
				var option_descriptions = new Array();
				option_descriptions['OPEN'] = '<em>Any student may look up this class and join it.</em>';
				option_descriptions['MODERATED'] = '<em>Students may request to join this class, but must be approved before gaining access to it.</em>';
				option_descriptions['CLOSED'] = '<em>Students may not add themselves to this class or request to join it.</em>';
				
				if(document.getElementById('enrollment_option_desc')) {
					document.getElementById('enrollment_option_desc').innerHTML = option_descriptions[option];
				}
			}
		</script>
		
		<input type="radio" name="enrollment" id="enrollment_open" value="OPEN" <?=$checked['OPEN']?> onclick="javascript: showEnrollmentOptionDescription('OPEN');" /> <span class="openEnrollment">OPEN</span>&nbsp; 
		<input type="radio" name="enrollment" id="enrollment_moderated" value="MODERATED" <?=$checked['MODERATED']?> onclick="javascript: showEnrollmentOptionDescription('MODERATED');" /> <span class="moderatedEnrollment">MODERATED</span>&nbsp; 
		<input type="radio" name="enrollment" id="enrollment_closed" value="CLOSED" <?=$checked['CLOSED']?> onclick="javascript: showEnrollmentOptionDescription('CLOSED');" /> <span class="closedEnrollment">CLOSED</span>&nbsp;
		<br />
		<div id="enrollment_option_desc"></div>
		
		<script language="JavaScript">
			showEnrollmentOptionDescription('<?=$default_enrollment?>');
		</script>

		
<?php	else : ?>
		
		<input type="radio" name="enrollment" id="enrollment" value="OPEN" <?=$checked['OPEN']?> /> <span class="openEnrollment">OPEN</span>&nbsp; 
		<input type="radio" name="enrollment" id="enrollment" value="MODERATED" <?=$checked['MODERATED']?> /> <span class="moderatedEnrollment">MODERATED</span>&nbsp; 
		<input type="radio" name="enrollment" id="enrollment" value="CLOSED" <?=$checked['CLOSED']?> /> <span class="closedEnrollment">CLOSED</span>&nbsp;

<?php	endif;

	}
	
	
	/**
	 * @return void
	 * @param string $next_cmd The next command to execute
	 * @param array $course_instances (optional) Array of courseInstance objects to show for proxy/instructor select; ignored for staff
	 * @param string $msg (optional) Text to display above the class select
	 * @param array $hidden_fields (optional) Array of info to pass on as hidden fields
	 * @desc Displays class selector -- ajax for staff, list of classes for proxy/instructor
	 */
	public function displaySelectClass($next_cmd, $course_instances=null, $msg=null, $hidden_fields=null) {
		global $u, $g_permission;
		
		if(!empty($msg)) {
			echo '<span class="helperText">'.$msg.'</span><p />';
		}				
		
		if($u->getRole() >= $g_permission['staff']) {	//staff - use ajax class lookup
			//display selectClass
			$mgr = new ajaxManager('lookupClass', $next_cmd, 'manageClasses', 'Continue', $hidden_fields);
			$mgr->display();
		}
		else {	//all others class select
			//begin display
?>
		<form action="index.php" method="post" name="select_class" id="select_class">
			<input type="hidden" id="cmd" name="cmd" value="<?=$next_cmd?>" />		
			<?php self::displayHiddenFields($hidden_fields); ?>
			
		<table width="100%" border="0" cellspacing="0" cellpadding="0" align="center">
			<tr>
				<td class="headingCell1" width="25%" align="center">SELECT CLASS</td>
				<td width="75%" align="center">&nbsp;</td>
			</tr>
			<tr>
		    	<td colspan="2">
			    	<table width="100%" border="0" cellspacing="0" cellpadding="5" class="displayList">
			    		<tr class="headingCell1" style="text-align:left;">
			    			<td width="5%" style="text-align:center;">Select</td>
			    			<td width="15%">Course Number</td>
							<td>Course Name</td>
							<td width="10%">Term</td>
							<td width="20%">Instructors</td>
<?php		if($u->getRole() >= $g_permission['instructor']):	//show preview link ?>
							<td width="10%" style="text-align:center;">Reserve List</td>
<?php		endif; ?>							
			    		</tr>
			
<?php	
			$rowClass = 'evenRow';
			//loop through the courses
			foreach($course_instances as $ci):
				$ci->getCourseForUser();	//fetch the course object
				$ci->getInstructors();	//get a list of instructors
				$rowClass = ($rowClass=='evenRow') ? 'oddRow' : 'evenRow';
?>
						<tr class="<?=$rowClass?>">
							<td style="text-align:center;"><input type="radio" id="ci" name="ci" value="<?=$ci->getCourseInstanceID()?>" onClick="this.form.submit.disabled=false;" /></td>
			    			<td><?=$ci->course->displayCourseNo()?></td>
							<td><?=$ci->course->getName()?></td>
							<td><?=$ci->displayTerm()?></td>
							<td><?=$ci->displayInstructors()?></td>
<?php		if($u->getRole() >= $g_permission['instructor']):	//show preview link ?>
							<td style="text-align:center;"><a href="javascript:openWindow('no_control=1&cmd=previewReservesList&ci=<?=$ci->getCourseInstanceID()?>','width=800,height=600');">preview</a></td>
<?php		endif; ?>
						</tr>   

<?php		endforeach;	?>
					</table>
				</td>
			</tr>
		</table>
		<p />		
		<input type="submit" name="submit" value="Continue" disabled="disabled">
		
		</form>
<?php
		}
	}
}