<?php
declare(strict_types=1);

namespace Firehed\API\Console;

use Firehed\API\Config;
use Firehed\Common\ClassMapGenerator;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateFrontController extends Command
{
    const TEMPLATE_FILE = 'FrontController.php.tpl';

    /** @var Config */
    private $config;

    public function __construct(Config $config)
    {
        parent::__construct();
        $this->config = $config;
    }

    protected function configure()
    {
        $this->setName('generate:frontController')
            ->setDescription('Generate the default front-contoller')
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $verbosityLevelMap = [
            LogLevel::INFO => OutputInterface::VERBOSITY_NORMAL,
            LogLevel::NOTICE => OutputInterface::VERBOSITY_NORMAL,
        ];
        $logger = new ConsoleLogger($output, $verbosityLevelMap);

        if ($this->config->has(Config::KEY_CONTAINER)) {
            $logger->debug('Using configured container');
            $container = sprintf("require '%s'", $this->config->get(Config::KEY_CONTAINER));
        } else {
            $logger->debug('No container configured');
            $container = 'null';
        }

        $webroot = $this->config->get(Config::KEY_WEBROOT);

        $template = file_get_contents(__DIR__.'/'.self::TEMPLATE_FILE);
        $frontController = sprintf(
            $template,
            $this->resolveRelativeProjectRoot($webroot),
            $container
        );

        if (!file_exists($webroot)) {
            $logger->notice('Webroot directory does not exist, creating');
            mkdir($webroot, 0755, true);
        }
        $path = $webroot . '/index.php';
        file_put_contents($path, $frontController);
        $logger->info('Wrote front controller to {path}', [
            'path' => $path,
        ]);
    }

    private function resolveRelativeProjectRoot(string $webroot)
    {
        $dirs = explode('/', $webroot);
        return str_repeat('/..', count($dirs));
    }
}
