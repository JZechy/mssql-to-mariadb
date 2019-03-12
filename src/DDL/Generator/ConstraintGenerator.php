<?php declare(strict_types=1);

namespace Zet\DbMigration\DDL\Generator;

use Zet\DbMigration\Reader\SchemaReader;

/**
 * Class ConstraintGenerator
 * @author  Zechy <email@zechy.cz>
 * @package Zet\DbMigration\DDL\Generator
 */
class ConstraintGenerator {

	/**
	 * @var SchemaReader
	 */
	private $schemaReader;

	/**
	 * @var string
	 */
	private $name;

	/**
	 * ConstraintGenerator constructor.
	 * @param SchemaReader $schemaReader
	 * @param string       $name
	 */
	public function __construct(SchemaReader $schemaReader, string $name) {
		$this->schemaReader = $schemaReader;
		$this->name = $name;
	}

	/**
	 * @return string
	 * @throws \Nextras\Dbal\QueryException
	 */
	public function generate(): string {
		$definition = $this->schemaReader->getConstraintDefinition($this->name);
		$target = $this->schemaReader->getConstraintTarget($this->name);

		if(strlen($this->name) > 64) {
			$this->name = sprintf("FK_%s_%s", $definition->TABLE_NAME, $definition->COLUMN_NAME);
		}
		if(strlen($this->name) > 64) {
			$this->name = sprintf("FK_%s_%s", $definition->TABLE_NAME, uniqid());
		}

		$query = sprintf("
			ALTER TABLE `%s`
			ADD CONSTRAINT `%s`
			FOREIGN KEY (`%s`)
			REFERENCES `%s` (`%s`);
		", $definition->TABLE_NAME, $this->name, $definition->COLUMN_NAME, $target->TABLE_NAME, $target->COLUMN_NAME);

		return $query;
	}
}