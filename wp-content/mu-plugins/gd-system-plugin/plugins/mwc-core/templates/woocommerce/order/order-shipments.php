<?php
/**
 * Order Shipments.
 *
 * This template can be overridden by copying it to yourtheme/mwc/order/order-shipments.php.
 *
 * HOWEVER, on occasion we will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @since 2.10.0
 * @version 2.10.0
 */
defined('ABSPATH') || exit;

/*
 * @var array $columns
 * @var array $packages
 */
?>
<section class="woocommerce-order-shipments">

    <h2 class="woocommerce-order-shipments__title"><?php esc_html_e('Shipments', 'mwc-core'); ?></h2>

    <table class="woocommerce-table woocommerce-table--order-shipments shop_table order_shipments">

        <thead>
            <tr>
                <?php foreach ($columns as $columnId => $columnName): ?>
                    <th class="<?php echo esc_attr($columnId); ?>"><span class="nobr"><?php echo esc_html($columnName); ?></span></th>
                <?php endforeach; ?>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($packages as $package): ?>
                <tr>
                    <td><?php echo esc_html($package['providerLabel']); ?></td>
                    <td>
                        <?php if (! empty($package['trackingUrl'])): ?>
                            <a target="_blank" href="<?php echo esc_url($package['trackingUrl']); ?>"><?php echo esc_html($package['trackingNumber']); ?></a>
                        <?php else: ?>
                            <?php echo esc_html($package['trackingNumber']); ?>
                        <?php endif; ?>
                    </td>
                    <?php if (! empty($columns['items'])): ?>
                        <td>
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
</section>
