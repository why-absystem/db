<?php
	/** @noinspection PhpInconsistentReturnPointsInspection */
	/** @noinspection SqlResolve */
	/** @noinspection SqlNoDataSourceInspection */
	
	/**
	 * Programmer: WHY
	 * Date: 10/11/20
	 * Time: 19.15
	 */
	
	namespace W;
	
	use Dotenv\Dotenv;
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
		
		/**
		 * DB constructor.
		 * @param string $path_env
		 */
		public function __construct ($path_env = __DIR__) {
			$dotenv = Dotenv::createImmutable($path_env);
			$dotenv->load();
			$this->db_host = getenv('DB_HOST');
			$this->db_user = getenv('DB_USER');
			$this->db_pass = getenv('DB_PASS');
			$this->db_name = getenv('DB_NAME');
			$this->db_port = getenv('DB_PORT');
		}
		
		
		/**
		 * @return bool
		 * Function to make connection to database
		 */
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
		
		
		/**
		 * @return bool
		 * Function to disconnect from the database
		 */
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
		
		/**
		 * @param $sql
		 * @return bool
		 */
		public function sql ($sql) {
			$this->connect();
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
		
		
		/**
		 * @param $table
		 * @param string $rows
		 * @param null $join
		 * @param null $where
		 * @param null $order
		 * @param null $limit
		 * @return bool
		 * Function to SELECT from the database
		 */
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
			if ($this->table_exists($table)) {
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
		
		
		/**
		 * @param $table
		 * @param array $params
		 * @return bool
		 * Function to insert into the database
		 */
		public function insert ($table, $params = array()) {
			$this->connect();
			// Check to see if the table exists
			if ($this->table_exists($table)) {
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
		
		/**
		 * @param $table
		 * @param null $where
		 * @return bool
		 * Function to delete table or row(s) from database
		 */
		public function delete ($table, $where = NULL) {
			$this->connect();
			// Check to see if table exists
			if ($this->table_exists($table)) {
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
		
		
		/**
		 * @param $table
		 * @param array $params
		 * @param $where
		 * @return bool
		 * Function to update row in database
		 */
		public function update ($table, $params = array(), $where) {
			$this->connect();
			// Check to see if table exists
			if ($this->table_exists($table)) {
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
		
		
		/**
		 * @param $table
		 * @return bool
		 * Private function to check if table exists for use with queries
		 */
		private function table_exists ($table) {
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
		
		
		/**
		 * @param bool $show
		 * @return array
		 */
		public function list_data ($show = FALSE) {
			$val          = $this->result;
			$this->result = array();
			if ($show)
				$this->show($val);
			return $val;
		}
		
		
		/**
		 * @param bool $show
		 * @return array|mixed
		 */
		public function get_data ($show = FALSE) {
			$val          = $this->result[0] ? $this->result[0] : [];
			$this->result = array();
			if ($show)
				$this->show($val);
			return $val;
		}
		
		
		/**
		 * @param bool $format
		 * @return string|void
		 */
		public function get_sql ($format = TRUE) {
			$val           = $this->myQuery;
			$this->myQuery = array();
			if ($format)
				return $this->show(SqlFormatter::format($val));
			else
				return $val;
		}
		
		
		/**
		 * @param bool $show
		 * @return string
		 */
		public function num_rows ($show = FALSE) {
			$val              = $this->numResults;
			$this->numResults = array();
			if ($show)
				$this->show($this->result);
			return $val;
		}
		
		
		/**
		 * @param $data
		 * @return mixed
		 */
		public function escape_string ($data) {
			return $this->myconn->real_escape_string($data);
		}
		
		/**
		 * @param $data
		 */
		protected function show ($data) {
			echo '<pre>';
			print_r($data);
			echo '</pre>';
		}
	}