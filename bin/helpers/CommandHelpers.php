<?php 

class CommandHelpers {

    protected string $blackbg = '40';
    protected string $redbg = '41';
    protected string $greenbg = '42';
    protected string $yellowbg = '43';
    protected string $bluebg = '44';
    protected string $magentabg = '45';
    protected string $cyanbg = '46';
    protected string $whitebg = '47';

    protected string $black = '30';
    protected string $red = '31';
    protected string $green = '32';
    protected string $yellow = '33';
    protected string $blue = '34';
    protected string $magenta = '35';
    protected string $cyan = '36';
    protected string $white = '37';

    protected string $bold = '1';
    protected string $underline = '4';
    protected string $reset = '0';

    public function color($text,$type='white',$emoji=false)
    {
        if(!isset($this->{$type})){
            return $text;
        }
        $text = $this->renderColor(
            $text,
            $this->{$type}
        );
        if($emoji){
            return $this->renderEmoji($emoji) . ' ' . $text;
        }
        return $text;
    }

    public function execCmd($cmd,$output=true,$returnOutput=false)
    {
        if($output)
        {
            return $this->outputExec($cmd);
        }
        return $this->rawExec($cmd,$returnOutput);
    }

    private function outputExec($cmd)
    {
        ob_start();
        $descriptorspec = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];
        $process = proc_open($cmd, $descriptorspec, $pipes);
        echo "\n\n";
        echo $this->color('OUTPUT (EXEC): ', 'yellow') . "\n";
        if (is_resource($process)) {
            while ($line = fgets($pipes[1])) {
                echo $line . "\n";  
                flush();
                ob_flush();
            }
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
        }
        ob_end_flush();
        return true;
    }


    private function rawExec($cmd,$returnOutput=false)
    {
        exec($cmd, $output, $return);
        echo "\n\n";
        echo $this->color('OUTPUT (EXEC): ', 'yellow') . "\n";
        echo json_encode($output);
        echo "\n\n";
        if($return !== 0){
            echo "\n\n";
            echo $this->color('ERROR:','red') . ' The executable failed.';
            return false;
        }
        if($returnOutput){
            return $output;
        }
        return true;
    }

    private function renderColor($text,$code)
    {
        return "\033[".$code."m".$text."\033[0m";
    }

    private function renderEmoji($type)
    {
        return match($type){
            'ok','good' => '✅',
            'cancel','error' => '❌',
            'warn','warning' => '⚠️ ',
            default => ''
        };
    }

}