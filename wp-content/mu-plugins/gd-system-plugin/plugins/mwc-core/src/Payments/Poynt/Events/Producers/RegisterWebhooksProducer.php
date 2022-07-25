<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events\Producers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\ProducerContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\DeprecationHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\SiteRepository;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\RegisterWebhooksRequest;
use GoDaddy\WordPress\MWC\Core\Sync\Jobs\PushSyncJob;

/**
 * Action Scheduler job to register Poynt Order / Transaction webhooks.
 */
class RegisterWebhooksProducer implements ProducerContract
{
    /* @var string */
    const WEBHOOK_PATH = 'wc-api/poynt';

    /**
     * Sets up the events' producer.
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
        Register::action()
            ->setGroup('mwc_push_register_poynt_webhooks_objects')
            ->setHandler([$this, 'registerWebhooks'])
            ->setArgumentsCount(2)
            ->execute();
    }

    /**
     * Handles the job to register hooks.
     *
     * @param int $jobId
     * @param array $topics
     * @throws Exception
     */
    public function registerWebhooks(int $jobId, array $topics)
    {
        $job = PushSyncJob::get($jobId);

        if (! $job || 'webhooks' !== $job->getObjectType()) {
            return;
        }

        $response = $this->sendRequest($topics);

        $status = 'complete';
        $responseStatus = $response->getStatus();

        if ($response->isError() || $responseStatus !== 201) {
            $job->setErrors(ArrayHelper::wrap("Could not register webhooks ({$responseStatus}): {$response->getErrorMessage()}"));
            $status = 'failed';
        } else {
            update_option('mwc_payments_poynt_onboarding_webhooksRegistered', 'yes');
        }

        $job->update([
            'status' => $status,
        ]);
    }

    /**
     * Sends the register webhooks request.
     *
     * @param array $topics
     * @return Response
     * @throws Exception
     */
    protected function sendRequest(array $topics) : Response
    {
        return (new RegisterWebhooksRequest())
            ->body($this->buildRegisterWebhookBody($topics))
            ->send();
    }

    /**
     * Builds the register webhooks body.
     *
     * @param array topics to register
     * @return array request body to register a webhook
     * @throws Exception
     */
    protected function buildRegisterWebhookBody(array $topics) : array
    {
        return [
            'businessId'    => Poynt::getBusinessId(),
            'applicationId' => Poynt::getApplicationId(),
            'eventTypes'    => $topics,
            'secret'        => Poynt::getWebhookSecret(),
            'deliveryUrl'   => StringHelper::trailingSlash(SiteRepository::getSiteUrl()).static::WEBHOOK_PATH,
        ];
    }
}
