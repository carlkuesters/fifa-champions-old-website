<?php	
	class Session {

		// Declare fields so they won't get caught in the read/write methods
		private $db;
		private $databaseTable;
		private $id;
		private $data;
		private $sessionPrefix;
		private $deleted = false;
		const allowedIDLetters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
		const idLength = 16;
		// In seconds (86400s = 24h)
		const cookieDuration = 86400;

		public function __construct($db, $databaseTable, $id, $sessionPrefix) {
			$this->db = $db;
			$this->databaseTable = $databaseTable;
			$this->id = $id;
			$this->sessionPrefix = $sessionPrefix;
			$this->updateCookie();
			$this->refreshData();
		}

		public function __destruct() {
			$this->updateDatabaseContent();
		}

		public function __get($key) {
			return $this->read($key);
		}

		public function __set($key, $value) {
			return $this->write($key, $value);
		}

		public function read($key) {
			if (isset($this->data[$key])) {
				return $this->data[$key];
			}
			return false;
		}

		public function write($key, $value) {
			if (!$this->deleted) {
				$this->data[$key] = $value;
			}
		}

		public function delete() {
			$this->data = array();
			$this->db->deleteRow($this->databaseTable, array("id"=>$this->id));
			$this->deleteCookie();
			$this->deleted = true;
		}

		public function refreshData() {
			$this->db->deleteRows($this->databaseTable, "(" . time() . " - last_modification_date) > " . self::cookieDuration);
			$this->data = array();
			$row = $this->db->getRow($this->databaseTable, "data", array("id"=>$this->id));
			if ($row) {
				$this->data = unserialize($row->data);
			}
		}

		public function updateCookie() {
			setcookie(self::getCookieName($this->sessionPrefix), $this->id, time() + self::cookieDuration);
		}

		public function deleteCookie() {
			setcookie(self::getCookieName($this->sessionPrefix), "", time() - self::cookieDuration);
		}

		public function updateDatabaseContent() {
			if (!$this->deleted) {
				if ($this->db->exists($this->databaseTable, array("id"=>$this->id))) {
					if (!empty($this->data)) {
						$this->db->updateRow($this->databaseTable, array("data"=>serialize($this->data),"last_modification_date"=>time()), array("id"=>$this->id));
					} else {
						$this->delete();
					}
				} else if (!empty($this->data)) {
					$this->db->insertRow($this->databaseTable, array("id"=>$this->id,"data"=>serialize($this->data),"last_modification_date"=>time()));
				}
			}
		}

		public static function getNextID($db, $databaseTable) {
			do {
				$id = self::generateRandomString(self::idLength);
			} while ($db->exists($databaseTable, array("id"=>$id)));
			return $id;
		}

		public static function generateRandomString($length) {
			$string = "";
			$lettersCount = strlen(self::allowedIDLetters);
			for ($i = 0; $i < $length; $i++) {
				$string .= substr(self::allowedIDLetters, rand(0, $lettersCount - 1), 1);
			}
			return $string;
		}

		public static function getCookieName($sessionPrefix) {
			return ($sessionPrefix . "_session");
		}

		public static function getCurrentSession($db, $databaseTable, $sessionPrefix) {
			$cookieName = self::getCookieName($sessionPrefix);
			if (isset($_COOKIE[$cookieName]) && $db->exists($databaseTable, array("id"=>$_COOKIE[$cookieName]))) {
				$sessionID = $_COOKIE[$cookieName];
			} else {
				$sessionID = self::getNextID($db, $databaseTable);
			}
			return new Session($db, $databaseTable, $sessionID, $sessionPrefix);
		}
	}
?>