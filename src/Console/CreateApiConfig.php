<?php
declare(strict_types=1);

namespace Firehed\API\Console;

use Firehed\API\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class CreateApiConfig extends Command
{
    private $questionHelper;

    protected function configure()
    {
        $this->setName('config:create')
            ->setDescription('Create an api config file');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->questionHelper = new QuestionHelper();

        $output->writeln('Hello');
        if (file_exists(Config::FILENAME)) {
            $overwriteQ = new ConfirmationQuestion(
                sprintf(
                    '%s already exists. Overwrite it? [<info>y/N</info>] ',
                    Config::FILENAME
                ),
                false
            );
            if (!$this->questionHelper->ask($input, $output, $overwriteQ)) {
                $output->writeln('Exiting.');
                return;
            }
        }

        $webroot = $this->askWithDefault(
            'What do you want for the webroot directory?',
            'public',
            $input,
            $output
        );

        $src = $this->askWithDefault(
            'What directory will contain your endpoints?',
            $this->guessSourceDirectory(),
            $input,
            $output
        );

        $namespace = $this->askWithDefault(
            'What namespace contains your endpoints?',
            $this->guessDefaultNamespace($src),
            $input,
            $output
        );

        $container = $this->askWithDefault(
            'What file contains your DI container?',
            '',
            $input,
            $output
        );

        $config = [
            Config::KEY_NAMESPACE => $namespace,
            Config::KEY_SOURCE => $src,
            Config::KEY_WEBROOT => $webroot,
        ];
        if ($container) {
            $config[Config::KEY_CONTAINER] = $container;
        }

        $json = json_encode($config, JSON_PRETTY_PRINT);
        file_put_contents(Config::FILENAME, $json);
        $output->writeln('Wrote config file');
    }

    private function askWithDefault(
        string $question,
        string $default,
        InputInterface $input,
        OutputInterface $output
    ): string {
        $question = new Question(
            sprintf('%s [<info>%s</info>] ', $question, $default),
            $default
        );
        return $this->questionHelper->ask($input, $output, $question);
    }

    private function guessSourceDirectory(): string
    {
        // TODO: examine Composer and/or look for presence of common directories
        return 'src';
    }

    /**
     * This tries to determine the namespace of the endpoints based on the
     * indicated source directory and the autoload config.
     *
     * For most installations, this can be the top-level namespace for the
     * project. However if endpoints are confined to a specific subdirectory
     * and namespace, this should be able to resolve that as well.
     */
    private function guessDefaultNamespace(string $srcDir): string
    {
        if (!file_exists('composer.json')) {
            return '';
        }

        $data = json_decode(file_get_contents('composer.json'), true);
        if (!$data) {
            return '';
        }

        if (!isset($data['autoload']['psr-4'])) {
            return '';
        }

        $autoload = $data['autoload']['psr-4'];
        $namespace = array_keys($autoload)[0];

        $autoloadBase = $autoload[$namespace];
        // Autoloading can split a NS into multiple directories, grab the first
        if (is_array($autoloadBase)) {
            $autoloadBase = $autoloadBase[0];
        }

        $srcDir = rtrim($srcDir, '/');
        $namespace = rtrim($namespace, '\\');
        $autoloadBase = rtrim($autoloadBase, '/');

        if (substr($srcDir, 0, strlen($autoloadBase)) === $autoloadBase) {
            $estimate = $namespace . '\\' . substr($srcDir, strlen($autoloadBase) + 1);
            return rtrim($estimate, '\\');
        }

        return '';
    }
}
