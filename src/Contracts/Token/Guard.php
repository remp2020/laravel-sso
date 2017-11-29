<?php

namespace Remp\LaravelSso\Contracts\Token;

use Remp\LaravelSso\Contracts\JwtException;
use Remp\LaravelSso\Contracts\SsoContract;
use Remp\LaravelSso\Contracts\SsoException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard as AuthGuard;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;

class Guard implements AuthGuard
{
    private $user;

    public function __construct(SsoContract $ssoContract, Request $request)
    {
        $this->ssoContract = $ssoContract;
        $this->request = $request;
        $this->inputKey = 'api_token';
        $this->storageKey = 'api_token';
    }

    public function authenticate()
    {
        throw new JwtException("token guard doesn't support authenticate()");
    }

    /**
     * Determine if the current user is authenticated.
     *
     * @return bool
     */
    public function check()
    {
        return boolval($this->user());
    }

    /**
     * Determine if the current user is a guest.
     *
     * @return bool
     */
    public function guest()
    {
        return $this->user === null;
    }

    /**
     * Get the currently authenticated user.
     * @return Authenticatable|null
     * @throws SsoException
     */
    public function user()
    {
        if (! is_null($this->user)) {
            return $this->user;
        }

        $user = null;
        $token = $this->getTokenForRequest();

        if (!empty($token)) {
            try {
                $valid = $this->ssoContract->apiToken($token);
                if ($valid) {
                    $user = new User;
                    $user->token = $token;
                    $user->scopes = [];
                }
            } catch (SsoException $e) {
                return null;
            }

        }

        return $this->user = $user;
    }

    /**
     * Get the ID for the currently authenticated user.
     *
     * @return int|null
     */
    public function id()
    {
        return $this->user()->getAuthIdentifier();
    }

    /**
     * Validate a user's credentials.
     *
     * @param  array $credentials
     * @return bool
     * @throws JwtException
     */
    public function validate(array $credentials = [])
    {
        throw new JwtException("token guard doesn't support credentials validation");
    }

    /**
     * Set the current user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @return void
     */
    public function setUser(Authenticatable $user)
    {
        $this->user = $user;
    }

    public function getTokenForRequest()
    {
        $token = $this->request->query($this->inputKey);

        if (empty($token)) {
            $token = $this->request->input($this->inputKey);
        }

        if (empty($token)) {
            $token = $this->request->bearerToken();
        }

        if (empty($token)) {
            $token = $this->request->getPassword();
        }

        return $token;
    }
}