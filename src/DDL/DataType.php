<?php declare(strict_types=1);

namespace Zet\DbMigration\DDL;

use Nextras\Dbal\Result\Row;

/**
 * Class DataType
 * @author  Zechy <email@zechy.cz>
 * @package Zet\DbMigration\DDL
 */
class DataType {

	/**
	 * @var array
	 */
	private $msTypes = [
		"string" => [
			"varchar", "nvarchar", "char", "text", "ntext", "nchar"
		],
		"numeric" => [
			"smallint", "bigint", "int", "tinyint", "float", "money", "decimal"
		],
		"date" => [
			"date", "datetime", "datetime2", "time", "timestamp"
		]
	];

	/**
	 * @var array
	 */
	private $typeConversion = [
		"nvarchar" => "varchar",
		"nchar" => "char",
		"ntext" => "text",
		"datetime2" => "datetime",
		"uniqueidentifier" => "binary",
		"varbinary" => "longblob",
		"money" => "decimal"
	];

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var int
	 */
	private $precision;

	/**
	 * @var int
	 */
	private $scale = 0;

	/**
	 * DataType constructor.
	 * @param Row $row
	 */
	public function __construct(Row $row) {
		$this->name = $this->convertDateType($row->DATA_TYPE);

		if($this->isString($row->DATA_TYPE)) {
			$this->precision = $row->CHARACTER_MAXIMUM_LENGTH;
		}
		if($this->isNumeric($row->DATA_TYPE)) {
			$this->precision = $row->NUMERIC_PRECISION;
			$this->scale = $row->NUMERIC_SCALE;
		}
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	private function isString(string $name): bool {
		return array_search($name, $this->msTypes["string"]) !== FALSE;
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	private function isNumeric(string $name): bool {
		return array_search($name, $this->msTypes["numeric"]) !== FALSE;
	}

	/**
	 * @param string $name
	 * @return string
	 */
	private function convertDateType(string $name): string {
		if(isset($this->typeConversion[$name])) {
			if($name == "uniqueidentifier") {
				$this->precision = 16;
			}
			return $this->typeConversion[$name];
		}

		return $name;
	}

	/**
	 * @return string
	 */
	public function __toString(): string {
		if($this->isString($this->name) && $this->precision < 0) {
			$this->name = "text";
		}

		$string = $this->name;
		if($this->precision > 0 && $this->scale > 0) {
			$string .= "($this->precision, $this->scale)";
		} else if($this->precision > 0) {
			$string .= "($this->precision)";
		}

		return $string;
	}
}