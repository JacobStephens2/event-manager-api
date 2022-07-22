<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$currentDate = new DateTime();

require dirname(dirname(__DIR__)) . '/initialize.php';

$mail = new PHPMailer(true);

try {
    //Server settings
    $mail->isSMTP();                                    //Send using SMTP
    $mail->Host       = 'smtp.sendgrid.net';            //Set the SMTP server to send through
    $mail->SMTPAuth   = true;                           //Enable SMTP authentication
    $mail->Username   = 'apikey';                       //SMTP username
    $mail->Password   = $_ENV['SENDGRID_API_KEY'];      //SMTP password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;    //Enable implicit TLS encryption
    $mail->Port       = 465;                            //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

    //Recipients
    $mail->setFrom('jacob@stewardgoods.com', 'Jacob');
    $mail->addAddress('jacob.stephens.701@gmail.com', 'Charles');     //Add a recipient
    $mail->addReplyTo('jacob@stewardgoods.com', 'Mr. Stephens');

    //Content
    $mail->isHTML(true);                                  //Set email format to HTML
    $mail->Subject = 'Here is the subject fron the 9a email.php cron job';
    $mail->Body    = 'Email body from cron';
    $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

    $mail->send();
    echo 'Message has been sent' . "\n";
    echo 'email.php ran at ' . $currentDate->format('Y-m-d H:i:s') . "\n";
} catch (Exception $e) {
    echo 'email.php exception caught at ' . $currentDate->format('Y-m-d H:i:s') . "\n";
    echo "Caught exception: " . $e->getMessage() . "\n";
}