<?php

class RequestSocket {

    public function send($query)
    {

        $query = str_replace("\\n", "\n", $query);
        $query = rtrim($query) . "\n";  

        $socket = fsockopen($this->host, $this->port, $errno, $errstr, 10);

        if (!$socket) {
            echo "Error: $errstr ($errno)<br />\n";
            return false;
        }

        fwrite($socket, $query);

        stream_set_timeout($socket, $this->options['timeout']);

        $response = '';
        while (!feof($socket)) {
            $line = fgets($socket, 1024); 
            if ($line) {
                $response .= $line;
            }
        }

        fclose($socket);

        return $response;
    }

}