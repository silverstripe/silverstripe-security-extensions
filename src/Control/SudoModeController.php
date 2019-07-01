<?php declare(strict_types=1);

namespace SilverStripe\SecurityExtensions\Control;

use Authenticator;
use Injector;
use LeftAndMain;
use Member;
use SecurityToken;
use SilverStripe\SecurityExtensions\Service\SudoModeServiceInterface;
use SS_HTTPRequest;
use SS_HTTPResponse;
use SS_HTTPResponse_Exception;

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
        'SudoModeService' => '%$SilverStripe\\SecurityExtensions\\Service\\SudoModeServiceInterface',
    ];

    /**
     * A user help documentation link to find out more about sudo mode
     *
     * @config
     * @var string
     */
    private static $help_link = 'http://google.com';

    /**
     * @var SudoModeServiceInterface
     */
    private $sudoModeService;

    public function getClientConfig()
    {
        return [
            'endpoints' => [
                'activate' => $this->Link('activate'),
            ],
            'sudoModeActive' => $this->getSudoModeService()->check($this->getSession()),
            'helpLink' => $this->config()->get('help_link'),
        ];
    }

    /**
     * Checks whether sudo mode is active for the current user
     *
     * @return SS_HTTPResponse
     */
    public function check(): SS_HTTPResponse
    {
        return $this->jsonResponse([
            'active' => $this->getSudoModeService()->check($this->getSession()),
        ]);
    }

    /**
     * After validating the request data including password against the current member, activate sudo mode
     * for the current member.
     *
     * @param SS_HTTPRequest $request
     * @return SS_HTTPResponse
     * @throws SS_HTTPResponse_Exception If the request was not made with POST
     */
    public function activate(SS_HTTPRequest $request): SS_HTTPResponse
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
        $this->getSudoModeService()->activate($this->getSession());
        return $this->jsonResponse(['result' => true]);
    }

    /**
     * Checks the provided password is valid for the current member. Will return false if insufficient data
     * is available to validate the request.
     *
     * @param SS_HTTPRequest $request
     * @return bool
     */
    private function checkPassword(SS_HTTPRequest $request): bool
    {
        $password = $request->postVar('Password');
        if (!$password) {
            return false;
        }

        $currentMember = Member::currentUser();
        if (!$currentMember) {
            return false;
        }

        $uniqueIdentifier = Member::config()->get('unique_identifier_field');
        $authenticationData = $request->postVars() + ['Email' => $currentMember->{$uniqueIdentifier}];

        $authenticators = Authenticator::get_authenticators();
        foreach ($authenticators as $authenticatorClass) {
            /** @var Authenticator $authenticator */
            $authenticator = Injector::inst()->create($authenticatorClass);
            $result = $authenticator::authenticate($authenticationData);
            if ($result) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns a JSON response with an encoded body and provided HTTP status code
     *
     * @param array $body
     * @param int $code
     * @return SS_HTTPResponse
     */
    private function jsonResponse(array $body, int $code = 200): SS_HTTPResponse
    {
        $response = new SS_HTTPResponse();
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
