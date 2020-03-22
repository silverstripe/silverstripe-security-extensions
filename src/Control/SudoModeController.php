<?php

declare(strict_types=1);

namespace SilverStripe\SecurityExtensions\Control;

use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Security\Authenticator;
use SilverStripe\Security\Security;
use SilverStripe\Security\SecurityToken;
use SilverStripe\SecurityExtensions\Service\SudoModeServiceInterface;

/**
 * Responsible for checking and verifying whether sudo mode is enabled
 */
class SudoModeController extends LeftAndMain
{
    private static $url_segment = 'sudomode';

    private static $ignore_menuitem = true;

    private static $allowed_actions = [
        'check',
        'activate',
    ];

    private static $dependencies = [
        'SudoModeService' => '%$' . SudoModeServiceInterface::class,
    ];

    /**
     * A user help documentation link to find out more about sudo mode
     *
     * @config
     * @var string
     */
    // phpcs:ignore 
    private static $help_link = 'https://userhelp.silverstripe.org/en/4/optional_features/multi-factor_authentication/user_manual/managing_your_mfa_settings/#managing-your-mfa-settings';

    /**
     * @var SudoModeServiceInterface
     */
    private $sudoModeService;

    /**
     * Explicitly disable required permissions for sudo mode checks
     *
     * @var boolean
     */
    private static $required_permission_codes = false;

    public function getClientConfig()
    {
        /** @var HTTPRequest $request */
        $request = Injector::inst()->get(HTTPRequest::class);

        return array_merge_recursive(parent::getClientConfig(), [
            'endpoints' => [
                'activate' => $this->Link('activate'),
            ],
            'sudoModeActive' => $this->getSudoModeService()->check($request->getSession()),
            'helpLink' => $this->config()->get('help_link'),
        ]);
    }

    /**
     * Checks whether sudo mode is active for the current user
     *
     * @param HTTPRequest $request
     * @return HTTPResponse
     */
    public function check(HTTPRequest $request): HTTPResponse
    {
        return $this->jsonResponse([
            'active' => $this->getSudoModeService()->check($request->getSession()),
        ]);
    }

    /**
     * After validating the request data including password against the current member, activate sudo mode
     * for the current member.
     *
     * @param HTTPRequest $request
     * @return HTTPResponse
     * @throws HTTPResponse_Exception If the request was not made with POST
     */
    public function activate(HTTPRequest $request): HTTPResponse
    {
        if (!$request->isPOST()) {
            return $this->httpError(404);
        }

        if (!SecurityToken::inst()->checkRequest($request)) {
            return $this->jsonResponse([
                'result' => false,
                'message' => _t(__CLASS__ . '.TIMEOUT', 'Session timed out, please refresh and try again.'),
            ], 403);
        }

        // Validate password
        if (!$this->checkPassword($request)) {
            return $this->jsonResponse([
                'result' => false,
                'message' => _t(__CLASS__ . '.INVALID', 'Incorrect password'),
            ]);
        }

        // Activate sudo mode and return successful result
        $this->getSudoModeService()->activate($request->getSession());
        return $this->jsonResponse(['result' => true]);
    }

    /**
     * Checks the provided password is valid for the current member. Will return false if insufficient data
     * is available to validate the request.
     *
     * @param HTTPRequest $request
     * @return bool
     */
    private function checkPassword(HTTPRequest $request): bool
    {
        $password = $request->postVar('Password');
        if (!$password) {
            return false;
        }

        $currentMember = Security::getCurrentUser();
        if (!$currentMember) {
            return false;
        }

        $result = ValidationResult::create();
        $authenticators = Security::singleton()->getApplicableAuthenticators(Authenticator::CHECK_PASSWORD);
        foreach ($authenticators as $authenticator) {
            $authenticator->checkPassword($currentMember, $password, $result);
            if (!$result->isValid()) {
                break;
            }
        }
        return $result->isValid();
    }

    /**
     * Returns a JSON response with an encoded body and provided HTTP status code
     *
     * @param array $body
     * @param int $code
     * @return HTTPResponse
     */
    private function jsonResponse(array $body, int $code = 200): HTTPResponse
    {
        $response = new HTTPResponse();
        $response
            ->addHeader('Content-Type', 'application/json')
            ->setBody(json_encode($body))
            ->setStatusCode($code);
        return $response;
    }

    /**
     * @param SudoModeServiceInterface $sudoModeService
     * @return $this
     */
    public function setSudoModeService(SudoModeServiceInterface $sudoModeService): self
    {
        $this->sudoModeService = $sudoModeService;
        return $this;
    }

    /**
     * @return SudoModeServiceInterface
     */
    public function getSudoModeService(): ?SudoModeServiceInterface
    {
        return $this->sudoModeService;
    }
}
