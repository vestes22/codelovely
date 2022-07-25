<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Producers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\ProducerContract;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\DeprecationHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Events\ButtonClickedEvent;
use GoDaddy\WordPress\MWC\Core\Exceptions\NonceVerificationFailedException;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events\AccountUpdatedEvent;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters\OnboardingStatusAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Onboarding;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\PaymentGatewayConnectedEvent;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\PaymentTransactionCreatedEvent;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\API\Controllers\Onboarding\OnboardingStartController;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\Onboarding\ReturnUrl;
use GoDaddy\WordPress\MWC\Payments\Events\ProviderRequestEvent;
use GoDaddy\WordPress\MWC\Payments\Events\ProviderResponseEvent;
use WC_Payment_Gateway;

/**
 * Class OnboardingEventsProducer.
 */
class OnboardingEventsProducer implements ProducerContract
{
    /** @var string action used to track when merchants click the Setup button on the payment method row */
    const ACTION_SETUP_INTENT = 'mwc_payments_godaddy_onboarding_setup_intent';

    /** @var string */
    const ACTION_START = 'mwc_payments_godaddy_onboarding_start';

    /** @var string */
    const ACTION_REDIRECT = 'mwc_payments_godaddy_onboarding_redirect';

    /** @var string */
    const ACTION_WEBHOOK = 'mwc_payments_godaddy_onboarding_webhook';

    /** @var string */
    const ACTION_UPDATE_ACCOUNT = 'mwc_payments_godaddy_onboarding_update_account';

    /** @var string action used to enable GoDaddy Payments gateway after the onboarding status changes to CONNECTED */
    const ACTION_ENABLE_PAYMENT_METHOD = 'mwc_payments_godaddy_onboarding_enable_payment_method';

    /** @var string action used to remove GoDaddy Payments gateway after the application is declined or terminated */
    const ACTION_REMOVE_PAYMENT_METHOD = 'mwc_payments_godaddy_onboarding_remove_payment_method';

    // TODO: update the name of the group when/if we change the ID of the gateway to godaddy_payments or something similar (@wvega 2021-05-27}
    /** @var string provider name for the GoDaddy Payments payment gateway */
    private $paymentsProviderName = 'poynt';

    /**
     * Gets the URL to kick off or resume onboarding.
     *
     * @param string $source source the action came from
     * @return string
     */
    public static function getOnboardingStartUrl(string $source) : string
    {
        $url = add_query_arg('action', static::ACTION_START, admin_url('admin.php'));

        if (! empty($source)) {
            $url = add_query_arg('source', $source, $url);
        }

        return $url;
    }

    /**
     * Gets the URL to enable the GoDaddy Payments method.
     *
     * @return string
     */
    public static function getEnablePaymentMethodUrl() : string
    {
        return wp_nonce_url(add_query_arg('action', static::ACTION_ENABLE_PAYMENT_METHOD, admin_url('admin.php')), static::ACTION_ENABLE_PAYMENT_METHOD);
    }

    /**
     * Sets up the events.
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
     * Broadcasts an event when the user clicks the Setup button on the payment method row.
     *
     * @internal
     */
    public function handleSetupIntent()
    {
        try {
            if (! wp_verify_nonce(ArrayHelper::get($_POST, 'setupIntentNonce'), self::ACTION_SETUP_INTENT)) {
                throw new NonceVerificationFailedException("Couldn't verify nonce to process GoDaddy Payments setup intent event");
            }

            Events::broadcast(new ButtonClickedEvent(
                StringHelper::sanitize(ArrayHelper::get($_POST, 'source', 'godaddy_payments_payment_method_setup_button'))
            ));

            (new Response())->body(['success' => true])->send();
        } catch (Exception $exception) {
            (new Response())->body(['success' => false])->send();
        }
    }

    /**
     * Handles the redirect back from GoDaddy Payments.
     */
    public function handleRedirects()
    {
        try {
            $this->handleRedirectRequest();

            // TODO: broadcast success event {@cwiseman 2021-05-20}
        } catch (Exception $exception) {
            // TODO: broadcast failure event {@cwiseman 2021-05-20}
        }

        $this->safeRedirect(ReturnUrl::get());
    }

    /**
     * Redirects to the given URL if it's a local URL.
     *
     * Calls exit if the redirect is successful.
     *
     * @param string $url
     */
    protected function safeRedirect(string $url)
    {
        if (wp_safe_redirect($url)) {
            exit;
        }
    }

    /**
     * Handles a redirect request.
     *
     * @throws Exception
     */
    protected function handleRedirectRequest()
    {
        if (! wp_verify_nonce(ArrayHelper::get($_GET, 'redirectNonce'), self::ACTION_REDIRECT)) {
            throw new SentryException('Invalid nonce');
        }

        // if the webhook with credential's hasn't bee delivered yet, set the status to pending
        if (! Poynt::getAppId() || ! Poynt::getPrivateKey()) {
            Onboarding::setStatus(Onboarding::STATUS_PENDING);
        }

        if ($applicationId = StringHelper::sanitize(ArrayHelper::get($_GET, 'applicationId', ''))) {
            Poynt::setApplicationId($applicationId);
        }

        if ($businessId = StringHelper::sanitize(ArrayHelper::get($_GET, 'businessId', ''))) {
            Poynt::setBusinessId($businessId);
        }
    }

    /**
     * Handles webhooks posted by the MWC API.
     */
    public function handleWebhooks()
    {
        try {
            $this->handleWebhookRequest();

            // TODO: broadcast a webhook received event {@cwiseman 2021-05-20}

            status_header(200);
        } catch (Exception $exception) {
            // TODO: broadcast failure event {@cwiseman 2021-05-20}

            status_header($exception->getCode());

            echo $exception->getMessage();
        }

        exit;
    }

    /**
     * Handles the webhook request data.
     *
     * @throws Exception
     */
    private function handleWebhookRequest()
    {
        if (empty($_SERVER['HTTP_MWC_WEBHOOK_SIGNATURE'])) {
            throw new Exception('Missing signature', 401);
        }

        $signature = $_SERVER['HTTP_MWC_WEBHOOK_SIGNATURE'];

        $payload = $this->getWebhookPayload();

        if (empty($payload)) {
            throw new Exception('Missing request body', 400);
        }

        $secret = Onboarding::getWebhookSecret();
        $hash = hash_hmac('sha512', $payload, $secret);

        if (! hash_equals($signature, $hash)) {
            throw new Exception('Invalid signature', 401);
        }

        $data = json_decode($payload, true);

        if ($this->hasValidAppCredentials($data)) {
            Poynt::setApplicationId(ArrayHelper::get($data, 'applicationId'));
            Poynt::setBusinessId(ArrayHelper::get($data, 'businessId'));
            Poynt::setAppId(ArrayHelper::get($data, 'appId', ''));
            Poynt::setPrivateKey(ArrayHelper::get($data, 'privateKey', ''));
            Poynt::setPublicKey(ArrayHelper::get($data, 'publicKey', ''));

            // unschedule the update account action to make sure the more-frequent action can be scheduled
            as_unschedule_action(static::ACTION_UPDATE_ACCOUNT);

            // trigger a request to get the latest account status now that we have credentials
            $this->updateAccount();
        }
    }

    /**
     * Gets the POSTed webhook payload.
     *
     * @return string
     */
    protected function getWebhookPayload() : string
    {
        return file_get_contents('php://input');
    }

    /**
     * Determines whether the given array includes app credentials for this site.
     *
     * @param array $data application data
     *
     * @return bool
     * @throws Exception;
     */
    protected function hasValidAppCredentials(array $data)
    {
        if (! $serviceType = ArrayHelper::get($data, 'serviceType')) {
            throw new Exception('Missing service type', 400);
        }

        if ($serviceType !== Configuration::get('payments.poynt.serviceType')) {
            throw new Exception("Service type doesn't match");
        }

        if (! $serviceId = ArrayHelper::get($data, 'serviceId')) {
            throw new Exception('Missing service ID', 400);
        }

        if ($serviceId !== Poynt::getServiceId()) {
            throw new Exception("Service ID doesn't match");
        }

        if (! $applicationId = ArrayHelper::get($data, 'applicationId')) {
            throw new Exception('Missing application ID', 400);
        }

        // we validate the application ID and business ID only if we already have a value stored
        // this allows the credentials webhook to fix an abandoned connection attempt
        if (Poynt::getApplicationId() && $applicationId !== Poynt::getApplicationId()) {
            throw new Exception("Application ID doesn't match", 409);
        }

        if (! $businessId = ArrayHelper::get($data, 'businessId')) {
            throw new Exception('Missing business ID', 400);
        }

        if (Poynt::getBusinessId() && $businessId !== Poynt::getBusinessId()) {
            throw new Exception("Business ID doesn't match");
        }

        if (! ArrayHelper::get($data, 'appId')) {
            throw new Exception('Missing App ID', 400);
        }

        if (! ArrayHelper::get($data, 'privateKey')) {
            throw new Exception('Missing private key', 400);
        }

        return true;
    }

    /**
     * Handles onboarding start.
     *
     * @internal
     */
    public function handleStart()
    {
        try {
            $this->verifyUserCanManageWooCommerce();

            $this->broadcastStartEvent(ArrayHelper::get($_REQUEST, 'source', ''));

            ReturnUrl::update(ArrayHelper::get($_REQUEST, 'return_url', ''));

            $data = (new OnboardingStartController())->updateItem()->get_data();

            $signupUrl = Onboarding::getSignupUrl($data['serviceId'], $data['redirectNonce']);

            if (wp_redirect($signupUrl)) {
                exit;
            }
        } catch (Exception $exception) {
            if (Configuration::get('wordpress.debug')) {
                error_log('There was an issue starting GD Payments onboarding: '.$exception->getMessage());
            }

            if (wp_safe_redirect(admin_url('admin.php?page=wc-settings&tab=checkout&onboardingError=true'))) {
                exit;
            }
        }
    }

    /**
     * Schedules an Action Scheduler event to update the account status.
     *
     * @internal
     *
     * @throws Exception
     */
    public function scheduleAccountUpdate()
    {
        $onboardingStatus = Onboarding::getStatus();

        // don't attempt to poll until the site has made some connection attempt
        if (! $onboardingStatus || $onboardingStatus === Onboarding::STATUS_DISCONNECTED) {
            return;
        }

        if (! as_next_scheduled_action(self::ACTION_UPDATE_ACCOUNT)) {

            // if not yet connected, poll every 5 minutes - otherwise every 12 hours
            $interval = Onboarding::STATUS_CONNECTED === $onboardingStatus ? HOUR_IN_SECONDS * 12 : MINUTE_IN_SECONDS;

            as_schedule_recurring_action(MINUTE_IN_SECONDS, $interval, self::ACTION_UPDATE_ACCOUNT);
        }
    }

    /**
     * Updates the account information with the latest API data.
     *
     * @internal
     *
     * @throws Exception
     */
    public function updateAccount()
    {
        // ensure we have the minimum requirements to be connected to the API
        if (
               ! Poynt::getApplicationId()
            || ! Poynt::getBusinessId()
            || ! Poynt::getAppId()
            || ! Poynt::getPrivateKey()
        ) {
            return;
        }

        $response = $this->requestAccount();

        if (200 !== $response->getStatus()) {
            return;
        }

        $data = (new OnboardingStatusAdapter())->convertToSource($response);

        if (empty($data)) {
            return;
        }

        $status = (string) ArrayHelper::get($data, 'status', '');
        $existingStatus = Onboarding::getStatus();

        $this->maybeBroadcastPaymentGatewayConnectedEvent($status, $existingStatus);

        Onboarding::setStatus($status);
        Onboarding::setHasBankAccount((bool) ArrayHelper::get($data, 'hasBankAccount', false));
        Onboarding::setDepositsEnabled((bool) ArrayHelper::get($data, 'depositsEnabled', false));
        Onboarding::setPaymentsEnabled((bool) ArrayHelper::get($data, 'paymentsEnabled', false));

        // if connected
        if ($status === Onboarding::STATUS_CONNECTED) {
            if (! Poynt::hasPoyntSmartTerminalActivated() || empty(Configuration::get('payments.poynt.storeId'))) {
                $devices = Poynt::getStoreDevices();

                // check for activated Poynt smart terminal devices
                Poynt::checkActivatedDevices($devices);
                // get store ID from devices and save it
                Poynt::setStoreId($devices);
            }
        }

        // if connected, unschedule the 1 minute action so that a less-frequent action can be scheduled
        if ($status === Onboarding::STATUS_CONNECTED) {
            as_unschedule_action(self::ACTION_UPDATE_ACCOUNT);
            $this->maybeAutoEnablePaymentGateway();
        }

        Events::broadcast(new AccountUpdatedEvent());
    }

    /**
     * Auto-Enables GoDaddy payment method if the onboarding process is completed.
     *
     * @since 2.10.0
     *
     * @throws Exception
     */
    protected function maybeAutoEnablePaymentGateway()
    {
        if (! Onboarding::shouldAutoEnablePaymentGateway()) {
            return;
        }

        if (! $gateway = $this->getWooCommerceGateway($this->paymentsProviderName)) {
            return;
        }

        $gateway->update_option('enabled', 'yes');
    }

    /**
     * Requests the account data.
     *
     * @return Response
     * @throws Exception
     */
    protected function requestAccount() : Response
    {
        $request = new Poynt\Http\AccountRequest();

        $request->query([
            'applicationId' => Poynt::getApplicationId(),
            'businessId'    => Poynt::getBusinessId(),
        ]);

        Events::broadcast(new ProviderRequestEvent($request));

        $response = $request->send();

        Events::broadcast(new ProviderResponseEvent($response));

        return $response;
    }

    /**
     * Handles the request to enable the GoDaddy Payments payment method and redirect to its settings page.
     */
    public function handleEnablePaymentMethod()
    {
        check_admin_referer(static::ACTION_ENABLE_PAYMENT_METHOD);

        try {
            $this->verifyUserCanManageWooCommerce();
            $this->maybeEnablePaymentMethod();
        } catch (Exception $exception) {
            // try to redirect to a settings page anyway
        }

        if (Onboarding::canManagePaymentGateway(Onboarding::getStatus())) {
            $redirect_url = admin_url("admin.php?page=wc-settings&tab=checkout&section={$this->paymentsProviderName}");
        } else {
            $redirect_url = admin_url('admin.php?page=wc-settings&tab=checkout');
        }

        if (wp_safe_redirect($redirect_url)) {
            exit;
        }
    }

    /**
     * Enables the GoDaddy Payments method if the current onboarding status allows for it.
     */
    protected function maybeEnablePaymentMethod()
    {
        if (! Onboarding::canEnablePaymentGateway(Onboarding::getStatus())) {
            return;
        }

        if (! $gateway = $this->getWooCommerceGateway($this->paymentsProviderName)) {
            return;
        }

        $gateway->update_option('enabled', 'yes');
    }

    /**
     * Checks whether the user can manage WooCommerce and throws an exception if they can't.
     *
     * @throws Exception
     */
    protected function verifyUserCanManageWooCommerce()
    {
        if (! current_user_can('manage_woocommerce')) {
            throw new Exception('User does not have permission to manage WooCommerce');
        }
    }

    /**
     * Handles the Ajax request to remove the GoDaddy Payments payment method.
     */
    public function handleRemovePaymentMethod()
    {
        try {
            if (! wp_verify_nonce(ArrayHelper::get($_POST, 'nonce'), self::ACTION_REMOVE_PAYMENT_METHOD)) {
                throw new NonceVerificationFailedException("Couldn't verify nonce to remove GoDaddy Payments");
            }

            $this->verifyUserCanManageWooCommerce();

            Configuration::set('payments.poynt.active', false);

            update_option('mwc_payments_poynt_active', 'no');

            (new Response())->body(['success' => true])->send();
        } catch (Exception $exception) {
            (new Response())->body(['success' => false])->send();
        }
    }

    /**
     * Broadcasts an event after the first GoDaddy Payments transaction is processed.
     */
    public function maybeBroadcastFirstPaymentTransactionEvent()
    {
        if (Configuration::get('woocommerce.flags.broadcastFirstGoDaddyPaymentsPaymentTransactionEvent') !== 'yes') {
            return;
        }

        if (! Configuration::get('payments.poynt.onboarding.hasFirstPayment')) {
            return;
        }

        Events::broadcast(new PaymentTransactionCreatedEvent($this->paymentsProviderName));

        Configuration::set('woocommerce.flags.broadcastFirstGoDaddyPaymentsPaymentTransactionEvent', 'no');

        update_option('gd_mwc_broadcast_first_godaddy_payments_payment_transaction_event', 'no');
    }

    /**
     * Broadcasts an event when the user starts or resumes the onboarding process.
     *
     * @param string $source where the user started or resumed the process
     *
     * @throws Exception
     */
    protected function broadcastStartEvent(string $source)
    {
        Events::broadcast(new ButtonClickedEvent("godaddy_payments_{$source}"));
    }

    /**
     * @return WC_Payment_Gateway|null
     */
    protected function getWooCommerceGateway(string $id)
    {
        if (! $woocommerce = WC()) {
            return null;
        }

        if (! $gateways = $woocommerce->payment_gateways()) {
            return null;
        }

        return ArrayHelper::get($gateways->payment_gateways(), $id);
    }

    /**
     * Broadcasts an event once the GDP gateway is successfully connected for the first time.
     *
     * @since 2.10.0
     *
     * @param string $newStatus
     * @param string $previousStatus
     * @throws Exception
     */
    protected function maybeBroadcastPaymentGatewayConnectedEvent(string $newStatus, string $previousStatus)
    {
        if ($newStatus === Onboarding::STATUS_CONNECTED && $previousStatus !== Onboarding::STATUS_CONNECTED) {
            $gateway = $this->getWooCommerceGateway($this->paymentsProviderName);

            Events::broadcast(new PaymentGatewayConnectedEvent($gateway->id));
        }
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function load()
    {
        Register::action()
            ->setGroup('wp_ajax_'.static::ACTION_SETUP_INTENT)
            ->setHandler([$this, 'handleSetupIntent'])
            ->execute();

        Register::action()
            ->setGroup('admin_action_'.self::ACTION_START)
            ->setHandler([$this, 'handleStart'])
            ->execute();

        Register::action()
            ->setGroup('woocommerce_api_'.self::ACTION_REDIRECT)
            ->setHandler([$this, 'handleRedirects'])
            ->execute();

        Register::action()
            ->setGroup('woocommerce_api_'.self::ACTION_WEBHOOK)
            ->setHandler([$this, 'handleWebhooks'])
            ->execute();

        Register::action()
            ->setGroup('admin_init')
            ->setHandler([$this, 'scheduleAccountUpdate'])
            ->execute();

        Register::action()
            ->setGroup(self::ACTION_UPDATE_ACCOUNT)
            ->setHandler([$this, 'updateAccount'])
            ->execute();

        Register::action()
            ->setGroup('admin_action_'.static::ACTION_ENABLE_PAYMENT_METHOD)
            ->setHandler([$this, 'handleEnablePaymentMethod'])
            ->execute();

        Register::action()
            ->setGroup('wp_ajax_'.static::ACTION_REMOVE_PAYMENT_METHOD)
            ->setHandler([$this, 'handleRemovePaymentMethod'])
            ->execute();

        Register::action()
            ->setGroup('shutdown')
            ->setHandler([$this, 'maybeBroadcastFirstPaymentTransactionEvent'])
            ->execute();
    }
}
