<?php

// src/parse_worker.php

use App\Entity\News;
use Doctrine\ORM\EntityManagerInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;

// Set up the RabbitMQ connection and channel
$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

// Declare the exchange and queue
$channel->exchange_declare('parse_news', 'direct', false, true, false);
$channel->queue_declare('parse_queue', false, true, false, false);
$channel->queue_bind('parse_queue', 'parse_news');

// Set up the Doctrine entity manager
$entityManager = EntityManagerInterface::create($params, $config);

// Define the callback function to handle parsing jobs
$callback = function ($message) use ($entityManager) {
    $article = json_decode($message->body, true);

    $news = new News();
    $news->setTitle($article['title']);
    $news
