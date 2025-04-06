<?php

class Test extends CommandHelpers {

    public function __construct(
        public array $params = [],
        public array $flags = []
    ){}

    public function execute()
    {
        echo "\n\n";
        echo $this->color("This is a test command.\nIf you see this message, the command cli is working.",'yellow')."\n\n";

        echo $this->color("These are your inputted arguments:","green") . "\n";
        echo json_encode($this->params, JSON_PRETTY_PRINT) . "\n\n";

        echo $this->color("These are your flags:","green") . "\n";
        echo json_encode($this->flags, JSON_PRETTY_PRINT) . "\n\n";
        return true;
    }

}