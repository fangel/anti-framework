<?php

/**
 * The classes that corresponds to a row in a database.
 * Supports - well, saving.
 * Access to the internal variables is handled via 
 * __get and __set
 * @author Morten Fangel <fangel@sevengoslings.net>
 */
class AF_Object {
	private $table = null;
	private $vars = array();
	private $is_new = true;
	
	/**
	 * Constructs a new AF_Object
	 * @param AF_Table $table
	 * @param array $vars
	 * @param bool $is_new
	 */
	public function __construct( AF_Table $table, $vars = array(), $is_new = true ) {
		$this->table = $table;
		$this->vars = $vars;
		$this->is_new = $is_new;
	}
	
	/**
	 * Saves the object to the database
	 * Note that setting $mode til AF::DELAY_SAFE will not
	 * lead to the database-call being executed with that
	 * mode. Instead the SQL is changed to a low-priority
	 * call, and executed with AF::NO_DELAY
	 * @param int $mode
	 * @return bool
	 */
	public function save( $mode = AF::NO_DELAY ) {
		if( $this->table->primary_key == false ) return false;
		$this->vars = array_filter( $this->vars, 'strlen' );
		
		$vars = $this->vars;		
		$sql = '';
		if( $this->is_new ) {
			$delayed = ($mode == AF::DELAY_SAFE) ? ' DELAYED ' : '';
			
			$sql  = 'INSERT ' . $delayed . ' INTO ' . $this->table->table;
			$sql .= '(`' . implode('`, `', array_keys($vars)) . '`) ';
			$sql .= 'VALUES (:' . implode(', :', array_keys($vars)) . ')';
		} else {
			$primary = $vars[$this->table->primary_key];
			unset($vars[$this->table->primary_key]);
			
			$delayed = ($mode == AF::DELAY_SAFE) ? ' LOW_PRIORITY ' : '';
			
			$sql = 'UPDATE ' . $delayed . $this->table->table . ' SET ';
			foreach( array_keys($vars) As $v )
				$sql .= '`' . $v . '` = :' . $v . ', ';
			$sql = substr($sql, 0, -2) . ' WHERE ' . $this->table->primary_key . ' = :id';
			
			$vars['id'] = $primary;
		}

		$stmt = AF::DB()->prepare( $sql, AF::NO_DELAY);
		$ret = $stmt->execute($vars);
		
		if( $this->is_new ) {
			if( $this->table->primary_key ) {
				$this->{$this->table->primary_key} = $stmt->lastInsertId();
			}
			$this->is_new = false;
		}
		
		return $ret !== false;
	}
	
	public function toArray() {	return $this->vars; }	
	public function __get($name) { return $this->vars[$name]; }
	public function __set($name, $value) { $this->vars[$name] = $value; }
	public function __isset($name) { return isset($this->vars[$name]); }
}