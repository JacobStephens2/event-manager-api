<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Firebase\JWT\JWT;

require __DIR__ . '/../initialize.php';

$app = AppFactory::create();

// Parse json, form data and xml
$app->addBodyParsingMiddleware();

// Define app routes
$app->get('/', 
    function( Request $request, Response $response, $args ) {
        $message = array(
            'message'=>'Hello from the Event Manager API',
            'UI Origin'=>$_ENV['REQUEST_ORIGIN'],
            'UI Repository'=>'https://github.com/JacobStephens2/event-manager-ui',
            'API Origin'=>$_ENV['API_ORIGIN'],
            'API Repository'=>'https://github.com/JacobStephens2/event-manager-api',
            'endpoints'=>array(
                'GET /'=>$_ENV['API_ORIGIN'] . '/',
                'GET /hello/{name}'=>$_ENV['API_ORIGIN'] . '/hello/Jacob',
                'POST /mimic-json'=>$_ENV['API_ORIGIN'] . '/mimic-json',
                'POST /login'=>$_ENV['API_ORIGIN'] . '/login',
                'POST /sign-up'=>$_ENV['API_ORIGIN'] . '/sign-up'
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
        $verified_user = $user->verify_login_credentials( 
                                    $requestBody['email'], 
                                    $requestBody['password'] 
                                );
        // create response
        $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        if( $verified_user ) {
            $message = new stdClass();
            $message->message = 'Log in succeeded';
            // Create JWT access token cookie for response 
            $issuedAt   = new DateTimeImmutable();
            $jwt_access_token_data = [
                // Issued at: time when the token was generated
                'iat'  => $issuedAt->getTimestamp(),  
                'iss'  => $_SERVER['SERVER_NAME'], // Issuer
                'nbf'  => $issuedAt->getTimestamp(), // Not before 
                'exp'  => $issuedAt->modify('+60 minutes')->getTimestamp(), // Expire                      
                'user_id' => $verified_user->id,
            ];
            $access_token = JWT::encode(
                $jwt_access_token_data,
                $_ENV['JWT_SECRET'],
                'HS256'
            );
            ( $_ENV['COOKIE_SECURE'] === 'true' ) 
                ? $cookie_secure = true 
                : $cookie_secure = false;
            setcookie(
                "access_token",         // name
                $access_token,          // value
                time() + (86400 * 7),   // expire, 86400 = 1 day
                "",                     // path
                $_ENV['API_DOMAIN'],    // domain
                $cookie_secure,         // if true, send cookie only to https requests
                true                    // httponly
            ); // End of JWT Access Token Cookie creation
            $message->logged_in = 'true';
            $responseBody = json_encode($message);
            $response->getBody()->write($responseBody);
            return $response;
        } else {
            $message = array('message'=>"Log in failed");
            $responseBody = json_encode($message);
            $response->getBody()->write($responseBody);
            return $response;
        }
    }
);

$app->post('/sign-up', 
    function( Request $request, Response $response, $args ) {
        $requestBody = $request->getParsedBody();  
        $user = new User($requestBody);
        $result = $user->createUser( $requestBody['email'], $requestBody['password'] );
        $responseBody = new stdClass();
        if( $result ) {
            $responseBody->message = 'Account creation succeeded';
        } else {
            $responseBody->message = 'Use a different email address';
        }
        $responseBodyJSON = json_encode($responseBody);
        $response->getBody()->write($responseBodyJSON);
        return $response;
    }
);

$app->post('/create-event',
    function( Request $request, Response $response, $args ) {
        $requestBody = $request->getParsedBody();  
        $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        $access_token = authenticate();
        if ($access_token == false) {
            $message = new stdClass();
            $message->message = 'You have not been authorized to see this page';
            $responseBody = json_encode($message);
            $response->getBody()->write($responseBody);
            return $response;
        }
        $event = new Event($requestBody);
        $event->merge_attributes($requestBody);
        $event->save();
        $responseBody = json_encode($event);
        $response->getBody()->write($responseBody);
        return $response;
    }
);

$app->get('/all-events',
    function( Request $request, Response $response, $args ) {
        $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        $access_token = authenticate();
        if ($access_token == false) {
            $message = new stdClass();
            $message->message = 'You have not been authorized to see this page';
            $responseBody = json_encode($message);
            $response->getBody()->write($responseBody);
            return $response;
        }
        $events = Event::find_all();
        $responseBody = json_encode($events);
        $response->getBody()->write($responseBody);
        return $response;
    }
);

$app->get('/event/{id}',
    function( Request $request, Response $response, $args ) {
        $id = $args['id'];
        $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        $access_token = authenticate();
        if ($access_token == false) {
            $message = new stdClass();
            $message->message = 'You have not been authorized to see this page';
            $responseBody = json_encode($message);
            $response->getBody()->write($responseBody);
            return $response;
        }
        $event = Event::find_by_id($id);
        $responseBody = json_encode($event);
        $response->getBody()->write($responseBody);
        return $response;
    }
);

$app->put('/update-event/{id}',
    function( Request $request, Response $response, $args ) {
        $id = $args['id'];
        $requestBody = $request->getParsedBody();  
        $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        $access_token = authenticate();
        if ($access_token == false) {
            $message = new stdClass();
            $message->message = 'You have not been authorized to see this page';
            $responseBody = json_encode($message);
            $response->getBody()->write($responseBody);
            return $response;
        }
        $event = new Event($requestBody);
        $event->merge_attributes($requestBody);
        $event->save();
        $responseBody = json_encode($event);
        $response->getBody()->write($responseBody);
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