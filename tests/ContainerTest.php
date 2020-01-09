<?php
declare(strict_types=1);

namespace Firehed\API;

use Psr\Container as Psr;

/**
 * @coversDefaultClass Firehed\API\Container
 * @covers ::<protected>
 * @covers ::<private>
 */
class ContainerTest extends \PHPUnit\Framework\TestCase
{
    /** @var Container */
    private $c;

    public function setUp(): void
    {
        $this->c = new Container(['key' => 'value']);
    }

    /** @covers ::__construct */
    public function testConstruct(): void
    {
        $this->assertInstanceOf(Psr\ContainerInterface::class, $this->c);
    }

    /** @covers ::has */
    public function testHas(): void
    {
        $this->assertTrue($this->c->has('key'));
        $this->assertFalse($this->c->has('nokey'));
    }

    /** @covers ::get */
    public function testGet(): void
    {
        $this->assertSame('value', $this->c->get('key'));
    }

    /** @covers ::get */
    public function testGetDoesNotEvaluateCallables(): void
    {
        $loader = function () {
            return new Container([]);
        };

        $container = new Container(['loader' => $loader]);
        $this->assertSame($loader, $container->get('loader'));
    }

    /** @covers ::get */
    public function testGetThrowsOnMissingKey(): void
    {
        $this->expectException(Psr\NotFoundExceptionInterface::class);
        $this->c->get('nokey');
    }
}
