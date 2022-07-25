<?php
/**
 * Order Shipments information shown in plain-text emails.
 *
 * This template can be overridden by copying it to yourtheme/mwc/emails/plain/order/order-shipments.php.
 *
 * HOWEVER, on occasion we will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @since 1.0.0
 * @version 2.10.0
 */
defined('ABSPATH') || exit;

/*
 * @var array $columns
 * @var array $packages
 */

echo "\n----------------------------------------\n\n";
echo strtoupper(esc_html__('Shipments', 'mwc-core'));
echo "\n\n";

foreach ($packages as $package):
    /* translators: Placeholders: %1$s - Shipping carrier, %2$s - Shipping provider label */
    printf(__('%1$s: %2$s', 'mwc-core'), $columns['carrier'], $package['providerLabel']."\n");
    /* translators: Placeholders: %1$s - Tracking number column name, %2$s - Tracking number */
    printf(__('%1$s: %2$s', 'mwc-core'), $columns['tracking-number'], $package['trackingNumber']."\n");

    if (! empty($package['trackingUrl'])):
        /* translators: Placeholder: %s - tracking URL */
        printf(__('Tracking URL: %s', 'mwc-core'), $package['trackingUrl']."\n");
    endif;

    if (! empty($columns['items'])):
        /* translators: Placeholder: %s - items column */
        printf(__('%s: ', 'mwc-core'), $columns['items']);

        foreach ($package['items'] as $item):
            /* translators: Placeholders: %1$s - item name, %2$s - item quantity  */
            printf(__('%1$s x %2$s', 'mwc-core'), $item['name'], $item['quantity']."\n");
        endforeach;
    endif;

    echo "\n";
endforeach;
