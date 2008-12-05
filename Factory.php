<?php

/**
 * A simple factory-class that contains method for creating 
 * a list of vital AF classes
 * @author Morten Fangel <fangel@sevengoslings.net>
 */
class AF_Factory {
	/**
	 * Creates a new logging object
	 * @param array $config
	 * @return AF_Log_Interface
	 */
	public static function log( $config ) {
		$type = $config['type'];
		if( class_exists( $type ) ) {
			$params = (isset($config['params'])) ? $config['params'] : array();
			$log = new $type( $params );
			if( $log instanceof AF_Log_Interface ) {
				return $log;
			}
		}
		return null;
	}
	
	/**
	 * Creates a new template object
	 * @param array $config
	 * @return AF_Template_Interface
	 */
	public static function template( $config ) {
		$type = (isset($config['type'])) ? $config['type'] : 'AF_Template';
		if( class_exists( $type ) ) {
			$template = new $type();
			if( $template instanceof AF_Template_Interface ) {
				return $template;
			}
		}
		return null;
	}
}