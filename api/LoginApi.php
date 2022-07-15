<?php

namespace Bubu\Account\Api;

use Bubu\Account\Account;

class LoginApi
{
    public static function login()
    {
        echo Account::login(
            $_POST['email'],
            $_POST['password'],
            $_POST['keepSession'] ?? false
        );
    }
}
