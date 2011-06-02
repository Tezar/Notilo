<?php
/** NotORM - simple reading data from the database
* @link http://www.notorm.com/
* @author Jakub Vrana, http://www.vrana.cz/
* @copyright 2010 Jakub Vrana
* @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
* @license http://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2 (one or other)
*/

/** SQL literal value
*/


/** Filtered table representation
*/
class NotORM_Result extends NotORM_Abstract implements Iterator, ArrayAccess, Countable {
	protected $single;
	protected $select = array(), $conditions = array(), $where = array(), $parameters = array(), $order = array(), $limit = null, $offset = null, $group = "", $having = "";
	protected $union = array(), $unionOrder = array(), $unionLimit = null, $unionOffset = null;
	protected $data, $referencing = array(), $aggregation = array(), $accessed, $access, $keys = array();
	
	/** Create table result
	* @param string
	* @param NotORM
	* @param bool single row
	*/
	protected function __construct($table, NotORM $notORM, $single = false) {
		$this->table = $table;
		$this->notORM = $notORM;
		$this->single = $single;
		$this->primary = $notORM->structure->getPrimary($table);
	}
	
	/** Save data to cache and empty result
	*/
	function __destruct() {
		if ($this->notORM->cache && !$this->select && isset($this->rows)) {
			$access = $this->access;
			if (is_array($access)) {
				$access = array_filter($access);
			}
			$this->notORM->cache->save("$this->table;" . implode(",", $this->conditions), $access);
		}
		$this->rows = null;
		$this->data = null;
	}
	
	protected function limitString($limit, $offset) {
		$return = "";
		if (isset($limit)) {
			$return .= " LIMIT $limit";
			if (isset($offset)) {
				$return .= " OFFSET $offset";
			}
		}
		return $return;
	}
	
	protected function removeExtraDots($expression) {
		return preg_replace('~\\b[a-z_][a-z0-9_.]*\\.([a-z_][a-z0-9_]*\\.[a-z_*])~i', '\\1', $expression); // rewrite tab1.tab2.col
	}
	
	protected function whereString() {
		$return = "";
		if ($this->group) {
			$return .= " GROUP BY $this->group";
		}
		if ($this->having) {
			$return .= " HAVING $this->having";
		}
		if ($this->order) {
			$return .= " ORDER BY " . implode(", ", $this->order);
		}
		$return = $this->removeExtraDots($return);
		
		$where = $this->where;
		if (isset($this->limit) && $this->notORM->driver == "oci") {
			$where[] = ($this->offset ? "rownum > $this->offset AND " : "") . "rownum <= " . ($this->limit + $this->offset);
		}
		if ($where) {
			$return = " WHERE (" . implode(") AND (", $where) . ")$return";
		}
		
		if ($this->notORM->driver != "oci" && $this->notORM->driver != "dblib") {
			$return .= $this->limitString($this->limit, $this->offset);
		}
		return $return;
	}
	
	protected function topString() {
		if (isset($this->limit) && $this->notORM->driver == "dblib") {
			return " TOP ($this->limit)"; //! offset is not supported
		}
		return "";
	}
	
	protected function createJoins($val) {
		$return = array();
		preg_match_all('~\\b([a-z_][a-z0-9_.]*)\\.[a-z_*]~i', $val, $matches, PREG_SET_ORDER);
		foreach ($matches as $match) {
			$parent = $this->table;
			if ($match[1] != $parent) { // case-sensitive
				foreach (explode(".", $match[1]) as $name) {
					$table = $this->notORM->structure->getReferencedTable($name, $parent);
					$column = $this->notORM->structure->getReferencedColumn($name, $parent);
					$primary = $this->notORM->structure->getPrimary($table);
					$return[$name] = " LEFT JOIN $table" . ($table != $name ? " AS $name" : "") . " ON $parent.$column = $name.$primary"; // should use alias if the table is used on more places
					$parent = $name;
				}
			}
		}
		return $return;
	}
	
	/** Get SQL query
	* @return string
	*/
	function __toString() {
		$return = "SELECT" . $this->topString() . " ";
		$join = $this->createJoins(implode(",", $this->conditions) . "," . implode(",", $this->select) . ",$this->group,$this->having," . implode(",", $this->order));
		if (!isset($this->rows) && $this->notORM->cache && !is_string($this->accessed)) {
			$this->accessed = $this->notORM->cache->load("$this->table;" . implode(",", $this->conditions));
			$this->access = $this->accessed;
		}
		if ($this->select) {
			$return .= $this->removeExtraDots(implode(", ", $this->select));
		} elseif ($this->accessed) {
			$return .= ($join ? "$this->table." : "") . implode(", " . ($join ? "$this->table." : ""), array_keys($this->accessed));
		} else {
			$return .= ($join ? "$this->table." : "") . "*";
		}
		$return .= " FROM $this->table" . implode($join) . $this->whereString();
		if ($this->union) {
			$return = ($this->notORM->driver == "sqlite" || $this->notORM->driver == "oci" ? $return : "($return)") . implode($this->union);
			if ($this->unionOrder) {
				$return .= " ORDER BY " . implode(", ", $this->unionOrder);
			}
			$return .= $this->limitString($this->unionLimit, $this->unionOffset);
		}
		return $return;
	}
	
	protected function query($query) {
		if ($this->notORM->debug) {
			if (!is_callable($this->notORM->debug)) {
				$parameters = "";
				if ($this->parameters) {
					$parameters = " -- " . implode(", ", array_map(array($this->notORM->connection, 'quote'), $this->parameters));
				}
				$pattern = '(^' . preg_quote(dirname(__FILE__)) . '(\\.php$|[/\\\\]))'; // can be static
				foreach (debug_backtrace() as $backtrace) {
					if (!preg_match($pattern, $backtrace["file"])) { // stop on first file outside NotORM source codes
						break;
					}
				}
				fwrite(STDERR, "$backtrace[file]:$backtrace[line]:$query;$parameters\n");
			} elseif (call_user_func($this->notORM->debug, $query, $this->parameters) === false) {
				return false;
			}
		}
		$return = $this->notORM->connection->prepare($query);
		if (!$return || !$return->execute($this->parameters)) {
			return false;
		}
		return $return;
	}
	
	protected function quote($val) {
		if (!isset($val)) {
			return "NULL";
		}
		if ($val instanceof DateTime) {
			$val = $val->format("Y-m-d H:i:s"); //! may be driver specific
		}
		return (is_int($val) || is_float($val) || $val instanceof NotORM_Literal // number or SQL code - for example "NOW()"
			? (string) $val
			: $this->notORM->connection->quote($val)
		);
	}
	
	/** Insert row in a table
	* @param mixed array($column => $value)|Traversable for single row insert or NotORM_Result|string for INSERT ... SELECT
	* @param ... used for extended insert
	* @return NotORM_Row inserted row or false in case of an error or number of affected rows for INSERT ... SELECT
	*/
	function insert($data) {
		if ($this->notORM->freeze) {
			return false;
		}
		if ($data instanceof NotORM_Result) {
			$data = (string) $data;
		} elseif ($data instanceof Traversable) {
			$data = iterator_to_array($data);
		}
		$insert = $data;
		if (is_array($data)) {
			$values = array();
			foreach (func_get_args() as $val) {
				if ($val instanceof Traversable) {
					$val = iterator_to_array($val);
				}
				$values[] = "(" . implode(", ", array_map(array($this, 'quote'), $val)) . ")";
			}
			//! driver specific empty $data and extended insert
			$insert = "(" . implode(", ", array_keys($data)) . ") VALUES " . implode(", ", $values);
		}
		// requires empty $this->parameters
		$return = $this->query("INSERT INTO $this->table $insert");
		if (!$return) {
			return false;
		}
		$this->rows = null;
		if (!is_array($data)) {
			return $return->rowCount();
		}
		if (!isset($data[$this->primary]) && ($id = $this->notORM->connection->lastInsertId($this->notORM->structure->getSequence($this->table)))) {
			$data[$this->primary] = $id;
		}
		return new NotORM_Row($data, $this);
	}
	
	/** Update all rows in result set
	* @param array ($column => $value)
	* @return int number of affected rows or false in case of an error
	*/
	function update(array $data) {
		if ($this->notORM->freeze) {
			return false;
		}
		if (!$data) {
			return 0;
		}
		$values = array();
		foreach ($data as $key => $val) {
			// doesn't use binding because $this->parameters can be filled by ? or :name
			$values[] = "$key = " . $this->quote($val);
		}
		// joins in UPDATE are supported only in MySQL
		$return = $this->query("UPDATE" . $this->topString() . " $this->table SET " . implode(", ", $values) . $this->whereString());
		if (!$return) {
			return false;
		}
		return $return->rowCount();
	}
	
	/** Insert row or update if it already exists
	* @param array ($column => $value)
	* @param array ($column => $value)
	* @param array ($column => $value), empty array means use $insert
	* @return int number of affected rows or false in case of an error
	*/
	function insert_update(array $unique, array $insert, array $update = array()) {
		if (!$update) {
			$update = $insert;
		}
		$insert = $unique + $insert;
		$values = "(" . implode(", ", array_keys($insert)) . ") VALUES (" . implode(", ", array_map(array($this, 'quote'), $insert)) . ")";
		if ($this->notORM->driver == "mysql") {
			$set = array();
			foreach ($update as $key => $val) {
				$set[] = "$key = " . $this->quote($val);
			}
			return $this->insert("$values ON DUPLICATE KEY UPDATE " . implode(", ", $set));
		} else {
			$connection = $this->notORM->connection;
			$errorMode = $connection->getAttribute(PDO::ATTR_ERRMODE);
			$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			try {
				$return = $this->insert($values);
				$connection->setAttribute(PDO::ATTR_ERRMODE, $errorMode);
				return $return;
			} catch (PDOException $e) {
				$connection->setAttribute(PDO::ATTR_ERRMODE, $errorMode);
				if ($e->getCode() == "23000") { // "23000" - duplicate key
					$clone = clone $this;
					$return = $clone->where($unique)->update($update);
					return ($return ? $return + 1 : $return);
				}
				if ($errorMode == PDO::ERRMODE_EXCEPTION) {
					throw $e;
				} elseif ($errorMode == PDO::ERRMODE_WARNING) {
					trigger_error("PDOStatement::execute(): " . $e->getMessage(), E_USER_WARNING); // E_WARNING is unusable
				}
			}
		}
	}
	
	/** Delete all rows in result set
	* @return int number of affected rows or false in case of an error
	*/
	function delete() {
		if ($this->notORM->freeze) {
			return false;
		}
		$return = $this->query("DELETE" . $this->topString() . " FROM $this->table" . $this->whereString());
		if (!$return) {
			return false;
		}
		return $return->rowCount();
	}
	
	/** Add select clause, more calls appends to the end
	* @param string for example "column, MD5(column) AS column_md5"
	* @param string ...
	* @return NotORM_Result fluent interface
	*/
	function select($columns) {
		$this->__destruct();
		foreach (func_get_args() as $columns) {
			$this->select[] = $columns;
		}
		return $this;
	}
	
	/** Add where condition, more calls appends with AND
	* @param string condition possibly containing ? or :name
	* @param mixed array accepted by PDOStatement::execute or a scalar value
	* @param mixed ...
	* @return NotORM_Result fluent interface
	*/
	function where($condition, $parameters = array()) {
		if (is_array($condition)) { // where(array("column1" => 1, "column2 > ?" => 2))
			foreach ($condition as $key => $val) {
				$this->where($key, $val);
			}
			return $this;
		}
		$this->__destruct();
		$this->conditions[] = $condition;
		$condition = $this->removeExtraDots($condition);
		$args = func_num_args();
		if ($args != 2 || strpbrk($condition, "?:")) { // where("column < ? OR column > ?", array(1, 2))
			if ($args != 2 || !is_array($parameters)) { // where("column < ? OR column > ?", 1, 2)
				$parameters = func_get_args();
				array_shift($parameters);
			}
			$this->parameters = array_merge($this->parameters, $parameters);
		} elseif (is_null($parameters)) { // where("column", null)
			$condition .= " IS NULL";
		} elseif ($parameters instanceof NotORM_Result) { // where("column", $db->$table())
			$clone = clone $parameters;
			if (!$clone->select) {
				$clone->select = array($this->notORM->structure->getPrimary($clone->table));
			}
			if ($this->notORM->driver != "mysql") {
				$condition .= " IN ($clone)";
				$this->parameters = array_merge($this->parameters, $clone->parameters);
			} else {
				$in = array();
				foreach ($clone as $row) {
					$val = implode(", ", array_map(array($this, 'quote'), iterator_to_array($row)));
					$in[] = (count($row) == 1 ? $val : "($val)");
				}
				$condition .= " IN (" . ($in ? implode(", ", $in) : "NULL") . ")";
			}
		} elseif (!is_array($parameters)) { // where("column", "x")
			$condition .= " = " . $this->quote($parameters);
		} else { // where("column", array(1, 2))
			$in = "NULL";
			if ($parameters) {
				$in = implode(", ", array_map(array($this, 'quote'), $parameters));
			}
			$condition .= " IN ($in)";
		}
		$this->where[] = $condition;
		return $this;
	}
	
	/** Shortcut for where()
	* @param string
	* @param mixed
	* @param mixed ...
	* @return NotORM_Result fluent interface
	*/
	function __invoke($where, $parameters = array()) {
		$args = func_get_args();
		return call_user_func_array(array($this, 'where'), $args);
	}
	
	/** Add order clause, more calls appends to the end
	* @param string for example "column1, column2 DESC"
	* @param string ...
	* @return NotORM_Result fluent interface
	*/
	function order($columns) {
		$this->rows = null;
		foreach (func_get_args() as $columns) {
			if ($this->union) {
				$this->unionOrder[] = $columns;
			} else {
				$this->order[] = $columns;
			}
		}
		return $this;
	}
	
	/** Set limit clause, more calls rewrite old values
	* @param int
	* @param int
	* @return NotORM_Result fluent interface
	*/
	function limit($limit, $offset = null) {
		$this->rows = null;
		if ($this->union) {
			$this->unionLimit = $limit;
			$this->unionOffset = $offset;
		} else {
			$this->limit = $limit;
			$this->offset = $offset;
		}
		return $this;
	}
	
	/** Set group clause, more calls rewrite old values
	* @param string
	* @param string
	* @return NotORM_Result fluent interface
	*/
	function group($columns, $having = "") {
		$this->__destruct();
		$this->group = $columns;
		$this->having = $having;
		return $this;
	}
	
	/** 
	* @param NotORM_Result
	* @param bool
	* @return NotORM_Result fluent interface
	*/
	function union(NotORM_Result $result, $all = false) {
		$this->union[] = " UNION " . ($all ? "ALL " : "") . ($this->notORM->driver == "sqlite" || $this->notORM->driver == "oci" ? $result : "($result)");
		$this->parameters = array_merge($this->parameters, $result->parameters);
		return $this;
	}
	
	/** Execute aggregation function
	* @param string
	* @return string
	*/
	function aggregation($function) {
		$join = $this->createJoins(implode(",", $this->conditions) . ",$function");
		$query = "SELECT $function FROM $this->table" . implode($join);
		if ($this->where) {
			$query .= " WHERE (" . implode(") AND (", $this->where) . ")";
		}
		foreach ($this->query($query)->fetch() as $return) {
			return $return;
		}
	}
	
	/** Count number of rows
	* @param string
	* @return int
	*/
	function count($column = "") {
		if (!$column) {
			$this->execute();
			return count($this->data);
		}
		return $this->aggregation("COUNT($column)");
	}
	
	/** Return minimum value from a column
	* @param string
	* @return int
	*/
	function min($column) {
		return $this->aggregation("MIN($column)");
	}
	
	/** Return maximum value from a column
	* @param string
	* @return int
	*/
	function max($column) {
		return $this->aggregation("MAX($column)");
	}
	
	/** Return sum of values in a column
	* @param string
	* @return int
	*/
	function sum($column) {
		return $this->aggregation("SUM($column)");
	}
	
	/** Execute built query
	* @return null
	*/
	protected function execute() {
		if (!isset($this->rows)) {
			$result = false;
			$exception = null;
			try {
				$result = $this->query($this->__toString());
			} catch (PDOException $exception) {
				// handled later
			}
			if (!$result) {
				if (!$this->select && $this->accessed) {
					$this->accessed = '';
					$this->access = array();
					$result = $this->query($this->__toString());
				} elseif ($exception) {
					throw $exception;
				}
			}
			$this->rows = array();
			if ($result) {
				$result->setFetchMode(PDO::FETCH_ASSOC);
				foreach ($result as $key => $row) {
					if (isset($row[$this->primary])) {
						$key = $row[$this->primary];
						if (!is_string($this->access)) {
							$this->access[$this->primary] = true;
						}
					}
					$this->rows[$key] = new $this->notORM->rowClass($row, $this);
				}
			}
			$this->data = $this->rows;
		}
	}
	
	/** Fetch next row of result
	* @return NotORM_Row or false if there is no row
	*/
	function fetch() {
		$this->execute();
		$return = current($this->data);
		next($this->data);
		return $return;
	}
	
	/** Fetch all rows as associative array
	* @param string
	* @param string column name used for an array value or an empty string for the whole row
	* @return array
	*/
	function fetchPairs($key, $value = '') {
		$return = array();
		$clone = clone $this;
		if ($value != "") {
			$clone->select = array();
			$clone->select("$key, $value"); // MultiResult adds its column
		} elseif ($clone->select) {
			array_unshift($clone->select, $key);
		} else {
			$clone->select = array("$key, $this->table.*");
		}
		foreach ($clone as $row) {
			$values = array_values(iterator_to_array($row));
			$return[$values[0]] = ($value != "" ? $values[1] : $row);
		}
		return $return;
	}
	
	protected function access($key, $delete = false) {
		if ($delete) {
			if (is_array($this->access)) {
				$this->access[$key] = false;
			}
			return false;
		}
		if (!isset($key)) {
			$this->access = '';
		} elseif (!is_string($this->access)) {
			$this->access[$key] = true;
		}
		if (!$this->select && $this->accessed && (!isset($key) || !isset($this->accessed[$key]))) {
			$this->accessed = '';
			$this->rows = null;
			return true;
		}
		return false;
	}
	
	// Iterator implementation (not IteratorAggregate because $this->data can be changed during iteration)
	
	function rewind() {
		$this->execute();
		$this->keys = array_keys($this->data);
		reset($this->keys);
	}
	
	/** @return NotORM_Row */
	function current() {
		return $this->data[current($this->keys)];
	}
	
	/** @return string row ID */
	function key() {
		return current($this->keys);
	}
	
	function next() {
		next($this->keys);
	}
	
	function valid() {
		return current($this->keys) !== false;
	}
	
	// ArrayAccess implementation
	
	/** Test if row exists
	* @param string row ID or array for where conditions
	* @return bool
	*/
	function offsetExists($key) {
		$row = $this->offsetGet($key);
		return isset($row);
	}
	
	/** Get specified row
	* @param string row ID or array for where conditions
	* @return NotORM_Row or null if there is no such row
	*/
	function offsetGet($key) {
		if ($this->single && !isset($this->data)) {
			$clone = clone $this;
			if (is_array($key)) {
				$clone->where($key)->limit(1);
			} else {
				$clone->where($this->primary, $key);
			}
			$return = $clone->fetch();
			if (!$return) {
				return null;
			}
			return $return;
		} else {
			$this->execute();
			if (is_array($key)) {
				foreach ($this->data as $row) {
					foreach ($key as $k => $v) {
						if ((isset($v) ? $row[$k] != $v : $row[$k] !== $v)) {
							break;
						}
						return $row;
					}
				}
			} elseif (isset($this->data[$key])) {
				return $this->data[$key];
			}
		}
	}
	
	/** Mimic row
	* @param string row ID
	* @param NotORM_Row
	* @return null
	*/
	function offsetSet($key, $value) {
		$this->execute();
		$this->data[$key] = $value;
	}
	
	/** Remove row from result set
	* @param string row ID
	* @return null
	*/
	function offsetUnset($key) {
		$this->execute();
		unset($this->data[$key]);
	}
	
}

class NotORM_Literal {
	/** @var string */
	protected $value = '';
	
	/** Create literal value
	* @param string
	*/
	function __construct($value) {
		$this->value = $value;
	}
	
	/** Get literal value
	* @return string
	*/
	function __toString() {
		return $this->value;
	}
	
}


/** Loading and saving data, it's only cache so load() does not need to block until save()
*/
interface NotORM_Cache {
	
	/** Load stored data
	* @param string
	* @return mixed or null if not found
	*/
	function load($key);
	
	/** Save data
	* @param string
	* @param mixed
	* @return null
	*/
	function save($key, $data);
	
}



/** Cache using $_SESSION["NotORM"]
*/
class NotORM_Cache_Session implements NotORM_Cache {
	
	function load($key) {
		if (!isset($_SESSION["NotORM"][$key])) {
			return null;
		}
		return $_SESSION["NotORM"][$key];
	}
	
	function save($key, $data) {
		$_SESSION["NotORM"][$key] = $data;
	}
	
}



/** Cache using file
*/
class NotORM_Cache_File implements NotORM_Cache {
	private $filename, $data = array();
	
	function __construct($filename) {
		$this->filename = $filename;
		$this->data = unserialize(@file_get_contents($filename)); // @ - file may not exist
	}
	
	function load($key) {
		if (!isset($this->data[$key])) {
			return null;
		}
		return $this->data[$key];
	}
	
	function save($key, $data) {
		if (!isset($this->data[$key]) || $this->data[$key] !== $data) {
			$this->data[$key] = $data;
			file_put_contents($this->filename, serialize($this->data), LOCK_EX);
		}
	}
	
}



/** Cache using PHP include
*/
class NotORM_Cache_Include implements NotORM_Cache {
	private $filename, $data = array();
	
	function __construct($filename) {
		$this->filename = $filename;
		$this->data = @include realpath($filename); // @ - file may not exist, realpath() to not include from include_path //! silently falls with syntax error and fails with unreadable file
		if (!is_array($this->data)) { // empty file returns 1
			$this->data = array();
		}
	}
	
	function load($key) {
		if (!isset($this->data[$key])) {
			return null;
		}
		return $this->data[$key];
	}
	
	function save($key, $data) {
		if (!isset($this->data[$key]) || $this->data[$key] !== $data) {
			$this->data[$key] = $data;
			file_put_contents($this->filename, '<?php return ' . var_export($this->data, true) . ';', LOCK_EX);
		}
	}
	
}



/** Cache storing data to the "notorm" table in database
*/
class NotORM_Cache_Database implements NotORM_Cache {
	private $connection;
	
	function __construct(PDO $connection) {
		$this->connection = $connection;
	}
	
	function load($key) {
		$result = $this->connection->prepare("SELECT data FROM notorm WHERE id = ?");
		$result->execute(array($key));
		$return = $result->fetchColumn();
		if (!$return) {
			return null;
		}
		return unserialize($return);
	}
	
	function save($key, $data) {
		// REPLACE is not supported by PostgreSQL and MS SQL
		$parameters = array(serialize($data), $key);
		$result = $this->connection->prepare("UPDATE notorm SET data = ? WHERE id = ?");
		$result->execute($parameters);
		if (!$result->rowCount()) {
			$result = $this->connection->prepare("INSERT INTO notorm (data, id) VALUES (?, ?)");
			try {
				@$result->execute($parameters); // @ - ignore duplicate key error
			} catch (PDOException $e) {
				if ($e->getCode() != "23000") { // "23000" - duplicate key
					throw $e;
				}
			}
		}
	}
	
}



// eAccelerator - user cache is obsoleted



/** Cache using "NotORM." prefix in Memcache
*/
class NotORM_Cache_Memcache implements NotORM_Cache {
	private $memcache;
	
	function __construct(Memcache $memcache) {
		$this->memcache = $memcache;
	}
	
	function load($key) {
		$return = $this->memcache->get("NotORM.$key");
		if ($return === false) {
			return null;
		}
		return $return;
	}
	
	function save($key, $data) {
		$this->memcache->set("NotORM.$key", $data);
	}
	
}



/** Cache using "NotORM." prefix in APC
*/
class NotORM_Cache_APC implements NotORM_Cache {
	
	function load($key) {
		$return = apc_fetch("NotORM.$key", $success);
		if (!$success) {
			return null;
		}
		return $return;
	}
	
	function save($key, $data) {
		apc_store("NotORM.$key", $data);
	}
	
}


/** Representation of filtered table grouped by some column
*/
class NotORM_MultiResult extends NotORM_Result {
	private $result, $column, $active;
	
	/** @access protected must be public because it is called from Row */
	function __construct($table, NotORM_Result $result, $column, $active) {
		parent::__construct($table, $result->notORM);
		$this->result = $result;
		$this->column = $column;
		$this->active = $active;
	}
	
	/** Specify referencing column
	* @param string
	* @return NotORM_MultiResult fluent interface
	*/
	function via($column) {
		$this->column = $column;
		return $this;
	}
	
	function insert($data) {
		$args = array();
		foreach (func_get_args() as $data) {
			if ($data instanceof Traversable && !$data instanceof NotORM_Result) {
				$data = iterator_to_array($data);
			}
			if (is_array($data)) {
				$data[$this->column] = $this->active;
			}
			$args[] = $data;
		}
		return call_user_func_array(array($this, 'parent::insert'), $args); // works since PHP 5.1.2, array('parent', 'insert') issues E_STRICT in 5.1.2 <= PHP < 5.3.0
	}
	
	function update(array $data) {
		$where = $this->where;
		$this->where[0] = "$this->column = " . $this->notORM->connection->quote($this->active);
		$return = parent::update($data);
		$this->where = $where;
		return $return;
	}
	
	function delete() {
		$where = $this->where;
		$this->where[0] = "$this->column = " . $this->notORM->connection->quote($this->active);
		$return = parent::delete();
		$this->where = $where;
		return $return;
	}
	
	function select($columns) {
		$args = func_get_args();
		if (!$this->select) {
			$args[] = "$this->table.$this->column";
		}
		return call_user_func_array(array($this, 'parent::select'), $args);
	}
	
	function order($columns) {
		if (!$this->order) { // improve index utilization
			$this->order[] = "$this->table.$this->column" . (preg_match('~\\bDESC$~i', $columns) ? " DESC" : "");
		}
		$args = func_get_args();
		return call_user_func_array(array($this, 'parent::order'), $args);
	}
	
	function aggregation($function) {
		$join = $this->createJoins(implode(",", $this->conditions) . ",$function");
		$column = ($join ? "$this->table." : "") . $this->column;
		$query = "SELECT $function, $column FROM $this->table" . implode($join);
		if ($this->where) {
			$query .= " WHERE (" . implode(") AND (", $this->where) . ")";
		}
		$query .= " GROUP BY $column";
		$aggregation = &$this->result->aggregation[$query];
		if (!isset($aggregation)) {
			$aggregation = array();
			foreach ($this->query($query, $this->parameters) as $row) {
				$aggregation[$row[$this->column]] = $row;
			}
		}
		if (isset($aggregation[$this->active])) {
			foreach ($aggregation[$this->active] as $return) {
				return $return;
			}
		}
	}
	
	function count($column = "") {
		$return = parent::count($column);
		return (isset($return) ? $return : 0);
	}
	
	protected function execute() {
		if (!isset($this->rows)) {
			$referencing = &$this->result->referencing[$this->__toString()];
			if (!isset($referencing)) {
				$limit = $this->limit;
				$rows = count($this->result->rows);
				if ($this->limit && $rows > 1) {
					$this->limit = null;
				}
				parent::execute();
				$this->limit = $limit;
				$referencing = array();
				$offset = array();
				foreach ($this->rows as $key => $row) {
					$ref = &$referencing[$row[$this->column]];
					$skip = &$offset[$row[$this->column]];
					if (!isset($limit) || $rows <= 1 || (count($ref) < $limit && $skip >= $this->offset)) {
						$ref[$key] = $row;
					} else {
						unset($this->rows[$key]);
					}
					$skip++;
					unset($ref, $skip);
				}
			}
			$this->data = &$referencing[$this->active];
			if (!isset($this->data)) {
				$this->data = array();
			}
		}
	}
	
}

/** Information about tables and columns structure
*/
interface NotORM_Structure {
	
	/** Get primary key of a table in $db->$table()
	* @param string
	* @return string
	*/
	function getPrimary($table);
	
	/** Get column holding foreign key in $table[$id]->$name()
	* @param string
	* @param string
	* @return string
	*/
	function getReferencingColumn($name, $table);
	
	/** Get target table in $table[$id]->$name()
	* @param string
	* @param string
	* @return string
	*/
	function getReferencingTable($name, $table);
	
	/** Get column holding foreign key in $table[$id]->$name
	* @param string
	* @param string
	* @return string
	*/
	function getReferencedColumn($name, $table);
	
	/** Get table holding foreign key in $table[$id]->$name
	* @param string
	* @param string
	* @return string
	*/
	function getReferencedTable($name, $table);
	
	/** Get sequence name, used by insert
	* @param string
	*/
	function getSequence($table);
	
}



/** Structure described by some rules
*/
class NotORM_Structure_Convention implements NotORM_Structure {
	protected $primary, $foreign, $table;
	
	/** Create conventional structure
	* @param string %s stands for table name
	* @param string %1$s stands for key used after ->, %2$s for table name
	* @param string %1$s stands for key used after ->, %2$s for table name
	*/
	function __construct($primary = 'id', $foreign = '%s_id', $table = '%s') {
		$this->primary = $primary;
		$this->foreign = $foreign;
		$this->table = $table;
	}
	
	function getPrimary($table) {
		return sprintf($this->primary, $table);
	}
	
	function getReferencingColumn($name, $table) {
		return $this->getReferencedColumn($table, $name);
	}
	
	function getReferencingTable($name, $table) {
		return $name;
	}
	
	function getReferencedColumn($name, $table) {
		if ($this->table != '%s' && preg_match('(^' . str_replace('%s', '(.*)', preg_quote($this->table)) . '$)', $name, $match)) {
			$name = $match[1];
		}
		return sprintf($this->foreign, $name, $table);
	}
	
	function getReferencedTable($name, $table) {
		return sprintf($this->table, $name, $table);
	}
	
	function getSequence($table) {
		return null;
	}
	
}



/** Structure reading meta-informations from the database
*/
class NotORM_Structure_Discovery implements NotORM_Structure {
	protected $connection, $cache, $structure = array();
	protected $foreign;
	
	/** Create autodisovery structure
	* @param PDO
	* @param NotORM_Cache
	* @param string use "%s_id" to access $name . "_id" column in $row->$name
	*/
	function __construct(PDO $connection, NotORM_Cache $cache = null, $foreign = '%s') {
		$this->connection = $connection;
		$this->cache = $cache;
		$this->foreign = $foreign;
		if ($cache) {
			$this->structure = $cache->load("structure");
		}
	}
	
	/** Save data to cache
	*/
	function __destruct() {
		if ($this->cache) {
			$this->cache->save("structure", $this->structure);
		}
	}
	
	function getPrimary($table) {
		$return = &$this->structure["primary"][$table];
		if (!isset($return)) {
			$return = "";
			foreach ($this->connection->query("EXPLAIN $table") as $column) {
				if ($column[3] == "PRI") { // 3 - "Key" is not compatible with PDO::CASE_LOWER
					if ($return != "") {
						$return = ""; // multi-column primary key is not supported
						break;
					}
					$return = $column[0];
				}
			}
		}
		return $return;
	}
	
	function getReferencingColumn($name, $table) {
		$name = strtolower($name);
		$return = &$this->structure["referencing"][$table];
		if (!isset($return[$name])) {
			foreach ($this->connection->query("
				SELECT TABLE_NAME, COLUMN_NAME
				FROM information_schema.KEY_COLUMN_USAGE
				WHERE TABLE_SCHEMA = DATABASE()
				AND REFERENCED_TABLE_SCHEMA = DATABASE()
				AND REFERENCED_TABLE_NAME = " . $this->connection->quote($table) . "
				AND REFERENCED_COLUMN_NAME = " . $this->connection->quote($this->getPrimary($table)) //! may not reference primary key
			) as $row) {
				$return[strtolower($row[0])] = $row[1];
			}
		}
		return $return[$name];
	}
	
	function getReferencingTable($name, $table) {
		return $name;
	}
	
	function getReferencedColumn($name, $table) {
		return sprintf($this->foreign, $name);
	}
	
	function getReferencedTable($name, $table) {
		$column = strtolower($this->getReferencedColumn($name, $table));
		$return = &$this->structure["referenced"][$table];
		if (!isset($return[$column])) {
			foreach ($this->connection->query("
				SELECT COLUMN_NAME, REFERENCED_TABLE_NAME
				FROM information_schema.KEY_COLUMN_USAGE
				WHERE TABLE_SCHEMA = DATABASE()
				AND REFERENCED_TABLE_SCHEMA = DATABASE()
				AND TABLE_NAME = " . $this->connection->quote($table) . "
			") as $row) {
				$return[strtolower($row[0])] = $row[1];
			}
		}
		return $return[$column];
	}
	
	function getSequence($table) {
		return null;
	}
	
}




/** Single row representation
*/
class NotORM_Row extends NotORM_Abstract implements IteratorAggregate, ArrayAccess {
	private $modified = array();
	protected $row, $result;
	
	protected function __construct(array $row, NotORM_Result $result) {
		$this->row = $row;
		$this->result = $result;
	}
	
	/** Get primary key value
	* @return string
	*/
	function __toString() {
		return (string) $this[$this->result->primary]; // (string) - PostgreSQL returns int
	}
	
	/** Get referenced row
	* @param string
	* @return NotORM_Row or null if the row does not exist
	*/
	function __get($name) {
		$column = $this->result->notORM->structure->getReferencedColumn($name, $this->result->table);
		$referenced = &$this->result->referenced[$name];
		if (!isset($referenced)) {
			$keys = array();
			foreach ($this->result->rows as $row) {
				if ($row[$column] !== null) {
					$keys[$row[$column]] = null;
				}
			}
			if ($keys) {
				$table = $this->result->notORM->structure->getReferencedTable($name, $this->result->table);
				$referenced = new NotORM_Result($table, $this->result->notORM);
				$referenced->where("$table." . $this->result->notORM->structure->getPrimary($table), array_keys($keys));
			} else {
				$referenced = array();
			}
		}
		if (!isset($referenced[$this[$column]])) { // referenced row may not exist
			return null;
		}
		return $referenced[$this[$column]];
	}
	
	/** Test if referenced row exists
	* @param string
	* @return bool
	*/
	function __isset($name) {
		return ($this->__get($name) !== null);
	}
	
	// __set is not defined to allow storing custom references (undocumented)
	
	/** Get referencing rows
	* @param string table name
	* @param array (["condition"[, array("value")]])
	* @return NotORM_MultiResult
	*/
	function __call($name, array $args) {
		$table = $this->result->notORM->structure->getReferencingTable($name, $this->result->table);
		$column = $this->result->notORM->structure->getReferencingColumn($table, $this->result->table);
		$return = new NotORM_MultiResult($table, $this->result, $column, $this[$this->result->primary]);
		$return->where("$table.$column", array_keys((array) $this->result->rows)); // (array) - is null after insert
		if ($args) {
			call_user_func_array(array($return, 'where'), $args);
		}
		return $return;
	}
	
	/** Update row
	* @param array or null for all modified values
	* @return int number of affected rows or false in case of an error
	*/
	function update($data = null) {
		// update is an SQL keyword
		if (!isset($data)) {
			$data = $this->modified;
		}
		return $this->result->notORM->__call($this->result->table, array($this->result->primary, $this[$this->result->primary]))->update($data);
	}
	
	/** Delete row
	* @return int number of affected rows or false in case of an error
	*/
	function delete() {
		// delete is an SQL keyword
		return $this->result->notORM->__call($this->result->table, array($this->result->primary, $this[$this->result->primary]))->delete();
	}
	
	protected function access($key, $delete = false) {
		if ($this->result->notORM->cache && !isset($this->modified[$key]) && $this->result->access($key, $delete)) {
			$this->row = $this->result[$this->row[$this->result->primary]]->row;
		}
	}
	
	// IteratorAggregate implementation
	
	function getIterator() {
		$this->access(null);
		return new ArrayIterator($this->row);
	}
	
	// ArrayAccess implementation
	
	/** Test if column exists
	* @param string column name
	* @return bool
	*/
	function offsetExists($key) {
		$this->access($key);
		$return = array_key_exists($key, $this->row);
		if (!$return) {
			$this->access($key, true);
		}
		return $return;
	}
	
	/** Get value of column
	* @param string column name
	* @return string
	*/
	function offsetGet($key) {
		$this->access($key);
		if (!array_key_exists($key, $this->row)) {
			$this->access($key, true);
		}
		return $this->row[$key];
	}
	
	/** Store value in column
	* @param string column name
	* @return null
	*/
	function offsetSet($key, $value) {
		$this->row[$key] = $value;
		$this->modified[$key] = $value;
	}
	
	/** Remove column from data
	* @param string column name
	* @return null
	*/
	function offsetUnset($key) {
		unset($this->row[$key]);
		unset($this->modified[$key]);
	}
	
}




// friend visibility emulation
abstract class NotORM_Abstract {
	protected $connection, $driver, $structure, $cache;
	protected $notORM, $table, $primary, $rows, $referenced = array();
	
	protected $debug = false;
	protected $freeze = false;
	protected $rowClass = 'NotORM_Row';
	
	abstract protected function __construct();
	
	protected function access($key, $delete = false) {
	}
	
}



/** Database representation
* @property-write mixed $debug = false Enable debuging queries, true for fwrite(STDERR, $query), callback($query, $parameters) otherwise
* @property-write bool $freeze = false Disable persistence
* @property-write string $rowClass = 'NotORM_Row' Class used for created objects
* @property-write string $transaction Assign 'BEGIN', 'COMMIT' or 'ROLLBACK' to start or stop transaction
*/
class NotORM extends NotORM_Abstract {
	
	/** Create database representation
	* @param PDO
	* @param NotORM_Structure or null for new NotORM_Structure_Convention
	* @param NotORM_Cache or null for no cache
	*/
	function __construct(PDO $connection, NotORM_Structure $structure = null, NotORM_Cache $cache = null) {
		$this->connection = $connection;
		$this->driver = $connection->getAttribute(PDO::ATTR_DRIVER_NAME);
		if (!isset($structure)) {
			$structure = new NotORM_Structure_Convention;
		}
		$this->structure = $structure;
		$this->cache = $cache;
	}
	
	/** Get table data to use as $db->table[1]
	* @param string
	* @return NotORM_Result
	*/
	function __get($table) {
		return new NotORM_Result($this->structure->getReferencingTable($table, ''), $this, true);
	}
	
	/** Set write-only properties
	* @return null
	*/
	function __set($name, $value) {
		if ($name == "debug" || $name == "freeze" || $name == "rowClass") {
			$this->$name = $value;
		}
		if ($name == "transaction") {
			switch (strtoupper($value)) {
				case "BEGIN": return $this->connection->beginTransaction();
				case "COMMIT": return $this->connection->commit();
				case "ROLLBACK": return $this->connection->rollback();
			}
		}
	}
	
	/** Get table data
	* @param string
	* @param array (["condition"[, array("value")]]) passed to NotORM_Result::where()
	* @return NotORM_Result
	*/
	function __call($table, array $where) {
		$return = new NotORM_Result($this->structure->getReferencingTable($table, ''), $this);
		if ($where) {
			call_user_func_array(array($return, 'where'), $where);
		}
		return $return;
	}
	
}
