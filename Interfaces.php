<?php

interface AF_Log_Interface {
	public function log( $type, $msg );
}

interface AF_Template_Interface {
	public function __get( $var );
	public function __set( $var, $value );
	public function __isset( $var );
	public function display( $template );
	public function render( $template, $vars );
	public function escape( $string );
}