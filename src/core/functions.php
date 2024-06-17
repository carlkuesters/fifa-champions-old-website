<?php
	function getURLParameter($key, $defaultValue=false) {
		return getArrayValue($_GET, $key, $defaultValue);
	}

	function getPOSTParameter($key, $defaultValue=false) {
		return getArrayValue($_POST, $key, $defaultValue);
	}

	function getArrayValue($array, $key, $defaultValue=false) {
		if (isset($array[$key])) {
			return $array[$key];
		}
		return $defaultValue;
	}

	function linkTo($url) {
		header("Location:" . $url);
		exit();
	}

	function startsWith($str, $sub) {
		return strpos($str, $sub) === 0;
	}

	function endsWith($str, $sub) {
		return substr($str, strlen($str) - strlen($sub)) == $sub;
	}
?>