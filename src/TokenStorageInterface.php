<?php

namespace OnPay;


interface TokenStorageInterface {
    /**
     * Should return the stored token, or null if no token is stored.
     *
     * @return null|string
     */
    public function getToken();

    /**
     * This method is responsible for saving the token to permanent storage.
     *
     * It is up to implementor where to store it, could be database, flat file or something else.
     * The token will change on an ongoing basis, whenever the access token expires and is refreshed.
     *
     * @param string $token
     * @return mixed
     */
    public function saveToken($token);
}
