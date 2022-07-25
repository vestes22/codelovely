<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Frontend;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Enqueue\Enqueue;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Common\Models\User;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Payments\DataStores\WooCommerce\PaymentMethodDataStore;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\CorePaymentGateways;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Frontend\Views\MyPaymentMethods\CardPaymentMethodView;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Frontend\Views\MyPaymentMethods\PaymentMethodView;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\AbstractPaymentMethod;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\CardPaymentMethod;
use WC_Payment_Token;
use WC_Payment_Tokens;

/**
 * The payment methods class.
 *
 * Used to integrate with the My Account -> Payment Methods page.
 */
class MyPaymentMethods
{
    /** @var string action used to save a payment method from the Account -> Payment Methods page */
    const SAVE_PAYMENT_METHOD_ACTION = 'mwc_save_payment_method';

    /** @var AbstractPaymentMethod[] customer payment methods */
    protected $paymentMethods;

    /**
     * Constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->addHooks();
    }

    /**
     * Adds the action and filter hooks.
     *
     * @throws Exception
     */
    protected function addHooks()
    {
        Register::action()
            ->setGroup('wp')
            ->setHandler([$this, 'init'])
            ->execute();

        Register::action()
            ->setGroup('wp_ajax_wc_'.static::SAVE_PAYMENT_METHOD_ACTION)
            ->setHandler([$this, 'handleSavePaymentMethodRequest'])
            ->execute();
    }

    /**
     * Initializes the My Payment Methods table.
     *
     * @since 5.1.0
     * @throws Exception
     */
    public function init()
    {
        if (! $this->isPaymentMethodsPage()) {
            return;
        }

        Register::action()
            ->setGroup('wp_enqueue_scripts')
            ->setHandler([$this, 'enqueueAssets'])
            ->execute();

        Register::filter()
            ->setGroup('woocommerce_payment_methods_list_item')
            ->setHandler([$this, 'addPaymentMethodsListItemId'])
            ->setArgumentsCount(2)
            ->execute();

        Register::filter()
            ->setGroup('woocommerce_payment_methods_list_item')
            ->setHandler([$this, 'addPaymentMethodsListItemEditAction'])
            ->setArgumentsCount(2)
            ->execute();

        Register::action()
            ->setGroup('woocommerce_account_payment_methods_columns')
            ->setHandler([$this, 'addPaymentMethodsColumns'])
            ->execute();

        Register::action()
            ->setGroup('woocommerce_account_payment_methods_column_title')
            ->setHandler([$this, 'addPaymentMethodTitle'])
            ->execute();

        Register::action()
            ->setGroup('woocommerce_account_payment_methods_column_details')
            ->setHandler([$this, 'addPaymentMethodDetails'])
            ->execute();

        Register::action()
            ->setGroup('woocommerce_account_payment_methods_column_default')
            ->setHandler([$this, 'addPaymentMethodDefault'])
            ->execute();

        wc_enqueue_js($this->getSafeHandlerJs());
    }

    /**
     * Determines whether we're viewing the My Account -> Payment Methods page.
     *
     * TODO: move this method to WooCommerceRepository or similar {@wvega 2021-05-31}
     *
     * @return bool
     */
    protected function isPaymentMethodsPage() : bool
    {
        return User::getCurrent() && $this->isAccountPage() && $this->hasQueryVar('payment-methods');
    }

    /**
     * Determines whether we're viewing the My Account page.
     *
     * TODO: move this method to WooCommerceRepository or similar {@wvega 2021-05-31}
     *
     * @return bool
     */
    protected function isAccountPage() : bool
    {
        return (bool) is_account_page();
    }

    /**
     * Determines whether the given query var is set.
     *
     * TODO: move this method to WordPressRepository or similar {@wvega 2021-06-01}
     *
     * @param string $name
     * @return bool
     */
    protected function hasQueryVar(string $name) : bool
    {
        if (! $wp = ArrayHelper::get($GLOBALS, 'wp')) {
            return false;
        }

        if (! isset($wp->query_vars)) {
            return false;
        }

        return ArrayHelper::get($wp->query_vars, $name) !== null;
    }

    /**
     * Gets the handler instantiation JS wrapped in a safe load technique.
     *
     * @return string
     */
    protected function getSafeHandlerJs() : string
    {
        $handlerName = $this->getJsHandlerClassName();
        $loadFunctionName = "load{$handlerName}";

        ob_start(); ?>

        function <?php echo esc_js($loadFunctionName) ?>() {
        <?php echo $this->getHandlerJs(); ?>
        }

        try {
        if ( 'undefined' !== typeof <?php echo esc_js($handlerName); ?> ) {
        <?php echo esc_js($loadFunctionName); ?>();
        } else {
        window.jQuery( document.body ).on( '<?php echo esc_js($this->getJsLoadedEventName()); ?>', <?php echo esc_js($loadFunctionName); ?> );
        }
        } catch ( err ) {
        <?php // TODO: add debug log request support {@wvega 2021-06-01}
        ?>
        }
        <?php

        return ob_get_clean();
    }

    /**
     * Gets the handler instantiation JS.
     *
     * @return string
     */
    protected function getHandlerJs() : string
    {
        return sprintf(
            'window.%1$s = new %2$s(%3$s);',
            esc_js($this->getJsHandlerObjectName()),
            esc_js($this->getJsHandlerClassName()),
            ArrayHelper::jsonEncode($this->getJsHandlerArgs())
        );
    }

    /**
     * Gets the name of the JS variable that should hold an instance of the handler.
     *
     * @return string
     */
    protected function getJsHandlerObjectName() : string
    {
        return 'mwc_payments_my_payment_methods_handler';
    }

    /**
     * Gets the name of the JS class for the handler.
     *
     * @return string
     */
    protected function getJsHandlerClassName() : string
    {
        return 'MWCPaymentsMyPaymentsMethodsHandler';
    }

    /**
     * Gets the name of the JS event triggered when the handler is loaded.
     *
     * @return string
     */
    protected function getJsLoadedEventName() : string
    {
        return 'mwc_payments_my_payment_methods_handler_loaded';
    }

    /**
     * Gets the JS args for the handler.
     *
     * @return array
     */
    protected function getJsHandlerArgs() : array
    {
        return [
            'ajaxUrl'                 => admin_url('admin-ajax.php'),
            'savePaymentMethodAction' => 'wc_'.static::SAVE_PAYMENT_METHOD_ACTION,
            'savePaymentMethodNonce'  => $this->getSavePaymentMethodNonce(),
            'i18n'                    => [
                'editButtonLabel'        => esc_html__('Edit', 'mwc-core'),
                'cancelButtonLabel'      => esc_html__('Cancel', 'mwc-core'),
                'savePaymentMethodError' => esc_html__('Oops, there was an error updating your payment method. Please try again.', 'mwc-core'),
                'deleteAys'              => esc_html__('Are you sure you want to delete this payment method?', 'mwc-core'),
            ],
        ];
    }

    /**
     * Enqueues the assets for the My Account -> Payment Methods integration.
     *
     * @throws Exception
     */
    public function enqueueAssets()
    {
        Enqueue::script()
            ->setHandle('jquery-tiptip')
            ->setSource($this->getWooComerceAssetsUrl('js/jquery-tiptip/jquery.tipTip.min.js'))
            ->setDependencies(['jquery'])
            ->setVersion(Configuration::get('woocommerce.version'))
            ->setDeferred(true)
            ->execute();

        Enqueue::script()
            ->setHandle('mwc-payments-my-payment-methods')
            ->setSource(WordPressRepository::getAssetsUrl('js/payments/frontend/my-payment-methods.js'))
            ->setDependencies(['jquery-tiptip', 'jquery'])
            ->setVersion(Configuration::get('mwc.version'))
            ->execute();

        Enqueue::style()
            ->setHandle('mwc-payments-my-payments-methods')
            ->setSource(WordPressRepository::getAssetsUrl('css/my-payment-methods.css'))
            ->setDependencies(['dashicons'])
            ->setVersion(Configuration::get('mwc.version'))
            ->execute();
    }

    /**
     * Gets the WooCommerce asset's URL.
     *
     * TODO: move this to the WooCommerceRepository class {@wvega 2021-06-01}
     *
     * @param string $path
     * @return string
     */
    protected function getWooComerceAssetsUrl(string $path = '') : string
    {
        $url = StringHelper::trailingSlash(WC()->plugin_url());

        return "{$url}assets/{$path}";
    }

    /**
     * Gets the nonce for the Save Payment Method action.
     *
     * @return string
     */
    protected function getSavePaymentMethodNonce() : string
    {
        return wp_create_nonce(static::SAVE_PAYMENT_METHOD_ACTION);
    }

    /**
     * Adds the token ID to the token data array.
     *
     * @see wc_get_account_saved_payment_methods_list
     *
     * @internal
     *
     * @param array $item individual list item from woocommerce_saved_payment_methods_list
     * @param WC_Payment_Token $token payment token associated with this method entry
     *
     * @return array
     */
    public function addPaymentMethodsListItemId($item, $token)
    {
        $item['id'] = $token->get_id();

        return $item;
    }

    /**
     * Adds the Edit and Save buttons to the Actions column.
     *
     * @see wc_get_account_saved_payment_methods_list
     *
     * @internal
     *
     * @param array $item individual list item from woocommerce_saved_payment_methods_list
     * @param \WC_Payment_Token $token payment token associated with this method entry
     *
     * @return array
     */
    public function addPaymentMethodsListItemEditAction($item, $token)
    {
        if ($this->getPaymentMethodById($token->get_id())) {
            $item['actions'] = array_merge([
                'edit' => [
                    'url'  => '#',
                    'name' => esc_html__('Edit', 'mwc-core'),
                ],
                'save' => [
                    'url'  => '#',
                    'name' => esc_html__('Save', 'mwc-core'),
                ],
            ], $item['actions']);
        }

        return $item;
    }

    /**
     * Adds columns to the payment methods table.
     *
     * @internal
     *
     * @param array of table columns in key => Title format
     *
     * @return array
     * @throws Exception
     */
    public function addPaymentMethodsColumns($columns = [])
    {
        $columns = ArrayHelper::insertAfter($columns, ['title' => __('Title', 'mwc-core')], 'method');
        $columns = ArrayHelper::insertAfter($columns, ['details' => __('Details', 'mwc-core')], 'title');

        return ArrayHelper::insertAfter($columns, ['default' => __('Default?', 'mwc-core')], 'expires');
    }

    /**
     * Adds the Title column content.
     *
     * @internal
     *
     * @param array $method payment method
     */
    public function addPaymentMethodTitle($method)
    {
        if ($paymentMethod = $this->getPaymentMethodFromMethodDataArray($method)) {
            echo $this->getPaymentMethodTitleHtml($paymentMethod);
        }
    }

    /**
     * Get a HTML for the payment method's title.
     *
     * @param AbstractPaymentMethod $paymentMethod payment method object
     *
     * @return string
     */
    protected function getPaymentMethodTitleHtml(AbstractPaymentMethod $paymentMethod) : string
    {
        if (! $view = $this->getPaymentMethodView($paymentMethod)) {
            return '';
        }

        return $view->getTitleHtml();
    }

    /**
     * Gets a payment method view for the given payment method.
     *
     * @param AbstractPaymentMethod $paymentMethod
     *
     * @return PaymentMethodView|null
     */
    protected function getPaymentMethodView(AbstractPaymentMethod $paymentMethod)
    {
        if ($paymentMethod instanceof CardPaymentMethod) {
            return new CardPaymentMethodView($paymentMethod);
        }

        return null;
    }

    /**
     * Adds the Details column content.
     *
     * @internal
     *
     * @param array $method payment method
     */
    public function addPaymentMethodDetails($method)
    {
        if ($paymentMethod = $this->getPaymentMethodFromMethodDataArray($method)) {
            echo $this->getPaymentMethodDetailsHtml($paymentMethod);
        }
    }

    /**
     * Gets the HTML for the payment method's details.
     *
     * This includes the method type icon and the last four digits. Example:
     *
     * [icon] * * * 1234
     *
     * @param AbstractPaymentMethod $paymentMethod payment method object
     *
     * @return string
     */
    protected function getPaymentMethodDetailsHtml(AbstractPaymentMethod $paymentMethod) : string
    {
        if (! $view = $this->getPaymentMethodView($paymentMethod)) {
            return '';
        }

        return $view->getDetailsHtml();
    }

    /**
     * Adds the Default column content.
     *
     * @internal
     *
     * @param array $method payment method
     */
    public function addPaymentMethodDefault($method)
    {
        echo $this->getPaymentMethodDefaultHtml(! empty(ArrayHelper::get($method, 'is_default')));
    }

    /**
     * Gets the HTML for the payment method "default" flag.
     *
     * @param bool $isDefault true if the payment methods is the default
     *
     * @return string
     */
    protected function getPaymentMethodDefaultHtml(bool $isDefault = false) : string
    {
        return $isDefault ? '<mark class="default">'.esc_html__('Default', 'mwc-core').'</mark>' : '';
    }

    /**
     * Gets a token object from a payment method data array.
     *
     * @param array $method payment method data array
     *
     * @return AbstractPaymentMethod|null
     */
    protected function getPaymentMethodFromMethodDataArray($method)
    {
        if (! $id = ArrayHelper::get($method, 'id')) {
            return null;
        }

        return $this->getPaymentMethodById($id);
    }

    /**
     * @return AbstractPaymentMethod|null
     */
    protected function getPaymentMethodById(int $id)
    {
        return ArrayHelper::get($this->getPaymentMethods(), $id);
    }

    /**
     * Gets payment method objects for the WooCommerce tokens of the current user.
     *
     * @return AbstractPaymentMethod[]
     */
    protected function getPaymentMethods() : array
    {
        if (is_null($this->paymentMethods)) {
            $this->paymentMethods = array_map(function (WC_Payment_Token $token) {
                return (new PaymentMethodDataStore($token->get_gateway_id()))->read($token->get_id());
            }, $this->getWooCommercePaymentTokens());
        }

        return $this->paymentMethods;
    }

    /**
     * Gets the available tokens for the current user for each mwc-core gateway.
     *
     * @return WC_Payment_Token[]
     */
    protected function getWooCommercePaymentTokens() : array
    {
        $gateways = CorePaymentGateways::getPaymentGateways();

        return ArrayHelper::where($this->getCurrentUserWooCommercePaymentTokens(), function (WC_Payment_Token $token) use ($gateways) {
            return ArrayHelper::exists($gateways, $token->get_gateway_id());
        });
    }

    /**
     * Gets the available tokens for the current user.
     *
     * @return WC_Payment_Token[]
     */
    protected function getCurrentUserWooCommercePaymentTokens() : array
    {
        if (! $user = User::getCurrent()) {
            return [];
        }

        return WC_Payment_Tokens::get_tokens(['user_id' => $user->getId()]);
    }

    /**
     * Saves a payment method via AJAX.
     *
     * @internal
     */
    public function handleSavePaymentMethodRequest()
    {
        check_ajax_referer(static::SAVE_PAYMENT_METHOD_ACTION, 'nonce');

        try {
            $tokenId = StringHelper::sanitize(ArrayHelper::get($_POST, 'tokenId'));

            if (! $paymentMethod = $this->getPaymentMethodById($tokenId)) {
                throw new Exception(__('Invalid token ID', 'mwc-core'));
            }

            $this->setPaymentMethodData($paymentMethod, $this->getPostedData());
            $this->savePaymentMethod($paymentMethod);

            (new Response())->body([
                'success' => true,
                'data'    => [
                    'title' => $this->getPaymentMethodTitleHtml($paymentMethod),
                    'nonce' => $this->getSavePaymentMethodNonce(),
                ],
            ])->send();
        } catch (Exception $e) {
            (new Response())->body(['success' => false, 'data' => $e->getMessage()])->send();
        }
    }

    /**
     * Gets data posted for the Save Payment Method request.
     *
     * @return [];
     */
    protected function getPostedData() : array
    {
        $data = [];

        parse_str(ArrayHelper::get($_POST, 'data', ''), $data);

        return $data;
    }

    /**
     * Sets values for the payment method properties using the given data.
     *
     * @param AbstractPaymentMethod $paymentMethod payment method object
     * @param array $data request data
     */
    protected function setPaymentMethodData(AbstractPaymentMethod $paymentMethod, array $data)
    {
        $rawNickname = ArrayHelper::get($data, 'nickname');
        $sanitizedNickname = StringHelper::sanitize($rawNickname);

        if ($sanitizedNickname || ! $rawNickname) {
            $paymentMethod->setLabel($sanitizedNickname);
        }
    }

    /**
     * Uses a data store to save the given payment method objects.
     *
     * @param AbstractPaymentMethod $paymentMethod
     * @throws BaseException
     */
    protected function savePaymentMethod(AbstractPaymentMethod $paymentMethod)
    {
        $this->getPaymentMethodDataStore($paymentMethod->getProviderName())->save($paymentMethod);
    }

    /**
     * Gets data store for given provider.
     *
     * @param string $providerName
     * @return PaymentMethodDataStore
     */
    protected function getPaymentMethodDataStore(string $providerName) : PaymentMethodDataStore
    {
        return new PaymentMethodDataStore($providerName);
    }
}
