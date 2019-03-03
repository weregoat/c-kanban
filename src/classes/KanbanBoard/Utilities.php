<?php
namespace KanbanBoard;

class Utilities
{
	private function __construct() {
	}

	public static function env($name, $default = NULL) {
		$value = getenv($name);
		if($default !== NULL) {
			if(!empty($value))
				return $value;
			return $default;
		}
		return (empty($value) && $default === NULL) ? die('Environment variable ' . $name . ' not found or has no value') : $value;
	}


    /**
     * Returns if an array has a key with a value.
     * @param array $array The array to check.
     * @param mixed $key The key.
     * @return bool
     */
	public static function hasValue(array $array, $key) :bool {
		return is_array($array) && array_key_exists($key, $array) && !empty($array[$key]);
	}

    /**
     * Returns the value for a given key from an array, if exists.
     * @param array $array The array to retrieve the value for.
     * @param mixed $key The key to the value.
     * @return null|mixed NULL if there is no such value, whatever otherwise.
     */
	public static function getValue(array $array, $key) {
	    $value = NULL;
	    if (self::hasValue($array, $key)) {
	        $value = $array[$key];
        }
	    return $value;
    }

    /**
     * Returns an the value of an array as an array.
     * @param array $array The array to retrieve the value from.
     * @param mixed $key The key to the value.
     * @return array Empty array if the value was an empty array or not an array.
     */
    public static function getArrayValue(array $array, $key) :array {
	    $value = self::getValue($array, $key);
	    if (!is_array($value)) {
	        $value = array();
        }
	    return $value;
    }

	public static function dump($data) {
		echo '<pre>';
		var_dump($data);
		echo '</pre>';
	}
}