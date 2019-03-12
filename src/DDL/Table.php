<?php declare(strict_types=1);

namespace Zet\DbMigration\DDL;

/**
 * Class Table
 * @author  Zechy <email@zechy.cz>
 * @package Zet\DbMigration\DDL
 */
class Table {

	/**
	 * @var string
	 */
	private $tableName;

	/**
	 * @var Column[]
	 */
	private $columns;

	/**
	 * @var string[]
	 */
	private $primaryKeys = [];

	/**
	 * @var string[][]
	 */
	private $uniqueKeys = [];

	/**
	 * Table constructor.
	 * @param string $tableName
	 */
	public function __construct(string $tableName) {
		$this->tableName = $tableName;
	}

	/**
	 * @param Column $column
	 */
	public function addColumn(Column $column): void {
		$this->columns[] = $column;
	}

	/**
	 * @param string $name
	 */
	public function addPrimaryKey(string $name): void {
		$this->primaryKeys[] = $name;
	}

	/**
	 * @param string $key
	 * @param string $name
	 */
	public function addUniqueKey(string $key, string $name): void {
		$this->uniqueKeys[$key][] = $name;
	}

	/**
	 * @return string
	 */
	public function generateQuery(): string {
		$create = "CREATE TABLE `$this->tableName` (\n";
		$columnsQuery = [];
		foreach ($this->columns as $column) {
			if($column->isAutoIncrement() && empty($this->primaryKeys)) {
				$column->setAutoIncrement(false);
			}
			$columnsQuery[] = $column->createQuery();
		}

		if(!empty($this->primaryKeys)) {
			$columnsQuery[] = sprintf("PRIMARY KEY (%s)", implode(",", $this->primaryKeys));
		}
		if(!empty($this->uniqueKeys)) {
			foreach($this->uniqueKeys as $name => $columns) {
				$columnsQuery[] = sprintf("CONSTRAINT `%s` UNIQUE (%s)", $name, implode(",", $columns));
			}
		}

		$create .= implode(",\n", $columnsQuery);
		$create .= "\n) DEFAULT CHARACTER SET='utf8';";

		return $create;
	}
}