<?php

declare(strict_types=1);

namespace SilverStripe\SecurityExtensions\Extension;

use Controller;
use Extension;
use Injector;
use SilverStripe\SecurityExtensions\Service\SudoModeServiceInterface;

class SudoModeOnLoginExtension extends Extension
{
    /**
     * Activates sudo mode on a successful log in
     */
    public function memberLoggedIn(): void
    {
        $session = Controller::curr()->getSession();
        if ($session) {
            /** @var SudoModeServiceInterface $service */
            $service = Injector::inst()->get(SudoModeServiceInterface::class);
            $service->activate($session);
        }
    }
}
