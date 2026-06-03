<?php

class Extensions {

    public function amount(int $limit): self
    {
        $this->query = 
            $this->query . MIXQL_LIMIT . $limit;

        return $this;
    }

    public function length(int $length): self
    {
        $this->query = 
            $this->query . MIXQL_LENGTH . $length;

        return $this;
    }

    public function sha(): self 
    {
        $this->query = $this->query . MIXQL_SHA;
        return $this;
    }

    public function uppercase(): self
    {
        $uppercaseQuery = $this->query . ' ' . MIXQL_UPPERCASE;
        if(strpos($this->query, "\\n")) {
            $lines = explode("\\n", $this->query);
            $query = $lines[0] ?? $this->query;
            $params = implode("\\n", array_slice($lines, 1));
            
            $uppercaseQuery = $query . MIXQL_UPPERCASE . "\\n" . $params;
        }
        $this->query = $uppercaseQuery;
        return $this;
    }

    public function key(string $key): self
    {
        $this->query = str_replace(MIXQL_ASHASH, MIXQL_KEY . $key . MIXQL_ASHASH, $this->query);
        return $this;
    }

    public function salt(string ...$salts): self
    {
        $this->query = str_replace(MIXQL_ASHASH, MIXQL_SALT . implode(',', $salts) . MIXQL_ASHASH, $this->query);
        return $this;
    }

    public function pepper(string ...$peppers): self
    {
        $this->query = str_replace(MIXQL_ASHASH, MIXQL_PEPPER . implode(',', $peppers) . MIXQL_ASHASH, $this->query);
        return $this;
    }

    public function store(string $name): self
    {
        $this->query = $this->query . MIXQL_STOREAS . $name;
        return $this;
    } 

    public function bind(array $params): self
    {
        foreach($params as $param){
            $this->query .= "\n" . $param;
        }
        return $this;
    }

    public function rawQuery(): string
    {
        return $this->query;
    }

    public function json(): self
    {   
        $this->res = json_encode(
            $this->array()
        );
        return $this;
    }

    public function pretty(): self
    {
        $this->res = json_encode(
            json_decode(
                $this->res, true
            ),
            JSON_PRETTY_PRINT
        );
        return $this;
    }

    public function array(): array
    {
        if (str_contains($this->res, MIXQL_STORED_QUERIES)) {
            return $this->parseRawList($this->res);
        }

        $normalized = str_replace(["\r\n", "\r"], "\n", (string) $this->res);
        $lines = explode("\n", $normalized);
        return array_filter(array_map('trim', $lines));
    }

    public function parseRawList(string $response): array
    {
        $lines = explode("\n", $response);

        $data = [];
        foreach ($lines as $line) {
            if (preg_match('/^\|\s*(.*?)\s*\|\s*(.*?)\s*\|$/', $line, $matches)) {
                $name = trim($matches[1]);
                $query = trim($matches[2]);
                if (strtolower($name) === 'name' && strtolower($query) === 'query') {
                    continue;
                }
                $data[] = [
                    'name' => $name,
                    'query' => $query
                ];
            }
        }
        return $data;
    }

}