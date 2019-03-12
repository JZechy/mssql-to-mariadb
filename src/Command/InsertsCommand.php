<?php declare(strict_types=1);

namespace Zet\DbMigration\Command;

use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Tracy\Debugger;
use Zet\DbMigration\Helper\RowHelper;
use Zet\DbMigration\Reader\DataReader;
use Zet\DbMigration\Reader\SchemaReader;
use Zet\DbMigration\Writer\DataWriter;

/**
 * Class InsertsCommand
 * @author  Zechy <email@zechy.cz>
 * @package Zet\DbMigration\Command
 */
class InsertsCommand extends BaseCommand {

	/**
	 * @var string
	 */
	protected static $defaultName = "migration:inserts";

	/**
	 *
	 */
	protected function configure() {
		$this->setName(self::$defaultName);
		$this->setDescription("Inserts all data from source to destination.");
	}

	/**
	 * @param InputInterface                $input
	 * @param OutputInterface|ConsoleOutput $output
	 * @return int
	 * @throws \Nextras\Dbal\QueryException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		ini_set('memory_limit','1024M');
		ini_set('sqlsrv.ClientBufferMaxKBSize','524288');
		ini_set('pdo_sqlsrv.client_buffer_max_kb_size','524288');

		/** @var FormatterHelper $formatter */
		$formatter = $this->getHelper("formatter");

		$progressSection = $output->section();
		$info = $output->section();
		$insertProgressSection = $output->section();

		$schemaReader = new SchemaReader($this->getSource());
		$tables = $schemaReader->getDatabaseTables();

		$dataReader = new DataReader($this->getSource());
		$dataWriter = new DataWriter($this->getDestination());

		$progress = new ProgressBar($progressSection, count($tables));
		/** @noinspection PhpParamsInspection */
		$progress->setFormat("verbose");
		$progress->start();

		$onSelect = 50000;

		foreach($tables as $table) {
			$info->overwrite(
				$formatter->formatSection("SOURCE", $formatter->formatBlock("Exporting data for table `$table->TABLE_NAME`", "comment"))
			);
			$rows = $dataReader->count($table->TABLE_NAME);
			$info->writeln(
				$formatter->formatSection("SOURCE", "Found $rows rows.")
			);
			$insertProgress = new ProgressBar($insertProgressSection, $rows);
			$iterations = ceil($rows / $onSelect);
			$primaryKeys = $schemaReader->getPrimaryKey($table->TABLE_NAME);
			$pk = null;
			if(isset($primaryKeys[0])) {
				$pk = $primaryKeys[0]->COLUMN_NAME;
			}

			$columns = $schemaReader->getTableColumns($table->TABLE_NAME);

			for($i = 0; $i < $iterations; $i++) {
				$data = $dataReader->select($table->TABLE_NAME, $onSelect, $onSelect * $i, $pk);
				foreach ($data as $row) {
					try {
						$dataWriter->insert($table->TABLE_NAME, $columns, $row);
					} catch (\Exception $e) {
						Debugger::log("Data for table `$table->TABLE_NAME` could not be inserted.", Debugger::EXCEPTION);
						Debugger::log($e->getMessage(), Debugger::EXCEPTION);
						Debugger::log(RowHelper::stringifyRow($row), Debugger::EXCEPTION);

						$info->writeln(
							$formatter->formatSection(
								"DESTINATION",
								"Row `" . RowHelper::stringifyRow($row) . "` could not be inserted.",
								"warning"
							)
						);
					}
					$insertProgress->advance();
				}
			}

			$insertProgressSection->clear();
			$progressSection->clear();
			$progress->advance();
		}

		$info->overwrite(
			$formatter->formatSection(
				"DESTINATION",
				$formatter->formatBlock("All rows was successfully exported to destination.", "success")
			)
		);

		return 0;
	}
}