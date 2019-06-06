<?php declare(strict_types=1);

namespace SilverStripe\SecurityExtensions\Extension;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;

/**
 * Extend Member to add relationship to registered methods and track some specific preferences
 *
 * @property Member|MemberExtension owner
 */
class MemberExtension extends DataExtension
{
    public function updateCMSFields(FieldList $fields)
    {
        $currentUser = Security::getCurrentUser();

        // We can allow an admin to require a user to change their password however. But:
        // - Don't show a read only field if the user cannot edit this record
        // - Don't show if a user views their own profile (just let them reset their own password)
        if ($currentUser && ($currentUser->ID !== $this->owner->ID) && $this->owner->canEdit()) {
            $requireNewPassword = CheckboxField::create(
                'RequirePasswordChangeOnNextLogin',
                _t(__CLASS__ . 'RequirePasswordChangeOnNextLogin', 'Require password change on next login')
            );
            $fields->insertAfter('Password', $requireNewPassword);

            $fields->dataFieldByName('Password')->addExtraClass('form-group--no-divider');
        }

        return $fields;
    }

    /**
     * Set password expiry to now to enforce a change of password next log in
     *
     * @param int|null $dataValue boolean representation checked/not checked {@see CheckboxField::dataValue}
     * @return Member
     */
    public function saveRequirePasswordChangeOnNextLogin($dataValue)
    {
        if ($dataValue && $this->owner->canEdit()) {
            // An expired password automatically requires a password change on logging in
            $this->owner->PasswordExpiry = DBDatetime::now()->Rfc2822();
        }
        return $this->owner;
    }
}
