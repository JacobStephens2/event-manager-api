<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../initialize.php';

$app = AppFactory::create();

// Parse json, form data and xml
$app->addBodyParsingMiddleware();

// Define app routes
$app->get('/', 
    function( Request $request, Response $response, $args ) {
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withHeader('Access-Control-Allow-Origin', $_ENV['ORIGIN']);
        $message = array(
            'message'=>'Hello from the Event Manager API',
            'API Origin'=>$_ENV['API_ORIGIN'],
            'GET /'=>$_ENV['API_ORIGIN'] . '/',
            'GET /hello/{name}'=>$_ENV['API_ORIGIN'] . '/hello/Jacob',
            'POST /mimic-json'=>$_ENV['API_ORIGIN'] . '/mimic-json'
        );
        $payload = json_encode($message);
        $response->getBody()->write($payload);
        return $response;
    }
);

$app->get('/hello/{name}', 
    function (Request $request, Response $response, $args) {
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withHeader('Access-Control-Allow-Origin', $_ENV['ORIGIN']);
        $name = $args['name'];
        $message = array('message'=>"Hello, $name");
        $payload = json_encode($message);
        $response->getBody()->write($payload);
        return $response;
    }
);

$app->post('/mimic-json', 
    function( Request $request, Response $response, $args ) {
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withHeader('Access-Control-Allow-Origin', $_ENV['ORIGIN']);
        $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        $requestBody = $request->getParsedBody();  
        $payload = json_encode($requestBody);
        $response->getBody()->write($payload);
        return $response;
    }
);

$app->addErrorMiddleware($_ENV['ERROR_DISPLAY'], true, true);

$app->run();