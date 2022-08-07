<?php
namespace Bubu\Account;

use Bubu\Database\Database;
use Bubu\Http\Session\Session;
use Bubu\Mail\MailTemplate;

class Account
{
    protected $globalAccountInformation;

    /**
     * login
     * @param string $email
     * @param string $password
     * @param bool $keepSession
     *
     * @return bool|string
     */
    public static function login(string $email, string $password, bool $keepSession = false)
    {
        $dbData = Database::queryBuilder('Users')
            ->select('id', 'username', 'password', 'email_verified_at', 'token')
            ->where(Database::expr()::eq('email', $email))
            ->fetch();

        if ($dbData === false || count($dbData) === 0) return 'account_not_found';
        elseif (!password_verify($password, $dbData['password'])) return 'wrong_password';
        elseif (is_null($dbData['email_verified_at'])) return 'email_not_verified';
        else {
            Session::set('token', $dbData['token']);
            Session::set('username', $dbData['username']);
            if ($keepSession) Session::changeSessionLifetime($_ENV['SESSION_KEEP_CONNECT']);
            return true;
        }
    }

    public static function regexPassword(string $password, string $confim): mixed
    {
        if (strlen($password) < 10) return 'password_too_short';
        elseif ($password !== $confim) return 'password_not_match';
        else return true;
    }

    /**
     * signup
     * @param string $username
     * @param string $password
     * @param string $passwordConfirm
     * @param string $email
     *
     * @return bool|string
     */
    public static function signup(
        string $username,
        string $password,
        string $passwordConfirm,
        string $email
    ) {
        $usernameFetch = Database::queryBuilder('Users')
            ->select('username')
            ->where(Database::expr()::eq('username', $username))
            ->fetch();

        $emailFetch = Database::queryBuilder('Users')
            ->select('email')
            ->where(Database::expr()::eq('email', $email))
            ->fetch();

        if ($usernameFetch !== false && count($usernameFetch) !== 0) return 'username_already_used';
        elseif ($emailFetch !== false && count($emailFetch) !== 0) return 'email_already_used';

        $passCheck = self::regexPassword($password, $passwordConfirm);
        if ($passCheck !== true) return $passCheck;

        $emailCode = bin2hex(random_bytes(10));

        Database::queryBuilder('Users')
            ->insert([
                'username' => $username,
                'email'    => $email,
                'password' => password_hash($password, constant($_ENV['HASH_ALGO'])),
                'token'    => bin2hex(random_bytes(30)),
                'email_verification_code' => $emailCode,
            ])
            ->execute();

        MailTemplate::sendEmailVerification($email, $emailCode);

        return true;
    }

    public static function checkPassword(string $password, ?string $tokenPassed): bool
    {
        $token = Session::get('token');
        $dbPass = Database::queryBuilder('Users')
            ->select('password')
            ->where(Database::expr()::eq('token', $token ?? $tokenPassed))
            ->fetch();
        if (password_verify($password, $dbPass['password'])) return true;
        else return false;
    }

    public static function updateToken(string $password, ?string $token): string
    {
        if (!self::checkPassword($password, $token)) return 'wrong_password';
        
        $newToken = bin2hex(random_bytes(30));

        Database::queryBuilder('Users')
            ->update([
                'token' => $newToken
            ])
            ->where(Database::expr()::eq('token', Session::get('token')))
            ->execute();

        Session::set('token', $newToken);

        return $newToken;
    }

    public static function updateEmail(string $newMail, string $password, ?string $token)
    {
        if (!self::checkPassword($password, $token)) return 'wrong_password';

        $emailCode = bin2hex(random_bytes(10));

        Database::queryBuilder('Users')
            ->update([
                'email' => $newMail,
                'email_verification_code' => $emailCode
            ])
            ->where(Database::expr()::eq('token', Session::get('token')))
            ->execute();

        MailTemplate::sendEmailVerification($newMail, $emailCode);
        return true;
    }

    public static function verifyEmailCode(string $code): bool
    {
        $r = Database::queryBuilder('Users')
            ->select('email_verification_code')
            ->where(Database::expr()::eq('email_verification_code', $code))
            ->fetch();
        
        if (!$r) return false;

        $r = Database::queryBuilder('Users')
            ->update([
                'email_verification_code' => null,
                'email_verified_at' => date('Y-m-d H:i:s')
            ])
            ->where(Database::expr()::eq('email_verification_code', $code))
            ->execute();

        if (!$r) return false;
        return true;
    }

    public static function updatePassword(
        string $currentPassword,
        string $newPassword,
        string $confirm,
        ?string $token
    ) {
        if (!self::checkPassword($currentPassword, $token)) return 'wrong_password';

        $passCheck = self::regexPassword($newPassword, $confirm);
        if ($passCheck !== true) return $passCheck;

        Database::queryBuilder('Users')
            ->update([
                'password' => password_hash($newPassword, constant($_ENV['HASH_ALGO']))
            ])
            ->where(Database::expr()::eq('token', Session::get('token')))
            ->execute();
        
        return true;
    }
}
