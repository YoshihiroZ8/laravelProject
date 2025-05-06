<?php

namespace App\Services;

class HelloWorldService
{
    /**
     * Get the hello world message.
     *
     * @return string
     */
    public function getMessage(): string
    {
        // In a real app, this service might fetch data,
        // perform calculations, or interact with other services.
        return 'Hello World from Service!';
    }
}
