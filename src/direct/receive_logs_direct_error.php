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

$channel->exchange_declare('direct_logs', 'direct', false, false, false);

[$queue_name, ,] = $channel->queue_declare('', false, false, true, false);

$channel->queue_bind($queue_name, 'direct_logs', 'error');

echo " [*] Waiting for logs. To exit press CTRL+C\n";

$callback = function (AMQPMessage $msg) {
	echo ' [x] ', $msg->getRoutingKey(), ':', $msg->getBody(), "\n";
};

$channel->basic_consume($queue_name, '', false, true, false, false, $callback);

try
{
	$channel->consume();
}
catch (\Throwable $exception)
{
	echo $exception->getMessage();
}

$channel->close();
$connection->close();