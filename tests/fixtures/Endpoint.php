<?php
declare(strict_types=1);

namespace Firehed\API\fixtures;

use Firehed\API\Interfaces\EndpointInterface;
use Firehed\API\Traits\Authentication;
use Firehed\API\Traits\Input;
use Firehed\API\Traits\Request;
use Firehed\Input\Containers\SafeInput;
use Firehed\InputObjects;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class Endpoint implements EndpointInterface
{
    use Authentication\None;
    use Input\NoOptional;
    use Input\NoRequired;
    use Request\Options;

    public function handleException(Throwable $e): ResponseInterface
    {
        throw $e;
    }

    public function getUri(): string
    {
        return '/.*';
    }

    public function execute(SafeInput $input): ResponseInterface
    {
        /** @var ResponseInterface */
        $response = (new Response(204))
            ->withHeader(
                'Access-Control-Allow-Headers',
                'Authorization, Content-type'
            );
        return $response;
    }
}
