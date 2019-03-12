<?php declare(strict_types=1);

namespace Zet\DbMigration\DDL\Generator;

use Nextras\Dbal\Result\Row;
use Zet\DbMigration\DDL\Column;
use Zet\DbMigration\DDL\DataType;
use Zet\DbMigration\DDL\Table;
use Zet\DbMigration\Reader\SchemaReader;

/**
 * Class TableGenerator
 * @author  Zechy <email@zechy.cz>
 * @package Zet\DbMigration\DDL\Generator
 */
class TableGenerator {

	/**
	 * @var Row
	 */
	private $table;

	/**
	 * @var SchemaReader
	 */
	private $schemaReader;

	/**
	 * TableGenerator constructor.
	 * @param Row          $table
	 * @param SchemaReader $schemaReader
	 */
	public function __construct(Row $table, SchemaReader $schemaReader) {
		$this->table = $table;
		$this->schemaReader = $schemaReader;
	}

	/**
	 * @return string
	 * @throws \Nextras\Dbal\QueryException
	 */
	public function generate(): string {
		$ddl = new Table($this->table->TABLE_NAME);
		$columns = $this->schemaReader->getTableColumns($this->table->TABLE_NAME);

		foreach($columns as $column) {
			$dataType = new DataType($column);
			$ddlColumn = new Column($column->COLUMN_NAME, $dataType, $column->IS_NULLABLE);
			if($column->COLUMN_DEFAULT != null) {
				$ddlColumn->setDefault(str_replace(["(", ")"], "", $column->COLUMN_DEFAULT));
			}
			if($this->schemaReader->isIdentity($this->table->TABLE_NAME, $column->COLUMN_NAME)) {
				$ddlColumn->setAutoIncrement(true);
			}

			$ddl->addColumn($ddlColumn);
		}
		$primaryKeys = $this->schemaReader->getPrimaryKey($this->table->TABLE_NAME);
		foreach ($primaryKeys as $row) {
			$ddl->addPrimaryKey("`" . $row->COLUMN_NAME . "`");
		}
		$uniqueKeys = $this->schemaReader->getUniqueKeys($this->table->TABLE_NAME);
		foreach($uniqueKeys as $uq) {
			$uqColumns = $this->schemaReader->getUniqueColumns($uq->CONSTRAINT_NAME);
			foreach($uqColumns as $uqCol) {
				$ddl->addUniqueKey($uq->CONSTRAINT_NAME, "`" . $uqCol->COLUMN_NAME . "`");
			}
		}

		$ddlQuery = $ddl->generateQuery();

		return $ddlQuery;
	}
}