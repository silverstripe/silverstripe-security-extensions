<?php

namespace SilverStripe\SecurityExtensions\Tests\Extension;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Security\Member;
use SilverStripe\Security\MemberAuthenticator\LoginHandler;
use SilverStripe\Security\MemberAuthenticator\MemberAuthenticator;
use SilverStripe\Security\Security;
use SilverStripe\SecurityExtensions\Control\SudoModeController;
use SilverStripe\SecurityExtensions\Extension\SudoModeOnLoginExtension;

class SudoModeOnLoginExtensionTest extends FunctionalTest
{
    protected static $required_extensions = [
        LoginHandler::class => [
            SudoModeOnLoginExtension::class,
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // Disable MFA on account
        Injector::inst()->load([
            Security::class => [
                'properties' => [
                    'Authenticators' => [
                        'default' => '%$' . MemberAuthenticator::class,
                    ],
                ],
            ],
        ]);
    }

    public function testSudoModeActivatesOnLogin()
    {
        // Explicitly update a fixtured member's password
        $memberID = $this->logInWithPermission();
        /** @var Member $member */
        $member = Member::get()->byID($memberID);
        $member->changePassword('0p3nS3samE!');
        $this->logOut();

        // Perform a login using the new password
        $this->autoFollowRedirection = true;
        $this->get(Security::login_url());
        $response = $this->submitForm('MemberLoginForm_LoginForm', 'action_doLogin', [
            'Email' => $member->Email,
            'Password' => '0p3nS3samE!',
        ]);
        $this->assertSame(200, $response->getStatusCode());

        // Check to ensure that sudo mode is enabled for the current user
        $checkResponse = $this->get(SudoModeController::singleton()->Link('check'));
        $this->assertSame(200, $checkResponse->getStatusCode());
        $body = json_decode((string) $checkResponse->getBody(), true);
        $this->assertTrue($body['active']);
    }
}
