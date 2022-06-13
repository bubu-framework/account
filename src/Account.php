<?php
namespace Bubu\Account;

use Bubu\Database\Database;
use Bubu\Http\Session\Session;
use Bubu\Lang\Lang;
use Bubu\Mail\MailTemplate;

class Account
{
    protected $globalAccountInformation;

    /**
     * login
     * @param string $username
     * @param string $password
     * @param bool $keepSession
     *
     * @return bool|string
     */
    public static function login(string $username, string $password, bool $keepSession = false)
    {
        $dbData = Database::queryBuilder('users')
        ->select('id', 'password', 'email_verified_at', 'token')
        ->where(Database::expr()::eq('username', $username))
        ->fetch();

        if ($dbData === false || count($dbData) === 0) {
            return Lang::get('account-not-found');
        } elseif (!password_verify($password, $dbData['password'])) {
            return Lang::get('incorrect-password');
        } elseif (is_null($dbData['email_verified_at'])) {
            return Lang::get('email-not-verified');
        } else {
            Session::set('token', $dbData['token']);
            if ($keepSession) {
                Session::changeSessionLifetime($_ENV['SESSION_KEEP_CONNECT']);
            }
            return true;
        }
    }

    public static function regexPassword(string $password, string $confim)
    {
        if (strlen($password) < 10) return Lang::get('password-length');
        elseif ($password !== $confim) return Lang::get('not-same-password');
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
        $usernameFetch = Database::queryBuilder('users')
        ->select('username')
        ->where(Database::expr()::eq('username', $username))
        ->fetch();

        $emailFetch = Database::queryBuilder('users')
        ->select('email')
        ->where(Database::expr()::eq('email', $email))
        ->fetch();

        if ($usernameFetch !== false && count($usernameFetch) !== 0) {
            return Lang::get('existing-username');
        } elseif ($emailFetch !== false && count($emailFetch) !== 0) {
            return Lang::get('existing-email');
        }

        $passCheck = self::regexPassword($password, $passwordConfirm);
        if ($passCheck !== true) return $passCheck;

        $emailCode = bin2hex(random_bytes(10));

        Database::queryBuilder('users')
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
        $dbPass = Database::queryBuilder('users')
            ->select('password')
            ->where(Database::expr()::eq('token', $token ?? $tokenPassed))
            ->fetch();
        if (password_verify($password, $dbPass['password'])) return true;
        else return false;
    }

    public static function updateToken(string $password, ?string $token): string
    {
        if (!self::checkPassword($password, $token)) return Lang::get('incorrect-password');
        
        $newToken = bin2hex(random_bytes(30));

        Database::queryBuilder('users')
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
        if (!self::checkPassword($password, $token)) return Lang::get('incorrect-password');

        $emailCode = bin2hex(random_bytes(10));

        Database::queryBuilder('users')
            ->update([
                'mail' => $newMail,
                'email_verification_code' => $emailCode
            ])
            ->where(Database::expr()::eq('token', Session::get('token')))
            ->execute();

        MailTemplate::sendEmailVerification($newMail, $emailCode);
        return true;
    }

    public static function verifyEmailCode(string $code): bool
    {
        Database::queryBuilder('users')
            ->update([
                'email_verification_code' => null,
            ])
            ->where(Database::expr()::eq('email_verification_code', $code))
            ->execute();

        return true;
    }

    public static function updatePassword(
        string $currentPassword,
        string $newPassword,
        string $confirm,
        ?string $token
    ) {
        if (!self::checkPassword($currentPassword, $token)) return Lang::get('incorrect-password');

        $passCheck = self::regexPassword($newPassword, $confirm);
        if ($passCheck !== true) return $passCheck;

        Database::queryBuilder('users')
            ->update([
                'password' => password_hash($newPassword, constant($_ENV['HASH_ALGO']))
            ])
            ->where(Database::expr()::eq('token', Session::get('token')))
            ->execute();
        
        return true;
    }
}
