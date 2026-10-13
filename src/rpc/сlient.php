<?php

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

class FibonacciRpcClient
{
	private AMQPChannel $channel;
	private $callback_queue;
	private $response;
	private $corr_id;

	public function __construct(private AMQPStreamConnection $connection)
	{
		$this->channel = $this->connection->channel();
		[$this->callback_queue, ,] = $this->channel->queue_declare(
			"",
			false,
			false,
			true,
			false
		);
		$this->channel->basic_consume(
			$this->callback_queue,
			'',
			false,
			true,
			false,
			false,
			[
				$this,
				'onResponse',
			]
		);
	}

	public function onResponse(AMQPMessage $rep)
	{
		if ($rep->get('correlation_id') === $this->corr_id)
		{
			$this->response = $rep->getBody();
		}
	}

	public function call(int $n)
	{
		echo ' [.] Send fib(', $n, ")\n";
		$this->response = null;
		$this->corr_id = uniqid();

		$msg = new AMQPMessage(
			(string)$n,
			[
				'correlation_id' => $this->corr_id,
				'reply_to' => $this->callback_queue,
			]
		);
		$this->channel->basic_publish($msg, '', 'rpc_queue');
		while (!$this->response)
		{
			$this->channel->wait();
		}

		return (int)($this->response);
	}
}

$connection = new AMQPStreamConnection(
	getenv('RABBITMQ_HOST'),
	getenv('RABBITMQ_PORT'),
	getenv('RABBITMQ_USER'),
	getenv('RABBITMQ_PASSWORD'),
	getenv('RABBITMQ_VHOST'),
);
$fibonacci_rpc = new FibonacciRpcClient($connection);
$response = $fibonacci_rpc->call(30);
echo ' [.] Got ', $response, "\n";