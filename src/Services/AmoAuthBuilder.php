<?php


namespace App\Services;


use AmoCRM\Client\AmoCRMApiClient;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;

/**
 * Class AmoAuthBuilder создает экземпляр AmoCRMApiClient и постепенно настраивает его в своих методах
 *
 * @package App\Services
 */
class AmoAuthBuilder
{

    private AmoCRMApiClient $client;

    public function init(): void
    {
        $this->client = new AmoCRMApiClient(...array_values($this->getApiClientRequisites()));
    }

    public function includeTokenFromFile(string $filePath): void
    {
        $accessToken = $this->getTokenFromJsonFile($filePath);
        $accessToken = $this->tokenArrayToAccessToken($accessToken);


        $this->client->setAccessToken($accessToken)
                     ->setAccountBaseDomain($accessToken->getValues()['baseDomain'])
                     ->onAccessTokenRefresh(
                         function (AccessTokenInterface $accessToken, string $baseDomain) use ($filePath) {
                             $this->saveTokenToJsonFile(
                                 [
                                     'accessToken'  => $accessToken->getToken(),
                                     'refreshToken' => $accessToken->getRefreshToken(),
                                     'expires'      => $accessToken->getExpires(),
                                     'baseDomain'   => $baseDomain,
                                 ],
                                 $filePath
                             );
                         }
                     );
    }

    protected function getTokenFromJsonFile(string $filePath): array
    {
        return json_decode(
            file_get_contents($filePath),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }

    protected function saveTokenToJsonFile(array $accessToken, string $filePath): void
    {
        file_put_contents(
            $filePath,
            json_encode(
                [
                    'accessToken'  => $accessToken['accessToken'],
                    'expires'      => $accessToken['expires'],
                    'refreshToken' => $accessToken['refreshToken'],
                    'baseDomain'   => $accessToken['baseDomain'],
                ],
                JSON_THROW_ON_ERROR
            )
        );
    }

    protected function getApiClientRequisites(): array
    {
        return [
            "id"       => $_ENV['CLIENT_ID'],
            "secret"   => $_ENV['CLIENT_SECRET'],
            "redirect" => $_ENV['CLIENT_REDIRECT_URI'],
        ];
    }

    protected function tokenArrayToAccessToken(array $tokenArray): AccessToken
    {
        return new AccessToken(
            [
                'access_token'  => $tokenArray['accessToken'],
                'refresh_token' => $tokenArray['refreshToken'],
                'expires'       => $tokenArray['expires'],
                'baseDomain'    => $tokenArray['baseDomain'],
            ]
        );
    }

    public function getClient(): AmoCRMApiClient
    {
        return $this->client;
    }
}