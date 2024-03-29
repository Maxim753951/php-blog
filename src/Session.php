<?php

declare(strict_types=1);

namespace Blog;

class Session
{
    public function start(): void
    {
        session_start();
    }

    /**
     * @param string $key
     * @param $value
     * @return void
     */
    public function setData(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function getData(string $key)
    {
        return !empty($_SESSION[$key]) ? $_SESSION[$key] : null;
    }

    public function save(): void
    {
        session_write_close();
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function flush(string $key)
    {
        $value = $this->getData($key);
        $this->unset($key); // вернули значение и сразу удалили с сессии

        return $value;
    }

    /**
     * @param string $key
     * @return void
     */
    private function unset(string $key): void
    {
        unset($_SESSION[$key]);
    }


}