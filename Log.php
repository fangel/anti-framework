<?php

class AF_Log_Array implements AF_Log_Interface {
	private $log = array();
	
	public function __construct( $params ) {
		if( isset($params['register_shutdown']) && $params['register_shutdown'] )
			register_shutdown_function( array($this, 'printLog') );
	}
	
	public function log( $type, $msg ) {
		if( !isset($this->log[$type]) ) {
			$this->log[$type] = array();
		}
		$this->log[$type][] = $msg;
	}
	
	public function printLog() {
		if( defined('AF_LOG_ARRAY_QUIET') && AF_LOG_ARRAY_QUIET ) return;
		
		echo "\n\n\n\n";
		foreach( $this->log AS $type => $log ) {
			echo '<h1>' . $type . '</h1>' . "\n" . '<pre>';
			foreach( $log AS $entry ) {
				echo "\n";
				var_export($entry);
			}
			echo "\n" . '</pre>' . "\n";
		}
	}
}

class AF_Log_File implements AF_Log_Interface {
	private $dir = '';
	private $files = array();
	
	public function __construct( $conf ) {
		$this->dir = $conf['dir'];
	}
	
	public function log( $type, $msg ) {
		if( !isset($this->files[$type]) ) {
			$this->files[$type] = fopen($this->dir.'/'.$type .'.log', 'a');
		}
		
		fwrite( $this->files[$type], print_r($msg, true));
	}
}