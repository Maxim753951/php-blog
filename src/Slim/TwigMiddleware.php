<?php

declare(strict_types=1);

namespace Blog\Slim;

use Blog\Twig\AssetExtension;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use Twig\Environment;

class TwigMiddleware implements MiddlewareInterface
{
    /**
     * @var Environment
     */
    private Environment $environment;

    /**
     * @param Environment $environment
     */
    public function __construct(Environment $environment)
    {
        $this->environment = $environment; //ну типа наш view это environment
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        //new AssetExtension($request); ну корчое не так, но видимо как-то иначе

        $this->environment->addExtension(new AssetExtension($request)); // было $view->addExtension(new AssetExtension());
        //короче это всё что бы красиво передать объект $request в AssetExtension что бы уже там избавиться от ХардКода
        return $handler->handle($request); //для обработки запроса в следующих...обработчиках

        // TODO: Implement process() method. //почитать
    }
}