<?php

namespace App\Handlers;
use Psr\Http\Message\ServerRequestInterface;

class RequestHandler
{
    private array $headers;
    private array $serverInfo;
    private array $requestInfo;
    private array $sensitiveHeaders;
    private ServerRequestInterface $request;

    public function __construct(ServerRequestInterface $request)
    {
        $this->request = $request;
        $this->headers = array_change_key_case($request->getHeaders(), CASE_LOWER);

        $this->sensitiveHeaders = [
            'authorization', 'cookie', 'set-cookie', 'x-api-key',
            'api-key', 'password', 'token', 'secret',
            strtolower(getenv('APP_HEADER_CDN_VERIFICATION_API_KEY_NAME')),
            strtolower(getenv('APP_HEADER_CDN_VERIFICATION_API_SECRET_NAME'))
        ];

        $serverParams = $request->getServerParams();
        $this->serverInfo = [
            'name' => gethostname(),
            'ips' => $this->getServerIps(),
            'software' => $serverParams['SERVER_SOFTWARE'] ?? 'RoadRunner',
            'protocol' => $this->getProtocolInfo(),
            'php_version' => PHP_VERSION,
            'php_sapi' => PHP_SAPI,
        ];

        $this->requestInfo = [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'query' => $request->getQueryParams()
        ];
    }

    public function getInfo(int $status = 200, string $message = 'success', ?array $error = null): array
    {
        $info = [
            'status' => [
                'code' => $status,
                'message' => $message
            ],
            'trace_id' => $this->getTraceId() ?? 'no-request-trace-id-is-set-on-develop-mode',
            'server' => $this->serverInfo,
            'headers' => $this->getProcessedHeaders(),
            'client_ips' => $this->getClientIps(),
            'request' => $this->requestInfo,
            'ENV' => [
                'APP_ENV_SITE_TITLE' => getenv('APP_ENV_SITE_TITLE'),
                'APP_ENV_SITE_COPYRIGHT_YEAR' => getenv('APP_ENV_SITE_COPYRIGHT_YEAR'),
                'APP_ENV_SITE_COPYRIGHT_NAME' => getenv('APP_ENV_SITE_COPYRIGHT_NAME'),
                'APP_ENV_SITE_COPYRIGHT_URL' => getenv('APP_ENV_SITE_COPYRIGHT_URL'),
            ]
        ];

        if ($error) {
            $info['error'] = $error;
        }

        return $info;
    }

    private function getProcessedHeaders(): array
    {
        $processed = [];
        foreach ($this->headers as $name => $values) {
            if (empty($values)) {
                continue;
            }

            if (in_array($name, $this->sensitiveHeaders)) {
                $processed[$name] = '[REDACTED]';
                continue;
            }

            $processed[$name] = implode(', ', $values);
        }

        return $processed;
    }

    private function getTraceId(): ?string
    {
        $headerName = strtolower(getenv('APP_HEADER_TRACE_ID_NAME'));
        return $this->headers[$headerName][0] ?? null;
    }

    private function getClientIps(): array
    {
        $ips = [];

        if (isset($this->headers['x-forwarded-for'])) {
            $allHeaders = is_array($this->headers['x-forwarded-for']) 
                ? $this->headers['x-forwarded-for'] 
                : [$this->headers['x-forwarded-for']];
            
            $ips['forwarded'] = implode(',', array_map('trim', explode(',', implode(',', $allHeaders))));
        }

        $serverParams = $this->request->getServerParams();
        if (!empty($serverParams['REMOTE_ADDR'])) {
            $ips['remote'] = $serverParams['REMOTE_ADDR'];
        }

        if (isset($this->headers['cf-connecting-ip'])) {
            $ips['cdn'] = $this->headers['cf-connecting-ip'][0];
        } elseif (isset($this->headers['true-client-ip'])) {
            $ips['cdn'] = $this->headers['true-client-ip'][0];
        }

        return $ips;
    }

    private function getServerIps(): array
    {
        $serverIps = [];

        $hostname = gethostname();
        if ($hostname !== false) {
            $serverIps['hostname'] = $hostname;

            $hostIps = gethostbyname($hostname);
            if ($hostIps && $hostIps !== $hostname) {
                $serverIps['host_ip'] = $hostIps;
            }
        }

        return $serverIps;
    }

    private function getProtocolInfo(): array
    {
        $serverParams = $this->request->getServerParams();
        
        $protocol = 'HTTP';
        $transport = 'TCP';
        $alpn = null;
        $cipher = null;
        
        // base protocol
        if (isset($serverParams['SERVER_PROTOCOL'])) {
            $protocol = strstr($serverParams['SERVER_PROTOCOL'], '/', true) ?: 'HTTP';
        }

        $isHttps = $this->request->getUri()->getScheme() === 'https'
            || ($serverParams['HTTPS'] ?? '') === 'on'
            || (isset($this->headers['x-forwarded-proto']) && strtolower($this->headers['x-forwarded-proto'][0]) === 'https');

        // tls info
        if ($isHttps && isset($serverParams['SSL_PROTOCOL'])) {
            $alpn = $serverParams['ALPN_PROTOCOL'] ?? $serverParams['SSL_PROTOCOL'];
            $cipher = $serverParams['SSL_CIPHER'] ?? null;
        }

        // http2
        if (isset($serverParams['HTTP2']) || 
            (isset($this->headers['x-firefox-spdy']) && $this->headers['x-firefox-spdy'][0] === 'h2')) {
            $protocol = 'HTTP/2';
            $transport = 'TCP';
        }

        // h3/quic
        if (isset($this->headers['alt-svc'])) {
            $altSvc = $this->headers['alt-svc'][0];
            if (stripos($altSvc, 'h3') !== false || stripos($altSvc, 'quic') !== false) {
                $protocol = 'HTTP/3';
                $transport = 'QUIC/UDP';
            }
        }

        // websocket
        if (isset($this->headers['upgrade'])) {
            $upgrade = strtolower($this->headers['upgrade'][0]);
            if ($upgrade === 'websocket') {
                $protocol = 'WebSocket';
                // WebSocket protocol version detection
                $wsVersion = $this->headers['sec-websocket-version'][0] ?? null;
                $transport = $wsVersion ? 'TCP' : 'UDP';
            }
        }

        // port
        $port = $this->request->getUri()->getPort();
        if ($port === null) {
            $port = $isHttps ? 443 : 80;
        }

        return [
            'application' => [
                'name' => $protocol,
                'version' => $this->request->getProtocolVersion(),
                'alpn' => $alpn,
            ],
            'transport' => [
                'protocol' => $transport,
                'secure' => $isHttps,
                'cipher' => $cipher,
            ],
            'connection' => [
                'scheme' => $this->request->getUri()->getScheme(),
                'port' => $port,
                'host' => $this->request->getUri()->getHost(),
            ],
            'headers' => [
                'via' => $this->headers['via'][0] ?? null,
                'forwarded' => $this->headers['forwarded'][0] ?? null,
                'alt_svc' => $this->headers['alt-svc'][0] ?? null,
            ]
        ];
    }
}