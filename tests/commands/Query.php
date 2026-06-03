<?php

class Query extends CommandHelpers {

    public array $defaultOptions = [
        'amount' => 1,
        'length' => 16
    ];

    public function __construct(
        public array $params = [],
        public array $flags = [],
        public MixQl $mixql = new MixQl
    ){}

    public function execute()
    {
        echo "\n\n";
        echo $this->color("Query: ",'yellow'). "\n";
        echo ($this->params[0] ?? 'None'). "\n\n";

        $result = $this->variant($this->params[0], $this->parseOptions($this->flags));
        if($result == 'INVALID_INPUT'){
            echo $this->color("There is an ERROR in your query.","red","error") . "\n";
            echo $result;
            return false;
        }

        echo $this->color("Result:","green","ok") . "\n";
        echo $result;

        return true;
    }

    private function variant($name, $options = [])
    {
        echo $this->color("Options: ",'yellow'). "\n";
        echo json_encode($options, JSON_PRETTY_PRINT) . "\n\n";

        $options = $this->setDefaults($options);

        $query = match($name){
            'CreateKey' => $this->mixql->createKey()->amount((int)$options['amount']),
            'CreateSalt' => $this->mixql->createSalt()->amount((int)$options['amount'])->length((int)$options['length']),
            'CreateStore' => $this->mixql->select($options['storeQuery'])->store($options['storeName']),
            'CreateUUID' => $this->mixql->createUUID(),
            'DeleteStore' => $this->mixql->storeDelete($options['storeName']),
            'ListStore' => $this->mixql->storeList(),
            'Select' => $this->mixql->select($options['query']),
            'SHA256' => $this->mixql->sha256($options['expr'] ?? ':input'),
            'SHA512' => $this->mixql->sha512($options['expr'] ?? ':input'),
            'EncGcm' => $this->mixql->encGcm($options['expr'] ?? ':input'),
            'DecGcm' => $this->mixql->decGcm($options['expr'] ?? ':input'),
            'Hmac' => $this->mixql->hmac($options['keyExpr'] ?? ':key', $options['msgExpr'] ?? ':msg'),
            'Argon2' => $this->mixql->argon2($options['expr'] ?? ':input'),
            'Argon2Verify' => $this->mixql->argon2Verify($options['hashExpr'] ?? ':hash', $options['passExpr'] ?? ':password'),
            default => $this->mixql->raw($this->params[0])
        };

        if(isset($options['bind'])){
            $query = $query->bind($options['bind']);
        }

        if(isset($options['key'])){
            $query = $query->key($options['key']);
        }

        if(isset($options['salt'])){
            $saltParts = is_array($options['salt']) ? $options['salt'] : explode(',', $options['salt']);
            $query = $query->salt(...$saltParts);
        }

        if(isset($options['pepper'])){
            $pepperParts = is_array($options['pepper']) ? $options['pepper'] : explode(',', $options['pepper']);
            $query = $query->pepper(...$pepperParts);
        }

        if(isset($options['sha'])){
            $query = $query->sha();
        }

        if(isset($options['uppercase'])){
            $query = $query->uppercase();
        }

        if(isset($options['auth'])){
            $authParts = explode(':', $options['auth']);
            $query = $query->auth($authParts[0], $authParts[1] ?? '');
        }

        echo $this->color("Sending: ",'yellow'). "\n";
        echo $query->rawQuery(). "\n\n";

        if(isset($options['json'])){
            $res = $query->execute()->json();
            if(isset($options['pretty'])){
                $res = $res->pretty();
            }
            return $res;
        }

        return $query->execute();

    }

    private function parseOptions($options)
    {
        $opts = [];
        foreach($options as $option)
        {
            $flagParse = explode('=', str_replace('--','',$option));
            if(isset($flagParse[1]) && $flagParse[0] == 'bind'){
                $flagParse[1] = explode(',',$flagParse[1]);
            }
            $opts[$flagParse[0]] = isset($flagParse[1]) ? $flagParse[1] : true;
        }
        return $opts;
    }

    private function setDefaults($options = [])
    {
        $optionsSet = $this->defaultOptions;
        foreach($options as $key => $value)
        {
            $optionsSet[$key] = $value;
        }

        return $optionsSet;
    }
}
