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

    public function setUp()
    {
        $this->config = new Config([
            'namespace' => 'Firehed\API',
            'source' => 'src',
            'webroot' => 'public',
        ]);
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
        $tester->execute([
            '--'.GenerateFrontController::OPT_DRY_RUN => true,
        ]);
        $output = $tester->getDisplay();

        $lines = explode("\n", $output);
        print_r($lines);

        $this->assertTrue(
            in_array('namespace Firehed\API\Foo;', $lines),
            'Output did not contain namespace'
        );

        $this->assertTrue(
            in_array('class Bar implements FrontControllerInterface', $lines),
            'Output did not contain class definition'
        );
    }
}
