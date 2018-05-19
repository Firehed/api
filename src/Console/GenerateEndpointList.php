<?php
declare(strict_types=1);

namespace Firehed\API\Console;

use Firehed\API\Config;
use Firehed\API\Interfaces\EndpointInterface;
use Firehed\Input\Interfaces\ParserInterface;
use Firehed\Common\ClassMapGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateEndpointList extends Command
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
        $this->setName('api:generateEndpointList')
            ->setDescription('Build the static route list')
            ;
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
            ->setNamespace($this->config->get('namespace'))
            ->setOutputFile('__endpoint_list__.json')
            ->generate();

        $output->writeln(
            'Wrote endpoint map to __endpoint_list__.json. Be sure to commit this ' .
            'file to version control.'
        );

        $logger->debug('Building parser map');
        // Also do the parser map
        (new ClassMapGenerator())
            ->setPath(getcwd().'/'.'vendor/firehed/input/src/Parsers')
            ->setInterface(ParserInterface::class)
            ->setMethod('getSupportedMimeTypes')
            ->setNamespace('Firehed\Input\Parsers')
            ->setOutputFile('__parser_list__.json')
            ->generate();
    }
}
