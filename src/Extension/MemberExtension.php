<?php declare(strict_types=1);

namespace SilverStripe\SecurityExtensions\Extension;

use CheckboxField;
use DataExtension;
use FieldList;
use Member;
use SS_Datetime;

/**
 * Extend Member to add relationship to registered methods and track some specific preferences
 *
 * @property Member|MemberExtension owner
 */
class MemberExtension extends DataExtension
{
    public function updateCMSFields(FieldList $fields)
    {
        $currentUser = Member::currentUser();

        // We can allow an admin to require a user to change their password however. But:
        // - Don't show a read only field if the user cannot edit this record
        // - Don't show if a user views their own profile (just let them reset their own password)
        if ($currentUser && ($currentUser->ID !== $this->owner->ID) && $this->owner->canEdit()) {
            $requireNewPassword = CheckboxField::create(
                'RequiresPasswordChangeOnNextLogin',
                _t(__CLASS__ . 'RequiresPasswordChangeOnNextLogin', 'Requires password change on next login')
            );
            $fields->insertAfter('Password', $requireNewPassword);

            $fields->dataFieldByName('Password')->addExtraClass('form-field--no-divider mb-0 pb-0');
        }

        return $fields;
    }

    public function getRequiresPasswordChangeOnNextLogin()
    {
        return $this->owner->isPasswordExpired();
    }

    /**
     * Set password expiry to now to enforce a change of password next log in
     *
     * @param int|null $dataValue boolean representation checked/not checked {@see CheckboxField::dataValue}
     * @return Member
     */
    public function saveRequiresPasswordChangeOnNextLogin($dataValue)
    {
        $member = $this->owner;

        if (!$member->canEdit()) {
            return $member;
        }

        $currentValue = $member->PasswordExpiry;
        /** @var SS_Datetime $currentDate */
        $currentDate = $member->dbObject('PasswordExpiry');

        if ($dataValue && (!$currentValue || $currentDate->inFuture())) {
            // Only alter future expiries - this way an admin could see how long ago a password expired still
            $member->PasswordExpiry = SS_Datetime::now()->Rfc2822();
        } elseif (!$dataValue && $member->isPasswordExpired()) {
            // Only unset if the expiry date is in the past
            $member->PasswordExpiry = null;
        }

        return $member;
    }
}
