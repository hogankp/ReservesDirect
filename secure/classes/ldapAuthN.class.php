<?php
/*******************************************************************************
ldapAuthN.class.php
Methods to allow ldap authentication

Created by Kyle Fenton (kyle.fenton@emory.edu)

This file is part of ReservesDirect

Copyright (c) 2004-2006 Emory University, Atlanta, Georgia.

Licensed under the ReservesDirect License, Version 1.0 (the "License");      
you may not use this file except in compliance with the License.     
You may obtain a copy of the full License at                              
http://www.reservesdirect.org/licenses/LICENSE-1.0

ReservesDirect is distributed in the hope that it will be useful,
but is distributed "AS IS" and WITHOUT ANY WARRANTY, without even the
implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE, and without any warranty as to non-infringement of any third
party's rights.  See the License for the specific language governing         
permissions and limitations under the License.

ReservesDirect is located at:
http://www.reservesdirect.org/

*******************************************************************************/

class ldapAuthN	{
	/**
	 * Declaration
	 */
	private $conn;	//LDAP resource link identifier
	private $username;	//username
	private $password;	//password
	private $user_found;	//boolean stores search result
	private $user_authed;	//boolean stores the authentication result
	private $user_info;	//a subset of the user's LDAP record
	
	
	/**
	 * @return void
	 * @desc constructor
	 */
	public function ldapAuthN() {
		$this->conn = $this->user_info = $this->username = $this->password = null;
		$this->user_authed = $this->user_found = false;
	}

	
	/**
	 * @return boolean
	 * @param string $user Username
	 * @param string $pass Password
	 * @desc Attempts to authenticate the user against LDAP. Returns true/false
	 */
	public function auth($user, $pass) {
		//check if authentication has already been run
		if(($user==$this->username) && ($pass==$this->password)) {	//username and password match
			return $this->user_authed;	//return previous result
		}
		
		//requires username and password
		if(empty($user) || empty($pass)) {
			return false;	//return false if it is
		}
		else {	//else store the user and pass
			$this->username = $user;
			$this->password = $pass;
		}
		
		//establish a connection
		if(!$this->connect()) {
//
//not sure if should trigger error
//or try another auth method
//
return false;
//			trigger_error('LDAP: connection failed.', E_USER_ERROR);
		}
		
		//search for the user in the directory
		if(!$this->search()) {	//user not found in directory
			return false;
		}
		
		//if user is found in dir, attempt to authenticate w/ provided password
		$this->user_authed = ldap_bind($this->conn, $this->user_info['dn'], $this->password);
		
		//close connection and clean up
		$this->disconnect();
		
		return $this->user_authed;
	}
	
	
	/**
	 * @return boolean
	 * @desc returns true if user was found with LDAP (only true if LDAP returned exactly 1 entry)
	 */
	public function userExists() {
		return $this->user_found;
	}
	
	
	/**
	 * @return array
	 * @desc returns the array of user info gathered from the directory
	 */
	public function getUserInfo() {
		return $this->user_info;
	}	
	
	
	/**
	 * @return boolean
	 * @desc Attempt to connect to LDAP server
	 */
	protected function connect() {
		global $g_ldap;
		
		//determine if trying to go through SSL
		//this is a bit of a hack, b/c we're just checking the port #
		//	if the port matches "secure ldap" port, prepend "ldaps://" to hostname
		//also make sure the host does not already include the prefix
		$host = $g_ldap['host'];	//default to plain host		
		if(($g_ldap['port'] == '636') && (stripos($g_ldap['host'], 'ldaps://') === false)) {
			$host = 'ldaps://'.$g_ldap['host'];
		}

		//connect	
		$conn = ldap_connect($host, $g_ldap['port']);		
		if($conn !== false) {
			ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, $g_ldap['version']);	//set version
			$this->conn = $conn;	//save resource link
			return true;
		}
		//else
		return false;
	}
	
	
	/**
	 * @return void
	 * @desc disconnect from server and do a little cleanup
	 */
	protected function disconnect() {
		ldap_close($this->conn);
		unset($this->conn);
	}
	
	
	/**
	 * @return boolean
	 * @desc Searches the user in the directory. Set the info, if user is found
	 */
	protected function search() {
		global $g_ldap;
		
		$this->user_info = null;	//clean out any previous info
		$this->user_found = false;	//set this user to "not found" by default
		
		//bind to the LDAP w/ search credentials
		if(ldap_bind($this->conn, $g_ldap['searchdn'], $g_ldap['searchpw'])) {	//if bound successfully, search for the user			
			//set up some search criteria
			$filter = $g_ldap['canonicalName'].'='.$this->username;	//search only for a person with the given username
			//ask for the dn, uid, first & last name, and email (dn is fetched automatically, but might as well be complete)
			$fetch_attribs = array('dn', $g_ldap['canonicalName'], $g_ldap['firstname'], $g_ldap['lastname'], $g_ldap['email']);
						
			//search
			$result = ldap_search($this->conn, $g_ldap['basedn'], $filter, $fetch_attribs);			
			if($result !== false) {
				$info = ldap_get_entries($this->conn, $result);	//get the info
				ldap_free_result($result);	//free memory
				
				if($info['count'] == 1) {	//if only one record returned, then successfully found the user
					$this->user_info = $info[0];	//grab the first record
					$this->user_found = true;
					return true;
				}
			}
		}
		
		return false;
	}
}
?>
