<?php


namespace Mi2\Import\Models;

use Mi2\Framework\AbstractModel;

class Response extends AbstractModel
{
    const SUCCESS = true;
    const FAILURE = false;

    protected $data = '';
    protected $messages = [];
    protected $result = true;

    /**
     * @return mixed
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * @param mixed $message
     */
    public function setMessages($messages)
    {
        $this->messages = $messages;
    }

    /**
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param mixed $result
     */
    public function setResult($result)
    {
        $this->result = $result;
    }
}
