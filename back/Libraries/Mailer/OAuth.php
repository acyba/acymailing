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
 * @license   https://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU Lesser General Public License
 * @note      This program is distributed in the hope that it will be useful - WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.
 */

namespace AcyMailerPhp;

/**
 * OAuth - OAuth2 authentication wrapper class.
 * Uses the oauth2-client package from the League of Extraordinary Packages.
 *
 * @see     https://oauth2-client.thephpleague.com
 *
 * @author  Marcus Bointon (Synchro/coolbru) <phpmailer@synchromedia.co.uk>
 */
class OAuth implements OAuthTokenProvider
{
    /**
     * The sending method used.
     */
    protected string $sendingMethod;

    /**
     * The URL called to generate an access token from a refresh token.
     */
    protected string $tokenGenerationUrl;

    /**
     * The current OAuth access token, prefixed with "Bearer ".
     */
    protected string $oauthToken;

    /**
     * The user's email address, usually used as the login ID
     * and also the from address when sending email.
     */
    protected string $oauthUserEmail;

    /**
     * The client secret, generated in the app definition of the service you're connecting to.
     */
    protected string $oauthClientSecret;

    /**
     * The client ID, generated in the app definition of the service you're connecting to.
     */
    protected string $oauthClientId;

    /**
     * The refresh token, used to obtain new AccessTokens.
     */
    protected string $oauthRefreshToken;

    /**
     * The timestamp at which the current token expires.
     */
    protected int $oauthTokenExpiration;

    public function __construct(array $options)
    {
        $this->sendingMethod = $options['sendingMethod'] ?? '';
        $this->tokenGenerationUrl = $options['tokenGenerationUrl'] ?? '';

        $this->oauthUserEmail = $options['userName'] ?? '';
        $this->oauthClientId = $options['clientId'] ?? '';
        $this->oauthClientSecret = $options['clientSecret'] ?? '';
        $this->oauthRefreshToken = $options['refreshToken'] ?? '';
        $this->oauthToken = $options['oauthToken'] ?? '';
        $this->oauthTokenExpiration = $options['oauthTokenExpiration'] ? (int)$options['oauthTokenExpiration'] : 0;
    }

    /**
     * Generate a base64-encoded OAuth token.
     */
    public function getOauth64(): string
    {
        $this->oauthToken = $this->getToken();

        if (empty($this->oauthToken)) {
            return '';
        }

        return base64_encode('user='.$this->oauthUserEmail."\001auth=Bearer ".$this->oauthToken."\001\001");
    }

    /**
     * Custom method to avoid having a million vendors in the extension zip file.
     */
    public function getToken(): string
    {
        // Get a new token only if it's not available or has expired
        if (!empty($this->oauthToken) && $this->oauthTokenExpiration > time()) {
            return $this->oauthToken;
        }

        $response = acym_makeCurlCall(
            $this->tokenGenerationUrl,
            [
                'data' => [
                    'client_id' => $this->oauthClientId,
                    'client_secret' => $this->oauthClientSecret,
                    'refresh_token' => $this->oauthRefreshToken,
                    'grant_type' => 'refresh_token',
                ],
                'method' => 'POST',
            ]
        );

        if (!empty($response['token_type']) && !empty($response['access_token'])) {
            $config = acym_config();
            $config->save(
                [
                    $this->sendingMethod.'_access_token' => $response['access_token'],
                    $this->sendingMethod.'_access_token_expiration' => time() + (int)$response['expires_in'],
                ]
            );
        } else {
            acym_logError('Response from OAuth refresh token call: '.json_encode($response), 'oauth', 100);

            $notification = [
                'name' => 'oauth_refresh_token_error',
                'removable' => 1,
            ];
            acym_enqueueMessage(acym_translationSprintf('ACYM_OAUTH_REFRESH_TOKEN_ERROR', $response['error'] ?? ''), 'error', true, [$notification]);
        }

        return $response['access_token'] ?? '';
    }
}
