# Event Manager API
The backend for the Event Manager app. The event manager app is hosted at https://eventmanager.stewardgoods.com/

The Event Manager App's repository is at https://github.com/JacobStephens2/event-manager-ui

## Slim 4, PHP 8, MySQL 8 and Apache 2.4

This project has been developed to run on a server running PHP 8 (https://www.php.net/releases/8.0/en.php) and Apache 2.4 (https://httpd.apache.org/docs/2.4/), while having access to a MySQL 8 server (https://dev.mysql.com/doc/refman/8.0/en/). This project uses the Slim 4 Framework: https://www.slimframework.com/docs/v4/

## Routes

### GET /

https://api.eventmanager.stewardgoods.com/

### GET /hello/{name}

https://api.eventmanager.stewardgoods.com/hello/Jacob

### POST /login

https://api.eventmanager.stewardgoods.com/login

Submit a JSON body in the request like the following:

`{
    "email": "jacob@example.com",
    "password": "goodPassword123"
}`

### POST /sign-up

https://api.eventmanager.stewardgoods.com/sign-up

Submit a JSON body in the request like the following:

`{
    "email": "jacob@example.com",
    "password": "goodPassword123"
}`

### POST /mimic-json

https://api.eventmanager.stewardgoods.com/mimic-json

Submit a JSON body in the request like the following:

`{
    "message": "Hello, world!"
}`

### GET /events
Get all events.

### GET /event/{id}
Get a specific event by id.

### POST /event
Create an event.

### PUT /event/{id}
Update an event by id.
