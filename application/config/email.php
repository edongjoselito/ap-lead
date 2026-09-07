<?php defined('BASEPATH') OR exit('No direct script access allowed');

// $config = array(
//     'protocol' => 'ssmtp', // 'mail', 'sendmail', or 'smtp'
//     'smtp_host' => 'ssl://ssmtp.googlemail.com', 
//     'smtp_port' => 465,
//     'smtp_user' => 'depedroxidev@gmail.com',
//     'smtp_pass' => 'Caballero18',
//     'smtp_crypto' => 'ssl', //can be 'ssl' or 'tls' for example
//     'mailtype' => 'text', //plaintext 'text' mails or 'html'
//     'smtp_timeout' => '4', //in seconds
//     'charset' => 'iso-8859-1',
//     'wordwrap' => TRUE
// );

$config['protocol'] = 'mail';
$config['smtp_host'] = 'mail.depeddavor.com';
$config['smtp_user'] = 'no-reply@depeddavor.com';
$config['smtp_pass'] = 'moth34board';
$config['smtp_port'] = 465;
 



$config['charset'] = 'utf-8';
$config['mailtype'] = 'html';
$config['newline'] = "\r\n"; 
