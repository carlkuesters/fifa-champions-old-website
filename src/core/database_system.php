<?php
	define("CORE_DATABASE_METHOD_INITIALIZE", "initialize");

	abstract class Database {

		protected $db;

		public function __construct($host, $port, $user, $password, $database=false) {
			$this->db = $this->createConnection($host, $port, $user, $password, $database);
		}

		public function __destruct() {
			// $this->closeConnection();
		}

		protected abstract function createConnection($host, $port, $user, $password, $database);

		protected abstract function closeConnection();

		public function getObject($table, $class, $where=false, $order=false) {
			return $this->getObjects($table, $class, $where, $order, 1, false);
		}

		public function getObjects($table, $class, $where=false, $order=false, $limit=false, $forceArray=true) {
			$rows = $this->getRows($table, "*", $where, $order, $limit, true);
			$objects = array();
			foreach ($rows as $row) {
				$object = new $class();
				foreach ($row as $key=>$value) {
					$object->$key = $value;
				}
				if (method_exists($object, CORE_DATABASE_METHOD_INITIALIZE)) {
					$object->{CORE_DATABASE_METHOD_INITIALIZE}();
				}
				$objects[] = $object;
			}
			if ((!$forceArray) && (count($objects) == 1)) {
				return $objects[0];
			}
			return $objects;
		}

		public function getValue($table, $column, $where=false, $order=false) {
			return $this->getValues($table, $column, $where, $order, 1, false);
		}

		public function getValues($table, $column, $where=false, $order=false, $limit=false, $forceArray=true) {
			$objects = $this->getRows($table, $column, $where, $order, $limit);
			$values = array();
			foreach ($objects as $object) {
				$values[] = $object->$column;
			}
			if ((!$forceArray) && (count($values) == 1)) {
				return $values[0];
			}
			return $values;
		}

		public function getRow($table, $columns="*", $where=false, $order=false) {
			return $this->getRows($table, $columns, $where, $order, 1, false);
		}

		public function getRows($table, $columns="*", $where=false, $order=false, $limit=false, $forceArray=true) {
			return $this->sendQuery("SELECT " . $this->getColumnsString($columns) . " FROM `" . $table . "` " . $this->getOptionString($where, $order, $limit), $forceArray);
		}

		public function exists($table, $columns) {
			return $this->getRowCount($table, $columns, false, 1) > 0;
		}

		public function getRowCount($table, $where=false, $order=false, $limit=false) {
			return $this->sendQuery("SELECT COUNT(*) as `count` FROM " . $table . " " . $this->getOptionString($where, $order, $limit))->count;
		}

		public function insertRow($table, $columns) {
			return $this->sendQuery("INSERT INTO `" . $table . "`" . $this->getInsertString($columns));
		}

		public function updateRow($table, $columns, $where=false, $order=false) {
			return $this->updateRows($table, $columns, $where, $order, 1);
		}

		public function updateRows($table, $columns, $where=false, $order=false, $limit=false) {
			return $this->sendQuery("UPDATE `" . $table . "` SET " . $this->getUpdateString($columns) . " " . $this->getOptionString($where, $order, $limit));
		}

		public function deleteRow($table, $where=false, $order=false) {
			return $this->deleteRows($table, $where, $order, 1);
		}

		public function deleteRows($table, $where=false, $order=false, $limit=false) {
			return $this->sendQuery("DELETE FROM `" . $table . "` " . $this->getOptionString($where, $order, $limit));
		}

		public function increaseColumn($table, $column, $amount=1, $where=false, $order=false) {
			return $this->increaseColumns($table, $column, $amount, $where, $order, 1);
		}

		public function increaseColumns($table, $column, $amount=1, $where=false, $order=false, $limit=false) {
			return $this->sendQuery("UPDATE `" . $table . "` SET `" . $column . "` = `" . $column . "` + " . $amount . " " . $this->getOptionString($where, $order, $limit));
		}

		public abstract function sendQuery($query, $forceArray=false);

		public abstract function getInsertID();

		public abstract function getError();

		private function getInsertString($columns) {
			$string = " (";
			$i = 0;
			foreach ($columns as $column=>$value) {
				if ($i != 0) {
					$string .= ", ";
				}
				$string .= "`" . $column . "`";
				$i++;
			}
			$string .= ") VALUES (";
			$i = 0;
			foreach ($columns as $column=>$value) {
				if ($i != 0) {
					$string .= ", ";
				}
				$string .= $this->getValueString($value);
				$i++;
			}
			$string .= ")";
			return $string;
		}

		private function getUpdateString($columns) {
			$string = "";
			$i = 0;
			foreach ($columns as $column=>$value) {
				if ($i != 0) {
					$string .= ", ";
				}
				$string .= $column . " = " . $this->getValueString($value);
				$i++;
			}
			return $string;
		}

		private function getOptionString($where=false, $order=false, $limit=false, $groupBy=false) {
			if (is_array($where)) {
				$where = $this->getWhereConditions($where);
			}
			return ($where?"WHERE " . $where:"") . ($groupBy?" GROUP BY " . $groupBy:"") . ($order?" ORDER BY " . $order:"") . ($limit?" LIMIT " . $limit:"");
		}

		private function getColumnsString($columns) {
			if (is_array($columns)) {
				$columnsString = "";
				foreach ($columns as $i=>$column) {
					if ($i != 0) {
						$columnsString .= ", ";
					}
					$columnsString .= "`" . $column . "`";
				}
				return $columnsString;
			}
			return $columns;
		}

		private function getValueString($value) {
			if (is_bool($value)) {
				return ($value?"true":"false");
			} else if (is_integer($value)) {
				return intval($value);
			} else if (is_float($value)) {
				return floatval($value);
			} else if (is_double($value)) {
				return doubleval($value);
			}
			return "'" . $this->escape($value) . "'";
		}

		private function getWhereConditions($columns) {
			$condition = "";
			$notFirstRow = false;
			foreach ($columns as $column=>$value) {
				if ($notFirstRow) {
					$condition .= " AND ";
				}
				$condition .= "(" . $column . " = " . $this->getValueString($value) . ")";
				$notFirstRow = true;
			}
			return $condition;
		}

		public abstract function escape($value);

		public function unescape($value) {
			return stripslashes($value);
		}
	}

	class MySQLDatabase extends Database {

		protected function createConnection($host, $port, $user, $password, $database) {
			$db  = mysqli_connect($host, $user, $password, null, $port);
			if (mysqli_connect_errno()) {
				echo "Connection failed: " . mysqli_connect_error();
			} else {
				if (!$db->set_charset("utf8")) {
					echo "The charset couldn't be set to UTF-8.";
				}
				if ($database && !mysqli_select_db($db, $database)) {
					echo "No connection to database.";  
				}
			}
			return $db;
		}

		protected function closeConnection() {
			$this->db->close();
		}

		public function sendQuery($query, $forceArray=false) {
			if ($result = $this->db->query($query)) {
				if (is_object($result)) {
					$objects = array();
					while ($data = $result->fetch_object()) {
						$objects[] = $data;
					}
					$result->close();
					if (!$forceArray && (count($objects) == 1)) {
						return $objects[0];
					}
					return $objects;
				} else {
					return $result;
				}
			} else {
				echo "Query Error: " . $query . "  =>  " . $this->getError();
			}
		}

		public function getInsertID() {
			return mysqli_insert_id($this->db);
		}

		public function getError() {
			return mysqli_error($this->db);
		}

		public function escape($value) {
			return mysqli_real_escape_string($this->db, $value);
		}
	}
?>