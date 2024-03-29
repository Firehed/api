<?php
declare(strict_types=1);

namespace Firehed\API\Console;

use Firehed\API\Config;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers Firehed\API\Console\GenerateConfig
 *
 * @see https://github.com/symfony/symfony/pull/29754 for trailing \n in args
 */
class GenerateConfigTest extends \PHPUnit\Framework\TestCase
{
    /** @var string */
    private $existingConfig;

    public function setUp(): void
    {
        if (file_exists(Config::FILENAME)) {
            $old = tempnam(sys_get_temp_dir(), 'phpunit_apiconfig_');
            assert($old !== false);
            $this->existingConfig = $old;
            rename(Config::FILENAME, $this->existingConfig);
        }
    }

    public function tearDown(): void
    {
        unlink(Config::FILENAME);
        if ($this->existingConfig !== null) {
            rename($this->existingConfig, Config::FILENAME);
        }
    }

    public function testExecute(): void
    {
        // Sanity check that setUp moved any existing local file
        $this->assertFalse(file_exists(Config::FILENAME), 'Config already exists');
        $command = new GenerateConfig();
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
        assert($json !== false);
        $data = json_decode($json, true);
        // These are the defaults inferred from Composer and the directory
        // structure
        $this->assertSame($data[Config::KEY_NAMESPACE], 'Firehed\\API', 'Namespace wrong');
        $this->assertSame($data[Config::KEY_SOURCE], 'src', 'Source wrong');
        $this->assertSame($data[Config::KEY_WEBROOT], 'public', 'Public wrong');
        $this->assertFalse(array_key_exists(Config::KEY_CONTAINER, $data), 'Container should not be set');
    }

    public function testOverwriteProtection(): void
    {
        $this->assertFalse(file_exists(Config::FILENAME), 'Config already exists');
        touch(Config::FILENAME);
        $command = new GenerateConfig();
        $tester = new CommandTester($command);
        $tester->setInputs(["\n"]); // Command should default to "no"
        $tester->execute([]);
        $this->assertSame('', file_get_contents(Config::FILENAME));
    }

    public function testOverwriteHappens(): void
    {
        $this->assertFalse(file_exists(Config::FILENAME), 'Config already exists');
        touch(Config::FILENAME);
        $command = new GenerateConfig();
        $tester = new CommandTester($command);
        $tester->setInputs(['y', 'publicdir', 'sourcedir', 'Some\\Namespace', "config.php"]);
        $tester->execute([]);
        $json = file_get_contents(Config::FILENAME);
        assert($json !== false);
        $data = json_decode($json, true);
        $this->assertSame($data[Config::KEY_NAMESPACE], 'Some\\Namespace', 'Namespace wrong');
        $this->assertSame($data[Config::KEY_SOURCE], 'sourcedir', 'Source wrong');
        $this->assertSame($data[Config::KEY_WEBROOT], 'publicdir', 'Public wrong');
        $this->assertSame($data[Config::KEY_CONTAINER], 'config.php', 'Config wrong');
    }
}
