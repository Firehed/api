<?php
declare(strict_types=1);

namespace Firehed\API\Console;

use Firehed\API\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @coversDefaultClass Firehed\API\Console\GenerateEndpointList
 * @covers ::<protected>
 * @covers ::<private>
 */
class GenerateEndpointListTest extends \PHPUnit\Framework\TestCase
{
    private $config;
    private $filePrefix;

    public function setUp()
    {
        $tmp = sys_get_temp_dir() . '/';
        $this->filePrefix = $tmp . dechex(random_int(0, PHP_INT_MAX));
        $this->config = new Config([
            'namespace' => 'Firehed\API',
            'source' => 'tests',
            'webroot' => 'public',
        ]);
    }

    /** @covers ::__construct */
    public function testConstruct()
    {
        $config = $this->createMock(Config::class);
        $this->assertInstanceOf(Command::class, new GenerateEndpointList($this->config));
    }

    /** @covers ::execute */
    public function testGeneratingPhpFiles()
    {
        $el = $this->filePrefix.'endpoint.php';
        $pl = $this->filePrefix.'parser.php';
        try {
            $command = new GenerateEndpointList($this->config);
            $tester = new CommandTester($command);
            $tester->execute([
                '--'.GenerateEndpointList::OPT_ENDPOINT_LIST => $el,
                '--'.GenerateEndpointList::OPT_PARSER_LIST => $pl,
            ]);

            $this->assertFileExists($el, 'Endpoint list not generated');
            $this->assertFileExists($pl, 'Parser list not generated');
            ob_start();
            $endpoints = include $el;
            $parsers = include $pl;
            ob_end_clean();
            $this->validateEndpointList($endpoints);
            $this->validateParserList($parsers);
        } finally {
            @unlink($el);
            @unlink($pl);
        }
    }

    private function validateEndpointList($data)
    {
        $this->assertInternalType('array', $data);
        $this->assertArrayHasKey('@gener'.'ated', $data);
        // print_r($data);
        $this->assertArrayHasKey('OPTIONS', $data);
    }

    private function validateParserList($data)
    {
        $this->assertInternalType('array', $data);
        $this->assertArrayHasKey('@gener'.'ated', $data);
        $this->assertArrayHasKey('application/json', $data);
        $this->assertArrayHasKey('application/x-www-form-urlencoded', $data);
    }
}
