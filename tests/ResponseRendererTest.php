<?php
declare(strict_types=1);

namespace Firehed\API;

use Psr\Http\Message\ResponseInterface;

/**
 * @covers Firehed\API\ResponseRenderer
 */
class ResponseRendererTest extends \PHPUnit\Framework\TestCase
{
    /**
     * The function should just blindly render the data, assuming everything is
     * reasonably well-formed and spec-compliant.
     *
     * @covers ::__construct
     * @covers ::render
     * @covers ::sendHeaders
     * @covers ::sendBody
     * @runInSeparateProcess
     */
    public function testResponseRendering()
    {
        $code = 999;
        $version = 1.2;
        $phrase = 'Just a test';

        $headers = [
            'Set-Cookie' => ['Some Cookie', 'Some other cookie'],
            'Content-type' => ['application/x-unit-test'],
        ];

        $body = 'Some text that came from the response';

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->atLeastOnce())
            ->method('getProtocolVersion')
            ->will($this->returnValue($version));
        $response->expects($this->atLeastOnce())
            ->method('getStatusCode')
            ->will($this->returnValue($code));
        $response->expects($this->atLeastOnce())
            ->method('getReasonPhrase')
            ->will($this->returnValue($phrase));
        $response->expects($this->atLeastOnce())
            ->method('getHeaders')
            ->will($this->returnValue($headers));
        $response->expects($this->atLeastOnce())
            ->method('getBody')
            ->will($this->returnValue($body));

        ob_start();
        ResponseRenderer::render($response);
        $data = ob_get_clean();

        $this->assertSame($code, http_response_code(), 'Wrong HTTP code');
        // Note: there doesn't appear to be any way to actually find the full
        // HTTP protocol header e.g. "HTTP/1.1 200 OK", just the code which is
        // tested above. Asserting that the version and reason phrase were at
        // least fetched (per the Mock setup) is basically the best that can be
        // done
        $this->assertSame($body, $data, 'Body was not sent');
        // header() doesn't actually do anything in CLI mode which unit tests
        // run in, so a normal call to headers_list() is always empty. If
        // xdebug is installed, xdebug_get_headers provides equivalent behavior
        // in all SAPIs - so use it if installed, or mark the test incomplete
        // otherwise.
        //
        // https://bugs.php.net/bug.php?id=39872
        if (!function_exists('xdebug_get_headers')) {
            $this->markTestIncomplete(
                'headers_list does not work in CLI mode to test response '.
                'rendering. Install xdebug to complete the test which uses '.
                '`xdebug_get_headers`.'
            );
        }
        $rendered_headers = xdebug_get_headers();
        $expected_headers = [
            'Set-Cookie: Some Cookie',
            'Set-Cookie: Some other cookie',
            'Content-type: application/x-unit-test',
        ];
        foreach ($expected_headers as $expected_header) {
            $this->assertContains($expected_header, $rendered_headers);
        }
    }
}
