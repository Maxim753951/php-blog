<?php

namespace Blog;

use PDO;

class CommentMapper
{
    /**
     * @var PDO
     */
    private PDO $connection;

    /**
     * @param PDO $connection
     */
    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    public function get(string $url_key): ?array
    {
        $statement = $this->connection->prepare('SELECT * FROM comment WHERE post_id = (select post_id from post where url_key = :url_key)');

        $statement->execute([
            'url_key' => $url_key
        ]);

        return $statement->fetchAll();
    }
}