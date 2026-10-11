<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

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
	'hello', // Имя очереди
	false, // Создать очередь если отсутствует, для существующей проверить совместимость параметров
	true, // Сохранение очереди после перезагрузки
	false, // Очередь доступна другим соединениям
	false, // Удаление очереди после отключения последнего потребителя
	false, // Дождаться ответа на объявление очереди
	new AMQPTable([
		'x-queue-type' => 'quorum'
	])
);
echo " [*] Waiting for messages. To exit press CTRL+C\n";

$callback = function (AMQPMessage $msg) {
	echo ' [x] Received ', $msg->getBody(), "\n";
};

$channel->basic_consume('hello', '', false, true, false, false, $callback);

try
{
	$channel->consume();
}
catch (\Throwable $exception)
{
	echo $exception->getMessage();
}