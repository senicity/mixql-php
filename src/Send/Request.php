<?php

include_once __DIR__  . '/Socket/Socket.php';

class Request extends RequestSocket {
    
    public function __construct(
        public string $host,
        public int $port,
        public array $options
    ){}

    public function execute($query): string
    {
        return $this->send($query);
    }

}