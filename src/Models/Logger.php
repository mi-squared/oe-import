<?php


namespace Mi2\Import\Models;


class Logger
{
    protected $messages = [];

    /**
     * @return array
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * @param array $messages
     */
    public function setMessages(array $messages): void
    {
        $this->messages = $messages;
    }

    public function addMessages(array $messages)
    {
        foreach ($messages as $message) {
            $this->messages[] = $message;
        }
    }

    public function addMessage($message)
    {
        $this->messages[] = $message;
    }
}
