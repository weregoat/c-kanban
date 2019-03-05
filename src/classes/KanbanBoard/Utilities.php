<?php
namespace KanbanBoard;

class Utilities
{

    /**
     * Returns the value of a environment variable, if exists. Otherwise kill the process.
     * @param string $name The name of the environment variable.
     * @param string|null $default Optionally use this value if the variable is not present or empty.
     * @return mixed
     * @see getenv
     * @throws \RuntimeException If the environment variable is not set and there is no default
     */
	public static function env(string $name, $default = NULL) {
		$value = getenv($name);
		if ($value === FALSE OR empty($value)) { // FALSE means no env was found
		    /* If there is a default */
            if ($default !== NULL) {
                $value = $default;
            } else {
                $value = FALSE;
            }
        }
		if ($value === FALSE) { // FALSE means no env
		    throw new \RuntimeException(sprintf("Environment variable %s not set", $name));
        }
		return $value;
	}

    /**
     * Returns if an array has a key with a value.
     * @param array $array The array to check.
     * @param mixed $key The key.
     * @return bool
     */
	public static function hasValue(array $array, $key) :bool {
		return array_key_exists($key, $array) && !empty($array[$key]);
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
}