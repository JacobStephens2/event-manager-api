<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Firebase\JWT\JWT;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../initialize.php';

$app = AppFactory::create();

// Parse json, form data and xml
$app->addBodyParsingMiddleware();

// Define app routes

// Users
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

    $app->post('/logout', 
        function( Request $request, Response $response, $args ) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
            ( $_ENV['COOKIE_SECURE'] === 'true' ) 
                ? $cookie_secure = true 
                : $cookie_secure = false;
            setcookie(
                "access_token", // name
                "loggedOut", // value
                time() + (86400 * 7), // expire, 86400 = 1 day
                "", // path
                $_ENV['API_DOMAIN'], // domain
                $cookie_secure, // secure
                true // httponly
            ); 
            $message = new stdClass();
            $message->logged_in = 'false';
            $responseBody = json_encode($message);
            $response->getBody()->write($responseBody);
            return $response;
        }
    );
//

// Events
    $app->post('/event',
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
            $requestBodyWithUserID = $requestBody;
            $requestBodyWithUserID['user_id'] = $access_token->user_id;
            // Save event
            $event = new Event();
            $event->merge_attributes($requestBodyWithUserID);
            $event->save();

            // Relate event to client if client id submitted
            $client_event_data['client_id'] = $requestBody['client_id'];
            $client_event_data['event_id'] = $event->id;
            $client_event_data['user_id'] = $access_token->user_id;
            $client_event = new ClientEvent();
            $clientEventResult = $client_event->create_client_event_by_user_id(
                $requestBody['client_id'],
                $event->id,
                $access_token->user_id
            ); 

            $responseBody = json_encode($event);
            $response->getBody()->write($responseBody);
            return $response;
        }
    );

    $app->get('/events',
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
            $ClientEvents = new ClientEvent();
            $results = $ClientEvents->get_events_and_clients_by_user_id($access_token->user_id);
            $responseBody = json_encode($results);
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
            $event = Event::find_by_id_and_user_id(
                $id, 
                $access_token->user_id
            );
            $responseBody = json_encode($event);
            $response->getBody()->write($responseBody);
            return $response;
        }
    );

    $app->get('/event/{id}/tasks',
        function( Request $request, Response $response, $args ) {
            $event_id = $args['id'];
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
            $access_token = authenticate();
            if ($access_token == false) {
                $message = new stdClass();
                $message->message = 'You have not been authorized to see this page';
                $responseBody = json_encode($message);
                $response->getBody()->write($responseBody);
                return $response;
            }
            $Event = new Event();
            $results = $Event->get_tasks_by_event_id_and_by_user_id(
                $event_id,
                $access_token->user_id
            );
            $responseBody = json_encode($results);
            $response->getBody()->write($responseBody);
            return $response;
        }
    );


    $app->put('/event',
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
            $requestBodyWithUserID = $requestBody;
            $requestBodyWithUserID['user_id'] = $access_token->user_id;
            $event = new Event();
            $event->merge_attributes($requestBodyWithUserID);
            $result = $event->save_by_user_id();
            if ($result === true) {
                $responseBody = json_encode($event);
            } else {
                $responseBody = json_encode($result);
            }
            $response->getBody()->write($responseBody);
            return $response;
        }
    );

    $app->delete('/event',
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
            $requestBodyWithUserID = $requestBody;
            $requestBodyWithUserID['user_id'] = $access_token->user_id;
            $event = new Event();
            $event->merge_attributes($requestBodyWithUserID);
            $result = $event->delete_by_user_id();
            if ($result === true) {
                $responseBody = json_encode($event);
            } else {
                $responseBody = json_encode($result);
            }
            $response->getBody()->write($responseBody);
            return $response;
        }
    );
//

// Clients
    $app->post('/client',
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
            $requestBodyWithUserID = $requestBody;
            $requestBodyWithUserID['user_id'] = $access_token->user_id;
            $client = new Client();
            $client->merge_attributes($requestBodyWithUserID);
            $client->save();
            $responseBody = json_encode($client);
            $response->getBody()->write($responseBody);
            return $response;
        }
    );

    $app->get('/clients',
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
            $clients = Client::find_all_by_user_id($access_token->user_id);
            $responseBody = json_encode($clients);
            $response->getBody()->write($responseBody);
            return $response;
        }
    );

    $app->get('/client/{id}',
        function( Request $request, Response $response, $args ) {
            $client_id = $args['id'];
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
            $access_token = authenticate();
            if ($access_token == false) {
                $message = new stdClass();
                $message->message = 'You have not been authorized to see this page';
                $responseBody = json_encode($message);
                $response->getBody()->write($responseBody);
                return $response;
            }
            $client = Client::find_by_id_and_user_id($client_id, $access_token->user_id);
            $responseBody = json_encode($client);
            $response->getBody()->write($responseBody);
            return $response;
        }
    );

    $app->put('/client',
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
            $requestBodyWithUserID = $requestBody;
            $requestBodyWithUserID['user_id'] = $access_token->user_id;
            $client = new Client();
            $client->merge_attributes($requestBodyWithUserID);
            $result = $client->save_by_user_id();
            if ($result === true) {
                $responseBody = json_encode($client);
            } else {
                $responseBody = json_encode($result);
            }        $response->getBody()->write($responseBody);
            return $response;
        }
    );

    $app->delete('/client',
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
            $requestBodyWithUserID = $requestBody;
            $requestBodyWithUserID['user_id'] = $access_token->user_id;
            $client = new Client();
            $client->merge_attributes($requestBodyWithUserID);
            $result = $client->delete_by_user_id();
            if ($result === true) {
                $responseBody = json_encode($client);
            } else {
                $responseBody = json_encode($result);
            }
            $response->getBody()->write($responseBody);
            return $response;
        }
    );

    $app->get('/client/{id}/events',
        function( Request $request, Response $response, $args ) {
            $client_id = $args['id'];
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
            $access_token = authenticate();
            if ($access_token == false) {
                $message = new stdClass();
                $message->message = 'You have not been authorized to see this page';
                $responseBody = json_encode($message);
                $response->getBody()->write($responseBody);
                return $response;
            }
            $ClientEvents = new ClientEvent();
            $results = $ClientEvents->get_events_by_client_id_and_by_user_id(
                $client_id,
                $access_token->user_id
            );
            $responseBody = json_encode($results);
            $response->getBody()->write($responseBody);
            return $response;
        }
    );
//

// Event Tasks
    $app->post('/task',
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
            $requestBodyWithUserID = $requestBody;
            $requestBodyWithUserID['user_id'] = $access_token->user_id;
            $task = new Task();
            $task->merge_attributes($requestBodyWithUserID);
            $task->save();
            $responseBody = json_encode($task);
            $response->getBody()->write($responseBody);
            return $response;
        }
    );

    $app->get('/tasks',
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
            $Task = new Task;
            $tasks = $Task->get_tasks_and_events_by_user_id($access_token->user_id);
            $responseBody = json_encode($tasks);
            $response->getBody()->write($responseBody);
            return $response;
        }
    );

    $app->get('/task/{id}',
        function( Request $request, Response $response, $args ) {
            $task_id = $args['id'];
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
            $access_token = authenticate();
            if ($access_token == false) {
                $message = new stdClass();
                $message->message = 'You have not been authorized to see this page';
                $responseBody = json_encode($message);
                $response->getBody()->write($responseBody);
                return $response;
            }
            $task = Task::find_by_id_and_user_id($task_id, $access_token->user_id);
            $responseBody = json_encode($task);
            $response->getBody()->write($responseBody);
            return $response;
        }
    );

    $app->put('/task',
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
            $requestBodyWithUserID = $requestBody;
            $requestBodyWithUserID['user_id'] = $access_token->user_id;
            $task = new Task();
            $task->merge_attributes($requestBodyWithUserID);
            $result = $task->save_by_user_id();
            if ($result === true) {
                $responseBody = json_encode($task);
            } else {
                $responseBody = json_encode($result);
            }        $response->getBody()->write($responseBody);
            return $response;
        }
    );

    $app->delete('/task',
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
            $requestBodyWithUserID = $requestBody;
            $requestBodyWithUserID['user_id'] = $access_token->user_id;
            $task = new Task();
            $task->merge_attributes($requestBodyWithUserID);
            $result = $task->delete_by_user_id();
            if ($result === true) {
                $responseBody = json_encode($task);
            } else {
                $responseBody = json_encode($result);
            }
            $response->getBody()->write($responseBody);
            return $response;
        }
    );

// Other
    $app->get('/', 
        function( Request $request, Response $response, $args ) {
            $message = array(
                'message'=>'Hello from the Event Manager API',
                'UI Origin'=>$_ENV['REQUEST_ORIGIN'],
                'UI Repository'=>'https://github.com/JacobStephens2/event-manager-ui',
                'API Origin'=>$_ENV['API_ORIGIN'],
                'API Repository'=>'https://github.com/JacobStephens2/event-manager-api',
                'endpoints' => array(
                    'GET /'=>$_ENV['API_ORIGIN'] . '/',
                    'GET /hello/{name}'=>$_ENV['API_ORIGIN'] . '/hello/Jacob',
                    'POST /mimic-json'=>$_ENV['API_ORIGIN'] . '/mimic-json',
                    'users' => array(
                        'POST /login'=>$_ENV['API_ORIGIN'] . '/login',
                        'POST /logout'=>$_ENV['API_ORIGIN'] . '/logout',
                        'POST /sign-up'=>$_ENV['API_ORIGIN'] . '/sign-up'
                    ),
                    'events' => array(
                        'GET /events'=>$_ENV['API_ORIGIN'] . '/events',
                        'GET /event/{id}'=>$_ENV['API_ORIGIN'] . '/events/1',
                        'POST /event'=>$_ENV['API_ORIGIN'] . '/event',
                        'PUT /event'=>$_ENV['API_ORIGIN'] . '/event',
                        'DELETE /event'=>$_ENV['API_ORIGIN'] . '/event'
                    ),
                    'clients' => array(
                        'GET /clients'=>$_ENV['API_ORIGIN'] . '/clients',
                        'GET /client/{id}'=>$_ENV['API_ORIGIN'] . '/clients/1',
                        'GET /client/{id}/events'=>$_ENV['API_ORIGIN'] . '/clients/1/events',
                        'POST /client'=>$_ENV['API_ORIGIN'] . '/client',
                        'PUT /client'=>$_ENV['API_ORIGIN'] . '/client',
                        'DELETE /client'=>$_ENV['API_ORIGIN'] . '/client'
                    )
                )
            );
            $payload = json_encode($message);
            $accessControlAllowOrigin = $_ENV['REQUEST_ORIGIN'];          
            $response = $response->withHeader('Access-Control-Allow-Origin', $accessControlAllowOrigin);
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
            $accessControlAllowOrigin = $_ENV['REQUEST_ORIGIN'];          
            $response = $response->withHeader('Access-Control-Allow-Origin', $accessControlAllowOrigin);
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

    $app->post('/email', 
        function (Request $request, Response $response, $args) {
            $requestBody = $request->getParsedBody();  

            $mail = new PHPMailer(true);

            try {
                //Server settings
                // $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
                $mail->isSMTP();                                            //Send using SMTP
                $mail->Host       = 'smtp.sendgrid.net';                    //Set the SMTP server to send through
                $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
                $mail->Username   = 'apikey';                               //SMTP username
                $mail->Password   = $_ENV['SENDGRID_API_KEY'];              //SMTP password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
                $mail->Port       = 465;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

                //Recipients
                $mail->setFrom('jacob@stewardgoods.com', 'Jacob');
                $mail->addAddress($requestBody['destinationEmail'], 'Charles');     //Add a recipient
                $mail->addReplyTo('jacob@stewardgoods.com', 'Mr. Stephens');

                //Content
                $mail->isHTML(true);                                  //Set email format to HTML
                $mail->Subject = 'Here is the subject';
                $mail->Body    = $requestBody['emailBody'];
                $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

                $mail->send();
                $message = array('message'=> 'Message has been sent');
            } catch (Exception $e) {
                $message = array('message'=>'Caught exception: '. $e->getMessage() ."\n");
            }
            
            $responseBody = json_encode($message);
            $response->getBody()->write($responseBody);
            return $response;
        }
    );
//

if ($_ENV['ERROR_DISPLAY'] == 'false') {
    $error_display = false;
} else {
    $error_display = true;
}

$app->addErrorMiddleware($error_display, true, true);

$app->run();