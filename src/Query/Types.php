<?php

include_once __DIR__ . '/Extensions.php';

class QueryTypes extends Extensions {

    public function raw(string $query): self
    {
        $this->query = $query;
        return $this;
    }

    public function select(string $hash): self
    {
        $this->query = MIXQL_SELECT . $hash . MIXQL_ASHASH;
        return $this;
    }

    public function createSalt(): self
    {
        $this->query = MIXQL_CREATE_SALT;
        return $this;
    }

    public function createKey(): self
    {
        $this->query = MIXQL_CREATE_KEY;
        return $this;
    }

    public function createUUID(): self
    {
        $this->query = MIXQL_CREATE_UUID;
        return $this;
    } 

    public function storeList(): self
    {
        $this->query = MIXQL_STORE_LIST;
        return $this;
    }

    public function storeDelete(string $name): self
    {
        $this->query = MIXQL_STORE_DELETE . $name;
        return $this;
    }

}