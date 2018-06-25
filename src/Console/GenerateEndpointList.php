<?php
declare(strict_types=1);

namespace Firehed\API\Console;

use Firehed\API\Config;
use Firehed\API\Interfaces\EndpointInterface;
use Firehed\Input\Interfaces\ParserInterface;
use Firehed\Common\ClassMapGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateEndpointList extends Command
{
    const OPT_ENDPOINT_LIST = 'endpoint-list';
    const OPT_PARSER_LIST = 'parser-list';

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
            ->addOption(
                self::OPT_ENDPOINT_LIST,
                null,
                InputOption::VALUE_REQUIRED,
                'Where to write the endpoint list',
                '__endpoint_list__.json'
            )
            ->addOption(
                self::OPT_PARSER_LIST,
                null,
                InputOption::VALUE_REQUIRED,
                'Where to write the parser list',
                '__parser_list__.json'
            )
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
            ->setOutputFile($input->getOption(self::OPT_ENDPOINT_LIST))
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
            ->setOutputFile($input->getOption(self::OPT_PARSER_LIST))
            ->generate();
    }
}
