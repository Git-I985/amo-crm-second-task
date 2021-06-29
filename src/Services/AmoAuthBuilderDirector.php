<?php


namespace App\Services;

use AmoCRM\Client\AmoCRMApiClient;

/**
 * Class AmoAuthBuilderDirector управляет созданием экземпляра AmoCRMApiClient классом AmoAuthBuilder
 *
 * @package App\Services
 */
class AmoAuthBuilderDirector
{
    private AmoAuthBuilder $builder;

    private string $tokenFile;

    public function __construct(AmoAuthBuilder $builder)
    {
        $this->builder = $builder;
    }

    public function setTokenFile(string $filePath): void
    {
        $this->tokenFile = $filePath;
    }

    public function buildAuthentication(): AmoAuthBuilderDirector
    {
        $this->builder->init();
        $this->builder->includeTokenFromFile($this->tokenFile);
        return $this;
    }

    public function getAuthenticatedClient(): AmoCRMApiClient
    {
        return $this->builder->getClient();
    }
}