<?php
declare(strict_types=1);

namespace Firehed\API;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

/**
 * @coversDefaultClass Firehed\API\Config
 * @covers ::<protected>
 * @covers ::<private>
 */
class ConfigTest extends \PHPUnit\Framework\TestCase
{

    const VALID_BASIC_CONFIG = [
        Config::KEY_NAMESPACE => 'My\Company\Endpoints',
        Config::KEY_SOURCE => 'src',
        Config::KEY_WEBROOT => 'public',
    ];

    /**
     * @covers ::__construct
     * @dataProvider constructProvider
     * @param string[] $params
     * @param class-string<\Throwable> $exceptionClass
     */
    public function testConstruct(array $params, string $exceptionClass = null): void
    {
        if ($exceptionClass !== null) {
            $this->expectException($exceptionClass);
        }
        $config = new Config($params);
        $this->assertInstanceOf(Config::class, $config);
        $this->assertInstanceOf(ContainerInterface::class, $config);
    }

    /** @covers ::get */
    public function testGetInvalidKeyImplementsPsr11Behavior(): void
    {
        $config = new Config(self::VALID_BASIC_CONFIG);
        $this->expectException(NotFoundExceptionInterface::class);
        $config->get('this_key_is_not_set');
    }

    /** @covers ::get */
    public function testGetReturnsValidData(): void
    {
        $config = new Config(self::VALID_BASIC_CONFIG);
        $this->assertSame('src', $config->get(Config::KEY_SOURCE));
    }

    /** @covers ::has */
    public function testHasWorksForSetKey(): void
    {
        $config = new Config(self::VALID_BASIC_CONFIG);
        $this->assertTrue($config->has(Config::KEY_SOURCE));
    }

    /** @covers ::has */
    public function testHasWorksForUnsetKey(): void
    {
        $config = new Config(self::VALID_BASIC_CONFIG);
        $this->assertFalse($config->has('this_key_is_not_set'));
    }

    /**
     * @covers ::load
     * @dataProvider loadProvider
     * @param class-string<\Throwable> $exceptionClass
     */
    public function testLoad(string $file, string $exceptionClass = null): void
    {
        if ($exceptionClass !== null) {
            $this->expectException($exceptionClass);
        }
        $config = Config::load($file);
        $this->assertInstanceOf(Config::class, $config);
        $this->assertInstanceOf(ContainerInterface::class, $config);
    }

    /**
     * @return array<array<string|class-string<Throwable>>>
     */
    public function constructProvider(): array
    {
        return [
            [
                self::VALID_BASIC_CONFIG,
            ],
            [
                [
                    Config::KEY_CONTAINER => __DIR__.'/fixtures/psr_11.php.fixture',
                    Config::KEY_NAMESPACE => 'ns',
                    Config::KEY_SOURCE => 'src',
                    Config::KEY_WEBROOT => 'public',
                ],
            ],
            [
                [
                    Config::KEY_SOURCE => 'src',
                    Config::KEY_WEBROOT => 'public',
                ],
                RuntimeException::class,
            ],
            [
                [
                    Config::KEY_CONTAINER => 'nonexistent',
                    Config::KEY_NAMESPACE => 'ns',
                    Config::KEY_SOURCE => 'src',
                    Config::KEY_WEBROOT => 'public',
                ],
                RuntimeException::class,
            ],
            [
                [
                    Config::KEY_CONTAINER => __DIR__.'/fixtures/not_psr_11.php.fixture',
                    Config::KEY_NAMESPACE => 'ns',
                    Config::KEY_SOURCE => 'src',
                    Config::KEY_WEBROOT => 'public',
                ],
                RuntimeException::class,
            ],
        ];
    }

    /** @return string[][] */
    public function loadProvider(): array
    {
        return [
            [__DIR__.'/fixtures/valid_apiconfig.json'],
            [__DIR__.'/fixtures/invalid_apiconfig_format.json', RuntimeException::class],
            [__DIR__.'/fixtures/invalid_apiconfig_missing_data.json', RuntimeException::class],
            [__DIR__.'/fixtures/missing_file.json', RuntimeException::class],
        ];
    }
}
