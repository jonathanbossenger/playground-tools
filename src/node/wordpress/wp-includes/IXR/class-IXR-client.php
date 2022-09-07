<?php
 class IXR_Client { var $server; var $port; var $path; var $useragent; var $response; var $message = false; var $debug = false; var $timeout; var $headers = array(); var $error = false; function __construct( $server, $path = false, $port = 80, $timeout = 15 ) { if (!$path) { $bits = parse_url($server); $this->server = $bits['host']; $this->port = isset($bits['port']) ? $bits['port'] : 80; $this->path = isset($bits['path']) ? $bits['path'] : '/'; if (!$this->path) { $this->path = '/'; } if ( ! empty( $bits['query'] ) ) { $this->path .= '?' . $bits['query']; } } else { $this->server = $server; $this->path = $path; $this->port = $port; } $this->useragent = 'The Incutio XML-RPC PHP Library'; $this->timeout = $timeout; } public function IXR_Client( $server, $path = false, $port = 80, $timeout = 15 ) { self::__construct( $server, $path, $port, $timeout ); } function query( ...$args ) { $method = array_shift($args); $request = new IXR_Request($method, $args); $length = $request->getLength(); $xml = $request->getXml(); $r = "\r\n"; $request = "POST {$this->path} HTTP/1.0$r"; $this->headers['Host'] = $this->server; $this->headers['Content-Type'] = 'text/xml'; $this->headers['User-Agent'] = $this->useragent; $this->headers['Content-Length']= $length; foreach( $this->headers as $header => $value ) { $request .= "{$header}: {$value}{$r}"; } $request .= $r; $request .= $xml; if ($this->debug) { echo '<pre class="ixr_request">'.htmlspecialchars($request)."\n</pre>\n\n"; } if ($this->timeout) { $fp = @fsockopen($this->server, $this->port, $errno, $errstr, $this->timeout); } else { $fp = @fsockopen($this->server, $this->port, $errno, $errstr); } if (!$fp) { $this->error = new IXR_Error(-32300, 'transport error - could not open socket'); return false; } fputs($fp, $request); $contents = ''; $debugContents = ''; $gotFirstLine = false; $gettingHeaders = true; while (!feof($fp)) { $line = fgets($fp, 4096); if (!$gotFirstLine) { if (strstr($line, '200') === false) { $this->error = new IXR_Error(-32300, 'transport error - HTTP status code was not 200'); return false; } $gotFirstLine = true; } if (trim($line) == '') { $gettingHeaders = false; } if (!$gettingHeaders) { $contents .= $line; } if ($this->debug) { $debugContents .= $line; } } if ($this->debug) { echo '<pre class="ixr_response">'.htmlspecialchars($debugContents)."\n</pre>\n\n"; } $this->message = new IXR_Message($contents); if (!$this->message->parse()) { $this->error = new IXR_Error(-32700, 'parse error. not well formed'); return false; } if ($this->message->messageType == 'fault') { $this->error = new IXR_Error($this->message->faultCode, $this->message->faultString); return false; } return true; } function getResponse() { return $this->message->params[0]; } function isError() { return (is_object($this->error)); } function getErrorCode() { return $this->error->code; } function getErrorMessage() { return $this->error->message; } } 