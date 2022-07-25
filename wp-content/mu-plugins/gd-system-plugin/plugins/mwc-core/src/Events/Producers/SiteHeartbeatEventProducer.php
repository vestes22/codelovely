<?php

namespace GoDaddy\WordPress\MWC\Core\Events\Producers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\ProducerContract;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Helpers\DeprecationHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Events\SiteHeartbeatEvent;

class SiteHeartbeatEventProducer implements ProducerContract
{
    /**
     * Sets up the Coupon events producer.
     *
     * @deprecated
     *
     * @throws Exception
     */
    public function setup()
    {
        DeprecationHelper::deprecatedFunction(__METHOD__, '2.18.1', __CLASS__.'::load');

        $this->load();
    }

    /**
     * Loads the component.
     *
     * @throws Exception
     */
    public function load()
    {
        if (! $this->shouldBroadcastSiteHeartbeatEvent()) {
            return;
        }

        Register::action()
            ->setGroup('init')
            ->setHandler([$this, 'broadcastSiteHeartbeatEvent'])
            ->setPriority(20)
            ->execute();
    }

    /**
     * Determines whether we should broadcast the site heart event.
     *
     * @return bool
     * @throws Exception
     */
    protected function shouldBroadcastSiteHeartbeatEvent() : bool
    {
        // broadcast the event for existing sites only: sites created before August 9, 2021
        if (((int) Configuration::get('godaddy.site.created')) >= 1628467200) {
            return false;
        }

        return Configuration::get('woocommerce.flags.broadcastSiteHeartbeatEvent');
    }

    /**
     * Broadcasts a Site Heartbeat event.
     *
     * @internal
     *
     * @since 2.11.0
     * @throws Exception
     */
    public function broadcastSiteHeartbeatEvent()
    {
        Events::broadcast(new SiteHeartbeatEvent());

        Configuration::set('woocommerce.flags.broadcastSiteHeartbeatEvent', false);

        update_option('mwc_site_heartbeat_event_sent_at', time());
    }
}
