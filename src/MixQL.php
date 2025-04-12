<?php

include_once __DIR__ . '/Constants/Define.php';
include_once __DIR__ . '/Query/Types.php';
include_once __DIR__ . '/Send/Request.php';

class MixQL extends QueryTypes {

    public string $query = '';
    public mixed $res = '';

    public Request $request;

    public function __construct(
        public $options = [
            'timeout' => 30
        ],
        public $host = 'localhost',
        public $port = 7272
    ){
        $this->request = new Request(
            $this->host,
            $this->port,
            $this->options
        );
    }

    public function execute(): self
    {
        $this->res = $this->request->execute($this->query);
        return $this;
    }

    public function __toString(): string
    {
        return $this->res;
    }


}
