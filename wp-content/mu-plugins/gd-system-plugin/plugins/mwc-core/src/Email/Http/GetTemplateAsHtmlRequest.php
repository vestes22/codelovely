<?php

namespace GoDaddy\WordPress\MWC\Core\Email\Http;

use Exception;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Core\Http\EventBridgeRequest;

/**
 * A request used to convert MJML templates into HTML.
 *
 * @method self url(string $value)
 * @method self setMethod(string $value)
 * @method self headers(array $additionalHeaders)
 * @method self setSiteId(string $siteId)
 */
class GetTemplateAsHtmlRequest extends EventBridgeRequest
{
    /** @var string MJML template */
    protected $template = '';

    /** @var array variables for the MJML template */
    protected $templateParameters = [];

    /**
     * Gets the MJML template for this request.
     *
     * @return string
     */
    public function getTemplate() : string
    {
        return $this->template;
    }

    /**
     * Gets the parameters for the template associated with this request.
     *
     * @return array
     */
    public function getTemplateParameters() : array
    {
        return $this->templateParameters;
    }

    /**
     * Sets the MJML template for this request.
     *
     * @param string $value the MJML template
     * @return self
     */
    public function setTemplate(string $value) : GetTemplateAsHtmlRequest
    {
        $this->template = $value;

        return $this;
    }

    /**
     * Sets the parameters for the template associated with this request.
     *
     * @param array $value an array of parameters
     * @return self
     */
    public function setTemplateParameters(array $value) : GetTemplateAsHtmlRequest
    {
        $this->templateParameters = $value;

        return $this;
    }

    /**
     * Sends the request.
     *
     * @return Response
     * @throws Exception
     */
    public function send() : Response
    {
        if (! $this->body) {
            $this->body([
                'query'     => $this->getQuery(),
                'variables' => $this->getVariables(),
            ]);
        }

        return parent::send();
    }

    /**
     * Gets the GraphQL query for the request.
     *
     * @return string
     */
    protected function getQuery() : string
    {
        return <<<'GQL'
query ($template: String, $templateParametersJson: String, $templateEncoded: String, $templateParametersJsonEncoded: String){
  templateAsHtml(template: $template, templateParametersJson: $templateParametersJson, templateEncoded: $templateEncoded, templateParametersJsonEncoded: $templateParametersJsonEncoded ) {
    html
  }
}
GQL;
    }

    /**
     * Gets the variables for the request.
     *
     * @return array
     */
    protected function getVariables() : array
    {
        return [
            'templateEncoded'               => base64_encode($this->getTemplate()),
            'templateParametersJsonEncoded' => base64_encode(json_encode($this->getTemplateParameters())),
        ];
    }
}
