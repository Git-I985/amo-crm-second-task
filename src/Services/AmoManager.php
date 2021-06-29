<?php

namespace App\Services;


use AmoCRM\Client\AmoCRMApiClient;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Creates instance of AmoCRMApiClient and wrap some functionality
 *
 * @package App\Services
 * @author Edward Konovalov
 */
class AmoManager
{
    public AmoCRMApiClient $apiClient;

    public function __construct(AmoAuthBuilderDirector $director, KernelInterface $kernel)
    {
        $director->setTokenFile($kernel->getProjectDir() . DIRECTORY_SEPARATOR . 'token.json');
        $this->apiClient = $director
            ->buildAuthentication()
            ->getAuthenticatedClient();
    }

}