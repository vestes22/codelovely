<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters;

use Exception;
use Firebase\JWT\JWT;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\GenerateTokenRequest;

/**
 * Class AccessTokenAdapter.
 */
class AccessTokenAdapter implements DataSourceAdapterContract
{
    /** @var string */
    private $source;

    /**
     * AccessTokenAdapter constructor.
     *
     * @param string $existingToken
     */
    public function __construct(string $existingToken)
    {
        $this->source = $existingToken;
    }

    /**
     * Converts into a request.
     *
     * @return GenerateTokenRequest
     * @throws Exception
     */
    public function convertFromSource() : GenerateTokenRequest
    {
        $request = $this->getGenerateTokenRequest();

        $request->setBody([
            'grantType' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $this->generateJwt(Poynt::getAppId(), Poynt::getPrivateKey(), $request->getRootUrl()),
        ]);

        return $request;
    }

    /**
     * Get new instance of generate token request.
     *
     * @return GenerateTokenRequest
     */
    protected function getGenerateTokenRequest() : GenerateTokenRequest
    {
        return new GenerateTokenRequest();
    }

    /**
     * Converts the given API response into an access token.
     *
     * @param Response|null $response
     *
     * @return string
     */
    public function convertToSource(Response $response = null) : string
    {
        if (! $response instanceof Response) {
            return $this->source;
        }

        $data = $response->getBody();

        $this->source = ArrayHelper::get($data, 'accessToken', '');

        return $this->source;
    }

    /**
     * Generates a signed JSON Web Token.
     *
     * @param string $appId the application ID
     * @param string $privateKey the private key
     * @param string $apiUrl the Poynt API URL
     * @return string signed JWT
     * @throws Exception
     */
    public function generateJwt(string $appId, string $privateKey, string $apiUrl) : string
    {
        $issuedAt = $notBeforeThan = time();
        $expireAt = $issuedAt + 300;
        $payload = [
            'iss' => $appId,
            'sub' => $appId,
            'aud' => $apiUrl,
            'iat' => $issuedAt,
            'nbf' => $notBeforeThan,
            'exp' => $expireAt,
            'jti' => StringHelper::generateUuid4(),
        ];

        return JWT::encode($payload, $privateKey, 'RS256');
    }
}
