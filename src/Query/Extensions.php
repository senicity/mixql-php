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

    public function store(string $name): self
    {
        $this->query = $this->query . MIXQL_STOREAS . $name;
        return $this;
    } 

    public function bind(array $params): self
    {
        foreach($params as $param){
            $this->query .= '\n' . $param;
        }
        return $this;
    }

}