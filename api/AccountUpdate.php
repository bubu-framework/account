<?php

namespace Bubu\Account\Api;

use Bubu\Account\Account;

class AccountUpdate
{
    public static function token()
    {
       echo  Account::updateToken($_POST['password'], $_POST['token'] ?? null);
    }

    public static function email()
    {
        echo Account::updateEmail($_POST['newMail'], $_POST['password'], $_POST['token'] ?? null);
    }

    public static function verifyEmail($code)
    {
        echo Account::verifyEmailCode($code);
    }

    public static function password()
    {
        echo Account::updatePassword($_POST['password'], $_POST['newPassword'], $_POST['confirmPassword'], $_POST['token'] ?? null);
    }
}
