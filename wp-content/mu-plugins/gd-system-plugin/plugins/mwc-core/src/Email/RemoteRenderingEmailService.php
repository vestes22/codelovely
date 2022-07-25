<?php

namespace GoDaddy\WordPress\MWC\Core\Email;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Email\Contracts\EmailContract;
use GoDaddy\WordPress\MWC\Common\Email\Contracts\EmailServiceContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Core\Email\Exceptions\EmailSendFailedException;
use GoDaddy\WordPress\MWC\Core\Email\Http\GetTemplateAsHtmlRequest;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\RenderableEmailContract;

/**
 * Email service for rendering MJML emails using a remote service and sending the resulting HTML through WordPress.
 */
class RemoteRenderingEmailService implements EmailServiceContract
{
    /**
     * Loads the component.
     */
    public function load()
    {
        // no-op, implements contract method
    }

    /**
     * Sends an email.
     *
     * @param EmailContract $email
     * @throws EmailSendFailedException
     */
    public function send(EmailContract $email)
    {
        if (! $email instanceof RenderableEmailContract || $email->getBodyFormat() !== 'mjml') {
            throw new EmailSendFailedException(sprintf(
                '%1$s does not support sending emails with the %2$s content type.',
                static::class,
                $email->getContentType()
            ));
        }

        $this->sendMjmlEmail($email);
    }

    /**
     * Sends a renderable email that uses an MJML template as the body.
     *
     * @param RenderableEmailContract $email the email object
     * @throws EmailSendFailedException
     */
    protected function sendMjmlEmail(RenderableEmailContract $email)
    {
        (clone $email)
            ->setContentType('text/html')
            ->setBody($this->getBodyAsHtml($email))
            ->setAltBody('')
            ->send();
    }

    /**
     * Uses a remote service to convert the MJML body of the given email into HTML.
     *
     * @param RenderableEmailContract $email
     * @return string
     * @throws EmailSendFailedException
     */
    protected function getBodyAsHtml(RenderableEmailContract $email) : string
    {
        try {
            $response = $this->buildRequest($email)->send();
        } catch (Exception $e) {
            throw new EmailSendFailedException($e->getMessage());
        }

        if ($errorMessage = $this->getResponseErrorMessage($response)) {
            throw new EmailSendFailedException($errorMessage);
        }

        if (! $html = ArrayHelper::get($response->getBody(), 'data.templateAsHtml.html')) {
            throw new EmailSendFailedException('The HTML template is missing or empty.');
        }

        return $html;
    }

    /**
     * Prepares a {@see GetTemplateAsHtmlRequest} object with the body and variables from the given email.
     *
     * @param RenderableEmailContract $email
     * @return GetTemplateAsHtmlRequest
     * @throws Exception
     */
    protected function buildRequest(RenderableEmailContract $email) : GetTemplateAsHtmlRequest
    {
        return (new GetTemplateAsHtmlRequest())
            ->url(Configuration::get('mwc.events.api.url'))
            ->setMethod('POST')
            ->headers([
                'Authorization' => Configuration::get('events.auth.type', 'Bearer').' '.Configuration::get('events.auth.token'),
            ])
            ->setSiteId(ManagedWooCommerceRepository::getSiteId())
            ->setTemplate($email->getBody())
            ->setTemplateParameters($email->getVariables());
    }

    /**
     * Gets the error message from a response object.
     *
     * Returns null if the response doesn't include information to indicate that an error occurred.
     *
     * @param Response $response the response object
     * @return string|null
     */
    protected function getResponseErrorMessage(Response $response)
    {
        if ($response->isError()) {
            return $response->getErrorMessage() ?: __('Unknown error.', 'mwc-core');
        }

        if ($errors = ArrayHelper::get($response->getBody(), 'errors')) {
            return ArrayHelper::get(reset($errors), 'message', __('Unknown error.', 'mwc-core'));
        }

        return null;
    }
}
