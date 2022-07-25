<?php

namespace GoDaddy\WordPress\MWC\Core\Email\Repositories;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Core\Email\Exceptions\EmailsServiceAuthProviderException;
use GoDaddy\WordPress\MWC\Core\Email\Exceptions\EmailsServiceException;
use GoDaddy\WordPress\MWC\Core\Email\Http\EmailsServiceRequest;
use GoDaddy\WordPress\MWC\Core\Email\Http\GraphQL\Mutations\CreateEmailSenderMutation;
use GoDaddy\WordPress\MWC\Core\Email\Http\GraphQL\Queries\EmailSenderQuery;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\EmailsPage;

/**
 * Repository to access information about email senders.
 */
class EmailSenderRepository
{
    /**
     * Gets or creates an email sender in the emails services.
     *
     * @param string $emailAddress email address for the sender
     * @return array email sender data
     * @throws EmailsServiceAuthProviderException
     * @throws EmailsServiceException
     */
    public static function getOrCreate(string $emailAddress) : array
    {
        $responseData = static::sendRequest((new EmailSenderQuery())->setVariables([
            'emailAddress' => urldecode($emailAddress),
        ]));

        if (! static::isExistingEmailSender($responseData)) {
            return static::create($emailAddress);
        }

        return static::getEmailSenderData($responseData);
    }

    /**
     * Sends the given request.
     *
     * @param AbstractGraphQLOperation $query
     * @return array
     * @throws EmailsServiceAuthProviderException
     * @throws EmailsServiceException
     */
    protected static function sendRequest(AbstractGraphQLOperation $query) : array
    {
        try {
            $response = (new EmailsServiceRequest())
                ->setOperation($query)
                ->send();

            if ($response->isError()) {
                throw new EmailsServiceException((string) $response->getErrorMessage());
            }

            return ArrayHelper::wrap($response->getBody());
        } catch (EmailsServiceException $exception) {
            throw $exception;
        } catch (Exception $exception) {
            throw new EmailsServiceException($exception->getMessage());
        }
    }

    /**
     * Check if the returned email sender exists and is valid.
     *
     * @param array $responseData The valid non-error response data
     * @return bool
     */
    protected static function isExistingEmailSender(array $responseData) : bool
    {
        return (bool) static::getEmailSenderData($responseData);
    }

    /**
     * Gets the email sender data from the response data.
     *
     * @param array $responseData The valid non-error response data
     * @return array
     */
    protected static function getEmailSenderData(array $responseData) : array
    {
        return ArrayHelper::wrap(ArrayHelper::get($responseData, 'data.emailSender'));
    }

    /**
     * Creates an email sender.
     *
     * @param string $emailAddress email address for the new sender
     * @return array email sender data
     * @throws EmailsServiceAuthProviderException
     * @throws EmailsServiceException
     */
    public static function create(string $emailAddress) : array
    {
        $responseData = static::sendRequest((new CreateEmailSenderMutation())->setVariables([
            'emailAddress'                   => urldecode($emailAddress),
            'siteId'                         => ManagedWooCommerceRepository::getXid(),
            'mailboxVerificationRedirectUrl' => static::getMailboxVerificationRedirectUrl(),
        ]));

        if (! $emailSender = ArrayHelper::wrap(ArrayHelper::get($responseData, 'data.createEmailSender'))) {
            throw new EmailsServiceException(__('Unable to retrieve the email sender data.', 'mwc-core'));
        }

        return $emailSender;
    }

    /**
     * Gets the mailbox verification redirect URL.
     *
     * @return string
     */
    public static function getMailboxVerificationRedirectUrl() : string
    {
        return add_query_arg([
            'tab'      => 'settings',
            'verified' => 'true',
        ], admin_url('admin.php?page='.EmailsPage::SLUG));
    }
}
