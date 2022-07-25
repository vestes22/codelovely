<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts;

use GoDaddy\WordPress\MWC\Common\Email\Contracts\EmailContract;

/**
 * The Renderable Email Contract.
 */
interface RenderableEmailContract extends EmailContract
{
    /**
     * Gets the email variables.
     *
     * @return array
     */
    public function getVariables(): array;

    /**
     * Sets the email variables.
     *
     * @param array $value
     * @return self
     */
    public function setVariables(array $value);

    /**
     * Gets the body format.
     * @return string
     */
    public function getBodyFormat(): string;
}
