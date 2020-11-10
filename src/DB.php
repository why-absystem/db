<?php
	/**
	 * Programmer: WHY
	 * Date: 10/11/20
	 * Time: 19.15
	 */
	
	namespace W;
	
	use SqlFormatter;
	
	class DB {
		protected $db_host;
		protected $db_user;
		protected $db_pass;
		protected $db_name;
		protected $db_port;
		
		/*
		 * Extra variables that are required by other function such as boolean con variable
		 */
		private $con        = FALSE; // Check to see if the connection is active
		private $myconn     = ""; // This will be our mysqli object
		private $result     = array(); // Any results from a query will be stored here
		private $myQuery    = "";// used for debugging process with SQL return
		private $numResults = "";// used for returning the number of rows
		
		public function __construct ($db_host, $db_user, $db_pass, $db_name, $db_port = '3306') {
			$this->db_host = $db_host;
			$this->db_user = $db_user;
			$this->db_pass = $db_pass;
			$this->db_name = $db_name;
			$this->db_port = $db_port;
		}
		
		// Function to make connection to database
		public function connect () {
			if (!$this->con) {
				$this->myconn = new \MySQLi($this->db_host, $this->db_user, $this->db_pass, $this->db_name, $this->db_port);
				if ($this->myconn->connect_errno > 0) {
					array_push($this->result, $this->myconn->connect_error);
					return FALSE; // Problem selecting database return FALSE
				} else {
					$this->con = TRUE;
					return TRUE; // Connection has been made return TRUE
				}
			} else {
				return TRUE; // Connection has already been made return TRUE
			}
		}
		
		// Function to disconnect from the database
		public function disconnect () {
			// If there is a connection to the database
			if ($this->con) {
				// We have found a connection, try to close it
				if ($this->myconn->close()) {
					// We have successfully closed the connection, set the connection variable to false
					$this->con = FALSE;
					// Return true tjat we have closed the connection
					return TRUE;
				} else {
					// We could not close the connection, return false
					return FALSE;
				}
			}
		}
		
		public function sql ($sql) {
			$query         = $this->myconn->query($sql);
			$this->myQuery = $sql; // Pass back the SQL
			if ($query) {
				// If the query returns >= 1 assign the number of rows to numResults
				$this->numResults = $query->num_rows;
				// Loop through the query results by the number of rows returned
				for ($i = 0; $i < $this->numResults; $i ++) {
					$r   = $query->fetch_array();
					$key = array_keys($r);
					for ($x = 0; $x < count($key); $x ++) {
						// Sanitizes keys so only alphavalues are allowed
						if (!is_int($key[$x])) {
							if ($query->num_rows >= 1) {
								$this->result[$i][$key[$x]] = $r[$key[$x]];
							} else {
								$this->result = NULL;
							}
						}
					}
				}
				return TRUE; // Query was successful
			} else {
				array_push($this->result, $this->myconn->error);
				return FALSE; // No rows where returned
			}
		}
		
		// Function to SELECT from the database
		public function select ($table, $rows = '*', $join = NULL, $where = NULL, $order = NULL, $limit = NULL) {
			$this->connect();
			// Create query from the variables passed to the function
			$q = 'SELECT ' . $rows . ' FROM ' . $table;
			if ($join != NULL) {
				$q .= ' JOIN ' . $join;
			}
			if ($where != NULL) {
				$q .= ' WHERE ' . $where;
			}
			if ($order != NULL) {
				$q .= ' ORDER BY ' . $order;
			}
			if ($limit != NULL) {
				$q .= ' LIMIT ' . $limit;
			}
			// echo $table;
			$this->myQuery = $q; // Pass back the SQL
			// Check to see if the table exists
			if ($this->tableExists($table)) {
				// The table exists, run the query
				$query = $this->myconn->query($q);
				if ($query) {
					// If the query returns >= 1 assign the number of rows to numResults
					$this->numResults = $query->num_rows;
					// Loop through the query results by the number of rows returned
					for ($i = 0; $i < $this->numResults; $i ++) {
						$r   = $query->fetch_array();
						$key = array_keys($r);
						for ($x = 0; $x < count($key); $x ++) {
							// Sanitizes keys so only alphavalues are allowed
							if (!is_int($key[$x])) {
								if ($query->num_rows >= 1) {
									$this->result[$i][$key[$x]] = $r[$key[$x]];
								} else {
									$this->result[$i][$key[$x]] = NULL;
								}
							}
						}
					}
					return TRUE; // Query was successful
				} else {
					array_push($this->result, $this->myconn->error);
					return FALSE; // No rows where returned
				}
			} else {
				return FALSE; // Table does not exist
			}
		}
		
		// Function to insert into the database
		public function insert ($table, $params = array()) {
			$this->connect();
			// Check to see if the table exists
			if ($this->tableExists($table)) {
				$sql           = 'INSERT INTO `' . $table . '` (`' . implode('`, `', array_keys($params)) . '`) VALUES ("' . implode('", "', $params) . '")';
				$this->myQuery = $sql; // Pass back the SQL
				// Make the query to insert to the database
				if ($ins = $this->myconn->query($sql)) {
					array_push($this->result, $this->myconn->insert_id);
					return TRUE; // The data has been inserted
				} else {
					array_push($this->result, $this->myconn->error);
					return FALSE; // The data has not been inserted
				}
			} else {
				return FALSE; // Table does not exist
			}
		}
		
		//Function to delete table or row(s) from database
		public function delete ($table, $where = NULL) {
			$this->connect();
			// Check to see if table exists
			if ($this->tableExists($table)) {
				// The table exists check to see if we are deleting rows or table
				if ($where == NULL) {
					$delete = 'DROP TABLE ' . $table; // Create query to delete table
				} else {
					$delete = 'DELETE FROM ' . $table . ' WHERE ' . $where; // Create query to delete rows
				}
				// Submit query to database
				if ($del = $this->myconn->query($delete)) {
					array_push($this->result, $this->myconn->affected_rows);
					$this->myQuery = $delete; // Pass back the SQL
					return TRUE; // The query exectued correctly
				} else {
					array_push($this->result, $this->myconn->error);
					return FALSE; // The query did not execute correctly
				}
			} else {
				return FALSE; // The table does not exist
			}
		}
		
		// Function to update row in database
		public function update ($table, $params = array(), $where) {
			$this->connect();
			// Check to see if table exists
			if ($this->tableExists($table)) {
				// Create Array to hold all the columns to update
				$args = array();
				foreach ($params as $field => $value) {
					// Seperate each column out with it's corresponding value
					$args[] = $field . '="' . $value . '"';
				}
				// Create the query
				$sql = 'UPDATE ' . $table . ' SET ' . implode(',', $args) . ' WHERE ' . $where;
				// Make query to database
				$this->myQuery = $sql; // Pass back the SQL
				if ($query = $this->myconn->query($sql)) {
					array_push($this->result, $this->myconn->affected_rows);
					return TRUE; // Update has been successful
				} else {
					array_push($this->result, $this->myconn->error);
					return FALSE; // Update has not been successful
				}
			} else {
				return FALSE; // The table does not exist
			}
		}
		
		// Private function to check if table exists for use with queries
		private function tableExists ($table) {
			$tablesInDb = $this->myconn->query('SHOW TABLES FROM ' . $this->db_name . ' LIKE "' . $table . '"');
			if ($tablesInDb) {
				if ($tablesInDb->num_rows == 1) {
					return TRUE; // The table exists
				} else {
					array_push($this->result, $table . " does not exist in this database");
					return FALSE; // The table does not exist
				}
			}
		}
		
		// Public function to return the data to the user
		public function getData () {
			$val          = $this->result;
			$this->result = array();
			return $val;
		}
		
		//Pass the SQL back for debugging
		public function getSql () {
			$val           = $this->myQuery;
			$this->myQuery = array();
			return SqlFormatter::format($val);
		}
		
		//Pass the number of rows back
		public function numRows () {
			$val              = $this->numResults;
			$this->numResults = array();
			return $val;
		}
		
		// Escape your string
		public function escapeString ($data) {
			return $this->myconn->real_escape_string($data);
		}
	}