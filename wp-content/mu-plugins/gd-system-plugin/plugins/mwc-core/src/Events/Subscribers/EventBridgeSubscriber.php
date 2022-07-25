<?php

namespace GoDaddy\WordPress\MWC\Core\Events\Subscribers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventBridgeEventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Common\Models\User;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\SiteRepository;
use GoDaddy\WordPress\MWC\Core\Exceptions\EventBridgeEventSendFailedException;
use GoDaddy\WordPress\MWC\Core\Http\EventBridgeRequest;

class EventBridgeSubscriber implements SubscriberContract
{
    /**
     * @param EventContract $event
     * @throws Exception
     */
    public function handle(EventContract $event)
    {
        if (! $this->shouldSendEvent($event)) {
            return;
        }

        try {
            $this->sendEvent($event);
        } catch (EventBridgeEventSendFailedException $e) {
            // If an EventBridgeEventSendFailedException exception is thrown, it
            // will automatically report itself to sentry when PHP destructs the
            // object, even if it’s caught in the try-catch above.
        }
    }

    /**
     * Determines whether the given event should be sent.
     *
     * @param EventContract $event event object
     *
     * @return bool
     * @throws Exception
     */
    protected function shouldSendEvent(EventContract $event) : bool
    {
        // don't send if this is not the production environment and the plugin is not configured to send local events
        if (! ManagedWooCommerceRepository::isProductionEnvironment() && ! Configuration::get('events.send_local_events')) {
            return false;
        }

        // only send events that are an EventBridgeEventContract
        return $event instanceof EventBridgeEventContract;
    }

    /**
     * Send the Event to the streamer.
     *
     * @param EventContract $event
     * @return Response
     * @throws Exception
     */
    protected function sendEvent(EventContract $event)
    {
        $response = (new EventBridgeRequest())
            ->url(Configuration::get('mwc.events.api.url'))
            ->setMethod('POST')
            ->headers([
                'Authorization' => Configuration::get('events.auth.type', 'Bearer').' '.Configuration::get('events.auth.token'),
            ])
            ->setSiteId(ManagedWooCommerceRepository::getSiteId())
            ->body([
                'query' => $this->getQuery($event, User::getCurrent()),
            ])
            ->send();

        // TODO: handle HTTP status code that indicate an error as well -- right now it fails only when a WP_Error is produced internally {WV 2021-03-30}
        if ($response->isError()) {
            throw new EventBridgeEventSendFailedException($response->getErrorMessage());
        }

        return $response;
    }

    /**
     * Gets the content for the query parameter.
     *
     * @param EventBridgeEventContract $event event object
     * @param User|null $user current user
     *
     * @return string
     * @throws Exception
     */
    private function getQuery(EventBridgeEventContract $event, User $user = null)
    {
        // @NOTE: Default to 0 or anonymous when user was null as the schema requires a userId {JO: 2021-09-09}
        $userId = $user ? $user->getId() : 0;
        $data = json_encode(json_encode($this->getEventData($event)));

        return <<<GQL
mutation {
  createEvent(input: { userId: {$userId}, resource: "{$event->getResource()}", action: "{$event->getAction()}", data: {$data} }) {
    statusCode
    message
  }
}
GQL;
    }

    /**
     * Gets the event data enhanced with data that we want to include with every event.
     *
     * @param EventBridgeEventContract $event event object
     *
     * @return array
     * @throws Exception
     */
    private function getEventData(EventBridgeEventContract $event) : array
    {
        $data = $event->getData();

        ArrayHelper::set($data, 'site.url', SiteRepository::getHomeUrl());
        ArrayHelper::set($data, 'site.id', Configuration::get('godaddy.site.id'));
        ArrayHelper::set($data, 'site.xid', ManagedWooCommerceRepository::getXid());
        ArrayHelper::set($data, 'site.uid', Configuration::get('godaddy.account.uid'));

        ArrayHelper::set($data, 'ip', static::getClientIp());

        return $data;
    }

    /**
     * Determines the user's actual IP address and attempts to partially
     * anonymize an IP address by converting it to a network ID.
     *
     * @see \WP_Community_Events::get_unsafe_client_ip()
     *
     * @return string|false
     */
    public static function getClientIp()
    {
        $clientIp = false;

        // in order of preference, with the best ones for this purpose first
        $addressHeaders = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ];

        foreach ($addressHeaders as $header) {
            if (ArrayHelper::has($_SERVER, $header)) {
                /*
                 * HTTP_X_FORWARDED_FOR can contain a chain of comma-separated
                 * addresses. The first one is the original client. It can't be
                 * trusted for authenticity, but we don't need to for this purpose.
                 */
                $addressChain = explode(',', $_SERVER[$header]);
                $clientIp = trim($addressChain[0]);

                break;
            }
        }

        if (! $clientIp) {
            return false;
        }

        $anonIp = wp_privacy_anonymize_ip($clientIp, true);

        if ('0.0.0.0' === $anonIp || '::' === $anonIp) {
            return false;
        }

        return $anonIp;
    }
}
