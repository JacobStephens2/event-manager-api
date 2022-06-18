<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Firebase\JWT\JWT;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../initialize.php';

$app = AppFactory::create();

// Parse json, form data and xml
$app->addBodyParsingMiddleware();

// Define app routes
$app->get('/', 
    function( Request $request, Response $response, $args ) {
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withHeader('Access-Control-Allow-Origin', $_ENV['REQUEST_ORIGIN']);
        $message = array(
            'message'=>'Hello from the Event Manager API',
            'UI Origin'=>$_ENV['REQUEST_ORIGIN'],
            'UI Repository'=>'https://github.com/JacobStephens2/event-manager-ui',
            'API Origin'=>$_ENV['API_ORIGIN'],
            'API Repository'=>'https://github.com/JacobStephens2/event-manager-api',
            'endpoints'=>array(
                'GET /'=>$_ENV['API_ORIGIN'] . '/',
                'GET /hello/{name}'=>$_ENV['API_ORIGIN'] . '/hello/Jacob',
                'POST /login'=>$_ENV['API_ORIGIN'] . '/login'
            )
        );
        $payload = json_encode($message);
        $response->getBody()->write($payload);
        return $response;
    }
);

$app->post('/', 
    function( Request $request, Response $response, $args ) {
        $message = array(
            'message'=>'Hello from the Event Manager API',
        );
        $payload = json_encode($message);
        $response->getBody()->write($payload);
        return $response;
    }
);

$app->post('/mimic-json', 
    function( Request $request, Response $response, $args ) {
        $requestBody = $request->getParsedBody();  
        $responseBody = json_encode($requestBody);
        $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        $response->getBody()->write($responseBody);
        return $response;
    }
);

$app->get('/hello/{name}', 
    function (Request $request, Response $response, $args) {
        $name = $args['name'];
        $message = array('message'=>"Hello, $name");
        $responseBody = json_encode($message);
        $response->getBody()->write($responseBody);
        return $response;
    }
);

$app->post('/login', 
    function( Request $request, Response $response, $args ) {
        // get request body
        $requestBody = $request->getParsedBody();  
        // verify user
        $user = new User();
        $verified_user = $user->verify_login_credentials( $requestBody['email'], $requestBody['password'] );
        // create response
        $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        if( $verified_user ) {
            $message = array('message'=>"Log in succeeded");
            $responseBody = json_encode($message);
            $response->getBody()->write($responseBody);
        } else {
            $message = array('message'=>"Log in failed");
            $responseBody = json_encode($message);
            $response->getBody()->write($responseBody);
        }
        return $response;
    }
);

if ($_ENV['ERROR_DISPLAY'] == 'false') {
    $error_display = false;
} else {
    $error_display = true;
}
$app->addErrorMiddleware($error_display, true, true);

$app->run();