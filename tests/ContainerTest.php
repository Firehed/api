<?php
declare(strict_types=1);

namespace Firehed\API;

use Psr\Container as Psr;

/**
 * @coversDefaultClass Firehed\API\Container
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
    public function testConstruct()
    {
        $this->assertInstanceOf(Psr\ContainerInterface::class, $this->c);
    }

    /** @covers ::has */
    public function testHas()
    {
        $this->assertTrue($this->c->has('key'));
        $this->assertFalse($this->c->has('nokey'));
    }

    /** @covers ::get */
    public function testGet()
    {
        $this->assertSame('value', $this->c->get('key'));
    }

    /** @covers ::get */
    public function testGetDoesNotEvaluateCallables()
    {
        $loader = function () {
            return new Container([]);
        };

        $container = new Container(['loader' => $loader]);
        $this->assertSame($loader, $container->get('loader'));
    }

    /** @covers ::get */
    public function testGetThrowsOnMissingKey()
    {
        $this->expectException(Psr\NotFoundExceptionInterface::class);
        $this->c->get('nokey');
    }
}
