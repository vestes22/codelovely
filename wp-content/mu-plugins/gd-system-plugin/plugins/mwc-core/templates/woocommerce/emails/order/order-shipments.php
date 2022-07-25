<?php
/**
 * Order Shipments information shown in HTML emails.
 *
 * This template can be overridden by copying it to yourtheme/mwc/emails/order/order-shipments.php.
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
?>
<h2 class="woocommerce-order-shipments__title"><?php esc_html_e('Shipments', 'mwc-core'); ?></h2>

<div style="margin-bottom: 40px;">
    <table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
        <thead>
            <tr>
                <?php foreach ($columns as $columnId => $columnName): ?>
                    <th class="td" scope="col"><?php echo esc_html($columnName); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($packages as $package): ?>
                <tr>
                    <td class="td" style="vertical-align: middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap:break-word;">
                        <?php echo esc_html($package['providerLabel']); ?>
                    </td>
                    <td class="td" style="vertical-align: middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap:break-word;">
                        <?php if (! empty($package['trackingUrl'])): ?>
                            <a target="_blank" href="<?php echo esc_url($package['trackingUrl']); ?>"><?php echo esc_html($package['trackingNumber']); ?></a>
                        <?php else: ?>
                            <?php echo esc_html($package['trackingNumber']); ?>
                        <?php endif; ?>
                    </td>
                    <?php if (! empty($columns['items'])): ?>
                        <td class="td" style="vertical-align: middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap:break-word;">
                            <?php foreach ($package['items'] as $index => $item): ?>
                                <a target="_blank" href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['name']); ?></a> &times; <?php echo esc_html($item['quantity']); ?>
                                <?php if ($index !== count($package['items']) - 1): ?>
                                    <br>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
