<?php declare(strict_types=1);

namespace Zet\DbMigration\Command;

use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Tracy\Debugger;
use Zet\DbMigration\DDL\Generator\TableGenerator;
use Zet\DbMigration\Reader\SchemaReader;

/**
 * Class RunDDLCommand
 * @author  Zechy <email@zechy.cz>
 * @package Zet\DbMigration\Command
 */
class RunDDLCommand extends BaseCommand {

	/**
	 * @var string
	 */
	protected static $defaultName = "migration:run-ddl";

	/**
	 *
	 */
	protected function configure() {
		$this->setName(self::$defaultName);
		$this->setDescription("Creates database tables from source database in destination.");
	}

	/**
	 * @param InputInterface  $input
	 * @param ConsoleOutput|OutputInterface $output
	 * @return int
	 * @throws \Nextras\Dbal\QueryException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		/** @var FormatterHelper $formatter */
		$formatter = $this->getHelper("formatter");


		$schemaReader = new SchemaReader($this->getSource());
		$tables = $schemaReader->getDatabaseTables();
		$tablesCount = count($tables);
		$line = $formatter->formatSection("SOURCE", "Found $tablesCount tables in database.");
		$output->writeln($line);

		$progress = new ProgressBar($output, $tablesCount);
		/** @noinspection PhpParamsInspection */
		$progress->setFormat("verbose");
		$progress->start();
		$output->writeln("");

		$success = 0;

		foreach($tables as $table) {
			$line = $formatter->formatSection("SOURCE", $formatter->formatBlock("Table `$table->TABLE_NAME`", "comment"));
			$output->writeln($line);

			$ddlQuery = "";
			try {
				$generator = new TableGenerator($table, $schemaReader);
				$ddlQuery = $generator->generate();

				$this->getDestination()->query($ddlQuery);

				$formatted = $formatter->formatBlock("Table was successfully created.", "success");
				$line = $formatter->formatSection("DESTINATION", $formatted);
				$output->writeln($line);

				$success++;
			} catch (\Exception $e) {
				$formatted = $formatter->formatBlock([
					"Couldn't create table `$table->TABLE_NAME`.",
					$e->getMessage()
				], "error");
				$line = $formatter->formatSection("DESTINATION", $formatted);
				$output->writeln($line);
				Debugger::log($e->getMessage(), Debugger::EXCEPTION);
				if($ddlQuery !== "") {
					Debugger::log($ddlQuery, Debugger::EXCEPTION);
				}
			}

			$progress->advance();
			$output->writeln("");
		}

		$output->writeln(
			$formatter->formatSection("DESTINATION",
				$formatter->formatBlock("Successfully created $success tables of $tablesCount.", "success")
			)
		);

		return 0;
	}
}