<?php

namespace SilverStripe\SecuityExtensions\Tests\Extension;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\SecurityExtensions\Extension\MemberExtension;
use SilverStripe\ORM\FieldType\DBDateTime;
use SilverStripe\Security\Member;

class MemberExtensionTest extends SapphireTest
{
    protected static $fixture_file = 'MemberExtensionTest.yml';

    protected static $required_extensions = [
        Member::class => [MemberExtension::class]
    ];

    public function testAdminCanRequirePasswordChangeOnNextLogIn()
    {
        /** @var Member&MemberExtension $targetMember */
        $targetMember = $this->objFromFixture(Member::class, 'someone');
        $this->logInWithPermission('ADMIN');
        $field = $targetMember->getCMSFields()->dataFieldByName('RequiresPasswordChangeOnNextLogin');
        $this->assertNotNull($field);
    }

    public function testUserCannotRequireTheirOwnPasswordChangeOnNextLogIn()
    {
        /** @var Member&MemberExtension $targetMember */
        $targetMember = $this->objFromFixture(Member::class, 'someone');
        $this->logInAs($targetMember);
        $field = $targetMember->getCMSFields()->dataFieldByName('RequiresPasswordChangeOnNextLogin');
        $this->assertNull($field);
    }

    public function testUserCannotRequireOthersToPasswordChangeOnNextLogIn()
    {
        /** @var Member&MemberExtension $targetMember */
        $targetMember = $this->objFromFixture(Member::class, 'anyone');
        $this->logInAs('someone');
        $field = $targetMember->getCMSFields()->dataFieldByName('RequiresPasswordChangeOnNextLogin');
        $this->assertNull($field);
    }

    public function testCheckingRequiresPasswordChangeOnNextLoginWillSetPasswordExpiryToNow()
    {
        $mockDate = '2019-03-02 00:00:00';
        DBDateTime::set_mock_now($mockDate);

        /** @var Member&MemberExtension $targetMember */
        $targetMember = $this->objFromFixture(Member::class, 'someone');

        $this->assertNull($targetMember->PasswordExpiry);

        $this->logInWithPermission('ADMIN');
        $fields = $targetMember->getCMSFields();
        $form = new Form(null, 'SomeForm', $fields, new FieldList());
        $field = $fields->dataFieldByName('RequiresPasswordChangeOnNextLogin');
        $field->setValue(1);
        $form->saveInto($targetMember);

        $this->assertEquals($mockDate, $targetMember->PasswordExpiry);
    }

    public function testCheckingPasswordChangeUpdatesFutureExpiriesToNow()
    {
        $mockDate = '2019-03-02 00:00:00';
        DBDateTime::set_mock_now($mockDate);

        /** @var Member&MemberExtension $targetMember */
        $targetMember = $this->objFromFixture(Member::class, 'willexpire');

        $this->assertTrue($targetMember->dbObject('PasswordExpiry')->inFuture());

        $this->logInWithPermission('ADMIN');
        $fields = $targetMember->getCMSFields();
        $form = new Form(null, 'SomeForm', $fields, new FieldList());
        $field = $fields->dataFieldByName('RequiresPasswordChangeOnNextLogin');
        $field->setValue(1);
        $form->saveInto($targetMember);

        $this->assertEquals($mockDate, $targetMember->PasswordExpiry);
    }

    public function testCheckingPasswordChangeDoesNotAlterPastDates()
    {
        $mockDate = '2019-03-02 00:00:00';
        DBDateTime::set_mock_now($mockDate);

        /** @var Member&MemberExtension $targetMember */
        $targetMember = $this->objFromFixture(Member::class, 'expired');
        $originalValue = $targetMember->PasswordExpiry;

        $this->assertTrue($targetMember->dbObject('PasswordExpiry')->inPast());

        $this->logInWithPermission('ADMIN');
        $fields = $targetMember->getCMSFields();
        $form = new Form(null, 'SomeForm', $fields, new FieldList());
        $field = $fields->dataFieldByName('RequiresPasswordChangeOnNextLogin');
        $field->setValue(1);
        $form->saveInto($targetMember);

        $this->assertEquals($originalValue, $targetMember->PasswordExpiry);
    }

    public function testSavingUncheckedPasswordChangeNullsPastDates()
    {
        $mockDate = '2019-03-02 00:00:00';
        DBDateTime::set_mock_now($mockDate);

        /** @var Member&MemberExtension $targetMember */
        $targetMember = $this->objFromFixture(Member::class, 'expired');

        $this->logInWithPermission('ADMIN');
        $fields = $targetMember->getCMSFields();
        $form = new Form(null, 'SomeForm', $fields, new FieldList());
        $field = $fields->dataFieldByName('RequiresPasswordChangeOnNextLogin');
        $field->setValue(0);
        $form->saveInto($targetMember);

        $this->assertNull($targetMember->PasswordExpiry);
    }

    public function testSavingUncheckedPasswordChangeDoesNotAlterFutureDates()
    {
        $mockDate = '2019-03-02 00:00:00';
        DBDateTime::set_mock_now($mockDate);

        /** @var Member&MemberExtension $targetMember */
        $targetMember = $this->objFromFixture(Member::class, 'willexpire');
        $originalValue = $targetMember->PasswordExpiry;

        $this->logInWithPermission('ADMIN');
        $fields = $targetMember->getCMSFields();
        $form = new Form(null, 'SomeForm', $fields, new FieldList());
        $field = $fields->dataFieldByName('RequiresPasswordChangeOnNextLogin');
        $field->setValue(0);
        $form->saveInto($targetMember);

        $this->assertNotNull($targetMember->PasswordExpiry);
        $this->assertEquals($originalValue, $targetMember->PasswordExpiry);
    }

    public function testSavingChangePasswordOnNextLoginIsNotPossibleIfTheCurrentMemberCannotEditTheMemberBeingSaved()
    {
        /** @var Member&MemberExtension $targetMember */
        $targetMember = $this->objFromFixture(Member::class, 'expired');
        $originalValue = $targetMember->PasswordExpiry;

        $this->logInAs('someone');
        $fields = $targetMember->saveRequiresPasswordChangeOnNextLogin(0);

        $this->assertEquals($originalValue, $targetMember->PasswordExpiry);
    }

    public function testGetRequiresPasswordChangeOnNextLogin()
    {
        $this->assertTrue(
            $this->objFromFixture(Member::class, 'expired')->getRequiresPasswordChangeOnNextLogin(),
            'PasswordExpiry date in the past should require a change'
        );
        $this->assertFalse(
            $this->objFromFixture(Member::class, 'willexpire')->getRequiresPasswordChangeOnNextLogin(),
            'PasswordExpiry date in the past should NOT require a change'
        );
        $this->assertFalse(
            $this->objFromFixture(Member::class, 'someone')->getRequiresPasswordChangeOnNextLogin(),
            'PasswordExpiry is NULL should NOT require a change'
        );
    }
}
