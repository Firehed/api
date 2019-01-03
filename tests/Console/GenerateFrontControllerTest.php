<?php
declare(strict_types=1);

namespace Firehed\API\Console;

use Firehed\API\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @coversDefaultClass Firehed\API\Console\GenerateFrontController
 * @covers ::<protected>
 * @covers ::<private>
 */
class GenerateFrontControllerTest extends \PHPUnit\Framework\TestCase
{
    private $oldFrontController;

    public function setUp()
    {
        if (file_exists('public/index.php')) {
            $this->oldFrontController = tempnam(sys_get_temp_dir(), 'phpunit_fc_');
            rename('public/index.php', $this->oldFrontController);
        }
    }

    public function tearDown()
    {
        if (file_exists('public/index.php')) {
            unlink('public/index.php');
        }
        if ($this->oldFrontController !== null) {
            rename($this->oldFrontController, 'public/index.php');
        }
    }

    /** @covers ::__construct */
    public function testConstruct()
    {
        /** @var Config */
        $config = $this->createMock(Config::class);
        $this->assertInstanceOf(Command::class, new GenerateFrontController($config));
    }

    public function testExecute()
    {
        $config = new Config([
            Config::KEY_NAMESPACE => 'Firehed\API',
            Config::KEY_SOURCE => 'src',
            Config::KEY_WEBROOT => 'public',
        ]);
        $command = new GenerateFrontController($config);
        $tester = new CommandTester($command);
        $tester->execute([]);
        $file = file_get_contents('public/index.php');
        $lines = explode("\n", $file);
        $this->assertSame('<?php', $lines[0], 'Output didn\'t start with a PHP tag');
    }

    public function testExecuteWithContainer()
    {
        $config = new Config([
            Config::KEY_CONTAINER => __DIR__ . '/config.php',
            Config::KEY_NAMESPACE => 'Firehed\API',
            Config::KEY_SOURCE => 'src',
            Config::KEY_WEBROOT => 'public',
        ]);
        $command = new GenerateFrontController($config);
        $tester = new CommandTester($command);
        $tester->execute([]);
        $file = file_get_contents('public/index.php');
        $lines = explode("\n", $file);
        $this->assertSame('<?php', $lines[0], 'Output didn\'t start with a PHP tag');

        $this->assertNotFalse(
            strpos($file, __DIR__ . '/config.php'),
            'Config file not set in output'
        );
    }
}
