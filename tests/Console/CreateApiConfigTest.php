<?php
declare(strict_types=1);

namespace Firehed\API\Console;

use Firehed\API\Config;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @coversDefaultClass Firehed\APIConsole\CreateApiConfig
 * @covers ::<protected>
 * @covers ::<private>
 *
 * @see https://github.com/symfony/symfony/pull/29754 for trailing \n in args
 */
class CreateApiConfigTest extends \PHPUnit\Framework\TestCase
{
    private $existingConfig;

    public function setUp()
    {
        if (file_exists(Config::FILENAME)) {
            // echo 'move';
            $this->existingConfig = tempnam(sys_get_temp_dir(), 'phpunit_apiconfig_');
            // var_dump($this->existingConfig);
            rename(Config::FILENAME, $this->existingConfig);
        }
    }

    public function tearDown()
    {
        unlink(Config::FILENAME);
        if ($this->existingConfig !== null) {
            rename($this->existingConfig, Config::FILENAME);
            // echo 'reset';
        }
    }

    /** @covers ::execute */
    public function testExecute()
    {
        // Sanity check that setUp moved any existing local file
        $this->assertFalse(file_exists(Config::FILENAME), 'Config already exists');
        $command = new CreateApiConfig();
        $tester = new CommandTester($command);
        $tester->setInputs([
            '',
            '',
            '',
            "\n",
        ]);
        $tester->execute([]);
        $this->assertTrue(file_exists(Config::FILENAME), 'Config not written');
        $json = file_get_contents(Config::FILENAME);
        $data = json_decode($json, true);
        $this->assertSame($data[Config::KEY_NAMESPACE], 'Firehed\\API', 'Namespace wrong');
        $this->assertSame($data[Config::KEY_SOURCE], 'src', 'Source wrong');
        $this->assertSame($data[Config::KEY_WEBROOT], 'public', 'Public wrong');
        $this->assertFalse(array_key_exists(Config::KEY_CONTAINER, $data), 'Container should not be set');
    }

    /** @covers ::execute */
    public function testOverwriteProtection()
    {
        $this->assertFalse(file_exists(Config::FILENAME), 'Config already exists');
        touch(Config::FILENAME);
        $command = new CreateApiConfig();
        $tester = new CommandTester($command);
        $tester->setInputs(["\n"]);
        $tester->execute([]);
        $this->assertSame('', file_get_contents(Config::FILENAME));
    }

    /** @covers ::execute */
    public function testOverwriteHappens()
    {
        $this->assertFalse(file_exists(Config::FILENAME), 'Config already exists');
        touch(Config::FILENAME);
        $command = new CreateApiConfig();
        $tester = new CommandTester($command);
        $tester->setInputs(['y', 'publicdir', 'sourcedir', 'Some\\Namespace', "config.php"]);
        $tester->execute([]);
        $json = file_get_contents(Config::FILENAME);
        $data = json_decode($json, true);
        $this->assertSame($data[Config::KEY_NAMESPACE], 'Some\\Namespace', 'Namespace wrong');
        $this->assertSame($data[Config::KEY_SOURCE], 'sourcedir', 'Source wrong');
        $this->assertSame($data[Config::KEY_WEBROOT], 'publicdir', 'Public wrong');
        $this->assertSame($data[Config::KEY_CONTAINER], 'config.php', 'Config wrong');
    }
}
