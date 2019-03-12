<?php declare(strict_types=1);

namespace Zet\DbMigration\Command;

use Nextras\Dbal\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class BaseCommand
 * @author  Zechy <email@zechy.cz>
 * @package Zet\DbMigration\Command
 */
abstract class BaseCommand extends Command {

	/**
	 * @var Connection
	 */
	private $source;

	/**
	 * @var Connection
	 */
	private $destination;

	/**
	 * BaseCommand constructor.
	 * @param Connection $source
	 * @param Connection $destination
	 */
	public function __construct(Connection $source, Connection $destination) {
		parent::__construct();
		$this->source = $source;
		$this->destination = $destination;
	}

	/**
	 * @return Connection
	 */
	public function getSource(): Connection {
		return $this->source;
	}

	/**
	 * @return Connection
	 */
	public function getDestination(): Connection {
		return $this->destination;
	}

	/**
	 * @param InputInterface  $input
	 * @param OutputInterface $output
	 */
	protected function interact(InputInterface $input, OutputInterface $output) {
		$output->getFormatter()->setStyle("success", new OutputFormatterStyle("green", "black"));
		$output->getFormatter()->setStyle("info", new OutputFormatterStyle("cyan", "black"));
		$output->getFormatter()->setStyle("comment", new OutputFormatterStyle("blue", "black"));
		$output->getFormatter()->setStyle("warning", new OutputFormatterStyle("yellow", "black"));
		$output->getFormatter()->setStyle("error", new OutputFormatterStyle("red", "black"));
	}
}