<?php

namespace Bubu\Account\Api;

use Bubu\Account\Account;

class AccountUpdate
{
    public static function token()
    {
       echo json_encode(['token' => Account::updateToken($_POST['password'], $_POST['token'] ?? null)]);
    }

    public static function email()
    {
        echo json_encode(['email' => Account::updateEmail($_POST['newMail'], $_POST['password'], $_POST['token'] ?? null)]);
    }

    public static function verifyEmail($code)
    {
        echo json_encode(['verified_email' => Account::verifyEmailCode($code)]);
    }

    public static function password()
    {
        echo json_encode(['token' => Account::updatePassword($_POST['password'], $_POST['newPassword'], $_POST['confirmPassword'], $_POST['token'] ?? null)]);
    }
}
