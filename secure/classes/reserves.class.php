<?
/*******************************************************************************
reserve.class.php
Reserve Primitive Object

Created by Jason White (jbwhite@emory.edu)

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
require_once("secure/classes/reserveItem.class.php");
require_once('secure/classes/notes.class.php');

class reserve extends Notes {
	
	//Attributes
	public $reserveID;
	public $courseInstanceID;
	public $itemID;
	public $parentID;
	public $item;
	public $activationDate;
	public $expirationDate;
	public $sortOrder;
	public $status;
	public $creationDate;
	public $lastModDate;
	public $hidden = false;
	public $requested_loan_period;
	
	/**
	* @return reserve
	* @param int $reserveID
	* @desc initalize the reserve object
	*/
	function reserve($reserveID = NULL)
	{
		if (!is_null($reserveID)){
			$this->getReserveByID($reserveID);
		}
	}

	/**
	* @return int reserveID
	* @desc create new reserve in database
	*/
	function createNewReserve($courseInstanceID, $itemID)
	{
		global $g_dbConn;
		switch ($g_dbConn->phptype)
		{
			default: //'mysql'
				$sql = "INSERT INTO reserves (course_instance_id, item_id, date_created, last_modified) VALUES (!, !, ?, ?)";
				$sql2 = "SELECT LAST_INSERT_ID() FROM reserves";

				$d = date("Y-m-d"); //get current date
		}


		$rs = $g_dbConn->query($sql, array($courseInstanceID, $itemID, $d, $d));
		if (DB::isError($rs))
		{

			if ($rs->getMessage() == 'DB Error: already exists')
			{
				return false;
			}
			else
				trigger_error($rs->getMessage(), E_USER_ERROR);
		}

		$rs = $g_dbConn->query($sql2);
		if (DB::isError($rs)) { trigger_error($rs->getMessage(), E_USER_ERROR); }

		$row = $rs->fetchRow();
		if (DB::isError($rs)) { trigger_error($rs->getMessage(), E_USER_ERROR); }

		$this->reserveID = $row[0];
		$this->creationDate = $d;
		$this->lastModDate = $d;

		$this->getReserveByID($this->reserveID);
		return true;
	}

	/**
	* @return void
	* @param int $reserveID
	* @desc get reserve info from the database
	*/
	function getReserveByID($reserveID)
	{
		global $g_dbConn, $g_notetype;

		switch ($g_dbConn->phptype)
		{
			default: //'mysql'
				$sql = "SELECT reserve_id, course_instance_id, item_id, activation_date, expiration, status, sort_order, date_created, last_modified, requested_loan_period, parent_id "
					.  "FROM reserves "
					.  "WHERE reserve_id = ! "
					;
		}

		$rs = $g_dbConn->getRow($sql, array($reserveID));
		if (DB::isError($rs)) { trigger_error($rs->getMessage(), E_USER_ERROR); }
		
		//if no row, return
		if(count($rs) == 0) {
			return;
		}

		list($this->reserveID, $this->courseInstanceID, $this->itemID, $this->activationDate, $this->expirationDate, $this->status, $this->sortOrder, $this->creationDate, $this->lastModDate, $this->requested_loan_period, $this->parentID) = $rs;

		//get the notes
		$this->setupNotes('reserves', $this->reserveID, $g_notetype['instructor']);
		$this->fetchNotesByType();
	}

	/**
	* @return itemID on success or null on failure
	* @param int $course_instance_id, int item_id
	* @desc get reserve info from the database by ci and item
	*/
	function getReserveByCI_Item($course_instance_id, $item_id) {
		global $g_dbConn;
		
		switch($g_dbConn->phptype) {
			default:	//mysql
				$sql = "SELECT reserve_id FROM reserves WHERE course_instance_id = ".$course_instance_id." AND item_id = ".$item_id;
		}
		
		$r_id = $g_dbConn->getOne($sql);
		if (DB::isError($r_id)) { trigger_error($r_id->getMessage(), E_USER_ERROR); }
		
		if(empty($r_id)) {
			return false;
		}
		else {
			return $this->getReserveByID($r_id);
		}
	}

	/**
	* @return void
	* @desc destroy the database entry
	*/
	function destroy()
	{
		global $g_dbConn;

		switch ($g_dbConn->phptype)
		{
			default: //'mysql'
				$sql = "DELETE "
					.  "FROM reserves "
					.  "WHERE reserve_id = ! "
					.  "LIMIT 1"
					;
		}

		if (!is_null($this->reserveID))
		{
			$rs = $g_dbConn->query($sql, $this->reserveID);
			if (DB::isError($rs)) { trigger_error($rs->getMessage(), E_USER_ERROR); }
			
			//delete the notes too
			$this->destroyNotes();
		}
	}

	
	/**
	 * @return int new reserve ID
	 * @desc Duplicates the item AND the reserve for the same CI and returns new reserveID
	 */
	function duplicateReserve() {
		global $g_dbConn;
		
		//vars
		$new_item_id = null;
		$new_reserve_id = null;
		$new_pc_id = null;
		
		//SQL
		switch ($g_dbConn->phptype) {
			default:	//'mysql'
				//insert item data
				$sql_item = "INSERT INTO items (title, author, source, volume_title, volume_edition, pages_times, performer, local_control_key, creation_date, last_modified, url, mimetype, home_library, private_user_id, item_group, item_type, item_icon) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?, ?, ?, ?, ?, ?, ?)";
			
				//insert reserve data
				$sql_reserve = "INSERT INTO reserves (course_instance_id, item_id, activation_date, expiration, status, sort_order, date_created, last_modified, requested_loan_period, parent_id) VALUES (!, !, ?, ?, 'INACTIVE', ?, NOW(), NOW(), ?, ?)";
				
				//insert physical copy data
				$sql_pc = "INSERT INTO physical_copies (reserve_id, item_id, status, call_number, barcode, owning_library, item_type, owner_user_id) VALUES (!, !, ?, ?, ?, ?, ?, ?)";
				
				//get the id of last insert				
				$sql_last_insert_id = "SELECT LAST_INSERT_ID() FROM reserves"; 		
		}
		
		
		//create new item
		
		$this->getItem();	//fetch data
		//build array of data to insert
		$data = array(
					$this->item->title.' (Duplicate)',
					$this->item->author,
					$this->item->source,
					$this->item->volumeTitle,
					$this->item->volumeEdition,
					$this->item->pagesTimes,
					$this->item->performer,
					$this->item->localControlKey,
					$this->item->URL,
					$this->item->mimeTypeID,
					$this->item->homeLibraryID,
					$this->item->privateUserID,
					$this->item->itemGroup,
					$this->item->itemType,	
					$this->item->itemIcon
				);
			
		//query
		$rs = $g_dbConn->query($sql_item, $data);
		if (DB::isError($rs)) { trigger_error($rs->getMessage(), E_USER_ERROR); }
		
		//get last insert id
		$rs = $g_dbConn->getOne($sql_last_insert_id);
		if (DB::isError($rs)) { trigger_error($rs->getMessage(), E_USER_ERROR); }
		//assign it
		$new_item_id = $rs;
		
		//create new reserve
		
		//build array of data
		$data = array(
					$this->courseInstanceID,
					$new_item_id,
					$this->activationDate,
					$this->expirationDate,
					$this->sortOrder,
					$this->requested_loan_period,
					$this->parentID
				);
		
		//query
		$rs = $g_dbConn->query($sql_reserve, $data);
		if (DB::isError($rs)) { trigger_error($rs->getMessage(), E_USER_ERROR); }
		
		//get last insert id
		$rs = $g_dbConn->getOne($sql_last_insert_id);
		if (DB::isError($rs)) { trigger_error($rs->getMessage(), E_USER_ERROR); }
		//assign it
		$new_reserve_id = $rs;
		
		//create a new physical item (if one exists)
		
		if($this->item->getPhysicalCopy()) {	//if physical copy exists
			$data = array(
						$new_reserve_id,
						$new_item_id,
						$this->item->physicalCopy->status,
						$this->item->physicalCopy->callNumber,
						$this->item->physicalCopy->barcode,
						$this->item->physicalCopy->owningLibrary,
						$this->item->physicalCopy->itemType,
						$this->item->physicalCopy->ownerUserID
					);
			
			//query
			$rs = $g_dbConn->query($sql_pc, $data);
			if (DB::isError($rs)) { trigger_error($rs->getMessage(), E_USER_ERROR); }
			
			//get last insert id
			$rs = $g_dbConn->getOne($sql_last_insert_id);
			if (DB::isError($rs)) { trigger_error($rs->getMessage(), E_USER_ERROR); }
			//assign it
			$new_pc_id = $rs;						
		}
		
		//duplicate notes		
		$this->fetchNotesByType();	//re-fetch notes for the reserve
		$this->duplicateNotes($new_reserve_id);	//duplicate notes for the reserve
		$this->item->fetchNotes();	//re-fetch notes for the item
		$this->item->duplicateNotes($this_item_id);	//duplicate notes for the item
		
		//return the new reserve's ID
		return $new_reserve_id;
	}
	

	/**
	 * @return void
	 * @param int $parent_id New parent reserve's ID
	 * @desc sets $parent_id as this reserve's parent
	 */
	function setParent($parent_id) {
		global $g_dbConn;

		switch ($g_dbConn->phptype) {
			default:	//mysql
				$sql = "UPDATE reserves	SET parent_id = !, last_modified = ? WHERE reserve_id = !";
				$d = date("Y-m-d"); //get current date
		}
		//PEAR DB chokes on null values, so change it manually
		$parent_id = empty($parent_id) ? 'NULL' : intval($parent_id);
		
		$rs = $g_dbConn->query($sql, array($parent_id, $d, $this->reserveID));
		if (DB::isError($rs)) { trigger_error($rs->getMessage(), E_USER_ERROR); }

		$this->parentID = $parent_id;
		$this->lastModDate = $d;		
	}
	
	
	/**
	* @return void
	* @param date $activationDate
	* @desc set new activationDate in database
	*/
	function setActivationDate($date)
	{
		global $g_dbConn;

		switch ($g_dbConn->phptype)
		{
			default: //'mysql'
				$sql = "UPDATE reserves SET activation_date = ?, last_modified = ? WHERE reserve_id = !";
				$d = date("Y-m-d"); //get current date
		}
		$rs = $g_dbConn->query($sql, array($date, $d, $this->reserveID));
		if (DB::isError($rs)) { trigger_error($rs->getMessage(), E_USER_ERROR); }

		$this->activationDate = $date;
		$this->lastModDate = $d;
	}

	/**
	* @return void
	* @param date $expirationDate
	* @desc set new expirationDate in database
	*/
	function setExpirationDate($date)
	{
		global $g_dbConn;

		switch ($g_dbConn->phptype)
		{
			default: //'mysql'
				$sql = "UPDATE reserves SET expiration = ?, last_modified = ? WHERE reserve_id = !";
				$d = date("Y-m-d"); //get current date
		}
		$rs = $g_dbConn->query($sql, array($date, $d, $this->reserveID));
		if (DB::isError($rs)) { trigger_error($rs->getMessage(), E_USER_ERROR); }

		$this->expirationDate = $date;
		$this->lastModDate = $d;
	}

		/**
	* @return void
	* @param string $status
	* @desc set new status in database
	*/
	function setStatus($status)
	{
		global $g_dbConn;

		switch ($g_dbConn->phptype)
		{
			default: //'mysql'
				$sql = "UPDATE reserves SET status = ?, last_modified = ? WHERE reserve_id = !";
				$d = date("Y-m-d"); //get current date
		}
		$rs = $g_dbConn->query($sql, array($status, $d, $this->reserveID));
		if (DB::isError($rs)) { trigger_error($rs->getMessage(), E_USER_ERROR); }

		$this->status = $status;
		$this->lastModDate = $d;
	}

		/**
	* @return void
	* @param int $sortOrder
	* @desc set new sortOrder in database
	*/
	function setSortOrder($sortOrder)
	{
		global $g_dbConn;

		switch ($g_dbConn->phptype)
		{
			default: //'mysql'
				$sql = "UPDATE reserves SET sort_order = !, last_modified = ? WHERE reserve_id = !";
				$d = date("Y-m-d"); //get current date
		}
		$rs = $g_dbConn->query($sql, array($sortOrder, $d, $this->reserveID));
		if (DB::isError($rs)) { trigger_error($rs->getMessage(), E_USER_ERROR); }

		$this->sortOrder = $sortOrder;
		$this->lastModDate = $d;
	}
	
	
	/**
	 * @return void
	 * @param int $ci_id CourseInstance ID
	 * @param string $r_title Title of this reserve item
	 * @param string $r_author Author of this reserve item
	 * @param int $folder_id Look at the sort order in this folder only
	 * @desc Attempt to determine the sort order for the course instance and insert this object into the sequence
	 */
	function insertIntoSortOrder($ci_id, $r_title, $r_author, $folder_id=null) {
		global $g_dbConn;
		//this reserve's ID
		$r_id = $this->getReserveID();
		
		switch($g_dbConn->phptype) {
			//this reserve has already been inserted into DB, but w/ a random sort order, so it must be ignored in all queries		
			default:	//mysql
				//format folder query substring
				$and_parent = empty($folder_id) ? " AND (parent_id IS NULL OR parent_id = '')" : " AND parent_id = ".intval($folder_id);
				
				//1. count the number or distinct course order values for all the reserves for this CI (if default/unsorted, count will = 1)
				$sql = "SELECT COUNT(DISTINCT sort_order)
						FROM reserves
						WHERE course_instance_id = ! AND reserve_id <> !".$and_parent;
				
				//2. get the list of reserve IDs and associated order #s, sorted in different ways
				$select_title_author = "SELECT i.title, i.author";
				$select_title = "SELECT i.title";
				$select_author = "SELECT i.author";
				$sql2 = " FROM items AS i
							JOIN reserves AS r ON r.item_id = i.item_id
							WHERE r.course_instance_id = ! AND r.reserve_id <> !".$and_parent;
				$order_current = " ORDER BY r.sort_order, i.title"; 
				$order_author = " ORDER BY i.author, i.title";
				$order_title = " ORDER BY i.title, i.author";
				
				//3. after this reserve position is set, shift everything following it down
				$sql_shift = "UPDATE reserves
								SET sort_order = (sort_order+1)
								WHERE course_instance_id = !
								AND sort_order >= !".$and_parent;
				
				//4. get the last sort order currently in the list
				$sql_max = "SELECT MAX(sort_order)
							FROM reserves
							WHERE course_instance_id = ! AND reserve_id <> !".$and_parent;
		}
		
		//is the list custom-sorted?		
		$rs = $g_dbConn->getOne($sql, array($ci_id, $r_id));
		if (DB::isError($rs)) { trigger_error($rs->getMessage(), E_USER_ERROR); }
		
		if($rs > 1) {	//using custom sort
			//atempt to figure out if items are sorted by title or author
			//basically, get list sorted by each and compare to list sorted by custom sort
			
			$current_order = array();
			$test_array = array();
			$new_reserve_position = 0;	//the position of this new reserve in the sorted order; default to top of the list
			
			//get the current (custom_sorted) array			
			$rs = $g_dbConn->query($select_title_author.$sql2.$order_current, array($ci_id, $r_id));
			if (DB::isError($rs)) { trigger_error($rs->getMessage(), E_USER_ERROR); }
			//fetch data into an array
			unset($row);
			while($row = $rs->fetchRow()) {
				$current_order[] = array($row[0], $row[1]);	//theoretically, this should be equivalent to Array[sort_order] = info
			}
			//get the size of the array
			$curr_size = count($current_order);
			
			//fetch title test array			
			$rs = $g_dbConn->query($select_title.$sql2.$order_title, array($ci_id, $r_id));
			if (DB::isError($rs)) { trigger_error($rs->getMessage(), E_USER_ERROR); }
			//fetch data into array
			unset($row);
			while($row = $rs->fetchRow()) {
				$test_array[] = $row[0];
			}

			//now compare the test TITLE array against the current order
			//also keep track of where the new reserve would fit into this array
			$test_passed = true;		
			for($x=0; $x<$curr_size; $x++) {
				if(strcmp($current_order[$x][0], $test_array[$x]) != 0) {	//current order is NOT by title
					$test_passed = false;	//fail the test
					$new_reserve_position = 0;	//reset new reserve's position
					break;	//break from the loop	
				}
				else {	//title order still matches current order
					if(strnatcasecmp($r_title, $current_order[$x][0]) >= 0) {	//if the new reserve title is >= than current entry, the new reserve should follow the current one in the list
						$new_reserve_position = $x+1;
					}
				}
			}
			
			if(!$test_passed) {	//current order is NOT by title, try by author
				$test_passed = true;	//reset test var
				
				//fetch asuthor test array			
				$rs = $g_dbConn->query($select_author.$sql2.$order_author, array($ci_id, $r_id));
				if (DB::isError($rs)) { trigger_error($rs->getMessage(), E_USER_ERROR); }
				//fetch data into array
				unset($row);
				$test_array = array();
				while($row = $rs->fetchRow()) {
					$test_array[] = $row[0];
				}

				//now compare the test AUTHOR array against the current order
				//also keep track of where the new reserve would fit into this array
				for($x=0; $x<$curr_size; $x++) {
					if(strcmp($current_order[$x][1], $test_array[$x]) != 0) {	//current order is NOT by author
						$test_passed = false;	//fail the test
						$new_reserve_position = 0;	//reset new reserve's position
						break;	//break from the loop	
					}
					else {	//author order still matches current order
						if(strnatcasecmp($r_author, $current_order[$x][1]) >= 0) {	//if the new reserve author is >= than current entry, the new reserve should follow the current one in the list
							$new_reserve_position = $x+1;
						}
					}
				}
			}
			
			//set the new orders
			
			if($test_passed) {	//if we passed the test, then new_res_pos is correct... use it		
				//in the DB order starts at 1, not 0, so add one to the position var
				$new_reserve_position++;
				//shift elements that fall behind the new reserve down to make room			
				$rs = $g_dbConn->query($sql_shift, array($ci_id, $new_reserve_position));
				if (DB::isError($rs)) { trigger_error($rs->getMessage(), E_USER_ERROR); }
				//insert the new reserve at its proper position
				$this->setSortOrder($new_reserve_position);
			}
			else {	//failed test, add this reserve to the end of the list
				//get the max sort_order
				$rs = $g_dbConn->getOne($sql_max, array($ci_id, $r_id));
				if (DB::isError($rs)) { trigger_error($rs->getMessage(), E_USER_ERROR); }
				//set this one to max+1
				$this->setSortOrder($rs+1);
			}				
		}
		else {	//no sort order set; add this reserve to the end of the list
			//get the max sort_order
			$rs = $g_dbConn->getOne($sql_max, array($ci_id, $r_id));
			if (DB::isError($rs)) { trigger_error($rs->getMessage(), E_USER_ERROR); }
			//set this one to max+1
			$this->setSortOrder($rs+1);
		}
	} //insertIntoSortOrder()
	

	function setRequestedLoanPeriod($lp)
	{
		global $g_dbConn;

		switch ($g_dbConn->phptype)
		{
			default: //'mysql'
				$sql = "UPDATE reserves SET requested_loan_period = ? WHERE reserve_id = !";
		}

		$rs = $g_dbConn->query($sql, array($lp, $this->reserveID));
		if (DB::isError($rs)) { trigger_error($rs->getMessage(), E_USER_ERROR); }
		
		$this->requested_loan_period = $lp;
	}	
	
	/**
	* @return void
	* @param int $userID
	* @desc log the users reserve view
	*/
	function addUserView($userID)
	{
		global $g_dbConn;

		switch ($g_dbConn->phptype)
		{
			default: //'mysql'
				$sql = "INSERT INTO user_view_log (user_id, reserve_id, timestamp_viewed) VALUES (!, !, CURRENT_TIMESTAMP)";
		}
		$rs = $g_dbConn->query($sql, array($userID, $this->reserveID));
		if (DB::isError($rs)) { trigger_error($rs->getMessage(), E_USER_ERROR); }

	}

	/**
	* @return void
	* @desc Retrieve the item object associated with this reserve
	*/
	function getItem()
	{
		$this->item = new reserveItem($this->itemID);
	}
	
	/**
	 * @return boolean true on access
	 * @desc Retrieve item object associated with this reserve if user has access
	 *
	 * @param int $userID
	 */
	function getItemForUser($user)
	{
		global $g_dbConn, $g_permission;

		if ($user->getRole() >= $g_permission['staff'])
		{
			$this->getItem();
			return true;
		}
		
		switch ($g_dbConn->phptype)
		{
			default: //'mysql'
				$sql = "SELECT DISTINCT a.permission_level, ci.activation_date, ci.expiration_date, ci.status
						FROM reserves as r
							JOIN course_aliases as ca ON r.course_instance_id = ca.course_instance_id
							JOIN course_instances as ci ON r.course_instance_id = ci.course_instance_id
							JOIN access as a ON ca.course_alias_id = a.alias_id
						WHERE a.user_id = ! 
							AND r.reserve_id = !
					   ";
				$d = date("Y-m-d"); //get current date
		}
		
		$rs = $g_dbConn->query($sql, array($user->getUserID(), $this->reserveID));
		if (DB::isError($rs)) { return false; }

		if ($row = $rs->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$active = (($row['activation_date'] <= $d) && ($d <= $row['expiration_date']) && ($row['status'] == 'ACTIVE')) ? "TRUE" : "FALSE";
			if ($row['permission_level'] < $g_permission['proxy'] && $active != "TRUE")
				return false;
			else 
				$this->getItem();
				return true;
		} else 
			return false;			
	}

	function getReserveID(){ return $this->reserveID; }
	function getCourseInstanceID() { return $this->courseInstanceID; }
	function getItemID() { return $this->itemID; }
	function getActivationDate() { return $this->activationDate; }
	function getExpirationDate() { return $this->expirationDate; }
	function getStatus() { return $this->status;}
	function getSortOrder() { return $this->sortOrder; }
	function getCreationDate() { return $this->creationDate; }
	function getModificationDate() { return $this->lastModDate; }
	function getParent() { return $this->parentID; }
	
	function getRequestedLoanPeriod() 
	{
		if (!is_null($this->requested_loan_period))
			return $this->requested_loan_period;
		else
			return "";
	}
	

	/**
	* @return boolean
	* @desc tests associated item if item is a heading returns true false otherwise
	*/
	function isHeading()
	{
		/*
		if (is_a($this->item, "reserveItem")) return false;  //reserveItems are not headings
		else return true;
		*/
		
		if (!is_a($this->item, "reserveItem"))
			$this->getItem();
		if ($this->item->itemType == 'HEADING')
			return true;
		else
			return false;
		
	}
	
	function hideReserve($userID)
	{
		global $g_dbConn;
		switch ($g_dbConn->phptype)
		{
			default: //'mysql'
				$sql = "INSERT INTO hidden_readings (user_id, reserve_id) VALUES (!, !)";
		}


		$rs = $g_dbConn->query($sql, array($userID, $this->reserveID));
		if (DB::isError($rs))
		{

			if ($rs->getMessage() == 'DB Error: already exists')
			{
				return false;
			}
			else
				trigger_error($rs->getMessage(), E_USER_ERROR);
		}

		$this->hidden=true;
		return true;
	}
	
	function unhideReserve($userID)
	{
		global $g_dbConn;
		switch ($g_dbConn->phptype)
		{
			default: //'mysql'
				$sql = "DELETE FROM hidden_readings WHERE user_id = ! AND reserve_id = !";
		}

		$rs = $g_dbConn->query($sql, array($userID, $this->reserveID));
		if (DB::isError($rs)) { trigger_error($rs->getMessage(), E_USER_ERROR); }

	}

}
?>
