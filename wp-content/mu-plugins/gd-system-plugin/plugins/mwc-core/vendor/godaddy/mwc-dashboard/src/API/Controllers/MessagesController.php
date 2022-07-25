<?php

namespace GoDaddy\WordPress\MWC\Dashboard\API\Controllers;

use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\ComparisonHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Models\User;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Dashboard\Exceptions\MessagesFailedFetchException;
use GoDaddy\WordPress\MWC\Dashboard\Http\MessagesRequest;
use GoDaddy\WordPress\MWC\Dashboard\Message\Message;
use GoDaddy\WordPress\MWC\Dashboard\Message\MessagesOptedIn;
use GoDaddy\WordPress\MWC\Dashboard\Message\MessageStatus;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * MessagesController controller class.
 */
class MessagesController extends AbstractController
{
    /**
     * Controller constructor.
     */
    public function __construct()
    {
        $this->route = 'messages';
    }

    /**
     * Registers the API routes for the endpoints provided by the controller.
     *
     * @since 1.0.0
     */
    public function registerRoutes()
    {
        register_rest_route($this->namespace, '/'.$this->route.'/opt-in', [
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'optIn'],
                'permission_callback' => [$this, 'createItemPermissionsCheck'],
            ],
        ]);

        register_rest_route($this->namespace, '/'.$this->route.'/opt-in', [
            [
                'methods'             => 'DELETE',
                'callback'            => [$this, 'optOut'],
                'permission_callback' => [$this, 'deleteItemPermissionsCheck'],
            ],
        ]);

        register_rest_route($this->namespace, "/{$this->route}", [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'getItems'],
                'permission_callback' => [$this, 'getItemsPermissionsCheck'],
            ],
        ]);

        register_rest_route($this->namespace, "/{$this->route}/bulk", [
            [
                'methods'             => 'POST', // WP_REST_Server::CREATABLE
                'callback'            => [$this, 'updateItems'],
                'permission_callback' => [$this, 'updateItemPermissionsCheck'],
            ],
            'args' => [
                'ids'    => [
                    'required'          => true,
                    'type'              => 'array',
                    'validate_callback' => 'rest_validate_request_arg',
                    'sanitize_callback' => 'rest_sanitize_request_arg',
                ],
                'status' => [
                    'required'          => true,
                    'type'              => 'string',
                    'enum'              => [MessageStatus::STATUS_UNREAD, MessageStatus::STATUS_READ, MessageStatus::STATUS_DELETED],
                    'validate_callback' => 'rest_validate_request_arg',
                    'sanitize_callback' => 'rest_sanitize_request_arg',
                ],
            ],
        ]);

        register_rest_route($this->namespace, "/{$this->route}/(?P<id>[a-zA-Z0-9-]+)", [
            [
                'methods'             => 'POST, PUT, PATCH', // WP_REST_Server::EDITABLE
                'callback'            => [$this, 'updateItem'],
                'permission_callback' => [$this, 'updateItemPermissionsCheck'],
            ],
            'args' => [
                'id'     => [
                    'required'          => true,
                    'type'              => 'string',
                    'validate_callback' => 'rest_validate_request_arg',
                    'sanitize_callback' => 'rest_sanitize_request_arg',
                ],
                'status' => [
                    'required'          => true,
                    'type'              => 'string',
                    'enum'              => [MessageStatus::STATUS_UNREAD, MessageStatus::STATUS_READ, MessageStatus::STATUS_DELETED],
                    'validate_callback' => 'rest_validate_request_arg',
                    'sanitize_callback' => 'rest_sanitize_request_arg',
                ],
            ],
        ]);

        register_rest_route($this->namespace, "/{$this->route}/(?P<id>[a-zA-Z0-9-]+)", [
            [
                'methods'             => 'DELETE',
                'callback'            => [$this, 'deleteItem'],
                'permission_callback' => [$this, 'deleteItemPermissionsCheck'],
            ],
            'args' => [
                'id' => [
                    'description'       => __('ID of the message to be deleted.', 'mwc-dashboard'),
                    'required'          => true,
                    'type'              => 'string',
                    'validate_callback' => 'rest_validate_request_arg',
                    'sanitize_callback' => 'rest_sanitize_request_arg',
                ],
            ],
        ]);
    }

    /**
     * Deletes a message.
     *
     * @param WP_REST_Request $request may have the message ID to be deleted
     *
     * @return WP_REST_Response|WP_Error
     */
    public function deleteItem(WP_REST_Request $request)
    {
        $messageId = StringHelper::sanitize($request->get_param('id'));

        try {
            $allMessages = $this->getAllMessages($request);
        } catch (MessagesFailedFetchException $ex) {
            return new WP_Error('mwc_dashboard_getting_messages_error', $ex->getMessage());
        }

        $message = $this->getMatchingMessageById($allMessages, $messageId);

        $this->updateMessage($message, MessageStatus::STATUS_DELETED);

        $response = rest_ensure_response([
            'id'     => $messageId,
            'status' => MessageStatus::STATUS_DELETED,
        ]);

        $response->set_status(204);

        return $response;
    }

    /**
     * Gets a list of messages.
     *
     * @since 1.0.0
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     * @throws Exception
     */
    public function getItems(WP_REST_Request $request)
    {
        try {
            $messages = $this->filterMessages($this->getAllMessages($request), $this->getQueryFilterParam($request));

            foreach ($messages as &$message) {
                $status = $message->status()->getStatus();
                $message = $message->toArray();
                $message['status'] = $status;
            }
        } catch (MessagesFailedFetchException $exception) {
            return new WP_Error('error_fetching_messages', $exception->getMessage());
        }

        return rest_ensure_response(['messages' => array_values($messages)]);
    }

    /**
     * Gets the matching message from the given array of messages and target message ID.
     *
     * @since 1.0.0
     *
     * @param Message[] $messages a list of messages
     * @param string $messageId target message ID
     *
     * @return Message|null
     */
    protected function getMatchingMessageById(array $messages, string $messageId)
    {
        $match = ArrayHelper::where($messages, static function (Message $message) use ($messageId) {
            return $messageId === $message->getId();
        }, false);

        if (count($match)) {
            return $match[0];
        }

        return null;
    }

    /**
     * Gets all of the available messages.
     *
     * These messages will be filtered on the frontend based on status and display rules.
     *
     * @param WP_REST_Request|null $request
     * @return Message[]
     * @throws MessagesFailedFetchException
     * @since 1.0.0
     */
    protected function getAllMessages(WP_REST_Request $request = null) : array
    {
        return array_map(function ($data) {
            return $this->buildMessage($data);
        }, $this->getMessagesData($request));
    }

    /**
     * Gets messages data from the remote JSON file.
     *
     * @param WP_REST_Request|null $request
     * @return array
     * @throws MessagesFailedFetchException
     * @throws Exception
     * @since 1.0.0
     */
    protected function getMessagesData(WP_REST_Request $request = null) : array
    {
        $response = (new MessagesRequest())
            // @NOTE: When adding as a query param the http_query_builder breaks the encoding {JO: 08-02-2021}
            ->url($this->getMessagesUrl().'?query='.StringHelper::encodeUriComponent($this->getMessagesQuery()))
            ->send();

        if ($response->isError() || 200 !== $response->getStatus()) {
            throw new  MessagesFailedFetchException(__('Could not retrieve remote messages data', 'mwc-dashboard'), $response->getStatus() ?? 404);
        }

        $messages = ArrayHelper::get($response->getBody(), 'data.messages.nodes');

        if (! ArrayHelper::accessible($messages)) {
            throw new MessagesFailedFetchException(__('Remote messages data is invalid', 'mwc-dashboard'), 500);
        }

        return $messages;
    }

    /**
     * Gets the GraphQL Query for the messages.
     *
     * @TODO Build a GraphQL Builder Class so we don't have these query strings lying around {JO: 08-02-2021}
     *
     * @since 1.0.0
     *
     * @return string
     * @throws Exception
     */
    protected function getMessagesQuery() : string
    {
        return 'query getMessages{messages(filter:{context:"MAIN"},limit:255){nodes{id subject body status createdBy createdAt updatedBy updatedAt contexts contextStatus publishedAt expiredAt actions{type href text extensionSlug successMessage}links{href rel href}rules{all{label name type rel comparator operator value}any{label name type rel comparator operator value}}type}}}';
    }

    /**
     * Gets the URL for the messages.
     *
     * @since 1.0.0
     *
     * @return string
     * @throws Exception
     */
    protected function getMessagesUrl() : string
    {
        return Configuration::get('messages.api.url');
    }

    /**
     * Builds a message instance using the given data.
     *
     * @since 1.0.0
     *
     * @param array $data message data
     *
     * @return Message
     * @throws Exception
     */
    protected function buildMessage($data) : Message
    {
        $links = ArrayHelper::get($data, 'links', []);

        if (ManagedWooCommerceRepository::hasEcommercePlan()) {
            $links = array_map(function ($link) {
                $link['href'] = str_replace('skyverge', 'godaddy/mwc', $link['href']);

                return $link;
            }, $links);
        }

        $messageData = [
            'id'            => ArrayHelper::get($data, 'id'),
            'type'          => ArrayHelper::get($data, 'type'),
            'subject'       => ArrayHelper::get($data, 'subject'),
            'body'          => ArrayHelper::get($data, 'body'),
            'publishedAt'   => $this->parseMessageDate(ArrayHelper::get($data, 'publishedAt', '')),
            'expiredAt'     => $this->parseMessageDate(ArrayHelper::get($data, 'expiredAt', '')),
            'actions'       => ArrayHelper::get($data, 'actions', []),
            'rules'         => ArrayHelper::get($data, 'rules', []),
            'links'         => $links,
            'contexts'      => ArrayHelper::wrap(ArrayHelper::get($data, 'contexts', ['global'])),
            'contextStatus' => ArrayHelper::get($data, 'contextStatus'),
        ];

        $messageData = ArrayHelper::where($messageData, static function ($value) {
            return ! is_null($value);
        });

        return $this->shouldNotExpireMessage(new Message($messageData));
    }

    /**
     * Sets the message to not expire if a recommendation and has no expiration date.
     *
     * @param Message $message
     * @return Message
     */
    protected function shouldNotExpireMessage(Message $message) : Message
    {
        if (! $message->getExpiredAt() && Message::TYPE_RECOMMENDATION === strtoupper($message->getType())) {
            $message->setDoNotExpire(true);
        }

        return $message;
    }

    /**
     * Attempts to convert a message date into a DateTime object.
     *
     * Returns null if the given date string is empty or an error occurs.
     *
     * @since 1.0.0
     *
     * @param string $date string representation of the date
     *
     * @return DateTime|null
     */
    protected function parseMessageDate(string $date)
    {
        if (empty($date)) {
            return null;
        }

        try {
            return new DateTime($date);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Updates a Message.
     *
     * @param WP_REST_Request $request full details about the request
     *
     * @since 1.0.0
     *
     * @return WP_REST_Response|WP_Error
     * @throws Exception
     */
    public function updateItem(WP_REST_Request $request)
    {
        $messageId = StringHelper::sanitize($request->get_param('id'));
        $status = StringHelper::sanitize($request->get_param('status'));

        try {
            $allMessages = $this->getAllMessages($request);
        } catch (MessagesFailedFetchException $ex) {
            return new WP_Error('mwc_dashboard_getting_messages_error', $ex->getMessage());
        }

        $message = $this->getMatchingMessageById($allMessages, $messageId);

        if (! $message) {
            return new WP_Error('mwc_dashboard_matching_message_error', __('Invalid message ID', 'mwc-dashboard'));
        }

        $this->updateMessage($message, $status);

        return rest_ensure_response($this->prepareItem($message));
    }

    /**
     * Updates a Message object with the given status.
     *
     * @since x.y.z
     */
    protected function updateMessage(Message $message, string $status)
    {
        $message->status()->setUserMeta($status)->saveUserMeta();

        $message->update();
    }

    /**
     * Prepares given message object for API response.
     *
     * @param Message $message
     *
     * @return array
     */
    protected function prepareItem(Message $message) : array
    {
        $publishedAt = $message->getPublishedAt();
        $expiredAt = $message->getExpiredAt();

        return [
            'id'          => $message->getId(),
            'subject'     => $message->getSubject(),
            'body'        => $message->getBody(),
            'publishedAt' => $publishedAt ? $publishedAt->format('Y-m-d H:i:s') : '',
            'expiredAt'   => $expiredAt ? $expiredAt->format('Y-m-d H:i:s') : '',
            'actions'     => $message->getActions(),
            'rules'       => $message->getRules(),
            'links'       => $message->getLinks(),
            'status'      => $message->status()->getUserMeta(),
        ];
    }

    /**
     * Updates given MessagesController IDs statuses.
     *
     * @internal
     *
     * @since 1.0.0
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response|WP_Error
     */
    public function updateItems(WP_REST_Request $request)
    {
        $messageIds = $request->get_param('ids');
        $status = StringHelper::sanitize($request->get_param('status'));

        try {
            $allMessages = $this->getAllMessages($request);
        } catch (MessagesFailedFetchException $ex) {
            return new WP_Error('mwc_dashboard_getting_messages_error', $ex->getMessage());
        }

        $messageIds = ArrayHelper::wrap($messageIds);

        foreach ($messageIds as $messageId) {
            $message = $this->getMatchingMessageById($allMessages, $messageId);
            $this->updateMessage($message, $status);
        }

        return rest_ensure_response([
            'ids'    => $messageIds,
            'status' => $status,
        ]);
    }

    /**
     * Triggers Dashboard messages optIn for current logged in user.
     *
     * @internal
     *
     * @since 1.0.0
     *
     * @return WP_REST_Response|WP_Error
     */
    public function optIn()
    {
        try {
            $messageOptIn = new MessagesOptedIn(User::getCurrent()->getId());
            $messageOptIn->optIn();
        } catch (Exception $ex) {
            return new WP_Error('mwc_dashboard_opt_in_error', $ex->getMessage());
        }

        return rest_ensure_response([
            'userId'  => $messageOptIn->getUserId(),
            'optedIn' => true,
        ]);
    }

    /**
     * Triggers Dashboard messages optOut for current logged in user.
     *
     * @internal
     *
     * @since 1.0.0
     *
     * @return WP_REST_Response|WP_Error
     */
    public function optOut()
    {
        try {
            $messageOptIn = new MessagesOptedIn(User::getCurrent()->getId());
            $messageOptIn->optOut();
        } catch (Exception $ex) {
            return new WP_Error('mwc_dashboard_opt_out_error', $ex->getMessage());
        }

        return rest_ensure_response([
            'userId'  => $messageOptIn->getUserId(),
            'optedIn' => false,
        ]);
    }

    /**
     * Gets the schema for REST items provided by the controller.
     *
     * @since 1.0.0
     *
     * @return array
     */
    public function getItemSchema() : array
    {
        return [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'message',
            'type'       => 'object',
            'properties' => [
                'id'          => [
                    'description' => __('Unique message ID.', 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'type'     => [
                    'description' => __('Message type.', 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'subject'     => [
                    'description' => __('Message subject.', 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'body'        => [
                    'description' => __('Message body.', 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'publishedAt' => [
                    'description' => __('Publish date.', 'mwc-dashboard'),
                    'type'        => 'string',
                    'format'      => 'date-time',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'expiredAt'   => [
                    'description' => __('Expiration date.', 'mwc-dashboard'),
                    'type'        => 'string',
                    'format'      => 'date-time',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'actions'     => [
                    'description' => __('Buttons or links to be displayed with the message.', 'mwc-dashboard'),
                    'type'        => 'array',
                    'items'       => [
                        'type'       => 'object',
                        'properties' => [
                            'text' => [
                                'description' => __('Action text.', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'href' => [
                                'description' => __('Action href.', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'type' => [
                                'description' => __('Action type.', 'mwc-dashboard'),
                                'type'        => 'string',
                                'enum'        => ['button', 'link'],
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                        ],
                    ],
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'rules'       => [
                    'description' => __('Rules to be evaluated by the client to decide if the message should be displayed or not.',
                        'mwc-dashboard'),
                    'type'        => 'array',
                    'items'       => [
                        'type'       => 'object',
                        'properties' => [
                            'label'      => [
                                'description' => __('Rule label.', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'name'       => [
                                'description' => __('Rule name.', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'type'       => [
                                'description' => __('Rule type.', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'rel'        => [
                                'description' => __('Related entity used to evaluate the rule.', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'comparator' => [
                                'description' => __('Element of the related entity used to evaluate the rule.',
                                    'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'operator'   => [
                                'description' => __('Comparison operator used to evaluate the rule.', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'value'      => [
                                'description' => __('Reference value used to evaluate the rule.', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                        ],
                    ],
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'links'       => [
                    'description' => __('Links with data to be retrieved and used to evaluate the rules.',
                        'mwc-dashboard'),
                    'type'        => 'array',
                    'items'       => [
                        'type'       => 'object',
                        'properties' => [
                            'href' => [
                                'description' => __('Link href.', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'rel'  => [
                                'description' => __('Related entity represented by the link.', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'type' => [
                                'description' => __('Request type to retrieve the data.', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                        ],
                    ],
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'status'      => [
                    'description' => __('Message status for the current user.', 'mwc-dashboard'),
                    'type'        => 'string',
                    'enum'        => ['read', 'unread', 'deleted'],
                    'context'     => ['view', 'edit'],
                    'readonly'    => false,
                ],
            ],
        ];
    }

    /**
     * Filters the given array of messages.
     *
     * @since 1.2.0
     *
     * @param Message[] $messages a list of messages
     * @param array $filters a list of filters
     *
     * @return Message[] filtered messages
     */
    protected function filterMessages(array $messages, array $filters = []) : array
    {
        $comparator = $this->getContextComparator($filters);

        $messages = ArrayHelper::where($messages, static function (Message $message) use ($comparator) {
            if ($comparator) {
                // TODO: add $comparator->all() and $comparator->any() methods that return true if the comparator returns true for all/any of the given values, respectively {WV 2021-04-09}
                $contextMatches = array_reduce($message->getContexts(), function ($result, $context) use ($comparator) {
                    return $result || $comparator->setValue($context)->compare();
                }, false);
            } else {
                $contextMatches = true;
            }

            return
                $contextMatches &&
                ! $message->isExpired() &&
                ! $message->status()->isDeleted();
        });

        return $messages;
    }

    /**
     * Extracts a comparator instance to serve as a filter for the message list.
     *
     * @since 1.2.0
     *
     * @param array $filters a list of filters
     *
     * @return ComparisonHelper|null the context comparator
     */
    protected function getContextComparator($filters = [])
    {
        foreach ($filters as $filter) {
            $context = (array) ArrayHelper::get($filter, 'context', []);
            $keys = array_keys($context);
            $operator = reset($keys);

            if ($context) {
                return ComparisonHelper::create()
                    ->setCaseSensitive(false)
                    ->setOperator($operator)
                    ->setWith($context[$operator]);
            }
        }

        return null;
    }

    /**
     * Gets the query filters param from the request as an associative array.
     *
     * @since 1.2.0
     *
     * @param WP_REST_Request $request
     * @throws Exception
     *
     * @return array
     */
    protected function getQueryFilterParam(WP_REST_Request $request) : array
    {
        $queryObject = json_decode(StringHelper::sanitize($request->get_param('query') ?? ''), true);

        return ArrayHelper::get($queryObject, 'filters', []);
    }
}
