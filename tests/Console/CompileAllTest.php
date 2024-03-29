<?php
declare(strict_types=1);

namespace Firehed\API\Console;

use Firehed\API\Config;
use Firehed\API\Dispatcher;
use Firehed\API\EndpointFixture;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers Firehed\API\Console\CompileAll
 */
class CompileAllTest extends \PHPUnit\Framework\TestCase
{
    /** @var Config */
    private $config;

    public function setUp(): void
    {
        $this->config = new Config([
            'namespace' => 'Firehed\API',
            'source' => 'tests',
            'webroot' => 'public',
        ]);
    }

    public function testConstruct(): void
    {
        $config = $this->createMock(Config::class);
        $this->assertInstanceOf(Command::class, new CompileAll($this->config));
    }

    public function testExecute(): void
    {
        try {
            $command = new CompileAll($this->config);
            $tester = new CommandTester($command);
            $tester->execute([]);
            $this->assertFileExists(Dispatcher::ENDPOINT_LIST, 'Endpoint list not generated');
            $endpoints = include Dispatcher::ENDPOINT_LIST;
            $this->validateEndpointList($endpoints);
        } finally {
            @unlink(Dispatcher::ENDPOINT_LIST);
        }
    }

    /**
     * @param string[][] $data
     */
    private function validateEndpointList(array $data): void
    {
        $this->assertArrayHasKey('@gener'.'ated', $data);
        $this->assertArrayHasKey('GET', $data);
        $this->assertContains(EndpointFixture::class, $data['GET']);
    }
}
