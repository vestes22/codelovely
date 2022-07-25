<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\API\Controllers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanFormatRequestSettingValuesTrait;
use GoDaddy\WordPress\MWC\Core\Email\Exceptions\EmailsServiceException;
use GoDaddy\WordPress\MWC\Core\Email\Http\EmailsServiceRequest;
use GoDaddy\WordPress\MWC\Core\Email\Http\GraphQL\Mutations\SendEmailSenderMailboxVerificationMutation;
use GoDaddy\WordPress\MWC\Core\Email\Models\EmailSender;
use GoDaddy\WordPress\MWC\Core\Email\Repositories\EmailSenderRepository;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\API\API;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Traits\CanGetEmailNotificationDataStoreTrait;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Traits\CanGetWooCommerceSettingsDataStoreTrait;
use GoDaddy\WordPress\MWC\Dashboard\API\Controllers\AbstractController;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST API controller for email notifications.
 */
class SendersController extends AbstractController implements ComponentContract
{
    use CanGetEmailNotificationDataStoreTrait;
    use CanGetWooCommerceSettingsDataStoreTrait;
    use CanFormatRequestSettingValuesTrait;
    /** @var string */
    protected $route = 'email-notifications/senders';

    /**
     * Initializes the controller.
     */
    public function load()
    {
        $this->registerRoutes();
    }

    /**
     * Registers the API routes for the endpoints provided by the controller.
     */
    public function registerRoutes()
    {
        $emailPattern = '(?P<email>[.\@\%a-zA-Z0-9_-]+)';

        register_rest_route($this->namespace, "/{$this->route}/{$emailPattern}", [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'getItem'],
                'permission_callback' => [$this, 'getItemPermissionsCheck'],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'createItem'],
                'permission_callback' => [$this, 'createItemPermissionsCheck'],
            ],
        ]);

        register_rest_route($this->namespace, "/{$this->route}/{$emailPattern}/send-verification", [
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'resendVerification'],
                'permission_callback' => [$this, 'resendVerificationPermissionsCheck'],
            ],
        ]);
    }

    /**
     * Sends the given request.
     *
     * @param AbstractGraphQLOperation $query
     * @return array|null|WP_Error
     * @throws Exception
     */
    protected function sendRequest(AbstractGraphQLOperation $query)
    {
        try {
            $response = (new EmailsServiceRequest())
                ->setOperation($query)
                ->send();

            if ($response->isError()) {
                throw new Exception($response->getErrorMessage(), (int) $response->getStatus());
            }

            return $response->getBody();
        } catch (Exception $exception) {
            $status = $exception->getCode() ?: 500;

            return $this->getWordPressError($status, $exception->getMessage(), [
                'status' => $status,
            ]);
        }
    }

    /**
     * Determines if the current user has permissions to issue requests to create items.
     *
     * @return bool
     */
    public function createItemPermissionsCheck() : bool
    {
        return API::hasAPIAccess();
    }

    /**
     * Determines if the current user has permissions to issue requests to get items.
     *
     * @return bool
     */
    public function getItemPermissionsCheck() : bool
    {
        return API::hasAPIAccess();
    }

    /**
     * Determines if the current user has permissions to issue requests to resend verifications.
     *
     * @return bool
     */
    public function resendVerificationPermissionsCheck() : bool
    {
        return API::hasAPIAccess();
    }

    /**
     * Handle create new email sender request.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function createItem(WP_REST_Request $request)
    {
        try {
            $emailSender = EmailSender::create($request->get_param('email'));
        } catch (EmailsServiceException $exception) {
            return $this->getWordPressError($exception->getCode(), $exception->getMessage());
        }

        return rest_ensure_response(['data' => ['emailSender' => $emailSender->toArray()]]);
    }

    /**
     * Gets an email notification.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function getItem(WP_REST_Request $request)
    {
        try {
            $emailSender = EmailSender::getOrFail($request->get_param('email'));
        } catch (EmailsServiceException $exception) {
            return $this->getWordPressError($exception->getCode(), $exception->getMessage());
        }

        return rest_ensure_response(['data' => ['emailSender' => $emailSender->toArray()]]);
    }

    /**
     * Gets WP Error instance with the given data.
     *
     * @param int|mixed $code
     * @param string $message
     * @param mixed $data
     * @return WP_Error
     */
    protected function getWordPressError($code, string $message, $data = '') : WP_Error
    {
        return new WP_Error($code, $message, $data);
    }

    /**
     * Sends request to resend email verification.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     * @throws Exception
     */
    public function resendVerification(WP_REST_Request $request)
    {
        $query = (new SendEmailSenderMailboxVerificationMutation())->setVariables([
            'emailAddress'                   => urldecode($request->get_param('email')),
            'siteId'                         => ManagedWooCommerceRepository::getXid(),
            'mailboxVerificationRedirectUrl' => EmailSenderRepository::getMailboxVerificationRedirectUrl(),
        ]);

        return rest_ensure_response($this->sendRequest($query));
    }

    /**
     * Gets the schema for REST email notification sender items provided by the controller.
     *
     * @return array
     */
    public function getItemSchema() : array
    {
        return [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'emailSender',
            'type'       => 'object',
            'properties' => [
                'id'           => [
                    'description' => __('Sender unique ID.', 'mwc-core'),
                    'type'        => 'integer',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'emailAddress' => [
                    'description' => __('Sender email address.', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'verifiedAt'   => [
                    'description' => __('Sender verified at.', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'verifiedBy'   => [
                    'description' => __('Sender verified by.', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'status'       => [
                    'description' => __('Sender status.', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
            ],
        ];
    }
}
