<?php declare(strict_types=1);

namespace SilverStripe\SecurityExtensins\Tests\Service;

use Config;
use SapphireTest;
use Session;
use SilverStripe\SecurityExtensions\Service\SudoModeService;
use SS_Datetime;

class SudoModeServiceTest extends SapphireTest
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @var SudoModeService
     */
    private $service;

    public function setUp()
    {
        parent::setUp();

        $this->session = new Session([]);
        $this->service = new SudoModeService();

        SS_Datetime::set_mock_now('2019-03-01 12:00:00');
        Config::inst()->update(SudoModeService::class, 'lifetime_minutes', 180);
    }

    public function testCheckWithoutActivation()
    {
        $this->session::clear_all();
        $this->assertFalse($this->service->check($this->session));
    }

    public function testCheckWithLastActivationOutsideLifetimeWindow()
    {
        // 240 minutes ago
        $lastActivated = SS_Datetime::now()->Format('U') - 240 * 60;
        $this->session::set('sudo-mode-last-activated', $lastActivated);
        $this->assertFalse($this->service->check($this->session));
    }

    public function testCheckWithLastActivationInsideLifetimeWindow()
    {
        // 25 minutes ago
        $lastActivated = SS_Datetime::now()->Format('U') - 25 * 60;
        $this->session::set('sudo-mode-last-activated', $lastActivated);
        $this->assertTrue($this->service->check($this->session));
    }

    public function testActivateAndCheckImmediately()
    {
        $this->service->activate($this->session);
        $this->assertTrue($this->service->check($this->session));
    }
}
