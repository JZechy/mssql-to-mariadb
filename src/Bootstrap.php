<?php declare(strict_types=1);

namespace Zet\DbMigration;

use Nette\Neon\Neon;
use Nextras\Dbal\Connection;
use Symfony\Component\Console\Application;
use Tracy\Debugger;
use Zet\DbMigration\Command\ConstraintsCommand;
use Zet\DbMigration\Command\InsertsCommand;
use Zet\DbMigration\Command\RunDDLCommand;
use Zet\DbMigration\Exception\FileNotFoundException;

/**
 * Class Bootstrap
 * @author  Zechy <email@zechy.cz>
 * @package Zet\DbMigration
 */
final class Bootstrap {

	/**
	 * @var string
	 */
	private $configfile = __DIR__ . "/../config.neon";

	/**
	 * @var array
	 */
	private $configuration;

	/**
	 * @var Connection
	 */
	private $sourceConnection;

	/**
	 * @var Connection
	 */
	private $destinationConnection;

	/**
	 *
	 */
	public function run(): void {
		Debugger::$logDirectory = __DIR__ . "/../log";

		$application = new Application();

		try {
			$this->loadConfiguration();
			$application->add(new RunDDLCommand($this->getSourceConnection(), $this->getDestinationConnection()));
			$application->add(new ConstraintsCommand($this->getSourceConnection(), $this->getDestinationConnection()));
			$application->add(new InsertsCommand($this->getSourceConnection(), $this->getDestinationConnection()));
			$application->run();
		} catch (\Exception $e) {
			echo $e->getMessage();
		}
	}

	/**
	 * @throws FileNotFoundException
	 */
	private function loadConfiguration(): void {
		if(!file_exists($this->configfile)) {
			throw new FileNotFoundException("File `config.neon` was not found.");
		}

		$this->configuration = Neon::decode(file_get_contents($this->configfile));
	}

	/**
	 * @return Connection
	 */
	private function getSourceConnection(): Connection {
		if($this->sourceConnection === null) {
			$config = ["driver" => "sqlsrv"] + $this->configuration["source"];
			$this->sourceConnection = new Connection($config);
		}

		return $this->sourceConnection;
	}

	/**
	 * @return Connection
	 */
	private function getDestinationConnection(): Connection {
		if($this->destinationConnection === null) {
			$config = ["driver" => "mysqli"] + $this->configuration["destination"];
			$this->destinationConnection = new Connection($config);
		}

		return $this->destinationConnection;
	}
}