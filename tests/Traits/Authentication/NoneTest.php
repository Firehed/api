<?php
declare(strict_types=1);

namespace Firehed\API\Traits\Authentication;

use Firehed\API\Traits;
use Firehed\Input\Containers\SafeInput;
use Psr\Http\Message;

/**
 * @coversDefaultClass Firehed\API\Traits\Authentication\None
 */
class NoneTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @covers ::authenticate
     */
    public function testAuthenticate()
    {
        $obj = new class implements \Firehed\API\Interfaces\EndpointInterface {
            use None;
            use Traits\Request\Get;
            use Traits\Input\NoRequired;
            use Traits\Input\NoOptional;
            public function getUri(): string
            {
                throw new \BadMethodCallException();
            }
            public function handleException(\Throwable $e): Message\ResponseInterface
            {
                throw new \BadMethodCallException();
            }
            public function execute(SafeInput $input): Message\ResponseInterface
            {
                throw new \BadMethodCallException();
            }
        };

        $request = $this->createMock(Message\RequestInterface::class);

        $this->assertEquals(
            $obj,
            $obj->authenticate($request),
            'authenticate did not return $this'
        );
    }
}
