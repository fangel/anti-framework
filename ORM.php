<?php

class AF_Object {
	private $table = null;
	private $vars = array();
	private $is_new = true;
	
	public function __construct( $table, $vars = array(), $is_new = true ) {
		$this->table = $table;
		$this->vars = $vars;
		$this->is_new = $is_new;
	}
	
	public function save( $mode = AF::NO_DELAY ) {
		if( $this->table->primary_key == false ) return false;
		
		$vars = $this->vars;		
		$sql = '';
		if( $this->is_new ) {
			$delayed = ($mode == AF::DELAY_SAFE) ? ' DELAYED ' : '';
			$sql  = 'INSERT ' . $delayed . ' INTO ' . $this->table->table . '(';
			$sql .= implode(', ', array_keys($vars)) . ') VALUES (:' . 
			$sql .= implode(', :', array_keys($vars)) . ')';
		} else {
			$primary = $vars[$this->table->primary_key];
			unset($vars[$this->table->primary_key]);
			$delayed = ($mode == AF::DELAY_SAFE) ? ' LOW_PRIORITY ' : '';
			$sql = 'UPDATE ' . $delayed . $this->table->table . ' SET ';
			foreach( array_keys($vars) As $v ) { $sql .= $v . ' = :' . $v . ', '; }
			$sql = substr($sql, 0, -2) . ' WHERE ' . $this->table->primary_key . ' = :id';
			$vars['id'] = $primary;
		}

		$stmt = AF::DB()->prepare( $sql, AF::NO_DELAY);
		$ret = $stmt->execute($vars);
		
		if( $this->is_new ) {
			$this->{$this->table->primary_key} = $stmt->lastInsertId();
			$this->is_new = false;
		}
		
		return $ret !== false
	}
	
	public function toArray() {	return $vars; }	
	public function __get($name) { return $this->vars[$name]; }
	public function __set($name, $value) { $this->vars[$name] = $value; }
	public function __isset($name) { return isset($this->vars[$name]); }
}

class AF_Table {
	public $table = false;
	public $primary_key = 'id';
	private $row_class = 'AF_Object';
	
	public function __construct( $table, $primary_key = 'id', $row_class = 'AF_Object' ) {
		$this->table = $table;
		$this->primary_key = $primary_key;
		$this->row_class = $row_class;
	}
	
	public function get( $id, $mode ) {
		if( $this->primary_key == false) return false;
		
		$sql = 'SELECT * FROM ' . $this->table . ' WHERE ' . $this->primary_key . ' = ?';
		$stmt = AF::DB()->prepare($sql, $mode);
		$stmt->execute( array($id) );
		$vars = $stmt->fetch();
		
		return new $this->row_class($this, $vars, false);
	}
	
	public function create() {
		return new $this->row_class( $this );
	}
}