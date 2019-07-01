<?php declare(strict_types=1);

namespace SilverStripe\SecurityExtensions\Service;

use Session;
use SS_Datetime;
use SS_Object;

class SudoModeService extends SS_Object implements SudoModeServiceInterface
{
    /**
     * The lifetime that sudo mode authorization lasts for, in minutes.
     *
     * Note that if the PHP session times out before this lifetime is reached, it will automatically be reset.
     * @see Session::$timeout
     *
     * @config
     * @var int
     */
    private static $lifetime_minutes = 45;

    /**
     * The session key that is used to store the timestamp for when sudo mode was last activated
     *
     * @var string
     */
    private const SUDO_MODE_SESSION_KEY = 'sudo-mode-last-activated';

    public function check(Session $session): bool
    {
        $lastActivated = $session::get(self::SUDO_MODE_SESSION_KEY);
        // Not activated at all
        if (!$lastActivated) {
            return false;
        }

        // Activated within the last "lifetime" window
        $nowTimestamp = SS_Datetime::now()->Format('U');
        return $lastActivated > ($nowTimestamp - $this->getLifetime() * 60);
    }

    public function activate(Session $session): bool
    {
        $session::set(self::SUDO_MODE_SESSION_KEY, SS_Datetime::now()->Format('U'));
        return true;
    }

    public function getLifetime(): int
    {
        return (int) $this->config()->get('lifetime_minutes');
    }
}
