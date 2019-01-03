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
    private $config;
    private $oldFrontController;

    public function setUp()
    {
        $this->config = new Config([
            'namespace' => 'Firehed\API',
            'source' => 'src',
            'webroot' => 'public',
        ]);
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
        $config = $this->createMock(Config::class);
        $this->assertInstanceOf(Command::class, new GenerateFrontController($this->config));
    }

    public function testExecute()
    {
        $command = new GenerateFrontController($this->config);
        $tester = new CommandTester($command);
        $tester->execute([]);
        $file = file_get_contents('public/index.php');
        $lines = explode("\n", $file);
        $this->assertSame('<?php', $lines[0], 'Output didn\'t start with a PHP tag');
    }
}
