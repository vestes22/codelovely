<?php

return [
    'enabled' => 'yes' === get_option('mwc_email_notifications_enabled', 'yes'),
    'allow_for_resellers' => defined('DISABLE_ACCOUNT_RESTRICTION_FOR_MWC_FEATURES') && DISABLE_ACCOUNT_RESTRICTION_FOR_MWC_FEATURES,

    /*
     * A list of plugins that are in conflict with the email deliverability feature.
     *
     * We will not attempt to send emails using our emails service if one of these plugins is active.
     */
    'conflicts' => [
        'plugins' => [
            'easy-wp-smtp/easy-wp-smtp.php',
            'postman-smtp/postman-smtp.php',
            'post-smtp/postman-smtp.php',
            'wp-mail-bank/wp-mail-bank.php',
            'smtp-mailer/main.php',
            'gmail-smtp/main.php',
            'wp-email-smtp/wp-email-smtp.php',
            'smtp-mail/index.php',
            'bws-smtp/bws-smtp.php',
            'wp-sendgrid-smtp/wp-sendgrid-smtp.php',
            'sar-friendly-smtp/sar-friendly-smtp.php',
            'wp-gmail-smtp/wp-gmail-smtp.php',
            'cimy-swift-smtp/cimy_swift_smtp.php',
            'wp-easy-smtp/wp-easy-smtp.php',
            'wp-mailgun-smtp/wp-mailgun-smtp.php',
            'my-smtp-wp/my-smtp-wp.php',
            'wp-mail-booster/wp-mail-booster.php',
            'sendgrid-email-delivery-simplified/wpsendgrid.php',
            'wp-mail-smtp-mailer/wp-mail-smtp-mailer.php',
            'wp-amazon-ses-smtp/wp-amazon-ses.php',
            'postmark-approved-wordpress-plugin/postmark.php',
            'mailgun/mailgun.php',
            'sparkpost/wordpress-sparkpost.php',
            'wp-yahoo-smtp/wp-yahoo-smtp.php',
            'wp-ses/wp-ses.php',
            'turbosmtp/turbo-smtp-plugin.php',
            'wp-smtp/wp-smtp.php',
            'woocommerce-sendinblue-newsletter-subscription/woocommerce-sendinblue.php',
            'disable-emails/disable-emails.php',
            'wp-mail-smtp/wp-mail-smtp.php',
        ],
    ],
];
