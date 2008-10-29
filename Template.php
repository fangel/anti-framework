<?php

class AF_Template implements AF_Template_Interface {
	static protected $preset = array();
		
	protected $template = '';
	protected $vars = array();
	
	public function __construct() {
		$this->vars = self::$preset;
	}
	
	public function display( $template ) {
		include $template;
	}
	
	public function render( $template, $vars ) {
		$tmpl = new AF_Template();
		$tmpl->vars = $vars;
		$tmpl->display( $template );
	}
	
	public function escape( $str ) {
		return htmlentities( $str, ENT_NOQUOTES, 'UTF-8' );
	}
	
	public function __get($name) { return $this->vars[$name]; }
	public function __set($name, $value) { $this->vars[$name] = $value; }
	public function __isset($name) { return isset($this->vars[$name]); }
	
	public static function addPreset( $var, $value ) {
		self::$preset[$var] = $value;
	}
}