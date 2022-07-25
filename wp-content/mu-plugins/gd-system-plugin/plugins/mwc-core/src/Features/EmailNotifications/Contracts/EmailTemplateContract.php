<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts;

/**
 * A contract for objects that provide a structured (HTML/MJML) and plain template or layout for an email notification.
 *
 * @since 2.14.1
 */
interface EmailTemplateContract extends EmailContentContract
{
    /**
     * Gets the email template name.
     *
     * @return string
     */
    public function getName() : string;

    /**
     * Sets the email template name.
     *
     * @since 2.15.0
     *
     * @NOTE the name of the parameter here has to match the corresponding definitions in {@see HasLabelTrait}
     *
     * @param string $value
     * @return self
     */
    public function setName(string $name);

    /**
     * Gets the email template label.
     *
     * @return string
     */
    public function getLabel() : string;

    /**
     * Sets the email template label.
     *
     * @NOTE the name of the parameter here has to match the corresponding definition in {@see HasLabelTrait}
     *
     * @param string $label
     * @return self
     */
    public function setLabel(string $label);

    /**
     * Sets the inner content email template.
     *
     * @param EmailContentContract $content
     * @return self
     */
    public function setInnerContentTemplate(EmailContentContract $content);

    /**
     * Gets the inner content email template.
     *
     * @return EmailContentContract|null
     */
    public function getInnerContentTemplate();
}
