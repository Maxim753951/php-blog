<?php

namespace Blog;

class Creation
{
    /**
     * @var Database
     */
    private Database $database;

    private Session $session;

    /**
     * @param Database $database
     * @param Session $session
     */
    public function __construct(Database $database, Session $session)
    {
        $this->database = $database;
        $this->session = $session;
    }

    /**
     * @param array $data
     * @return bool
     * @throws CreationException
     */
    public function createPost(array $data):bool
    {
        if(empty($data['title'])) {
            throw new CreationException('The Title should not be empty' );
        }
        if(empty($data['url_key'])) {
            throw new CreationException('The Url_key should not be empty' );
        }

        if(empty($data['image_path'])) {
            $data['image_path'] = NULL;
        }
        if(empty($data['published_date'])) {
            $data['published_date'] = NULL;
        }

        // проверка на url_key
        $statement = $this->database->getConnection()->prepare('SELECT * FROM post WHERE url_key = :url_key');
        $statement->execute([
            'url_key' => $data['url_key']
        ]);

        $post = $statement->fetch();

        if (!empty($post)) {
            throw new CreationException('Post with such url_key exists');
        }

        $statement = $this->database->getConnection()->prepare(
            'INSERT INTO post (title, url_key, subject, image_path, content, description, published_date) VALUES (:title, :url_key, :subject, :image_path, :content, :description, :published_date)'
        );

        $statement->execute([
            'title' => $data['title'],
            'url_key' => $data['url_key'],
            'subject' => $data['subject'],
            'image_path' => $data['image_path'],
            'content' => $data['content'],
            'description' => $data['description'],
            'published_date' => $data['published_date']
        ]);

        return true;
    }

    public function deletePost(string $urlKey): ?array
    {
        $statement = $this->database->getConnection()->prepare('delete from post where url_key = :url_key');
        $statement->execute([
            'url_key' => $urlKey
        ]);

        return null;
    }

    /**
     * @param array $data
     * @return bool
     * @throws CreationException
     */
    public function createComment(array $data):bool
    {
        if(empty($data['content'])) {
            throw new CreationException('The Content should not be empty' );
        }

        $statement = $this->database->getConnection()->prepare(
            'INSERT INTO comment (user_id, post_id, content) VALUES (:user_id, :post_id, :content)'
        );

        $statement->execute([
            'user_id' => $data['user_id'],
            'post_id' => $data['post_id'],
            'content' => $data['content'],
        ]);

        return true;
    }
}