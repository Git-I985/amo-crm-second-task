<?php

//namespace App\Controller;
//
//use AmoCRM\Exceptions\InvalidArgumentException;
//use App\Services\AmoManager;
//use App\Services\Entities\Contact;
//use ReflectionClass;
//use ReflectionNamedType;
//use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
//use Symfony\Component\Config\Definition\Exception\Exception;
//use Symfony\Component\Config\Definition\Exception\InvalidDefinitionException;
//use Symfony\Component\Config\Resource\ReflectionClassResource;
//use Symfony\Component\HttpFoundation\Request;
//use Symfony\Component\HttpFoundation\Response;
//use Symfony\Component\Routing\Annotation\Route;
//
//class CustomFieldsController extends AbstractController
//{
//    private AmoManager $amoManager;
//
//    public function __construct(AmoManager $amoManager)
//    {
//        $this->amoManager = $amoManager;
//    }
//
//    public function getEntities(): array
//    {
//        $methods  = (new ReflectionClass($this->amoManager->apiClient))->getMethods();
//        $entities = [];
//
//        foreach ($methods as $method) {
//            if ($method->hasReturnType()) {
//                $returnType          = $method->getReturnType()->getName();
//                $namespaceComponents = explode('\\', $returnType);
//                if (in_array('EntitiesServices', $namespaceComponents, true)) {
//                    $entities[] = end($namespaceComponents);
//                }
//            }
//        }
//
//        return $entities;
//    }
//
//    public function getCustomFieldsTableHeader(array $fields): array
//    {
//        $header = array_keys(end($fields));
//        foreach ($fields as $field) {
//            if (count(array_keys($field)) > count($header)) {
//                $header = array_keys($field);
//            }
//        }
//
//        return $header;
//    }
//
//    /**
//     * @Route("/custom_fields", methods={"GET"})
//     */
//    public function index(): Response
//    {
//        return $this->render(
//            'custom_fields/index.html.twig',
//            [
//                "entities" => $this->getEntities(),
//            ]
//        );
//    }
//
//    /**
//     * @Route("/custom_fields", name="custom_fields_selected_entity", methods={"POST"})
//     */
//    public function detail(Request $request): Response
//    {
//        $entity = $request->request->get('entity');
//
//        try {
//            $fields = $this->amoManager->apiClient->customFields($entity)->get();
//        } catch (\Exception $e) {
//            $request->getSession()
//                    ->getFlashBag()
//                    ->add('error', $e->getMessage());
//
//            return $this->redirect($request->headers->get('referer'));
//        }
//
//        dump($fields);
//
//        return $this->render(
//            "custom_fields/detail.html.twig",
//            [
//                "entity"  => $entity,
//                "columns" => $this->getCustomFieldsTableHeader($fields->toArray()),
//                "items"   => $fields->toArray(),
//            ]
//        );
//    }
//}
