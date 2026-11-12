<?php

class RequestSocket {

    public function send($query)
    {
        $query = str_replace("\\n", "\n", $query);
        $query = rtrim($query) . "\n";

        $timeout = (float) ($this->options['timeout'] ?? 30);
        $connectTimeout = (float) ($this->options['connect_timeout'] ?? 10);
        $tls = $this->optionAsBool(
            $this->options['tls'] ?? false
        );

        $socket = $this->openSocket(
            $this->host,
            $this->port,
            $connectTimeout,
            $tls,
            $errno,
            $errstr
        );

        if (!$socket) {
            echo "Error: $errstr ($errno)<br />\n";
            return false;
        }

        if (fwrite($socket, $query) === false) {
            fclose($socket);
            echo "Error: unable to write to MixQL socket<br />\n";
            return false;
        }

        stream_set_timeout($socket, $timeout);
        $response = stream_get_contents($socket);

        fclose($socket);

        return $response === false ? '' : $response;
    }

    protected function openSocket(
        string $host,
        int $port,
        float $connectTimeout,
        bool $tls,
        &$errno,
        &$errstr
    ) {
        $endpoint = $this->formatEndpoint($host, $port);
        $context = null;
        $transport = 'tcp';

        if ($tls) {
            $transport = 'tls';
            $serverName = $this->options['tls_server_name'] ?? $host;
            $verify = $this->optionAsBool(
                $this->options['tls_verify'] ?? true
            );

            $sslOptions = [
                'verify_peer' => $verify,
                'verify_peer_name' => $verify,
                'allow_self_signed' => !$verify,
                'peer_name' => $serverName,
                'SNI_enabled' => true,
            ];

            if (!empty($this->options['tls_ca_file'])) {
                $sslOptions['cafile'] = $this->options['tls_ca_file'];
            }

            if (!empty($this->options['tls_ca_path'])) {
                $sslOptions['capath'] = $this->options['tls_ca_path'];
            }

            $context = stream_context_create([
                'ssl' => $sslOptions,
            ]);
        }

        return stream_socket_client(
            $transport . '://' . $endpoint,
            $errno,
            $errstr,
            $connectTimeout,
            STREAM_CLIENT_CONNECT,
            $context
        );
    }

    private function formatEndpoint(string $host, int $port): string
    {
        if (strpos($host, ':') !== false && substr($host, 0, 1) !== '[') {
            $host = '[' . $host . ']';
        }

        return $host . ':' . $port;
    }

    private function optionAsBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $parsed === null ? false : $parsed;
    }

}
