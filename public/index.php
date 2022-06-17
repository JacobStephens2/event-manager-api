<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../initialize.php';

$app = AppFactory::create();

// Define app routes
$app->get('/', function( Request $request, Response $response, $args ) {
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withHeader('Access-Control-Allow-Origin', $_ENV['ORIGIN']);

        $message = array('message'=>'Hello world!');
        $json_message = json_encode($message);
        $response->getBody()->write($json_message);

        return $response;
    }
);

$app->get('/hello/{name}', function (Request $request, Response $response, $args) {
    $name = $args['name'];
    $response->getBody()->write("Hello, $name");
    return $response;
});

$app->get('/users', function( Request $request, Response $response, $args ) {
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withHeader('Access-Control-Allow-Origin', $_ENV['ORIGIN']);

        $Users = new User;
        $all_users = $Users->find_all();

        $json_message = json_encode($all_users);
        $response->getBody()->write($json_message);

        return $response;
    }
);


$app->run();