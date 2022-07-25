<?php

namespace GoDaddy\WordPress\MWC\Core\Email;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ConditionalComponentContract;
use GoDaddy\WordPress\MWC\Common\Email\Contracts\EmailContract;
use GoDaddy\WordPress\MWC\Common\Email\Contracts\EmailServiceContract;
use GoDaddy\WordPress\MWC\Common\Email\Emails;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Core\Email\DataSources\WordPress\Adapters\AttachmentsAdapter;
use GoDaddy\WordPress\MWC\Core\Email\Exceptions\EmailAttachmentException;
use GoDaddy\WordPress\MWC\Core\Email\Exceptions\EmailSendFailedException;
use GoDaddy\WordPress\MWC\Core\Email\Exceptions\EmailsServiceException;
use GoDaddy\WordPress\MWC\Core\Email\Http\EmailsServiceRequest;
use GoDaddy\WordPress\MWC\Core\Email\Http\GraphQL\Mutations\CreateScheduledEmailMutation;
use GoDaddy\WordPress\MWC\Core\Email\Models\EmailSender;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\RenderableEmailContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\EmailNotifications;

class EmailService implements EmailServiceContract, ConditionalComponentContract
{
    /** @var bool whether we can use this email service */
    protected static $enabled = true;

    /**
     * Determines whether the email service can be used.
     *
     * @return bool
     */
    public static function shouldLoad() : bool
    {
        return static::$enabled && static::isSenderAddressVerified();
    }

    /**
     * Determines whether the configured sender address is verified.
     *
     * @return bool
     */
    protected static function isSenderAddressVerified() : bool
    {
        if (! $emailAddress = EmailNotifications::getSenderAddress()) {
            return false;
        }

        return static::isEmailAddressVerified($emailAddress);
    }

    /**
     * Determines whether the given email address is verified.
     *
     * @param string $emailAddress email address to verify
     * @return bool
     */
    protected static function isEmailAddressVerified(string $emailAddress) : bool
    {
        $emailSender = EmailSender::get($emailAddress);

        return $emailSender && $emailSender->isVerified();
    }

    /**
     * Initializes the email service.
     */
    public function load()
    {
        // TODO: Implement load() method.
    }

    /**
     * Allows this email service to be used to send emails if all other load conditions are met.
     */
    public static function enable()
    {
        static::$enabled = true;
    }

    /**
     * Prevents this email service from being used to send emails.
     */
    public static function disable()
    {
        static::$enabled = false;
    }

    /**
     * Sends an email.
     *
     * @param EmailContract $email
     * @throws Exception
     */
    public function send(EmailContract $email)
    {
        try {
            if (! $email instanceof RenderableEmailContract) {
                throw new EmailSendFailedException(sprintf(
                    '%1$s does not support sending an email of the class %2$s.',
                    static::class,
                    get_class($email)
                ));
            }

            $this->sendEmail($email);
        } catch (EmailsServiceException $exception) {
            $this->tryToSendEmailWithAnotherService($email);
        } catch (EmailSendFailedException $exception) {
            $this->tryToSendEmailWithAnotherService($email);
        }
    }

    /**
     * Sends an email using our emails service.
     *
     * @param RenderableEmailContract $email
     * @throws EmailsServiceException
     * @throws EmailSendFailedException
     */
    protected function sendEmail(RenderableEmailContract $email)
    {
        try {
            $response = $this->buildRequest($email)->send();
        } catch (EmailsServiceException $exception) {
            throw $exception;
        } catch (Exception $exception) {
            throw new EmailSendFailedException($exception->getMessage());
        }

        if ($response->isError()) {
            throw new EmailSendFailedException((string) $response->getErrorMessage());
        }
    }

    /**
     * Prepares a GraphQL request object from the given email.
     *
     * @param RenderableEmailContract $email
     * @return EmailsServiceRequest
     */
    protected function buildRequest(RenderableEmailContract $email) : EmailsServiceRequest
    {
        return (new EmailsServiceRequest())->setOperation($this->buildMutation($email));
    }

    /**
     * Prepares a CreateScheduledEmailMutation from the given email.
     *
     * @param RenderableEmailContract $email
     * @return CreateScheduledEmailMutation
     */
    protected function buildMutation(RenderableEmailContract $email) : CreateScheduledEmailMutation
    {
        return (new CreateScheduledEmailMutation())->setVariables([
            'input' => [
                'emailEventName'     => $this->getEmailEventName($email),
                'siteId'             => ManagedWooCommerceRepository::getSiteId(),
                'emailMessage'       => array_merge([
                    'sender'       => [
                        'name'    => (string) EmailNotifications::getSenderName(),
                        'address' => (string) EmailNotifications::getSenderAddress(),
                    ],
                    'toRecipients' => array_map(static function (string $to) : array {
                        return ['address' => $to];
                    }, ArrayHelper::wrap($email->getTo())),
                    'subject'      => $email->getSubject(),
                    'extraHeaders' => array_map(static function (string $headerName, string $headerBody) {
                        return ['headerName' => $headerName, 'headerBody' => $headerBody];
                    }, array_keys($email->getHeaders()), array_values($email->getHeaders())),
                    'attachments'  => $this->buildMutationInputAttachments($email),
                ], $this->getEmailBodyFields($email)),
                'templateParamsJson' => json_encode($email->getVariables()),
            ],
        ]);
    }

    /**
     * Prepares email attachments data for the mutation input.
     *
     * @param RenderableEmailContract $email
     * @return array
     */
    protected function buildMutationInputAttachments(RenderableEmailContract $email) : array
    {
        $attachmentsData = [];

        foreach ($email->getAttachments() as $attachmentFile) {
            try {
                $attachmentsData[] = $this->getAttachmentsAdapter($attachmentFile)->convertFromSource();
            } catch (EmailAttachmentException $exception) {
                // do nothing
            }
        }

        return $attachmentsData;
    }

    /**
     * Gets an instance of Attachments adapter with given data.
     *
     * @param string $filePath
     * @return AttachmentsAdapter
     */
    protected function getAttachmentsAdapter(string $filePath) : AttachmentsAdapter
    {
        return new AttachmentsAdapter($filePath);
    }

    /**
     * Gets body fields according to format of given email.
     *
     * @param RenderableEmailContract $email
     * @return string[] with key(s) mjml/html/plain key.
     */
    protected function getEmailBodyFields(RenderableEmailContract $email) : array
    {
        return [$email->getBodyFormat() => $email->getBody()];
    }

    /**
     * The email event name, sourced from the email id or name, e.g., new_order.
     *
     * @param RenderableEmailContract $email
     * @return string
     */
    protected function getEmailEventName(RenderableEmailContract $email) : string
    {
        return $email->getEmailName();
    }

    /**
     * Try to send an email using one of the other registered email services.
     *
     * @param RenderableEmailContract $email
     * @throws Exception
     */
    protected function tryToSendEmailWithAnotherService(RenderableEmailContract $email)
    {
        static::disable();

        Emails::send($email);

        static::enable();
    }
}
