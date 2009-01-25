<?php

/**
 * AF_Table is responsible for querying a table which returns AF_Models
 * @author Morten Fangel <fangel@sevengoslings.net>
 */
class AF_Table {
	public $table = false;
	public $primary_key = 'id';
	public $row_class = 'AF_Object';
	
	/**
	 * Constructs a new table 
	 * @param string $table 
	 * @param string $primary_key
	 * @param string $row_class
	 */
	public function __construct( $table, $primary_key = 'id', $row_class = 'AF_Object' ) {
		$this->table = $table;
		$this->primary_key = $primary_key;
		$this->row_class = $row_class;
	}
	
	/**
	 * Perform a query with the condition defined in $condition
	 * @param string $condition WHERE-part of the query
	 * @param array $parameters
	 * @param int $mode
	 * @return AF_Object[]
	 */
	public function fetch( $condition, $parameters, $mode ) {
		$parameters = (array) $parameters; 
		$sql = 'SELECT * FROM :table';
		if( $condition !== null )
			$sql .= ' WHERE ' . $condition;
		if( isset($parameters['limit'], $parameters['offset']) )
			$sql .= ' LIMIT ' . $parameters['offset'] . ', ' . $parameters['limit'];
		else if( isset($parameters['limit']) )
			$sql .= ' LIMIT ' . $parameters['limit'];
		unset($parameters['limit']);
		unset($parameters['offset']);
		
		return $this->query( $sql, $parameters, $mode );
	}
	
	/**
	 * Performs a SELECT * limited by $offset, $limit
	 * CONSIDER: is fetchLimited really the best name for this?
	 * @param int $limit
	 * @param int $offset
	 * @param int $mode
	 * @return AF_Object[]
	 */
	public function fetchLimited( $limit, $offset, $mode ) {
		return $this->fetch( null, array('limit'=>$limit, 'offset'=>$offset), $mode );
	}
	
	/**
	 * Executes the full sql-query
	 * Replaces ':table' and ':primary' with the table name and the name of the primary key
	 * If $boolean is set to true, this function will return a boolean on success/failure
	 * Otherwise it will return an array. An empty array could be because of error, or just
	 * no results. So be careful.
	 * @param string $sql
	 * @param array $parameters
	 * @param int $mode
	 * @param bool $boolean 
	 * @return AF_Object[]
	 */
	public function query( $sql, $parameters, $mode, $boolean = false ) {
		$sql = str_replace(array(':table', ':primary'), array($this->table, $this->primary_key), $sql);
		$stmt = AF::DB()->prepare($sql, $mode);
		$status = $stmt->execute( (array) $parameters );
		if( ! $status ) { return ($boolean) ? false : array(); }
		if( $boolean ) { return true; }
		
		$objs = array();
		while( $vars = $stmt->fetch() ) {
			if( $this->primary_key )
				$objs[ $vars[$this->primary_key] ] = new $this->row_class($this, $vars, false);
			else
				$objs[] = new $this->row_class($this, $vars, false);
		}
		$stmt->closeCursor();
		return $objs;
	}
	
	/**
	 * Finds a single row with $key=$value
	 * @param string $key
	 * @param mixed $value
	 * @param int $mode
	 * @return AF_Object
	 */
	public function find( $key, $value, $mode ) {
		return reset($this->fetch('`'.$key . '` = ?', $value, $mode));
	}
	
	/**
	 * Retuns the row with :primary=$id
	 * @param int $id
	 * @param int $mode
	 * @return AF_Object
	 */
	public function get( $id, $mode ) {
		if( $this->primary_key == false) return false;
		return reset($this->fetch(':primary = ?', $id, $mode));
	}
	
	/**
	 * Creates a new object
	 * @return AF_Object
	 */
	public function create() {
		return new $this->row_class( $this );
	}
}