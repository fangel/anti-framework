<?php

class AF_Table {
	public $table = false;
	public $primary_key = 'id';
	public $row_class = 'AF_Object';
	
	public function __construct( $table, $primary_key = 'id', $row_class = 'AF_Object' ) {
		$this->table = $table;
		$this->primary_key = $primary_key;
		$this->row_class = $row_class;
	}
	
	public function fetch( $query, $parameters, $mode ) {
		$sql = 'SELECT * FROM ' . $this->table . ' WHERE ' . $query;
		$stmt = AF::DB()->prepare($sql, $mode);
		$stmt->execute( (array) $parameters );
		$objs = array();
		while( $vars = $stmt->fetch() ) {
			if( $this->primary_key )	$objs[ $vars[$this->primary_key] ] = new $this->row_class($this, $vars, false);
			else						$objs[] = new $this->row_class($this, $vars, false);
		}
		return $objs;
	}
	
	public function fetchAll( $mode, $limit = 10, $offset = 0 ) {
		$sql = 'SELECT * FROM ' . $this->table . ' LIMIT ' . $offset . ', ' .$limit;
		$stmt = AF::DB()->prepare($sql, $mode);
		$stmt->execute();
		$objs = array();
		while( $vars = $stmt->fetch() ) {
			if( $this->primary_key )	$objs[ $vars[$this->primary_key] ] = new $this->row_class($this, $vars, false);
			else						$objs[] = new $this->row_class($this, $vars, false);
		}
		return $objs;
	}
	
	public function find( $key, $value, $mode ) {
		return reset($this->fetch($key . ' = ?', $value, $mode));
	}
	
	public function get( $id, $mode ) {
		if( $this->primary_key == false) return false;
		
		$sql = 'SELECT * FROM ' . $this->table . ' WHERE ' . $this->primary_key . ' = ?';
		$stmt = AF::DB()->prepare($sql, $mode);
		$stmt->execute( array($id) );
		$vars = $stmt->fetch();
		$stmt->closeCursor();
		
		return ($vars) ? new $this->row_class($this, $vars, false) : false;
	}
	
	public function create() {
		return new $this->row_class( $this );
	}
}