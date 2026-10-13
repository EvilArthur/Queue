<?php

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection(
	getenv('RABBITMQ_HOST'),
	getenv('RABBITMQ_PORT'),
	getenv('RABBITMQ_USER'),
	getenv('RABBITMQ_PASSWORD'),
	getenv('RABBITMQ_VHOST'),
);
$channel = $connection->channel();

$data = implode(' ', array_slice($argv, 1));
if (empty($data))
{
	$data = "Hello World!..........";
}
$msg = new AMQPMessage($data, ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);

$channel->basic_publish($msg, '', 'hello');

echo ' [x] Sent ', $data, "\n";
