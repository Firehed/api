<?php
declare(strict_types=1);

namespace Firehed\API\Console;

use Firehed\API\Config;
use Firehed\API\Dispatcher;
use Firehed\API\Interfaces\EndpointInterface;
use Firehed\Input\Interfaces\ParserInterface;
use Firehed\Common\ClassMapGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class CompileAll extends Command
{
    /** @var Config */
    private $config;

    public function __construct(Config $config)
    {
        parent::__construct();
        $this->config = $config;
    }

    protected function configure()
    {
        $this->setName('compile:all')
            ->setDescription('Build required static resources');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new ConsoleLogger($output);

        $logger->debug('Current directory: {cwd}', ['cwd' => getcwd()]);
        $logger->debug('Building classmap');
        // Build out the endpoint map
        (new ClassMapGenerator())
            ->setPath(getcwd().'/'.$this->config->get('source'))
            ->setInterface(EndpointInterface::class)
            ->addCategory('getMethod')
            ->setMethod('getURI')
            ->setNamespace($this->config->get(Config::KEY_NAMESPACE))
            ->setOutputFile(Dispatcher::ENDPOINT_LIST)
            ->generate();

        $output->writeln(sprintf(
            'Wrote endpoint map to %s',
            Dispatcher::ENDPOINT_LIST
        ));

        $logger->debug('Building parser map');
        // Also do the parser map
        (new ClassMapGenerator())
            ->setPath(getcwd().'/'.'vendor/firehed/input/src/Parsers')
            ->setInterface(ParserInterface::class)
            ->setMethod('getSupportedMimeTypes')
            ->setNamespace('Firehed\Input\Parsers')
            ->setOutputFile(Dispatcher::PARSER_LIST)
            ->generate();
        $output->writeln(sprintf(
            'Wrote parser map to %s',
            Dispatcher::PARSER_LIST
        ));
    }
}
