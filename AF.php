<?php

require dirname(__FILE__) . '/Interfaces.php';
require dirname(__FILE__) . '/Factory.php';

/**
 * The main interface of AF
 * Basically you call setConfig, and the bootstrap() and your on your
 * way..
 * @author Morten Fangel <fangel@sevengoslings.net>
 */
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
	
	/**
	 * Update the config of AF (overrides, doesn't append)
	 * @param $config array
	 */
	public static function setConfig( $config ) {
		self::$config = $config;
	}
	
	/**
	 * Prime AF for use.
	 * Use one of the AF::[level] constants to indicate to what level
	 * you need AF at
	 * @param $level int 
	 */
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
	
	/**
	 * Return the DB object
	 * @return AF_Database
	 */
	public static function DB() {
		return self::$db;
	}
	
	/**
	 * Add a message to the log (if no log is initiated, it's ignored)
	 * @param string $type
	 * @param string $msg
	 */
	public static function log( $type, $msg ) {
		if( self::$log ) {
			self::$log->log( $type, $msg );
		}
	}
	
	/**
	 * Return the log instance
	 * @return AF_Log_Interface
	 */
	public static function getLog() {
		return self::$log;
	}
	
	/**
	 * Create a new template
	 * @return AF_Template_Interface
	 */
	public static function template() {
		$config = (isset(self::$config['template'])) ? self::$config['template'] : array();
		return AF_Factory::template( $config );
	}
	
	/**
	 * Return the registry (a ArrayObject).
	 */
	public static function registry() {
		return self::$registry;
	}
	
	/**
	 * A shutdown-method. It's called if AF is loaded with AF::PAGEGEN
	 * Ends the page-gen timer and calls AF::log() with type 'pagegen'
	 */
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