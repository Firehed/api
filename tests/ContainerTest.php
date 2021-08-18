<?php
declare(strict_types=1);

namespace Firehed\API;

use Psr\Container as Psr;

/**
 * @covers Firehed\API\Container
 */
class ContainerTest extends \PHPUnit\Framework\TestCase
{
    /** @var Container */
    private $c;

    public function setUp(): void
    {
        $this->c = new Container(['key' => 'value']);
    }

    public function testConstruct(): void
    {
        $this->assertInstanceOf(Psr\ContainerInterface::class, $this->c);
    }

    public function testHas(): void
    {
        $this->assertTrue($this->c->has('key'));
        $this->assertFalse($this->c->has('nokey'));
    }

    public function testGet(): void
    {
        $this->assertSame('value', $this->c->get('key'));
    }

    public function testGetDoesNotEvaluateCallables(): void
    {
        $loader = function () {
            return new Container([]);
        };

        $container = new Container(['loader' => $loader]);
        $this->assertSame($loader, $container->get('loader'));
    }

    public function testGetThrowsOnMissingKey(): void
    {
        $this->expectException(Psr\NotFoundExceptionInterface::class);
        $this->c->get('nokey');
    }
}
