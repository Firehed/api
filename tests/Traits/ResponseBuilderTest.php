<?php
declare(strict_types=1);

namespace Firehed\API\Traits;

use Psr\Http\Message\ResponseInterface;

/**
 * @coversDefaultClass Firehed\API\Traits\ResponseBuilder
 * @covers ::<protected>
 * @covers ::<private>
 */
class ResponseBuilderTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @covers ::emptyResponse
     */
    public function testEmptyResponse()
    {
        $impl = new class { use ResponseBuilder;

        };

        $code = random_int(400, 417);
        $response = $impl->emptyResponse($code);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEmpty(
            $response->getHeaderLine('content-type'),
            'Content-type header'
        );
        $this->assertSame(
            $code,
            $response->getStatusCode(),
            'Status code was not set'
        );
        $this->assertEmpty(
            (string)$response->getBody(),
            'Body was not empty'
        );
    }

    /**
     * @covers ::htmlResponse
     */
    public function testHtmlResponse()
    {
        $impl = new class { use ResponseBuilder;

        };

        $code = random_int(400, 417);
        $html = '<html><body><p>Hi</p></body></html>';
        $response = $impl->htmlResponse($html, $code);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(
            'text/html; charset=utf-8',
            $response->getHeaderLine('content-type'),
            'Wrong content-type header'
        );
        $this->assertSame(
            $code,
            $response->getStatusCode(),
            'Status code was not set'
        );
        $this->assertSame(
            $html,
            (string)$response->getBody(),
            'Body was rendered incorrectly'
        );
    }

    /**
     * @covers ::jsonResponse
     * @dataProvider jsonData
     */
    public function testJsonResponse($data)
    {
        $impl = new class { use ResponseBuilder;

        };

        $code = random_int(400, 417);
        $response = $impl->jsonResponse($data, $code);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(
            'application/json',
            $response->getHeaderLine('content-type'),
            'Wrong content-type header'
        );
        $this->assertSame(
            $code,
            $response->getStatusCode(),
            'Status code was not set'
        );
        $this->assertSame(
            json_encode($data),
            (string)$response->getBody(),
            'Body was rendered incorrectly'
        );
    }

    /**
     * @covers ::textResponse
     */
    public function testtextResponse()
    {
        $impl = new class { use ResponseBuilder;

        };

        $code = random_int(400, 417);
        $text = 'Hello, world!';
        $response = $impl->textResponse($text, $code);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(
            'text/plain; charset=utf-8',
            $response->getHeaderLine('content-type'),
            'Wrong content-type header'
        );
        $this->assertSame(
            $code,
            $response->getStatusCode(),
            'Status code was not set'
        );
        $this->assertSame(
            $text,
            (string)$response->getBody(),
            'Body was rendered incorrectly'
        );
    }


    public function jsonData(): array
    {
        return [
            [1],
            [false],
            [null],
            ['some string'],
            [[1,2,3]],
            [[true, false]],
            [['a', 'b', 'c']],
            [['key' => 'value']],
        ];
    }
}
