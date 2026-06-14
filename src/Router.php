<?php

declare(strict_types=1);

namespace Demo;

use Demo\Http\Response;
use Throwable;
use Xakki\FileUploader\Exception\AttentionException;
use Xakki\FileUploader\Exception\AuthorizationException;
use Xakki\FileUploader\Protocol\ResponseFactory;

/**
 * Tiny HTTP router. Routes are (method, exact-path | regex) → callable. The first
 * match wins; its handler runs inside the shared protocol exception handling and
 * is expected to terminate the request (the controllers exit via Response). If no
 * route matches, a 404 JSON envelope is returned.
 */
final class Router
{
    /** @var list<array{method: string, pattern: string, regex: bool, handler: callable}> */
    private array $routes = [];

    public function __construct(
        private readonly Response $response,
    ) {}

    /**
     * Register a route. $pattern is an exact path, or a regex (delimited, with
     * capture groups) when $regex is true; captures are passed to the handler.
     */
    public function add(string $method, string $pattern, callable $handler, bool $regex = false): void
    {
        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'regex' => $regex,
            'handler' => $handler,
        ];
    }

    public function get(string $pattern, callable $handler, bool $regex = false): void
    {
        $this->add('GET', $pattern, $handler, $regex);
    }

    public function post(string $pattern, callable $handler, bool $regex = false): void
    {
        $this->add('POST', $pattern, $handler, $regex);
    }

    public function delete(string $pattern, callable $handler, bool $regex = false): void
    {
        $this->add('DELETE', $pattern, $handler, $regex);
    }

    /**
     * Match a route and run its handler (handlers terminate the request). On no
     * match, emit a 404. Protocol exceptions map to 422/403; anything else → 500.
     */
    public function dispatch(string $method, string $path): never
    {
        try {
            foreach ($this->routes as $route) {
                if ($route['method'] !== $method) {
                    continue;
                }

                if ($route['regex']) {
                    if (preg_match($route['pattern'], $path, $m)) {
                        $route['handler'](...array_slice($m, 1));
                    }

                    continue;
                }

                if ($route['pattern'] === $path) {
                    $route['handler']();
                }
            }
        } catch (AttentionException $e) {
            $this->response->json(ResponseFactory::error($e->getMessage()), 422);
        } catch (AuthorizationException $e) {
            $this->response->json(ResponseFactory::error($e->getMessage()), 403);
        } catch (Throwable $e) {
            $this->response->json(ResponseFactory::error($e->getMessage()), 500);
        }

        $this->response->json(ResponseFactory::error('Not found'), 404);
    }
}
