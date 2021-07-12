<?php
declare(strict_types=1);

namespace Firehed\API\Console;

use Composer\Autoload\ClassMapGenerator;
use Firehed\API\Config;
use Firehed\API\Dispatcher;
use Firehed\API\Interfaces\EndpointInterface;
use Firehed\Input\Interfaces\ParserInterface;
use Firehed\Input\Parsers;
use ReflectionClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

use function array_filter;
use function array_keys;
use function array_reduce;
use function array_values;
use function get_class;
use function gmdate;

class CompileAll extends Command
{
    /** @var Config */
    private $config;

    public function __construct(Config $config)
    {
        parent::__construct();
        $this->config = $config;
    }

    protected function configure(): void
    {
        $this->setName('compile:all')
            ->setDescription('Build required static resources');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = new ConsoleLogger($output);

        $logger->debug('Current directory: {cwd}', ['cwd' => getcwd()]);
        $logger->debug('Building classmap');

        $endpoints = $this->getFilteredClasses(
            $this->config->get(Config::KEY_SOURCE),
            function (ReflectionClass $rc): bool {
                if (!$rc->isInstantiable()) {
                    return false;
                }
                if (!$rc->implementsInterface(EndpointInterface::class)) {
                    return false;
                }
                return true;
            }
        );
        $endpointMap = array_reduce($endpoints, function (array $carry, ReflectionClass $rc) {
            $instance = $rc->newInstanceWithoutConstructor();
            assert($instance instanceof EndpointInterface); // Filtered above
            $carry[$instance->getMethod()][$instance->getUri()] = $rc->getName();
            return $carry;
        }, []);
        $endpointMap['@gener'.'ated'] = gmdate('c');
        file_put_contents(
            Dispatcher::ENDPOINT_LIST,
            sprintf("<?php\n return %s;", var_export($endpointMap, true)),
        );

        $output->writeln(sprintf(
            'Wrote endpoint map to %s',
            Dispatcher::ENDPOINT_LIST
        ));

        return 0;
    }

    /**
     * @param callable(ReflectionClass<object>): bool $filter
     * @return ReflectionClass<object>[]
     */
    private function getFilteredClasses(string $directory, callable $filter): array
    {
        /** @var array<class-string, string> */
        $cm = ClassMapGenerator::createMap($directory);
        $classes = array_keys($cm);
        $rcs = array_map(fn ($fqcn) => new ReflectionClass($fqcn), $classes);
        $result = array_filter($rcs, $filter);
        // Compact the result set
        return array_values($result);
    }
}
