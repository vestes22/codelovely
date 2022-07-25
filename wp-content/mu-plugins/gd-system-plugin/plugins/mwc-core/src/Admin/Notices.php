<?php

namespace GoDaddy\WordPress\MWC\Core\Admin;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ConditionalComponentContract;
use GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsTrait;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Common\Models\User;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Traits\Features\IsConditionalFeatureTrait;

/**
 * Admin Dashboard notices handler class.
 */
class Notices implements ConditionalComponentContract
{
    use IsConditionalFeatureTrait;
    use HasComponentsTrait;

    /** @var string action used to dismiss a notice */
    const ACTION_DISMISS_NOTICE = 'mwc_dismiss_notice';

    /** @var string */
    const DISMISS_META_KEY_NAME = '_mwc_dismissed_notices';

    /**
     * Constructor.
     *
     * TODO: remove this method when {@see Pacakge} is converted to use {@see HasComponentsTrait} {nmolham 2021-10-08}
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->load();
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function load()
    {
        $this->registerHooks();
    }

    /**
     * Register actions and filters hooks.
     *
     * @throws Exception
     */
    protected function registerHooks()
    {
        Register::action()
            ->setGroup('wp_ajax_'.static::ACTION_DISMISS_NOTICE)
            ->setHandler([$this, 'handleDismissNoticeRequest'])
            ->execute();
    }

    /**
     * Handles the dismiss notice Ajax request.
     *
     * @internal
     */
    public function handleDismissNoticeRequest()
    {
        $user = User::getCurrent();

        // gets the sanitized message ID from the $_REQUEST array
        $messageId = StringHelper::sanitize((string) ArrayHelper::get(ArrayHelper::wrap($_REQUEST), 'messageId', ''));

        $shouldDismiss = $user && $messageId;
        if ($shouldDismiss) {
            static::dismissNotice($user, $messageId);
        }

        $this->sendResponse(['success' => $shouldDismiss]);
    }

    /**
     * Sends an AJAX response.
     */
    protected function sendResponse(array $parameters)
    {
        (new Response())->body($parameters)->send();
    }

    /**
     * Marks a notice as dismissed for the given user.
     *
     * @param User $user a user object
     * @param string $messageId an identifier for the notice
     */
    protected static function dismissNotice(User $user, string $messageId)
    {
        $dismissedNotices = static::getDismissedNotices($user);

        ArrayHelper::set($dismissedNotices, $messageId, true);

        static::updateDismissedNotices($user, $dismissedNotices);
    }

    /**
     * Gets an array of dismissed notices for the given user.
     *
     * The keys of the array are notice identifier and the value indicates whether
     * the notice is currently dismissed or not.
     *
     * @param User $user a user object
     *
     * @return array
     */
    protected static function getDismissedNotices(User $user) : array
    {
        return ArrayHelper::wrap(get_user_meta($user->getId(), static::DISMISS_META_KEY_NAME, true));
    }

    /**
     * Stores the array of dismissed notices for the given user.
     *
     * @param User $user a user object
     * @param array $dismissedNotices dismissed no tices for the user
     *
     * @return void
     */
    protected static function updateDismissedNotices(User $user, array $dismissedNotices)
    {
        update_user_meta($user->getId(), static::DISMISS_META_KEY_NAME, $dismissedNotices);
    }

    /**
     * Determines whether the given notice is dismissed for the given user.
     *
     * @param User $user a user object
     * @param string $messageId an identifier for the notice
     *
     * @return bool
     */
    public static function isNoticeDismissed(User $user, string $messageId) : bool
    {
        return ArrayHelper::get(static::getDismissedNotices($user), $messageId, false);
    }

    /**
     * Determines whether the component should be loaded or not.
     *
     * @return true
     */
    public static function shouldLoad() : bool
    {
        return is_admin();
    }

    /**
     * Determines whether the Email Notifications feature should load.
     *
     * TODO: remove this method when {@see Package} is converted to use {@see HasComponentsTrait} {nmolham 2021-10-08}
     *
     * @return bool
     */
    public static function shouldLoadConditionalFeature() : bool
    {
        return static::shouldLoad();
    }
}
