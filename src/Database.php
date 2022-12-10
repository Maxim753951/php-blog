<?php

declare(strict_types=1);

namespace Blog;

use InvalidArgumentException;
use PDO;
use PDOException;

class Database
{
    /**
     * @var PDO
     */
    private PDO $connection;

    /**
     * @param string $dsn
     * @param string $username
     * @param string $password
     */
    public function __construct(string $dsn, string $username = '', string $password = '')
    {
        try {
            $this->connection = new PDO($dsn, $username, $password);
        } catch (PDOException $exception) {
            throw  new InvalidArgumentException('Database error: ' . $exception->getMessage());
        }
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); //обработка ошибки (очередная)
        $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        // дефолтное поведение для вызова и использования метода SELECT, ожидаем ассоциативный массив как результат выполнения
    }

    /**
     * @return PDO
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }
}