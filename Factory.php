<?php

class AF_Factory {
	public static function log( $config ) {
		$type = $config['type'];
		if( class_exists( $type ) ) {
			$log = new $type($config['params']);
			if( $log instanceof AF_Log_Interface ) {
				return $log;
			}
		}
		return null;
	}
	
	public static function template( $config ) {
		$type = (isset($config['type'])) ? $config['type'] : 'AF_Template';
		if( class_exists( $type ) ) {
			$log = new $type();
			if( $log instanceof AF_Template_Interface ) {
				return $log;
			}
		}
		return null;
	}
}