<?php

class Query extends CommandHelpers {

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

        /*if (empty($this->params[0])) {
            echo $this->color("There is an ERROR in your query: Query cannot be empty.", "red", "error") . "\n";
            return false;
        }*/

        //$result = $this->mixql->raw($this->params[0]);
        //$result = $this->mixql->select($this->params[0])->uppercase()->execute();
        //$result = $this->mixql->createSalt()->amount(3)->length(256)->sha()->execute();
        //$result = $this->mixql->createSalt()->amount(3)->length(256)->sha()->execute();
        //$result = $this->mixql->storeList()->execute();
        //$result = $this->mixql->storeDelete('test-data')->execute();
        //$result = $this->mixql->storeList()->execute();
        //$result = $this->mixql->select($this->params[0])->store('test-data')->execute();
        //$result = $this->mixql->select('SHA1(:input, MD5(:input2))')->bind(['test1','test2'])->execute();
        $result = $this->mixql->createUUID()->execute();

        if($result == 'INVALID_INPUT'){
            echo $this->color("There is an ERROR in your query.","red","error") . "\n";
            echo $result;
            return false;
        }

        echo $this->color("Result:","green","ok") . "\n";
        echo $result;

        return true;
    }
}
