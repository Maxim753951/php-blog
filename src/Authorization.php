<?php

declare(strict_types=1);

namespace Blog;

class Authorization
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
     * @throws AuthorizationException
     */
    public function register(array $data):bool
    {
        if(empty($data['username'])) {
            throw new AuthorizationException('The Username should not be empty' );
            // это один из вариантов реализации валидации, может быть:
            //отдельным классом
            //отдельным методом
            //вместо Exception собирается массив ошибок и выводится
            //меняется возвращаемый тип (bool или массив ошибок)
            //на практике рекомендуется возвращать всегда один и тот же тип с метода, а если надо что-то дообработать можно воспользоваться:
            //исключительной ситуацией (как мы сейчас) или отдельным классом
        }
        if(empty($data['email'])) {
            throw new AuthorizationException('The Email should not be empty' );
        }
        if(empty($data['password'])) {
            throw new AuthorizationException('The Password should not be empty' );
        }
        if($data['password'] !== $data['confirm_password']) {
            throw new AuthorizationException('The Password and Confirm Password should match' );
        }

        // проверка на email
        $statement = $this->database->getConnection()->prepare('SELECT * FROM user WHERE email = :email');
        $statement->execute([
           'email' => $data['email']
        ]);
        $user = $statement->fetch();
        if (!empty($user)) {
            throw new AuthorizationException('User with such email exists');
        }

        // проверка на username
        $statement = $this->database->getConnection()->prepare('SELECT * FROM user WHERE username = :username');
        $statement->execute([
            'username' => $data['username']
        ]);
        $user = $statement->fetch();
        if (!empty($user)) {
            throw new AuthorizationException('User with such username exists');
        }

        // метод prepare возвращает новый объект, который называется statement
        $statement = $this->database->getConnection()->prepare(
            'INSERT INTO user (email, username, password) VALUES (:email, :username, :password)'
        );
        // передаём ключами, а не через значения полученные через регестрационную форму, потому что
        // этот запрос может закешироваться (если мы передаём только ПлейсХолдеры, наверное)
        // и + это позволяет отдельно безопастно обработать эти 3 поля

        $statement->execute([
            'email' => $data['email'],
            'username' => $data['username'],
            'password' => password_hash($data['password'], PASSWORD_BCRYPT)
        ]);

        return true;
    }

    /**
     * @param string $email
     * @param $password
     * @return bool
     * @throws AuthorizationException
     */
    public function login(string $email, $password): bool
    {
        if(empty($email)) {
            throw new AuthorizationException('The Email should not be empty' );
        }
        if(empty($password)) {
            throw new AuthorizationException('The Password should not be empty' );
        }

        $statement = $this->database->getConnection()->prepare(
            'SELECT * FROM user WHERE email = :email'
        );
        $statement->execute([
           'email' => $email
        ]);

        $user = $statement->fetch();

        if (empty($user)) {
            throw new AuthorizationException('User with such email not found');
        }

        if (password_verify($password, $user['password'])) {
            $this->session->setData('user', [
                'user_id' => $user['user_id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role_id' => $user['role_id'],
            ]);
            return true;
        }

        throw new AuthorizationException('Incorrect email or password'); // хотя фактически Incorrect password
    }
}