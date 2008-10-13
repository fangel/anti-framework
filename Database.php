<?php

class AF_Database {
	private $master = null;
	private $slave = null;
	private $prepared = array();
	
	function __construct( $config ) {
		if( !isset($config['master']['dsn']) ) {
			// TODO: Choose at random
			$config['master'] = reset($config['master']);
		}
		$this->master = new AF_PDO($config['master']['dsn'], $config['master']['username'], $config['master']['password'], $config['master']['identifier']);
		
		if( isset( $config['slave'])) {
			if( !isset( $config['slave']['dsn'] ) ) {
				// TODO: Choose at random
				$config['slave'] = reset($config['slave']);
			}
			$this->slave = new AF_PDO($config['slave']['dsn'], $config['slave']['username'], $config['slave']['password'], $config['slave']['identifier']);
		} else {
			$this->slave = $this->master;
		}
	}
	
	public function __call( $name, $arguments ) {
		$mode = array_pop($arguments);
		$callback = array();
		if( $mode == AF::DELAY_SAFE ) {
			$db = $this->slave;
		} else {
			$db = $this->master;
		}
		
		if( in_array($name, array('query', 'exec') ) ) {
			$start = microtime(true);
			$ret = call_user_func_array( array($db, $name), $arguments );
			$end = microtime(true);
			$log = array(
				'query' => reset($arguments),
				'duration' => ($end - $start)*1000,
				'identifier' => $this->getIdentifier(),
				'success' => true
			);
			if( $ret === false ) {
				$log['success'] = false;
				$log['errorMsg'] = end(call_user_func( array($db, 'errorInfo') ));
			}
			AF::log('query', $log);
		} else if( $name == 'prepare' ) {
			$key = md5(reset($arguments) . '_' . $mode);
			if( ! isset( $this->prepared[ $key ] ) ) {
				$stmt = call_user_func_array( array($db, $name), $arguments );
				$this->prepared[ $key ] = $stmt;
			} 
			return $this->prepared[ $key ];
		} else {
			return call_user_func_array( array($db, $name), $arguments );
		}
	}
}

class AF_PDO extends PDO {
	private $identifier;
	
	public function __construct( $dsn, $username, $password, $identifier ) {
		parent::__construct( $dsn, $username, $password );
		$this->setAttribute( PDO::ATTR_STATEMENT_CLASS, array('AF_PDOStatement', array( $this )) );
		$this->identifier = $identifier;
	}
	
	public function getIdentifier() {
		return $this->identifier;
	}
}

class AF_PDOStatement extends PDOStatement {
	private $pdo;
	
	protected function __construct( $pdo ) {
		$this->pdo = $pdo;
		$this->setFetchMode( PDO::FETCH_ASSOC );
	}
	
	public function execute( $input_parameters = null ) {
		$start = microtime(true);
		$ret = parent::execute( $input_parameters );
		$end = microtime(true);
		$log = array(
			'query' => $this->queryString,
			'duration' => ($end - $start)*1000,
			'identifier' => $this->pdo->getIdentifier(),
			'success' => true
		);
		if( $ret === false ) {
			$log['success'] = false;
			$log['errorMsg'] = end($this->errorInfo());
		}
		
		AF::log('query', $log);
	}
	
	public function lastInsertId() {
		return $this->pdo->lastInsertId();
	}
}