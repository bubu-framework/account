<?php

namespace Bubu\Account\Api;

use Bubu\Account\Account;

class RegisterApi
{
    public static function register()
    {
        header('Content-Type: application/json');
        echo json_encode(['account' => Account::signup(
            $_POST['username'],
            $_POST['password'],
            $_POST['passwordConfirm'],
            $_POST['email']
        )]);
    }
}
