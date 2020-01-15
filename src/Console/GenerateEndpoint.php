<?php
declare(strict_types=1);

namespace Firehed\API\Console;

use Firehed\API\Config;
use Firehed\Common\ClassMapGenerator;
use Psr\Log\LogLevel;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class GenerateEndpoint extends Command
{
    const ARGUMENT_PATH = 'relative_path';

    const TEMPLATE_FILE = 'Endpoint.php.tpl';

    /** @var Config */
    private $config;

    public function __construct(Config $config)
    {
        parent::__construct();
        $this->config = $config;
    }

    protected function configure(): void
    {
        $this->setName('generate:endpoint')
            ->setDescription('Generate skeleton code for a new endpoint')
            ->addArgument(self::ARGUMENT_PATH, InputArgument::REQUIRED, 'Where?')
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $verbosityLevelMap = [
            LogLevel::INFO => OutputInterface::VERBOSITY_NORMAL,
            LogLevel::NOTICE => OutputInterface::VERBOSITY_NORMAL,
        ];
        $logger = new ConsoleLogger($output, $verbosityLevelMap);

        $relativePath = $input->getArgument(self::ARGUMENT_PATH);
        assert(is_string($relativePath));
        $normalizedPath = str_replace('\\', '/', $relativePath);
        $logger->debug('Building endpoint for {path}', [
            'path' => $normalizedPath,
        ]);

        $fqcn = $this->config->get(Config::KEY_NAMESPACE)
            . '\\' . str_replace('/', '\\', $relativePath);
        $logger->debug('Target class: {class}', [
            'class' => $fqcn,
        ]);

        list($namespace, $class) = $this->parseFQCN($fqcn);
        $logger->debug('Resolved to class {class} in NS {ns}', [
            'class' => $class,
            'ns' => $namespace,
        ]);

        $template = file_get_contents(__DIR__.'/'.self::TEMPLATE_FILE);

        assert($template !== false);
        $rendered = sprintf(
            $template,
            $namespace,
            $class,
            $normalizedPath
        );
        $logger->debug($rendered);

        $destination = $this->getPathForFQCN($fqcn, $this->config->get(Config::KEY_SOURCE));
        if (!$destination) {
            throw new RuntimeException('Could not determine where to write the file');
        }
        $logger->debug('Target file is {dest}', [
            'dest' => $destination,
        ]);

        $this->writeFile($destination, $rendered, $input, $output);
        return 0;
    }

    private function getPathForFQCN(string $fqcn, string $sourceDir): string
    {
        // It would be nice to just use the composer loader directly to do
        // this, but there doesn't seem to be a way to figure out where a file
        // would be expected for a class that doesn't yet exist. This is probably
        // due at least in part to the fact that autoload definitions could
        // yield multiple locations for a given class.
        if (!file_exists('composer.json') || !is_readable('composer.json')) {
            throw new RuntimeException('composer.json not found or not readable');
        }
        $composerJson = file_get_contents('composer.json');
        assert($composerJson !== false);
        $composerConfig = json_decode($composerJson, true);
        if (!isset($composerConfig['autoload']['psr-4'])) {
            throw new RuntimeException('Only PSR-4 autoload definitions can be '
                . 'used with the endpoint generator');
        }
        foreach ($composerConfig['autoload']['psr-4'] as $prefix => $paths) {
            // Ensure there is always a trailing slash
            if (substr($prefix, -1) !== '\\') {
                $prefix .= '\\';
            }
            // PSR-4 prefix outside of the target
            if (0 !== strpos($fqcn, $prefix)) {
                continue;
            }
            // Look for the autoloader path that matches
            foreach ((array) $paths as $path) {
                // This isn't perfect - basically the first path spec for any
                // autoload candidate will be used as the target destination.
                // For most common setups this will be fine. If you're reading
                // this because the file went to an unexpected location, your
                // best bet is to add a level of specificity to your
                // .apiconfig's source and namespace values.
                if (0 === strpos($sourceDir, $path)) {
                    $relative = substr($fqcn, strlen($prefix));
                    $dest = sprintf('%s/%s.php', $path, $relative);
                    // This is a bit of path munging
                    /** @var string */
                    return preg_replace(
                        '#/{2,}#',
                        DIRECTORY_SEPARATOR,
                        str_replace('\\', '/', $dest)
                    );
                }
            }
        }

        return '';
    }

    /**
     * Parse a fully-qualified class name into the namespace and class
     * components.
     *
     * @param string $fqcn
     * @return array<string> [namespace, class]
     */
    private function parseFQCN(string $fqcn): array
    {
        $last = strrpos($fqcn, '\\');
        assert($last !== false);
        return [
            substr($fqcn, 0, $last),
            substr($fqcn, $last + 1),
        ];
    }

    private function writeFile(
        string $filename,
        string $contents,
        InputInterface $input,
        OutputInterface $output
    ): void {
        if (file_exists($filename)) {
            throw new RuntimeException('File already exists');
        }
        if (!file_exists(dirname($filename))) {
            $helper = new QuestionHelper();
            $question = new ConfirmationQuestion(sprintf(
                'Directory %s%s does not exist. Create it? [y/N] ',
                dirname($filename),
                DIRECTORY_SEPARATOR
            ), false);

            if (!$helper->ask($input, $output, $question)) {
                throw new RuntimeException('Aborting');
            }
            mkdir(dirname($filename), 0755, true);
        }
        file_put_contents($filename, $contents);

        $output->writeln(sprintf('Endpoint written to %s', $filename));
    }
}
