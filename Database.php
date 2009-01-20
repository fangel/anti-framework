<?php

/**
 * The main database class.
 * Functions as a proxy for AF_PDO, in that any call to AF_Database
 * should be a valid PDO method, but with a added $mode parameter.
 * This param determins if the master or the slave database object is
 * then handed the call
 * @author Morten Fangel <fangel@sevengoslings.net>
 */
class AF_Database {
	private $master = null;
	private $slave = null;
	
	/**
	 * Constructor
	 * @param array $config
	 */
	function __construct( $config ) {
		$this->master = new AF_PDO($config['master']['dsn'], $config['master']['username'], $config['master']['password'], $config['master']['identifier']);
		$this->master->exec('SET NAMES utf8');
		
		if( isset( $config['slave'])) {
			$this->slave = new AF_PDO($config['slave']['dsn'], $config['slave']['username'], $config['slave']['password'], $config['slave']['identifier']);
			$this->slave->exec('SET NAMES utf8');
		} else {
			$this->slave = $this->master;
		}
	}
	
	/**
	 * Method-overloading. Handles the forwarding of calls to either the
	 * master or slave database object. 
	 * @param string $name
	 * @param mixed $arguments
	 * @return mixed
	 */
	public function __call( $name, $arguments ) {
		$mode = array_pop($arguments);
		$callback = array();
		if( $mode == AF::DELAY_SAFE )
			$db = $this->slave;
		else
			$db = $this->master;
		
		// TODO: Move this to AF_PDO::query() and AF_PDO::exec()
		if( in_array($name, array('query', 'exec') ) ) {
				$start = microtime(true);
			$ret = call_user_func_array( array($db, $name), $arguments );
				$end = microtime(true);
				AF_Database::log( reset($arguments), $end-$start, $this, $ret!==false, call_user_func(array($db,'errorInfo')) );
			return $ret;
		} else {
			return call_user_func_array( array($db, $name), $arguments );
		}
	}
	
	/**
	 * Handy shortcut for publishing things to the AF::Log with type 'query'
	 * @param string $query
	 * @param float $dur
	 * @param PDO $pdo
	 * @param bool $success
	 * @param string $errorMsg
	 */
	public static function log( $query, $dur, $pdo, $success, $errorMsg = null ) {
		AF::Log('query', array_filter(array(	'query' => $query,
								'duration' => $dur * 1000,
								'identifier' => $pdo->getIdentifier(),
								'success' => (bool) $success,
								'errorMsg' => (end($errorMsg) !== '00000') ? end($errorMsg) : null
		)),'strlen');
	}
}

/**
 * Overloaded PDO class. Added a identifier, and sets the statement 
 * class to AF_PDOStatement
 * Prepared statements are save in case further prepares are made 
 * with a already prepared statment
 * @author Morten Fangel <fangel@sevengoslings.net>
 */
class AF_PDO extends PDO {
	private $identifier;
	private $prepared = array();
	
	/**
	 * Constructor
	 * @see PDO::__construct()
	 * @param string $dsn
	 * @param string $username
	 * @param string $password
	 * @param string $identifier
	 */
	public function __construct( $dsn, $username, $password, $identifier ) {
		parent::__construct( $dsn, $username, $password );
		$this->setAttribute( PDO::ATTR_STATEMENT_CLASS, array('AF_PDOStatement', array( $this )) );
		$this->identifier = $identifier;
	}
	
	/**
	 * Return the identifier
	 * @return string
	 */
	public function getIdentifier() {
		return $this->identifier;
	}
	
	/**
	 * Create a new statement
	 * @see PDO::prepare
	 */
	public function prepare( $statement, $driver_options = array() ) {
		if( ! isset($this->prepared[ md5($statement) ] ) ) {
			$this->prepared[ md5($statement) ] = parent::prepare( $statement, $driver_options );
		}
		return $this->prepared[ md5($statement) ];
	}
}

/**
 * Overlaods teh PDOStatement object
 * Added logging to the execute method, and added the lastInsertId method
 * @author Morten Fangel <fangel@sevengoslings.net>
 */
class AF_PDOStatement extends PDOStatement {
	private $pdo;
	
	/**
	 * Constructor
	 * @param PDO $pdo
	 */
	protected function __construct( $pdo ) {
		$this->pdo = $pdo;
		$this->setFetchMode( PDO::FETCH_ASSOC );
	}
	
	/**
	 * Executes the statement.
	 * @see PDOStatement::execute
	 */
	public function execute( $input_parameters = null ) {
			$start = microtime(true);
		$ret = parent::execute( $input_parameters );
			$end = microtime(true);
			AF_Database::log($this->queryString, $end-$start, $this->pdo, $ret!==false, $this->errorInfo() );
		return $ret;
	}
	
	/**
	 * Returns the last inserted id
	 * Just calls the associated PDO object for it's last inserted id.
	 * @see PDO::lastInsertId()
	 */
	public function lastInsertId() {
		return $this->pdo->lastInsertId();
	}
}