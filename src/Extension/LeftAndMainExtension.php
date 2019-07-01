<?php declare(strict_types=1);

namespace SilverStripe\SecurityExtensions\Extension;

use Requirements;
use SecurityToken;
use SilverStripe\SecurityExtensions\Control\SudoModeController;

/**
 * @deprecated 3.0.0 Replaced with LeftAndMain::getClientConfig() by default in SilverStripe 4
 */
class LeftAndMainExtension extends \LeftAndMainExtension
{
    /**
     * Add a small replica of the LeftAndMain::getClientConfig() data store from SilverStripe 4, which ends up
     * in a Redux store provided by SecurityExtensions
     */
    public function init(): void
    {
        $clientConfig = json_encode([
            'SecurityID' => SecurityToken::inst()->getValue(),
            'sections' => [
                array_merge(
                    ['name' => SudoModeController::class],
                    SudoModeController::singleton()->getClientConfig()
                ),
            ],
        ]);

        $script = <<<CONFIG
window.ss = window.ss || {};
window.ss.config = {$clientConfig};
CONFIG;

        Requirements::customScript($script);
    }
}
