<?php

// src/Command/ParseNewsCommand.php

namespace App\Command;

use Goutte\Client;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ParseNewsCommand extends Command
{
    private $connection;

    protected static $defaultName = 'app:parse-news';

    public function __construct(AMQPStreamConnection $connection)
    {
        $this->connection = $connection;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Parse news articles from hightload.today');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Set up the Goutte client
        $client = new Client();

        // Set up the RabbitMQ channel
        $channel = $this->connection->channel();

        // Declare the exchange and queue
        $channel->exchange_declare('parse_news', 'direct', false, true, false);
        $channel->queue_declare('parse_queue', false, true, false, false);
        $channel->queue_bind('parse_queue', 'parse_news');

        // Get the list of news articles to parse
        $crawler = $client->request('GET', 'https://hightload.today/');
        $articles = $crawler->filter('div.article')->each(function ($node) {
            $title = $node->filter('h2.title')->text();
            $description = $node->filter('div.excerpt')->text();
            $image = $node->filter('img')->attr('src');
            $date = $node->filter('time')->attr('datetime');
            return [
                'title' => $title,
                'description' => $description,
                'image' => $image,
                'date' => $date,
            ];
        });

        // For each news article, check if it already exists in the database and add a parsing job to the RabbitMQ queue
        foreach ($articles as $article) {
            $existingNews = $entityManager->getRepository(News::class)->findOneBy([
                'title' => $article['title'],
                'publishedAt' => new \DateTime($article['date']),
            ]);

            if (!$existingNews) {
                $message = new AMQPMessage(json_encode($article));
                $channel->basic_publish($message, 'parse_news');
            }
        }

        // Close the RabbitMQ channel
        $channel->close();

        $output->writeln('Parsing complete.');

        return Command::SUCCESS;
    }
}
