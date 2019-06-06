<?php declare(strict_types=1);

namespace SilverStripe\SecurityExtensins\Tests\Service;

use SilverStripe\Control\Session;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\SecurityExtensions\Service\SudoModeService;

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

    protected function setUp()
    {
        parent::setUp();

        $this->session = new Session([]);
        $this->service = new SudoModeService();

        DBDatetime::set_mock_now('2019-03-01 12:00:00');
        SudoModeService::config()->set('lifetime_minutes', 180);
    }

    public function testCheckWithoutActivation()
    {
        $this->session->clearAll();
        $this->assertFalse($this->service->check($this->session));
    }

    public function testCheckWithLastActivationOutsideLifetimeWindow()
    {
        // 240 minutes ago
        $lastActivated = DBDatetime::now()->getTimestamp() - 240 * 60;
        $this->session->set('sudo-mode-last-activated', $lastActivated);
        $this->assertFalse($this->service->check($this->session));
    }

    public function testCheckWithLastActivationInsideLifetimeWindow()
    {
        // 25 minutes ago
        $lastActivated = DBDatetime::now()->getTimestamp() - 25 * 60;
        $this->session->set('sudo-mode-last-activated', $lastActivated);
        $this->assertTrue($this->service->check($this->session));
    }

    public function testActivateAndCheckImmediately()
    {
        $this->service->activate($this->session);
        $this->assertTrue($this->service->check($this->session));
    }
}
