<?php
//class RecordModel implements ArrayAccess{
//	protected $tableName;
//	protected $record;
//	
//	public function __construct(Record $r=null) {
//		if ($r === null) {
//			$adapter = Database::table($this->tableName);
//			$this->record = $table->newRecord();
//		} else {
//			$this->record = $r;
//		}
//	}
//	
//	public static function load($id_or_ids) {
//		$adapter = Database::table($this->$tableName);
//		if (is_array($id_or_ids)) {
//			$returnme = [];
//			foreach ($adapter->load($id_or_ids) as $record) {
//				$returnme[] = new self($record);
//			}
//			return $returnme;
//		} else {
//			$record = $table->load($id_or_ids);
//			return new self($record);
//		}
//	}
//	
//	public abstract function validate();
//	
//	public function __get($prop) {		
//		return $this[$prop];
//	}
//
//	public function __set($prop, $val) {
//		return $this[$prop] = $val;
//	}
//	
//	public function offsetExists($offset ) {
//		return isset($this->record[$offset]);
//	}
//
//	public function offsetGet($offset) {
//		return $this->record[$offset];
//	}
//
//	public function offsetSet($offset, $value ) {
//		$this->record[$offset] = $value;
//		return $value;
//	}
//
//	public function offsetUnset($offset) {
//		unset($this->record[$offset]);
//	}	
//}

/**
 * Database record abstraction
 */
class Record implements ArrayAccess {
	private $tableAdapter;
	private $fields = [];
	private $dirty = [];

	public function __construct(TableAdapter $tableAdapter, array $data=[]) {
		$this->tableAdapter = $tableAdapter;
		$this->fields = $data;		
	}

	public function __get($prop) {		
		return $this[$prop];
	}

	public function __set($prop, $val) {
		return $this[$prop] = $val;
	}

	/**
	 * Attempts to insert this record, if there is any sort of key conflict this record is ignored.
	 */
	public function insertOrIgnore() {
		return $this->tableAdapter->insertOrIgnore($this);		
	}

	/**
	 * Attempts to insert this record, if there is any sort of key conflict the conflicting database row will be instead will be updated with the values from this record.
	 */
	public function insertOrUpdate() {		
		return $this->tableAdapter->insertOrUpdate($this);
	}

	/**
	 * Saves the record to the database
	 * @return boolean True on success, false on failure
	 */
	public function save() {
		return $this->tableAdapter->save($this);
	}

	/**
	 * Deletes this record from the database
	 * @return boolean True on success, false on failure
	 */
	public function delete() {
		return $this->tableAdapter->deleteRecord($this);
	}

	/**
	 * Flattens the data into a simple array
	 * @return array
	 */	
	public function toArray() {
		return $this->fields;
	}
	
	/**
	 * Flattens the data into a simple structure, either an arror or standard object
	 * @return object
	 */	
	public function toObject() {
		return (object)$this->fields;
	}	

	public function getSchema() {
		return $this->tableAdapter->getSchema();
	}
	
	public function manyToOne($tableName) {
		$table = Database::table($tableName);
		$foreignKey = $tableName.'_id';
		return $table->load($this[$foreignKey]);		
	}
	
	public function oneToMany($tableName) {
		$table = Database::table($tableName);
		$foreignKey = $this->tableAdapter->getTableName().'_id';
		$primaryKey = $this->getSchema()->primaryKey;
		$thisKey = $this[$primaryKey];
		return $table->select()->where("{$foreignKey}=?", [$thisKey])->fetchRecords();
	}
	
	/**
	 * Gets a list of all fields modified since last sync with the database.
	 * @return array All the dirty fields
	 */
	public function getDirtyFields() {
		$dfields = [];
		foreach ($this->dirty as $fieldname=>$isdirty) {
			if ($isdirty) {
				$dfields[] = $fieldname;
			}
		}
		return $dfields;
	}

	/**
	 * Clears all dirty flags
	 */
	public function clearDirtyFields() {
		foreach ($this->dirty as $fieldname=>$isdirty) {
			$this->dirty[$fieldname] = false;
		}
	}

	
	//========== ArrayAccess methods ===========
	
	public function offsetExists($offset ) {
		return isset($this->fields[$offset]);
	}

	public function offsetGet($offset) {
		return $this->fields[$offset];
	}

	public function offsetSet($offset, $value ) {
		$this->fields[$offset] = $value;
		$this->dirty[$offset] = true;
		return $value;
	}

	public function offsetUnset($offset) {
		unset($this->fields[$offset]);
		unset($this->dirty[$offset]);
	}
}

class TableAdapter {
	private $tableName = null;
	private $link = null;
	private $schema = null;
	private $queryType = null;
	private $selectFields = null;
	private $updateClause = null;	
	private $updateParams = null;
	private $whereClause = null;
	private $whereParams = null;
	private $orderClause = null;
	private $limit = null;
	private $offset = 0;

	public function __construct($tableName, $schema) {
		$this->tableName = $tableName;
		$this->schema = $schema;
	}

	/**
	 * Loads one or more records by id
	 * @param mixed $id_or_ids A single id or array of ids
	 * @return \Record|array[\Record]
	 */
	public function load($id_or_ids) {
		if (is_array($id_or_ids)) {
			$where = "`{$this->schema->primaryKey}` IN (".Database::slots(count($id_or_ids)).")";
			return $this->select()->where($where,$id_or_ids)->fetchRecords();
		} else {
			$rows = $this->select()->where("`{$this->schema->primaryKey}`=?",
						[$id_or_ids])->fetchRecords();
			if (count($rows)==0) {
				return null;
			} else {
				return $rows[0];
			}
		}
	}

	public function getSchema() {
		return $this->schema;
	}
	
	public function keyMap(array $records) {
            $key = $this->schema->primaryKey;
            $mappedRecords = [];
            foreach ($records as $r) {
                    $mappedRecords[$r[$key]] = $r;
            }
            return $mappedRecords;
	}

	/**
	 * Creates a new record for this table
	 * @return \Record
	 */
	public function newRecord() {
		return new Record($this);
	}

	/**
	 * Sets the where clause to get/effect all available rows
	 * @return \TableAdapter
	 */
	public function all() {
		return $this->where('1');
	}

	/**
	 * Creates a select query for the specified fields
	 * @param type $fields
	 * @return \TableAdapter
	 */
	public function select($fields='*') {
		$c = clone $this;
		$c->queryType = 'select';
		$c->selectFields = $fields;
		return $c;
	}

	/**
	 * Creates an update query based on the given query fragment
	 * @param type $updateString
	 * @param array $params
	 * @return \TableAdapter
	 */
	public function update($updateString, array $params=[]) {
		$c = clone $this;
		$c->queryType = 'update';
		$c->updateClause = $updateString;
		$c->updateParams = $params;
		return $c;
	}

	/**
	 * Creates a delete query
	 * @return \TableAdapter
	 */
	public function delete() {		
		$c = clone $this;
		$c->queryType = 'delete';
		return $c;
	}

	/**
	 * Addes a where clause to the query
	 * @param string $conditions Plain SQL condition
	 * @param array $params Parameters for the where clause
	 * @return \TableAdapter
	 */
	public function where($conditions, array $params=[]) {
		$c = clone $this;
		$c->whereClause = $conditions;
		$c->whereParams = $params;
		return $c;
	}

	/**
	 * Adds an ORDER BY clause to the query
	 * @param string $orderClause
	 * @return \TableAdapter
	 */
	public function orderBy($orderClause) {
		$c = clone $this;
		$c->orderClause = $orderClause;
		return $c;
	}

	/**
	 * Adds a LIMIT clause to the query
	 * @param int $limit
	 * @param int $offset
	 * @return \TableAdapter
	 */
	public function limit($limit,$offset=0) {
		$c = clone $this;
		$c->limit = $limit;
		$c->offset = $offset;
		return $c;
	}

	/**
	 * Executes the query built so far
	 * @throws Exception
	 * @return bool True on success, false on failure
	 */
	public function execute() {	
		if ($this->queryType !== 'delete' && $this->queryType !== 'update') {
			throw new Exception("Fetch only works on select queries!");
		}		
		$link = Database::getConnection();
		list($query, $params) = $this->buildQuery();
		$stmt = $link->prepare($query);
		return $stmt->execute($params);
	}

	/**
	 * Gets all records from a table and returns them as \Records
	 * @return array[\Record]
	 */
	public function allRecords() {
		return $this->select()->all()->fetchRecords();
	}

	/**
	 * Executes the select query and retuns all \Records in an array
	 * @return array[\Record]
	 * @throws Exception On invalid query type
	 */
	public function fetchRecords() {
		if ($this->queryType !== 'select') {
			throw new Exception("Fetch only works on select queries!");
		}
		$link = Database::getConnection();
		list($query, $params) = $this->buildQuery();
		//echo pprint([$query,$params])."<br>";
		$stmt = $link->prepare($query);
		if ($stmt->execute($params)) {
			$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$records = [];
			foreach ($rows as $row) {
				$records[] = new Record($this, $row);
			}
			return $records;
		} else {
			throw new Exception("Select query failed!");
		}
	}

	/**
	 * Attempts to insert the given record, or does an update on all dirty fields if a key clash occurs
	 * @param Record $record
	 * @return boolean
	 * @throws Exception On query failure
	 */
	public function insertOrUpdate(Record $record) {
		$key = $this->schema->primaryKey;
		$link = Database::getConnection();

		$data = $record->flatten();
		$dirtyFields = $record->getDirtyFields();		
		$names = implode(',', array_map(function($v){
											return "`$v`";
										}, array_keys($data)));
		$params = array_values($data);
		$slots = Database::slots(count($data));
		$query = "INSERT INTO `{$this->tableName}` ({$names}) VALUES ({$slots}) ON DUPLICATE KEY UPDATE ";
		foreach ($dirtyFields as $fieldname) {
			$updateSegments[] = "`{$fieldname}`=?";
			$params[] = $record[$fieldname];
		}
		$query .= implode(',', $updateSegments);

		$stmt = $link->prepare($query);
		if ($stmt->execute($params)) {
			$insertId = $link->lastInsertId();
			$record[$key] = $insertId;
			$record->clearDirtyFields();
			return true;
		} else {
			throw new Exception("Insertion save failed!");
			return false;
		}			
	}

	/**
	 * Attempts to insert the given record, doing nothing on key collision. Will not reset the dirty flags for the given record. This behaviour may be problematic and hsould be revisted.
	 * @param Record $record
	 * @return boolean
	 * @throws Exception On query failure
	 */
	public function insertOrIgnore(Record $record) {
		$key = $this->schema->primaryKey;
		$link = Database::getConnection();

		$data = $record->flatten();
		$names = implode(',', array_map(function($v){
											return "`$v`";
										}, array_keys($data)));
		$values = array_values($data);
		$slots = Database::slots(count($data));
		$query = "INSERT INTO `{$this->tableName}` ({$names}) VALUES ({$slots}) ON DUPLICATE KEY SET `{$key}`=`{$key}`";
		$stmt = $link->prepare($query);
		if ($stmt->execute($values)) {
			$insertId = $link->lastInsertId();
			$record[$key] = $insertId;
			return true;
		} else {
			throw new Exception("Insertion save failed!");
			return false;
		}		
	}

	/**
	 * Saves the given record, either inserting or update based on whether the record already has a primary key set.
	 * @param Record $record
	 * @return boolean
	 * @throws Exception
	 */
	public function save(Record $record) {	
		$key = $this->schema->primaryKey;
		$link = Database::getConnection();		

		//if primary key is set, do an update
		if (isset($record[$key])) {
			$dirtyFields = $record->getDirtyFields();
			//short circuit if no fields are dirty			
			if (count($dirtyFields) == 0) {
				return true;
			}
			$query = "UPDATE `{$this->tableName}` SET ";
			$params = [];
			$querySegments = [];
			foreach ($dirtyFields as $fieldname) {
				$querySegments[] = "`{$fieldname}`=?";
				$params[] = $record[$fieldname];
			}
			$query .= implode(',', $querySegments);
			$query .= " WHERE `{$key}`={$record[$key]}";
			$stmt = $link->prepare($query);
			if ($stmt->execute($params)) {
				$record->clearDirtyFields();
				return true;
			} else {
				return false;
			}
		} else { //else do an insert
			$data = $record->toArray();
			$names = implode(',', array_map(function($v){
												return "`$v`";
											}, array_keys($data)));
			$values = array_values($data);
			$slots = Database::slots(count($data));
			$query = "INSERT INTO `{$this->tableName}` ({$names}) VALUES ({$slots})";
			$stmt = $link->prepare($query);
			if ($stmt->execute($values)) {
				$insertId = $link->lastInsertId();
				$record[$key] = $insertId;
				return true;
			} else {
				throw new Exception("Insertion save failed!");
				return false;
			}
		}
		throw new Exception("Save not implemented yet");
	}

	/**
	 * Deletes the given record from the database
	 * @param \Record $record
	 * @return bool
	 */
	public function deleteRecord(Record $record) {
		$key = $this->schema->primaryKey;
		return $this->delete()->where("{$key}=?",[$record[$key]])->execute();
	}

	public function getTableName() {
		return $this->tableName;
	}
	
	/**
	 * Builds a query string and parameter array
	 * @return list($query, $params)
	 * @throws Exception If no where clause was supplied, in order to prevent forgetful Franks from accidentally nuking their database tables
	 * @todo Verify that the number of parameters is correct!
	 */
	public function buildQuery() {
		$queryString = '';
		$params = [];

		switch ($this->queryType) {
			case 'select':
				$queryString = "SELECT {$this->selectFields} FROM {$this->tableName}";
				break;
			case 'update':
				$queryString = "UPDATE {$this->tableName} SET {$this->updateClause}";
				$params = array_merge($params, $this->updateParams);
				break;
			case 'delete':
				$queryString = "DELETE FROM {$this->tableName}";
				break;
			default:
				throw new Exception('No query type selected!');
				break;
		}
		if ($this->whereClause !== null) {
			$queryString .= ' WHERE '.$this->whereClause;
			$params = array_merge($params, $this->whereParams);
		} else {
			throw new Exception("No where clause specified, if you want the whole table please use all()");
		}
		if ($this->orderClause !== null) {
			$queryString .= ' ORDER BY '.$this->orderClause;
		}
		if ($this->limit !== null) {
			$queryString .= ' LIMIT '.(int)$this->limit;
			if ($this->offset!=0) {
				$queryString .= ','.(int)$this->offset;
			}
		}
		return [$queryString, $params];
	}
}

/**
 * Helper wrapper class that contains info about table schema
 */
class TableSchema {
	public $primaryKey = null;
	public $columns = [];

	public function __construct($primary, $columns) {
		if (!is_array($columns)) {
			throw new Exception("Invalid columns parameter, must be an array");
		}
		if (!is_string($primary)) {
			throw new Exception("Invalid primary key parameter, must be a string");
		}
		$this->columns = $columns;		
		$this->primaryKey = $primary;
	}
}

/**
 * @todo Improve multi database support
 */
class Database {
	private static $server = 'localhost';
	private static $database = 'db1';
	private static $user = 'user';
	private static $password = 'pass';

	private static $tableSchemas = [];
	private static $connection = null;

	/**
	 * Creates a new TableAdapter for the given table
	 * @param type $tableName
	 * @return \TableAdapter
	 * @todo Handle nonexistent tables
	 */
	public static function table($tableName) {
		$schema = self::getTableSchema($tableName);
		return new TableAdapter($tableName, $schema);
	}

	/**
	 * Generates a comma separated list of ? slots
	 * @param int $num
	 * @return string
	 */
	public static function slots($num) {
		return implode(',', array_fill(0, $num, '?'));
	}

	/**
	 * Gets the PDO object for our database connection
	 * @return type
	 */
	public static function getConnection() {
		if (is_null(self::$connection)) {
			$dsn = 'mysql:dbname='.self::$database.';host='.self::$server.';charset=utf8mb4';
			self::$connection = new PDO($dsn, self::$user, self::$password);
			self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);				
		}
		return self::$connection;
	}

	/**
	 * Gets a list of all tables in the given database
	 * @param string $database
	 * @return type
	 */
	public static function getTables($database) {	
		$db = self::getConnection();
		$queryString = "SELECT * FROM INFORMATION_SCHEMA.Tables WHERE TABLE_SCHEMA=?";
		$stmt = $db->prepare($queryString);
		$stmt->execute([$database]);
		$columnNames = [];
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$columnNames = $row['TABLE_NAME'];
		}
		return $columnNames;		
	}

	/**
	 * Gets the schema for the given table from the schema cache, loading it from the database on cache miss
	 * @param string $table
	 * @param string $database
	 * @return TableSchema
	 */
	public static function getTableSchema($table, $database=null) {
		if (!isset(self::$tableSchemas[$table])) {
			self::$tableSchemas[$table] = self::buildTableSchema($table, $database);
		}
		return self::$tableSchemas[$table];
	}

	/**
	 * Queries the database information schema for the given table and builds a TableSchema object
	 * @param string $table
	 * @param string $database
	 * @return \TableSchema
	 * @throws Exception
	 */
	public static function buildTableSchema($table, $database=null) {
		if ($database === null) {
			$database = self::$database;
		}
		$db = self::getConnection();
		$queryString = "SELECT * FROM INFORMATION_SCHEMA.Columns WHERE TABLE_SCHEMA=? AND TABLE_NAME=?";
		$stmt = $db->prepare($queryString);
		$stmt->execute([$database,$table]);

		$primaryKey = null;
		$primaryKeyCount = 0;
		$columnNames = [];
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$columnNames[] = $row['COLUMN_NAME'];
			if ($row['COLUMN_KEY'] === 'PRI') {
				$primaryKeyCount++;
				if ($primaryKeyCount == 1) {
					$primaryKey = $row['COLUMN_NAME'];
				} else {
					$primaryKey = null;
				}
			}
		}
		if (count($columnNames) === 0) {
			throw new Exception("Table $database.$table not found!");
		}
		return new TableSchema($primaryKey, $columnNames);
	}

	/**
	 * Converts an array of records to simple arrays
	 * @param array $records
	 * @throws Exception On bad input
	 */
	public static function recordsToArrays(array $records) {
		if (!is_array($records)) {
			throw new Exception("Records must be an array!");
		}
		$flatRecords = [];
		foreach ($records as $rec) {
			$flatRecords[] = $rec->toArray();
		}
	}
	
	/**
	 * Converts an array of records to simple objects
	 * @param array $records
	 * @throws Exception On bad input
	 */
	public static function recordsToObjects(array $records) {
		if (!is_array($records)) {
			throw new Exception("Records must be an array!");
		}
		$flatRecords = [];
		foreach ($records as $rec) {
			$flatRecords[] = (object)$rec->toArray();
		}
	}	
}