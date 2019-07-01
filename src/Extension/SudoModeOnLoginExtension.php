<?php declare(strict_types=1);

namespace SilverStripe\SecurityExtensions\Extension;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\SecurityExtensions\Service\SudoModeServiceInterface;

class SudoModeOnLoginExtension extends Extension
{
    /**
     * Activates sudo mode on a successful log in
     */
    public function afterLogin(): void
    {
        if (Injector::inst()->has(HTTPRequest::class)) {
            /** @var SudoModeServiceInterface $service */
            $service = Injector::inst()->get(SudoModeServiceInterface::class);

            /** @var HTTPRequest $request */
            $request = Injector::inst()->get(HTTPRequest::class);

            $service->activate($request->getSession());
        }
    }
}
