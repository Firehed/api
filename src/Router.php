<?php
declare(strict_types=1);

namespace Firehed\API;

use FastRoute;
use Firehed\Input\Containers\ParsedInput;
use OutOfBoundsException;
use Psr\Http\Message\ServerRequestInterface;

class Router
{
    private const CACHE_FILE = '__routes__.php';

    private $routeMap;

    /**
     * Provide the route map, in the shape of:
     * [
     *    'HTTP METHOD' => [
     *        'SOME REGEX' => 'FQCN',
     *    ],
     * ]
     * @param array $routeMap the route map
     */
    public function setData(array $routeMap)
    {
        $this->routeMap = $routeMap;
    }

    public function writeCache()
    {
        $rd = $this->getRouteData();
        file_put_contents(self::CACHE_FILE, sprintf('<?php return %s;', var_export($rd, true)));
    }

    /**
     * Routes the request to an Endpoint, either returning a tuple of the fully-
     * qualified class name of the routed endpoint and a ParsedInput object of
     * the URL parameters, or throwing an OutOfboundsException if no appropriate
     * route can be found (with the appropraite HTTP code in the excption code).
     */
    public function route(ServerRequestInterface $request): array
    {
        $dispatcher = new FastRoute\Dispatcher\GroupCountBased($this->getRouteData());
        $info = $dispatcher->dispatch(
            $request->getMethod(),
            $request->getUri()->getPath()
        );
        switch ($info[0]) {
            case FastRoute\Dispatcher::NOT_FOUND:
                throw new OutOfBoundsException('Endpoint not found', 404);
            case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                // allowed methods = $info[1]
                throw new OutOfBoundsException('Method not allowed', 405);
            case FastRoute\Dispatcher::FOUND:
                return [$info[1], new ParsedInput($info[2])];
            default:
                // @codeCoverageIgnoreStart
                throw new \DomainException('Unexpected Dispatcher route info');
                // @codeCoverageIgnoreEnd
        }
    }

    private function getRouteData(): array
    {
        if ($this->routeMap === null) {
            if (!file_exists(self::CACHE_FILE)) {
                throw new \Exception('Route file missing. Run bin/app compile:all');
            }
            return include self::CACHE_FILE;
        }
        $rc = new FastRoute\RouteCollector(
            new FastRoute\RouteParser\Std(),
            new FastRoute\DataGenerator\GroupCountBased()
        );

        // Regex-parsing regex: grab named captures
        $pattern = '#\(\?P?<(\w+)>(.*)\)#';
        foreach ($this->routeMap as $method => $routes) {
            foreach ($routes as $regex => $fqcn) {
                $frUri = preg_replace($pattern, '{\1:\2}', $regex);
                $stripped = strtr($frUri, ['(' => '', ')' => '']);
                // echo "$regex => $frUri => $stripped\n";
                $rc ->addRoute($method, $stripped, $fqcn);
            }
        }

        return $rc->getData();
    }
}
