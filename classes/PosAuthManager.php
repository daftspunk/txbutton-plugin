<?php namespace TxButton\App\Classes;

use Cookie;
use Session;
use Request;
use Txbutton\App\Models\UserSetting as UserSettingModel;
use October\Rain\Auth\AuthException;
use Exception;

class PosAuthManager
{
    use \October\Rain\Support\Traits\Singleton;

    protected $user;

    protected $sessionKey = 'txbutton_pos_auth7';

    /**
     * Finds a user by the login value.
     * @param string $login
     */
    public function findUserByLogin($login)
    {
        return UserSettingModel::where('pos_username', $login)->first();
    }

    public function authenticate(array $credentials, $remember = true)
    {
        if (empty($username = array_get($credentials, 'username'))) {
            throw new AuthException('The username attribute is required.');
        }

        if (empty($pin = array_get($credentials, 'pin'))) {
            throw new AuthException('The pin attribute is required.');
        }

        $user = $this->findUserByLogin($username);

        if (!$user) {
            throw new AuthException('A user was not found with the given credentials.');
        }

        if (!hash_equals($user->pos_pin, $pin)) {
            throw new AuthException('Incorrect pin.');
        }

        $this->login($user, $remember);

        return $this->user;
    }

    public function login($user, $remember = true)
    {
        $this->user = $user;

        /*
         * Create session/cookie data to persist the session
         */
        $toPersist = [$user->getKey(), $user->getPosHash()];
        Session::put($this->sessionKey, $toPersist);

        if ($remember) {
            Cookie::queue(Cookie::forever($this->sessionKey, $toPersist));
        }
    }

    /**
     * Logs the current user out.
     */
    public function logout()
    {
        $this->user = null;

        Session::forget($this->sessionKey);
        Cookie::queue(Cookie::forget($this->sessionKey));
    }

    public function check()
    {
        if (is_null($this->user)) {

            /*
             * Check session first, follow by cookie
             */
            if (
                !($userArray = Session::get($this->sessionKey)) &&
                !($userArray = Cookie::get($this->sessionKey))
            ) {
                return false;
            }

            /*
             * Check supplied session/cookie is an array (username, persist code)
             */
            if (!is_array($userArray) || count($userArray) !== 2) {
                return false;
            }

            list($id, $posHash) = $userArray;

            /*
             * Look up user
             */
            $user = UserSettingModel::find($id);

            /*
             * Confirm the persistence code is valid, otherwise reject
             */
            if (!$user || !$user->checkPosHash($posHash)) {
                return false;
            }

            /*
             * Pass
             */
            $this->user = $user;
        }

        /*
         * Check pos user is activated
         */
        if (!($user = $this->getUser()) || !$user->isPosConfigured()) {
            return false;
        }

        return true;
    }

    /**
     * Returns the current user, if any.
     */
    public function getUser()
    {
        if (is_null($this->user)) {
            $this->check();
        }

        return $this->user;
    }
}
