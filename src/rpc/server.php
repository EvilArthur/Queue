<?php

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

$connection = new AMQPStreamConnection(
	getenv('RABBITMQ_HOST'),
	getenv('RABBITMQ_PORT'),
	getenv('RABBITMQ_USER'),
	getenv('RABBITMQ_PASSWORD'),
	getenv('RABBITMQ_VHOST'),
);
$channel = $connection->channel();

$channel->queue_declare(
	'rpc_queue',
	false,
	true,
	false,
	false,
	false,
	new AMQPTable([
		'x-queue-type' => 'quorum',
	])
);

function fib(int $n)
{
	if ($n === 0)
	{
		return 0;
	}
	if ($n === 1)
	{
		return 1;
	}

	return fib($n - 1) + fib($n - 2);
}

echo " [x] Awaiting RPC requests\n";
$callback = function (AMQPMessage $req) {
	$n = (int)($req->getBody());
	echo ' [.] fib(', $n, ")\n";

	$msg = new AMQPMessage(
		(string)fib($n),
		['correlation_id' => $req->get('correlation_id')]
	);

	$req->getChannel()->basic_publish(
		$msg,
		'',
		$req->get('reply_to')
	);
	$req->ack();
};

$channel->basic_qos(0, 1, false);
$channel->basic_consume('rpc_queue', '', false, false, false, false, $callback);

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