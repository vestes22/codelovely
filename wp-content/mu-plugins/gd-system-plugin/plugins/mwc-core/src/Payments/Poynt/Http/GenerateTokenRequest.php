<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http;

use Exception;
use GoDaddy\WordPress\MWC\Common\Http\Response;

class GenerateTokenRequest extends Request
{
    /** @var string */
    protected $route = 'token';

    /**
     * GenerateTokenRequest constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        unset($this->headers['Authorization']);

        $this->headers['Content-Type'] = 'application/x-www-form-urlencoded';
    }

    /**
     * Sends the request.
     *
     * @return Response
     * @throws Exception
     */
    public function send() : Response
    {
        $this->validate();

        return new Response(wp_remote_request($this->buildUrlString(), [
            'body'      => http_build_query($this->body, '', '&'),
            'headers'   => $this->headers,
            'method'    => $this->method,
            'sslverify' => $this->sslVerify,
            'timeout'   => $this->timeout,
        ]));
    }
}
