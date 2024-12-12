<?php

/**
 * PHPMailer - PHP email creation and transport class.
 * PHP Version 5.5.
 *
 * @see       https://github.com/PHPMailer/PHPMailer/ The PHPMailer GitHub project
 *
 * @author    Marcus Bointon (Synchro/coolbru) <phpmailer@synchromedia.co.uk>
 * @author    Jim Jagielski (jimjag) <jimjag@gmail.com>
 * @author    Andy Prevost (codeworxtech) <codeworxtech@users.sourceforge.net>
 * @author    Brent R. Matzelle (original founder)
 * @copyright 2012 - 2020 Marcus Bointon
 * @copyright 2010 - 2012 Jim Jagielski
 * @copyright 2004 - 2009 Andy Prevost
 * @license   http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 * @note      This program is distributed in the hope that it will be useful - WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.
 */

namespace AcyMailerPhp;


/**
 * OAuth - OAuth2 authentication wrapper class.
 * Uses the oauth2-client package from the League of Extraordinary Packages.
 *
 * @see     http://oauth2-client.thephpleague.com
 *
 * @author  Marcus Bointon (Synchro/coolbru) <phpmailer@synchromedia.co.uk>
 */
class OAuth implements OAuthTokenProvider
{
    const HOST_AUTH_2 = ['smtp.gmail.com', 'smtp-mail.outlook.com', 'smtp.office365.com'];

    /**
     * An instance of the League OAuth Client Provider.
     *
     * @var AbstractProvider
     */
    protected $provider;

    /**
     * The current OAuth access token.
     *
     * @var AccessToken
     */
    protected $oauthToken;

    /**
     * The user's email address, usually used as the login ID
     * and also the from address when sending email.
     *
     * @var string
     */
    protected $oauthUserEmail = '';

    /**
     * The client secret, generated in the app definition of the service you're connecting to.
     *
     * @var string
     */
    protected $oauthClientSecret = '';

    /**
     * The client ID, generated in the app definition of the service you're connecting to.
     *
     * @var string
     */
    protected $oauthClientId = '';

    /**
     * The refresh token, used to obtain new AccessTokens.
     *
     * @var string
     */
    protected $oauthRefreshToken = '';

    protected $expiredIn = '';

    protected $host = '';

    /**
     * OAuth constructor.
     *
     * @param array $options Associative array containing
     *                       `provider`, `userName`, `clientSecret`, `clientId` and `refreshToken` elements
     */
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

    /**
     * Get a new AccessToken.
     *
     * @return AccessToken
     */
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

        $data = ['client_id' => $this->oauthClientId, 'grant_type' => 'refresh_token', 'refresh_token' => $this->oauthRefreshToken, 'client_secret' => $this->oauthClientSecret];

        $response = acym_makeCurlCall(
            $url,
            ['data' => $data, 'method' => 'POST']
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

    /**
     * Generate a base64-encoded OAuth token.
     *
     * @return string
     */
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
