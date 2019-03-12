<?php declare(strict_types=1);

namespace Zet\DbMigration\Command;

use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Tracy\Debugger;
use Zet\DbMigration\DDL\Generator\ConstraintGenerator;
use Zet\DbMigration\Reader\SchemaReader;

/**
 * Class ConstraintsCommand
 * @author  Zechy <email@zechy.cz>
 * @package Zet\DbMigration\Command
 */
class ConstraintsCommand extends BaseCommand {

	/**
	 * @var string
	 */
	protected static $defaultName = "migration:constraints";

	/**
	 *
	 */
	protected function configure() {
		$this->setName(self::$defaultName);
		$this->setDescription("Adds tables constraints.");
	}

	/**
	 * @param InputInterface  $input
	 * @param OutputInterface|ConsoleOutput $output
	 * @return int
	 * @throws \Nextras\Dbal\QueryException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		/** @var FormatterHelper $formatter */
		$formatter = $this->getHelper("formatter");
		$progressSection = $output->section();
		$info = $output->section();

		$schemaReader = new SchemaReader($this->getSource());
		$tables = $schemaReader->getDatabaseTables();
		$tableCount = count($tables);

		$progress = new ProgressBar($progressSection, $tableCount);
		/** @noinspection PhpParamsInspection */
		$progress->setFormat("verbose");
		$progress->start();

		$this->getDestination()->query("SET FOREIGN_KEY_CHECKS=0;");

		$success = 0;
		foreach($tables as $table) {
			$formatted = $formatter->formatBlock("Generating constraints for table `$table->TABLE_NAME`", "comment");
			$line = $formatter->formatSection("SOURCE", $formatted);
			$info->overwrite($line);
			$query = "";

			$constraints = $schemaReader->getTableConstraints($table->TABLE_NAME);
			$ok = true;
			foreach($constraints as $constraint) {
				try {
					$line = $formatter->formatSection("SOURCE", "Foreign Key Constraint `$constraint->CONSTRAINT_NAME`");
					$info->writeln($line);

					$constraintGenerator = new ConstraintGenerator($schemaReader, $constraint->CONSTRAINT_NAME);
					$query = $constraintGenerator->generate();
					$this->getDestination()->query($query);
				} catch (\Exception $e) {
					$ok = false;
					$formatted = $formatter->formatBlock([
						"Couldn't create constraint for table.",
						$e->getMessage()
					], "error");
					$line = $formatter->formatSection("DESTINATION", $formatted);
					$info->writeln($line);
					Debugger::log($e->getMessage(), Debugger::EXCEPTION);
					if($query != "") {
						Debugger::log($query, Debugger::EXCEPTION);
					}
				}
			}

			if(empty($constraints)) {
				$line = $formatter->formatSection("SOURCE", "Table does not have any constraints.");
				$info->writeln($line);
			} else {
				if($ok) {
					$formatted = $formatter->formatBlock("All constraints was successfully created.", "success");
					$line = $formatter->formatSection("DESTINATION", $formatted);
					$info->writeln($line);
				} else {
					$formatted = $formatter->formatBlock("Error during constraints generating has appeared.", "warning");
					$line = $formatter->formatSection("DESTINATION", $formatted);
					$info->writeln($line);
				}
			}
			if($ok) {
				$success++;
			}

			$progressSection->clear();
			$progress->advance();
		}

		$this->getDestination()->query("SET FOREIGN_KEY_CHECKS=1;");

		$formatted = $formatter->formatBlock("Successfully created constraints for $success tables of $tableCount.", "info");
		$line = $formatter->formatSection("DESTINATION", $formatted);
		$info->overwrite($line);

		return 0;
	}
}