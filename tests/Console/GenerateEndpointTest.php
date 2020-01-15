<?php
declare(strict_types=1);

namespace Firehed\API\Console;

use Firehed\API\Config;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @coversDefaultClass Firehed\API\Console\GenerateEndpoint
 * @covers ::<protected>
 * @covers ::<private>
 */
class GenerateEndpointTest extends \PHPUnit\Framework\TestCase
{
    /** @var Config */
    private $config;

    public function setUp(): void
    {
        $this->config = new Config([
            Config::KEY_NAMESPACE => 'Firehed\API\TestGen',
            Config::KEY_SOURCE => 'src/TestGen',
            Config::KEY_WEBROOT => 'public',
        ]);
    }

    public function tearDown(): void
    {
        if (file_exists('src/TestGen')) {
            $this->rm('src/TestGen');
        }
    }

    /** @covers ::__construct */
    public function testConstruct(): void
    {
        $config = $this->createMock(Config::class);
        $this->assertInstanceOf(Command::class, new GenerateEndpoint($this->config));
    }

    public function testExecute(): void
    {
        $command = new GenerateEndpoint($this->config);
        $tester = new CommandTester($command);
        $tester->setInputs(['y']);
        $tester->execute([
            GenerateEndpoint::ARGUMENT_PATH => 'Foo\Bar',
        ]);
        $this->assertTrue(file_exists('src/TestGen/Foo/Bar.php'));
        $output = file_get_contents('src/TestGen/Foo/Bar.php');
        assert($output !== false);

        $lines = explode("\n", $output);

        $this->assertTrue(
            in_array('namespace Firehed\API\TestGen\Foo;', $lines),
            'Output did not contain namespace'
        );

        $this->assertTrue(
            in_array('class Bar implements EndpointInterface', $lines),
            'Output did not contain class definition'
        );
    }

    public function testExecuteWithExistingFile(): void
    {
        $command = new GenerateEndpoint($this->config);
        $tester = new CommandTester($command);
        $tester->setInputs(['y']);
        $tester->execute([
            GenerateEndpoint::ARGUMENT_PATH => 'Foo\Bar',
        ]);

        $tester->setInputs(["\n"]);
        $this->expectException(RuntimeException::class);
        $tester->execute([
            GenerateEndpoint::ARGUMENT_PATH => 'Foo\Bar',
        ]);
    }

    public function testExecuteWithoutCreatingDirectory(): void
    {
        $command = new GenerateEndpoint($this->config);
        $tester = new CommandTester($command);

        $tester->setInputs(["\n"]);
        $this->expectException(RuntimeException::class);
        $tester->execute([
            GenerateEndpoint::ARGUMENT_PATH => 'Foo\Bar',
        ]);
    }


    /**
     * Simple `rm -r` equivalent
     */
    private function rm(string $dir): bool
    {
        if (!file_exists($dir)) {
            return true;
        }
        if (!is_dir($dir)) {
            return unlink($dir);
        }
        $contents = scandir($dir);
        assert($contents !== false);
        foreach ($contents as $content) {
            if ($content === '.' || $content === '..') {
                continue;
            }
            if (!$this->rm($dir . DIRECTORY_SEPARATOR . $content)) {
                return false;
            }
        }
        return rmdir($dir);
    }
}
