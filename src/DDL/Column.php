<?php declare(strict_types=1);

namespace Zet\DbMigration\DDL;

/**
 * Class Column
 * @author  Zechy <email@zechy.cz>
 * @package Zet\DbMigration\DDL
 */
class Column {

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var DataType
	 */
	private $dataType;

	/**
	 * @var bool
	 */
	private $nullable;

	/**
	 * @var mixed
	 */
	private $default;

	/**
	 * @var bool
	 */
	private $autoIncrement = false;

	/**
	 * Column constructor.
	 * @param string   $name
	 * @param DataType $dataType
	 * @param string   $nullable
	 */
	public function __construct(string $name, DataType $dataType, string $nullable) {
		$this->name = $name;
		$this->nullable = $nullable == "YES" ? true : false;
		$this->dataType = $dataType;
	}

	/**
	 * @param mixed $default
	 */
	public function setDefault($default) {
		if($default == "getdate") {
			$default = "now";
		}
		if($default == "newid") {
			$default = "uuid()";
		}

		$this->default = $default;
	}

	/**
	 * @return string
	 */
	public function createQuery(): string {
		$query = sprintf("`%s` %s %s", $this->name, (string)$this->dataType, $this->isNullable());
		if($this->autoIncrement) {
			$query .= " auto_increment";
		}

		return $query;
	}

	/**
	 * @return string
	 */
	public function isNullable(): string {
		return $this->nullable ? "NULL" : "NOT NULL";
	}

	/**
	 * @param bool $autoIncrement
	 */
	public function setAutoIncrement(bool $autoIncrement) {
		$this->autoIncrement = $autoIncrement;
	}

	/**
	 * @return bool
	 */
	public function isAutoIncrement(): bool {
		return $this->autoIncrement;
	}
}