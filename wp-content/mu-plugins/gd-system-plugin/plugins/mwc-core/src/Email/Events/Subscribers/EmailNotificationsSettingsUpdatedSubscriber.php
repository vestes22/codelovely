<?php

namespace GoDaddy\WordPress\MWC\Core\Email\Events\Subscribers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Core\Email\Http\EmailsServiceRequest;
use GoDaddy\WordPress\MWC\Core\Email\Http\GraphQL\Mutations\CreateEmailSenderMutation;
use GoDaddy\WordPress\MWC\Core\Email\Http\GraphQL\Queries\EmailSenderQuery;
use GoDaddy\WordPress\MWC\Core\Email\Repositories\EmailSenderRepository;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Events\EmailNotificationsSettingsUpdatedEvent;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Settings\GeneralSettings;

class EmailNotificationsSettingsUpdatedSubscriber implements SubscriberContract
{
    /**
     * Handles the event.
     *
     * @param EventContract $event
     * @throws Exception
     */
    public function handle(EventContract $event)
    {
        // bail if isn't the correct event type
        if (! $event instanceof EmailNotificationsSettingsUpdatedEvent) {
            return;
        }

        /** @var $event EmailNotificationsSettingsUpdatedEvent */
        if (! $emailAddress = ArrayHelper::get($event->getData(), 'settings.'.GeneralSettings::SETTING_ID_SENDER_ADDRESS)) {
            return;
        }

        try {
            try {
                if ($this->shouldCreateEmailSender($emailAddress)) {
                    $this->getEmailsServiceRequest($this->getCreateEmailSenderMutationOperation($emailAddress))->send();
                }
            } catch (Exception $exception) {
                throw ($exception instanceof SentryException ? $exception : new SentryException($exception->getMessage()));
            }
        } catch (SentryException $exception) {
            // prevent the exception from propagating: the error will be automatically reported to sentry
        }
    }

    /**
     * Determines that given email address needs to a corresponding email sender.
     *
     * @param string $emailAddress
     * @return bool
     * @throws Exception
     */
    protected function shouldCreateEmailSender(string $emailAddress) : bool
    {
        $response = $this->getEmailsServiceRequest($this->getEmailSenderQueryOperation($emailAddress))->send();

        return ! $response->isError() && ! ArrayHelper::get($response->getBody(), 'data.emailSender');
    }

    /**
     * Gets a proper Emails Service request instance to querying email sender.
     *
     * @param AbstractGraphQLOperation $operation
     * @return EmailsServiceRequest
     */
    protected function getEmailsServiceRequest(AbstractGraphQLOperation $operation) : EmailsServiceRequest
    {
        return (new EmailsServiceRequest())->setOperation($operation);
    }

    /**
     * Gets an email sender GraphQL query operation.
     *
     * @param string $emailAddress
     * @return EmailSenderQuery
     */
    protected function getEmailSenderQueryOperation(string $emailAddress) : EmailSenderQuery
    {
        return (new EmailSenderQuery())->setVariables(['emailAddress' => $emailAddress]);
    }

    /**
     * Gets an email sender GraphQL query operation.
     *
     * @param string $emailAddress
     * @return CreateEmailSenderMutation
     * @throws Exception
     */
    protected function getCreateEmailSenderMutationOperation(string $emailAddress) : CreateEmailSenderMutation
    {
        return (new CreateEmailSenderMutation())->setVariables([
            'emailAddress'                   => $emailAddress,
            'siteId'                         => ManagedWooCommerceRepository::getSiteId(),
            'mailboxVerificationRedirectUrl' => EmailSenderRepository::getMailboxVerificationRedirectUrl(),
        ]);
    }
}
