<?php
/**
 * Configure class
 *
 * This is a class to read/write system/framework settings such as enable/disable Debug mode etc...
 *
 * @author Vikas Garg <vikasgarg@illuminz.com>
 * @version 1.0
 * @since 1.0
 * @access public
 * @copyright Illuminz
 */
class Configure {

    /**
     * System Configuration settings
     *
     * @var array
     * @access public static
     * @see write()
	 * @see read()
     */
    public static $configurations = array('debug'=>DEBUG);

    /**
     * Write system configuration settings
     *
     * @param string $var Setting name
	 * @param mixed $value Setting value
     * @access public static
     * @since 1.0
     */
    public static function write($var, $value){
		self::$configurations[$var] = $value;
    }

	/**
     * Read system configuration settings
     *
     * @param string $var Setting name
     * @access public static
	 * @return mixed
     * @since 1.0
     */
    public static function read($var){
		return self::$configurations[$var];
    }
}
?>