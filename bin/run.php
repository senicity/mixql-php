<?php

include_once __DIR__ . '/../autoload.php';
include_once __DIR__ . '/helpers/CommandHelpers.php';

$CmdHelpers = new CommandHelpers;

echo "\n";
echo "███╗   ███╗██╗██╗  ██╗ ██████╗ ██╗\n";     
echo "████╗ ████║██║╚██╗██╔╝██╔═══██╗██║\n";     
echo "██╔████╔██║██║ ╚███╔╝ ██║   ██║██║\n";     
echo "██║╚██╔╝██║██║ ██╔██╗ ██║▄▄ ██║██║\n";     
echo "██║ ╚═╝ ██║██║██╔╝ ██╗╚██████╔╝███████╗\n";     
echo "╚═╝     ╚═╝╚═╝╚═╝  ╚═╝ ╚══▀▀═╝ ╚══════╝\n";   
echo "__  __\n";     
echo "\\ \/ /\n";     
echo " \  / \n";     
echo " /  \\ \n";     
echo "/_/\\_\\\n";     
echo "       _\n";           
echo " _ __ | |__  _ __  \n";  
echo "| '_ \\| '_ \\| '_ \\ \n";     
echo "| |_) | | | | |_) |\n";     
echo "| .__/|_| |_| .__/ \n";     
echo "|_|         |_|    \n";    
echo "                                       \n"; 
echo "// -- Powered by:\n"; 

echo "┏┓┏┓┳┓┳┏┓┳┏┳┓┓┏\n";
echo "┗┓┣ ┃┃┃┃ ┃ ┃ ┗┫\n";     
echo "┗┛┗┛┛┗┻┗┛┻ ┻ ┗┛\n";               
echo "               \n";     
echo "// --> https://senicity.com\n";
echo "// --                                           \n";
echo "\n";
echo "--// MIXQL-PHP Test Runner: \n";
echo "\n";

// -- Remove the first argument item, which is just the command file loc:
array_shift($argv);

// -- Define Class Label
$Class = $argv[0] ?? false;
echo $CmdHelpers->color('Running Command:','bluebg') . " " . $Class;

// -- Remove the Class Name from the input arguments:
array_shift($argv);

// -- Separate Flags From Args ::
$flags = [];
$args = [];
foreach ($argv as $arg) {
    if (strpos($arg, '--') === 0){
        $flags[] = $arg;
    } else {
        $args[] = $arg;
    }
}

if(!$Class){
    echo "\n\n";
    echo $CmdHelpers->color('ERROR:','red','error') . ' Missing Command Option in Request';
    echo "\n\n";
    exit();
}

$type = 'commands';
/*if(in_array('--flag',$flags)){
    // -- add further flag types if needed.
}*/

$ClassFile = __DIR__ . '/../tests/' . $type . '/' . $Class . '.php';
if(!is_file($ClassFile))
{
    echo "\n\n";
    echo $CmdHelpers->color('ERROR:','red','error') . ' Command Does Not Exist';
    echo "\n\n";
    exit();
}

 // -- Pull in the Command Class ::
 include_once $ClassFile;

// -- Build the Command Object ::
$commandClass = new $Class($args,$flags);

// -- Run the Command ::
// -- Display all okay ::
try {
    if ($commandClass->execute()) {
        echo "\n\n";
        echo $CmdHelpers->color('SUCCESS','green','ok') . "\n";
        echo 'Command Successfully Executed';
        echo "\n\n";
    }else {
        $lastError = error_get_last();
        $errorMessage = $lastError ? $lastError['message'] : 'Unknown error';
        throw new Exception('Command execution failed: ' . $errorMessage);
    }
} catch (Exception $e){
    echo "\n\n";
    echo $CmdHelpers->color('ERROR:','red','error') . ' There was an error recorded while executing the command.' . "\n\n";
    echo $CmdHelpers->color('ERROR OUTPUT:','red') . "\n";
    echo $CmdHelpers->color($e->getMessage(), 'redbg');
    echo "\n\n";
}
