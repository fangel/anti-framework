<?php

/**
 * A very basic template class.
 * Simply add variables with $obj->[var] = [value],
 * then call $obj->display([template])
 * @author Morten Fangel <fangel@sevengoslings.net>
 */
class AF_Template implements AF_Template_Interface {
	static protected $preset = array();
	protected $vars = array();
	
	public function __construct() {
		$this->vars = self::$preset;
	}
	
	/**
	 * Renders the $template (outputs directly)
	 * @param string $template
	 */
	public function display( $template ) {
		include $template;
	}
	
	/**
	 * Renders a sub-template with a given list of vars
	 * (outputs directly)
	 * @param string $template
	 * @param array $vars
	 */
	public function render( $template, $vars ) {
		$tmpl = new AF_Template();
		$tmpl->vars = $vars;
		$tmpl->display( $template );
	}
	
	/**
	 * A simple HTML escaping. It's highly recommended that
	 * the app-developer uses more specific escaping-codes.
	 * But this will do in most simple cases
	 * Running ENT_COMPAT mode, so " but not ' is replaced
	 * @param string $str
	 * @return string
	 */
	public function escape( $str ) {
		return htmlentities( $str, ENT_COMPAT, 'UTF-8' );
	}
	
	public function __get($name) { return $this->vars[$name]; }
	public function __set($name, $value) { $this->vars[$name] = $value; }
	public function __isset($name) { return isset($this->vars[$name]); }
	
	/**
	 * Adds a list of pre set variables. This list of variables
	 * is auto-added to new instances of the template object.
	 * @param string $var
	 * @param mixed $value
	 */
	public static function addPreset( $var, $value ) {
		self::$preset[$var] = $value;
	}
}