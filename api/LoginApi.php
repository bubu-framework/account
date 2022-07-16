<?php

namespace Bubu\Account\Api;

use Bubu\Account\Account;

class LoginApi
{
    public static function login()
    {
        header('Content-Type: application/json');
        echo json_encode(['account' => Account::login(
            $_POST['email'],
            $_POST['password'],
            $_POST['keepSession'] ?? false
        )]);
    }
}
