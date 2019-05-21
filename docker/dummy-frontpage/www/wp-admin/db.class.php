<?php	
	class DB {
		private $link;
		public $errors;
		public $debug;
	
		public function __construct($host, $user, $password, $database, $debug = false) {
			$this->link = new mysqli($host, $user, $password, $database);
			$this->errors = '';
			$this->debug = $debug;
			
			if(mysqli_connect_errno())
			{
				//$this->log_db_errors( "Connect failed: %s\n", mysqli_connect_error(), 'Fatal' );
				exit();
			}
		}
		
		public function errorLog($query, $error) {
			$this->errors .= "Query: ".$query."<br>\n";
			$this->errors .= "Error: ".$error."<br>\n";
			writeErrorLog($this->errors);
		}
		
		public function getErrors() {
			echo "<b>ERRORS:</b><br>\n".$this->errors;
		}
		
		public function filter($data) {	//TODO: stimmt so?
			if (is_array($data)) {
				foreach($data as $key => $value) {
					$data[$key] = self::filter($value);
				}
			} else {
				$data = trim(htmlentities($data));
				$data = mysqli_real_escape_string($this->link, $data);
			}
			return $data;
		}
		
		public function query($query) {
			if ($this->debug) {
				echo "<pre>";
				echo trim(preg_replace('/\s\s+/', ' ', $query))."<br>";
				echo "</pre>";
			}
			$query = $this->link->query($query);
			if(mysqli_error($this->link)) {
				$this->errorLog(mysqli_error($this->link), $query);
				return false;
			} else {
				return true;
			}
			mysqli_free_result($query);
		}
		
		public function getRow($query) {
			$query = $this->link->query($query);
			if(mysqli_error($this->link)) {
				$this->errorLog(mysqli_error($this->link), $query);
				return false;
			} else {
				$r = mysqli_fetch_row($query);
				mysqli_free_result($query);
				return $r;
			}
		}
		
		public function getResults($query) {
			$row = array();
			$query = $this->link->query($query);
			if(mysqli_error($this->link)) {
				$this->errorLog(mysqli_error($this->link), $query);
				return false;
			} else {
				while($r = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
					$row[] = $r;
				}
				mysqli_free_result($query);
				return $row;
			}
		}
		
		public function getResult($query) {
			$row = array();
			$query = $this->link->query($query);
			if(mysqli_error($this->link)) {
				$this->errorLog(mysqli_error($this->link), $query);
				return false;
			} else {
				$row = mysqli_fetch_array($query, MYSQLI_ASSOC);
				mysqli_free_result($query);
				return $row;
			}
		}
		
		public function getValue($query) {
			$result = self::getResult($query);
			if (is_array($result) && count($result) == 1) {
				return reset($result);
			} else {
				return $result;
			}
		}
		
	    public function numRows($query) {
	        $query = $this->link->query($query);
	        if(mysqli_error($this->link)) {
	            $this->errorLog($query, mysqli_error($this->link));
	            return mysqli_error($this->link);
	        } else {
	            return mysqli_num_rows($query);
	        }
	        mysqli_free_result($query);
	    }
	    
	    public function getLastId() {
	    	return mysqli_insert_id($this->link);
	    }
	    
	    public function escape($text) {
	    	return mysqli_real_escape_string($this->link, $text);
	    }
	    
	    public function sanitize($text) {
	    	return mysqli_real_escape_string($this->link, $text);
	    }
	}
?>