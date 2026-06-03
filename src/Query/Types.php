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

    public function storeSelect(string $name): self
    {
        $this->query = MIXQL_STORE_SELECT . $name;
        return $this;
    }

    public function storeUse(string $name): self
    {
        $this->query = MIXQL_STORE_USE . $name;
        return $this;
    }

    public function sha256(string $expr = ':input'): self
    {
        $this->query = MIXQL_SELECT . MIXQL_SHA256 . $expr . ')' . MIXQL_ASHASH;
        return $this;
    }

    public function sha512(string $expr = ':input'): self
    {
        $this->query = MIXQL_SELECT . MIXQL_SHA512 . $expr . ')' . MIXQL_ASHASH;
        return $this;
    }

    public function encGcm(string $expr = ':input'): self
    {
        $this->query = MIXQL_SELECT . MIXQL_ENC_GCM . $expr . ')' . MIXQL_ASHASH;
        return $this;
    }

    public function decGcm(string $expr = ':input'): self
    {
        $this->query = MIXQL_SELECT . MIXQL_DEC_GCM . $expr . ')' . MIXQL_ASHASH;
        return $this;
    }

    public function hmac(string $keyExpr = ':key', string $msgExpr = ':msg'): self
    {
        $this->query = MIXQL_SELECT . MIXQL_HMAC . $keyExpr . ', ' . $msgExpr . ')' . MIXQL_ASHASH;
        return $this;
    }

    public function argon2(string $expr = ':input'): self
    {
        $this->query = MIXQL_SELECT . MIXQL_ARGON2 . $expr . ')' . MIXQL_ASHASH;
        return $this;
    }

    public function argon2Verify(string $hashExpr = ':hash', string $passExpr = ':password'): self
    {
        $this->query = MIXQL_SELECT . MIXQL_ARGON2_VERIFY . $hashExpr . ', ' . $passExpr . ')' . MIXQL_ASHASH;
        return $this;
    }

    public function auth(string $username, string $password): self
    {
        $this->query = MIXQL_AUTH . $username . ':' . $password . "\n" . $this->query;
        return $this;
    }

}