<?php declare(strict_types=1);

namespace SilverStripe\SecurityExtensions\Tests\Control;

use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Security\Member;
use SilverStripe\Security\SecurityToken;
use SilverStripe\SecurityExtensions\Control\SudoModeController;

class SudoModeControllerTest extends FunctionalTest
{
    protected $usesDatabase = true;

    /**
     * @var bool
     */
    private $securityTokenEnabled;

    protected function setUp()
    {
        parent::setUp();

        $this->securityTokenEnabled = SecurityToken::is_enabled();
        SecurityToken::disable();

        $memberID = $this->logInWithPermission();

        /** @var Member $member */
        $member = Member::get()->byID($memberID);
        $member->changePassword('0p3nS3samE!');
    }

    protected function tearDown()
    {
        if ($this->securityTokenEnabled) {
            SecurityToken::enable();
        }

        parent::tearDown();
    }

    public function testCheckReturnsFalse()
    {
        $response = $this->get(SudoModeController::singleton()->Link('check'));
        $this->assertSame(200, $response->getStatusCode());
        $result = json_decode((string) $response->getBody(), true);
        $this->assertFalse($result['active'], 'Sudo mode should not be active yet');
    }

    public function testActivateFailsWithIncorrectPassword()
    {
        $response = $this->post(SudoModeController::singleton()->Link('activate'), [
            'Password' => 'wrongpassword!',
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $result = json_decode((string) $response->getBody(), true);
        $this->assertFalse($result['result'], 'Should have failed with incorrect password');
        $this->assertEquals('Invalid credentials', $result['message']);
    }

    public function testActivateSudoModeWithValidCredentials()
    {
        $activateResponse = $this->post(SudoModeController::singleton()->Link('activate'), [
            'Password' => '0p3nS3samE!',
        ]);

        $this->assertSame(200, $activateResponse->getStatusCode());
        $result = json_decode((string) $activateResponse->getBody(), true);
        $this->assertTrue($result['result'], 'Should have activated sudo mode');

        $checkResponse = $this->get(SudoModeController::singleton()->Link('check'));
        $this->assertSame(200, $checkResponse->getStatusCode());
        $checkResult = json_decode((string) $checkResponse->getBody(), true);
        $this->assertTrue($checkResult['active'], 'Sudo mode should be active after activate() called');
    }

    public function testActivateFailsWithGetRequest()
    {
        $response = $this->get(SudoModeController::singleton()->Link('activate'));
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testActivateChecksCSRFToken()
    {
        SecurityToken::enable();
        $activateResponse = $this->post(SudoModeController::singleton()->Link('activate'), [
            'Password' => 'wrongpassword!',
        ]);

        $this->assertSame(403, $activateResponse->getStatusCode());
        $result = json_decode((string) $activateResponse->getBody(), true);
        $this->assertFalse($result['result'], 'Should have failed on CSRF token validation');
        $this->assertSame($result['message'], 'Invalid CSRF token provided');
    }
}
