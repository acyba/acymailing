<?php

namespace AcyMailerPhp;


class OAuth implements OAuthTokenProvider
{
    const HOST_AUTH_2 = ['smtp.gmail.com', 'smtp-mail.outlook.com', 'smtp.office365.com'];

    protected $oauthToken;

    protected $oauthUserEmail = '';

    protected $oauthClientSecret = '';

    protected $oauthClientId = '';

    protected $oauthRefreshToken = '';

    protected $expiredIn = '';

    protected $host = '';

    public function __construct($options)
    {
        $this->oauthUserEmail = $options['userName'];
        $this->oauthClientSecret = $options['clientSecret'];
        $this->oauthClientId = $options['clientId'];
        $this->oauthToken = $options['oauthToken'];
        $this->oauthRefreshToken = $options['refreshToken'];
        $this->expiredIn = $options['expiredIn'];
        $this->host = strtolower($options['host']);
    }

    protected function getToken()
    {
        $config = acym_config();
        if ($this->host === 'smtp.gmail.com') {
            $url = 'https://oauth2.googleapis.com/token';
        } else {
            $tenant = $config->get('smtp_tenant');
            if (empty($tenant)) {
                acym_enqueueMessage(acym_translation('ACYM_TENANT_FIELD_IS_MISSING'), 'error');
            }
            $url = 'https://login.microsoftonline.com/'.$tenant.'/oauth2/v2.0/token';
        }

        $response = acym_makeCurlCall(
            $url,
            ['client_id' => $this->oauthClientId, 'grant_type' => 'refresh_token', 'refresh_token' => $this->oauthRefreshToken, 'client_secret' => $this->oauthClientSecret]
        );

        if (empty($response['error'])) {
            $token = $response['token_type'].' '.$response['access_token'];
            $expireIn = time() + (int)$response['expires_in'];
            $config->save(['smtp_token' => $token, 'smtp_token_expireIn' => $expireIn]);

            return $token;
        } else {
            acym_enqueueMessage(acym_translationSprintf('ACYM_OAUTH_REFRESH_TOKEN_ERROR', $response['error']), 'error');
        }
    }

    public function getOauth64()
    {
        //Get a new token if it's not available or has expired
        if ($this->tokenHasExpired() || empty($this->oauthToken)) {
            $this->oauthToken = $this->getToken();
        }

        return base64_encode(
            'user='.
            $this->oauthUserEmail.
            "\001auth=".
            $this->oauthToken.
            "\001\001"
        );
    }

    private function tokenHasExpired()
    {
        $expired = false;
        if ($this->expiredIn < time()) {
            $expired = true;
        }

        return $expired;
    }

    static function hostRequireOauth($host, $connectionType)
    {
        $host = strtolower($host);

        if (!in_array($host, self::HOST_AUTH_2)) {
            return false;
        }

        return $connectionType !== 'password';
    }
}
