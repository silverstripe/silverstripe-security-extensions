<?php

declare(strict_types=1);

namespace SilverStripe\SecurityExtensions\Service;

use SilverStripe\Control\Session;

/**
 * A service class responsible for activating and checking the current status of elevated permission levels
 * via "sudo mode". This is done by checking a timestamp value in the provided session.
 */
interface SudoModeServiceInterface
{
    /**
     * Checks the current session to see if sudo mode was activated within the last section of lifetime allocation.
     *
     * @param Session $session
     * @return bool
     */
    public function check(Session $session): bool;

    /**
     * Register activated sudo mode permission in the provided session, which lasts for the configured lifetime.
     *
     * @param Session $session
     * @return bool
     */
    public function activate(Session $session): bool;

    /**
     * How long the sudo mode activation lasts for in minutes.
     *
     * @return int
     */
    public function getLifetime(): int;
}
