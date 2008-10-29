<?php

require dirname(__FILE__) . '/Interfaces.php';
require dirname(__FILE__) . '/Factory.php';

class AF {
	const NO_DELAY		= 0;
	const DELAY_SAFE	= 1;
	
	const NOTHING		= 0;
	const DB			= 1;
	const LOGGING		= 2;
	const ORM			= 3;
	const TEMPLATE		= 4;
	const REGISTRY		= 5;
	const PAGE_GEN		= 6;
	const ALL			= 6;
	
	protected static $config = array();
	protected static $db = null;
	protected static $log = null;
	protected static $attributes = array();
	protected static $registry = null;
	
	public static function setConfig( $config ) {
		self::$config = $config;
	}
	
	public static function bootstrap( $level = AF::NOTHING ) {
		switch( $level ) {
			case AF::PAGE_GEN:
				self::$attributes['start'] = microtime(true);
				register_shutdown_function( array('AF', 'shutdown') );
			case AF::REGISTRY:
				self::$registry = new ArrayObject();
			case AF::TEMPLATE:
				require dirname(__FILE__).'/Template.php';
			case AF::ORM:
				require dirname(__FILE__).'/ORM.php';
			case AF::LOGGING:
				if( isset(self::$config['log']) ) {
					require dirname(__FILE__).'/Log.php';
					self::$log = AF_Factory::log( self::$config['log'] );
				}
			case AF::DB:
				if( isset(self::$config['db']) ) {
					require dirname(__FILE__).'/Database.php';
					self::$db = new AF_Database( self::$config['db'] );
				}
			case AF::NOTHING:
		}
	}
	
	public static function DB() {
		return self::$db;
	}
	
	public static function log( $type, $msg ) {
		if( self::$log ) {
			self::$log->log( $type, $msg );
		}
	}
	
	public static function getLog() {
		return self::$log;
	}
	
	public static function template() {
		$config = (isset(self::$config['template'])) ? self::$config['template'] : array();
		return AF_Factory::template( $config );
	}
	
	public static function registry() {
		return self::$registry;
	}
	
	public static function shutdown() {
		self::$attributes['end'] = microtime(true);
		$duration = self::$attributes['end'] - self::$attributes['start'];
		
		$pagegen = array(
			'page' => $_SERVER['REQUEST_URI'],
			'execution' => $duration * 1000
		);
		
		AF::log('pagegen', $pagegen);
	}
}