<?php

namespace GoDaddy\WordPress\MWC\Core\Email;

use GoDaddy\WordPress\MWC\Common\Email\Email;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\RenderableEmailContract;

class RenderableEmail extends Email implements RenderableEmailContract
{
    /**
     * @var array assoc. array of variables used to substitute merge tags when rendering the email.
     */
    protected $variables = [];

    /**
     * {@inheritdoc}
     */
    public function getVariables(): array
    {
        return $this->variables;
    }

    /**
     * {@inheritdoc}
     */
    public function setVariables(array $value): RenderableEmail
    {
        $this->variables = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getBodyFormat(): string
    {
        return ArrayHelper::get([
            'text/html' => 'html',
            'text/mjml' => 'mjml',
            'text/plain' => 'plain',
        ], $this->getContentType(), 'html');
    }
}
