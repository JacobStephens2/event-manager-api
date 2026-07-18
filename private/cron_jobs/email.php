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
    $mail->Host       = 'smtp.resend.com';            //Set the SMTP server to send through
    $mail->SMTPAuth   = true;                           //Enable SMTP authentication
    $mail->Username   = 'resend';                       //SMTP username
    $mail->Password   = $_ENV['RESEND_API_KEY'];      //SMTP password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;    //Enable implicit TLS encryption
    $mail->Port       = 587;                            //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

    //Recipients
    $mail->setFrom('noreply@stephens.page', 'Jacob');
    $mail->addAddress('jacob.stephens.701@gmail.com', 'Charles');     //Add a recipient
    $mail->addReplyTo('jacob@stephens.page', 'Mr. Stephens');

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