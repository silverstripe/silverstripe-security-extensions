<?php

declare(strict_types=1);

namespace SilverStripe\SecurityExtensions\Tests\Control;

use FunctionalTest;
use Member;
use PHPUnit_Framework_MockObject_MockObject;
use SecurityToken;
use Session;
use SilverStripe\SecurityExtensions\Control\SudoModeController;
use SilverStripe\SecurityExtensions\Service\SudoModeServiceInterface;

class SudoModeControllerTest extends FunctionalTest
{
    protected $usesDatabase = true;

    /**
     * @var bool
     */
    private $securityTokenEnabled;

    public function setUp()
    {
        parent::setUp();

        $this->securityTokenEnabled = SecurityToken::is_enabled();
        SecurityToken::disable();

        $memberID = $this->logInWithPermission();

        /** @var Member $member */
        $member = Member::get()->byID($memberID);
        $member->changePassword('0p3nS3samE!');

        // Logging in above will have set sudo mode, clear it for tests
        Session::clear('sudo-mode-last-activated');
    }

    public function tearDown()
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
        $this->assertEquals('Incorrect password', $result['message']);
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
        $this->assertSame($result['message'], 'Session timed out, please refresh and try again.');
    }

    public function testClientConfig()
    {
        /** @var SudoModeServiceInterface&PHPUnit_Framework_MockObject_MockObject $serviceMock */
        $serviceMock = $this->createMock(SudoModeServiceInterface::class);
        $serviceMock->expects($this->once())->method('check')->willReturn(true);

        $controller = new SudoModeController();
        $controller->setSudoModeService($serviceMock);
        $controller->setSession(new Session([]));

        $result = $controller->getClientConfig();
        $this->assertArrayHasKey('activate', $result['endpoints'], 'Client config should provide activation endpoint');
        $this->assertTrue($result['sudoModeActive'], 'Client config should expose sudo mode status');
    }
}
