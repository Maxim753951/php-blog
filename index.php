<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Factory\AppFactory;

use \Twig\Environment;
use \Twig\Loader\FilesystemLoader;

use Blog\Twig\AssetExtension; //теперь не нужен
use Blog\Slim\TwigMiddleware;

use Blog\LatestPosts;
use Blog\PostMapper;

use Blog\Database;

use Blog\Session;

use Blog\Authorization;
use Blog\AuthorizationException;

use Blog\Creation;
use Blog\CreationException;

use Blog\CommentMapper;

require __DIR__ . '/vendor/autoload.php';

$loader = new FilesystemLoader('templates'); // Twig
$view = new Environment($loader); // указание пути местонахождения всех шаблонов и передача этого в переменную $view (ну она же Environment)

//$view->addExtension(new AssetExtension()); //не можем зарегестрировать Extension для, я так понимаю, TwigMiddleware, так как нет request
//$view передали ниже, а объект AssetExtension() уже в TwigMiddleware

/*
$config = include 'config/database.php';
$dsn = $config['dsn'];
$username = $config['username'];
$password = $config['password'];
*/

$dsn = 'mysql:host=127.0.0.1;dbname=blog_php';
$username = 'root';
$password = '';

$database= new Database($dsn, $username, $password);
$connection = $database->getConnection();

//$postMapper = new PostMapper($connection); //будем создавать локально в нужной функции

$app = AppFactory::create(); // Slim
$app->addBodyParsingMiddleware(); // аналог глобального массива пост ($_POST) и взятия прямо оттуда, но только это норм

$app->add(new TwigMiddleware($view)); // для избавления от ХардКода в AssetExtension надо передать объект $request
// но он передаётся только в том случае, когда находится какой-нибудь Роут и обрабатывается КоллБекк функция
// но у Slim есть возможность добавить так называемый Middleware, который будет отрабатывать либо при всех запросах, либо на конкретных
// и в нём мы сможем зарегестрировать наш AssetExtension, передать $request, и уже с ним переписать хард код

$session = new Session();
$sessionMiddleware = function (Request $request, RequestHandlerInterface $handler) use ($session) {
    $session->start();
    $response = $handler->handle($request);
    $session->save();

    return $response;
};
//эта КоллБекк функция будет отрабатывать для каждого запроса get или post

$app->add($sessionMiddleware); // Session

$authorization = new Authorization($database, $session);

$creation = new Creation($database, $session);


// КоллБекк функции с ЭндПоинтами/Обработчиками/Хендлерами
$app->get('/', function (Request $request, Response $response) use ($view, $connection, $session) {
    $latestPosts = new LatestPosts($connection);
    $posts = $latestPosts->get(3);

    $body = $view->render('index.twig', [
        'user' => $session->getData('user'),
        'posts' => $posts
    ]);

    //$response->getBody()->write('Hello on Home Page'); // было до twig
    $response->getBody()->write($body);

    return $response; //все такие КоллБекк функции в разрезе обработчиков Slim должны возвращать такие функции
    //get запрос - отрисовка страницы
});

$app->get('/about', function (Request $request, Response $response) use ($view, $session) {
    $body = $view->render('about.twig', [
        'user' => $session->getData('user'),
        'name' => 'max'
    ]);
    $response->getBody()->write($body);
    return $response;
});

$app->get('/edit', function (Request $request, Response $response) use ($view, $session) {
    $body = $view->render('edit.twig', [
        'user' => $session->getData('user'),
    ]);
    $response->getBody()->write($body);
    return $response;
});

$app->post('/edit-post', function (Request $request, Response $response) use ($authorization, $session) {

    return $response->withHeader('Location', '/')->withStatus(302);

});

$app->get('/blog[/{page}]', function (Request $request, Response $response, $args) use ($view, $connection, $session) {
    $postMapper = new PostMapper($connection);

    $page = isset($args['page']) ? (int) $args['page'] : 1;
    //$page = 1;
    $limit = 2;

    $posts = $postMapper->getList($page, $limit, 'DESC');

    $totalCount = $postMapper->getTotalCount();
    $body = $view->render('blog.twig', [
        'user' => $session->getData('user'),
        'posts' => $posts,
        'pagination' => [
            'current' => $page,
            'paging' => ceil($totalCount / $limit),
        ],
    ]);
    $response->getBody()->write($body);
    return $response;
});

$app->post('/search-post', function (Request $request, Response $response) use ($authorization, $session) {

    return $response->withHeader('Location', '/')->withStatus(302);

});

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

$app->get('/create', function (Request $request, Response $response) use($view, $session) {

    $body = $view->render('create.twig', [
        'user' => $session->getData('user'),
        'message' => $session->flush('message'),
        'form' => $session->flush('form'),
    ]);

    $response->getBody()->write($body);

    return $response;
});

$app->post('/create-post', function (Request $request, Response $response) use($creation, $session) {

    $params = (array) $request->getParsedBody();

    try {
        $creation->createPost($params);
    } catch (CreationException $exception) {
        $session->setData('message', $exception->getMessage());
        $session->setData('form', $params);

        return $response->withHeader('Location', '/create')->withStatus(302);
    }

    return $response->withHeader('Location', '/')->withStatus(302);
});

$app->post('/delete-post', function (Request $request, Response $response) use($creation, $session) {

    $params = (array) $request->getParsedBody();
    $url_key = $params['url_key'];


    $creation->deletePost($url_key);

    return $response->withHeader('Location', '/')->withStatus(302);
});

$app->post('/change-post', function (Request $request, Response $response) use ($authorization, $session) {

    return $response->withHeader('Location', '/')->withStatus(302);

});

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

$app->get('/login', function (Request $request, Response $response) use ($view, $session) {

    $body = $view->render('login.twig', [
        'message' => $session->flush('message'),
        'form' => $session->flush('form'),
    ]);

    $response->getBody()->write($body);

    return $response;
});

$app->post('/login-post', function (Request $request, Response $response) use ($authorization, $session) {

    //post обработка внутри кода, нельзя слать запрос перехода на страницу (ну она тупо пустая)
    $params = (array) $request->getParsedBody();

    try {
        $authorization->login($params['email'], $params['password']);
    } catch (AuthorizationException $exception) {
        $session->setData('message', $exception->getMessage());
        $session->setData('form', $params);

        return $response->withHeader('Location', '/login')->withStatus(302);
    }
    return $response->withHeader('Location', '/')->withStatus(302);
});

$app->get('/register', function (Request $request, Response $response) use($view, $session) {

    //$body = $twig->render('register.twig'); // было до Session
    $body = $view->render('register.twig', [
        'message' => $session->flush('message'),
        'form' => $session->flush('form'),
    ]);

    $response->getBody()->write($body);

    return $response;
});

$app->post('/register-post', function (Request $request, Response $response) use($authorization, $session) {

    $params = (array) $request->getParsedBody(); //вернёт все параметры, которые мы послали как post запрос
    //var_dump($params); // тестовый вывод на страницу

    try {
        $authorization->register($params);
    } catch (AuthorizationException $exception) {
        $session->setData('message', $exception->getMessage()); // сохраняем результат AuthorizationException
        $session->setData('form', $params); // вторым ключом в Сессию добавим те параметры, которые мы получили как post

        return $response->withHeader('Location', '/register')->withStatus(302);
    }

    return $response->withHeader('Location', '/')->withStatus(302);
});

$app->get('/logout', function (Request $request, Response $response) use($session) {

    $session->setData('user', null);
    return $response->withHeader('Location', '/')->withStatus(302);
});

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

$app->post('/comment-post', function (Request $request, Response $response) use($creation, $session) {

    $params = (array) $request->getParsedBody();

    $url_key = $params['url_key'];

    try {
        $creation->createComment($params);
    } catch (CreationException $exception) {
        $session->setData('message', $exception->getMessage());
        $session->setData('form', $params);

        return $response->withHeader('Location', '/' . $url_key)->withStatus(302);
    }

    return $response->withHeader('Location', '/' . $url_key)->withStatus(302);
});

$app->get('/{url_key}', function (Request $request, Response $response, $args) use ($view, $connection, $session) {
    $postMapper = new PostMapper($connection);
    $post = $postMapper->getByUrlKey((string) $args['url_key']);

    $commentMapper = new commentMapper($connection);
    $comments = $commentMapper->get((string) $args['url_key']);

    if (empty($post)){
        $body = $view->render('not-found.twig');
    } else {
        $body = $view->render('post.twig', [
            //'url_key' => $args['url_key']
            'user' => $session->getData('user'),
            'post' => $post,
            'comments' => $comments,
        ]);
    }
    $response->getBody()->write($body);
    return $response;
});


$app->run();