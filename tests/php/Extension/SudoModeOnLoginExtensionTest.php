<?php

namespace SilverStripe\SecurityExtensions\Tests\Extension;

use Authenticator;
use FunctionalTest;
use Member;
use MemberAuthenticator;
use Security;
use SilverStripe\SecurityExtensions\Control\SudoModeController;
use SilverStripe\SecurityExtensions\Extension\SudoModeOnLoginExtension;

class SudoModeOnLoginExtensionTest extends FunctionalTest
{
    protected $requiredExtensions = [
        Member::class => [
            SudoModeOnLoginExtension::class,
        ],
    ];

    public function setUp()
    {
        parent::setUp();

        // Disable MFA on account
        Authenticator::set_default_authenticator(MemberAuthenticator::class);
    }

    public function testSudoModeActivatesOnLogin()
    {
        // Explicitly update a fixtured member's password
        $memberID = $this->logInWithPermission();
        /** @var Member $member */
        $member = Member::get()->byID($memberID);
        $member->changePassword('0p3nS3samE!');
        $member->logOut();

        // Perform a login using the new password
        $this->autoFollowRedirection = true;
        $this->get(Security::login_url());
        $response = $this->submitForm('MemberLoginForm_LoginForm', 'action_dologin', [
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
