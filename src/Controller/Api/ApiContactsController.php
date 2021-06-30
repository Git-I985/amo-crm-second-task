<?php

namespace App\Controller\Api;

use AmoCRM\Collections\LinksCollection;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Exceptions\AmoCRMApiNoContentException;
use AmoCRM\Exceptions\AmoCRMMissedTokenException;
use AmoCRM\Exceptions\AmoCRMoAuthApiException;
use AmoCRM\Exceptions\InvalidArgumentException;
use AmoCRM\Filters\CatalogsFilter;
use AmoCRM\Filters\ContactsFilter;
use AmoCRM\Filters\LeadsFilter;
use AmoCRM\Models\LeadModel;
use App\Entities\Contact;
use App\Entities\Lead;
use App\Services\AmoManager;
use Exception;
use Rakit\Validation\RuleNotFoundException;
use Rakit\Validation\Validation;
use Rakit\Validation\Validator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api", name="api_")
 */
class ApiContactsController extends AbstractController
{

    private AmoManager $amoManager;

    public function __construct(AmoManager $amoManager)
    {
        $this->amoManager = $amoManager;
    }

    /**
     * @throws RuleNotFoundException
     */
    public function validate(array $data): Validation
    {
        $validator = new Validator(
            [
                "required" => ":attribute должен быть указан",
                "name"     => "Неккоректное имя пользователя",
                "lastname" => "Неккоректная фамилия пользователя",
                "email"    => "Некорректный email адрес",
                "age"      => "Некорректный возраст",
                "tel"      => "Некорректный номер телефона",
                "gender"   => "Указан некорректный пол",
            ]
        );

        return $validator->validate(
            $data,
            [
                "name"     => "required|regex:/^[a-zа-я]+$/ui",
                "lastname" => "required|regex:/^[a-zа-я]+$/ui",
                "age"      => "required|integer|between:18,125",
                "tel"      => [
                    "required",
                    $validator('regex', "/^(\s*)?(\+)?([- _():=+]?\d[- _():=+]?){10,14}(\s*)?$/"),
                ],
                "email"    => "required|email",
                "gender"   => [
                    "required",
                    $validator("regex", "/^(Мужской|Женский)$/u"),
                ],
            ]
        );
    }

    /**
     * @Route("/contacts", name="contacts", methods={"POST"})
     *
     * @param  Request  $requestf
     *
     * @return Response
     * @throws AmoCRMApiException
     * @throws AmoCRMApiNoContentException
     * @throws AmoCRMMissedTokenException
     * @throws AmoCRMoAuthApiException
     * @throws InvalidArgumentException|RuleNotFoundException
     */
    public function create(Request $request): Response
    {
        $params     = $request->request->all();
        $validation = $this->validate($params);

        // валидируем входящие данные
        if ($validation->errors()->count()) {
            $errors  = $validation->errors()->all();
            $session = new Session();
            $session->start();

            foreach ($errors as $error) {
                $session->getFlashBag()->add('error', $error);
            }

            return $this->redirect($request->headers->get('referer'));
        }

        try {
            $contactsFilter = new ContactsFilter();
            $contactsFilter->setQuery($params['tel']);
            $contacts = $this->amoManager->apiClient->contacts()->get();
        } catch (AmoCRMApiException $e) {
        }

        // Если такой контакт уже есть, смотрим его сделки
        if (isset($contacts)) {
            try {
                $leads = $this
                    ->amoManager
                    ->apiClient
                    ->leads()
                    ->get((new LeadsFilter())->setQuery($params['tel']));
            } catch (AmoCRMApiException $e) {
            }

            if (isset($leads) && $leads->all()[0]->getStatusId() === LeadModel::WON_STATUS_ID) {
                $msg = 'Пользователь с такими номером уже существует, сделка в успешном статусе';
            } else {
                $msg = 'Пользователь с такими номером уже существует';
            }

            $session = new Session();
            $session->start();

            $session->getFlashBag()->add('error', $msg);

            return $this->redirect($this->generateUrl('index'));
        }

        // создаем контакт с всеми полями
        $contact = (new Contact($params['name'], $params['lastname']))
            ->setPhone($params['tel'])
            ->setEmail($params['email'])
            ->setGender($params['gender'])
            ->setAge($params['age']);

        // добавляем контакт
        $this->amoManager->apiClient->contacts()->addOne($contact);

        // Делаем ответственным случайного ююзера
        $users = $this->amoManager->apiClient->users()->get();

        if (is_null($users)) {
            throw new AmoCRMApiNoContentException(
                'Ошика выбора ответсвенного пользователя  для добавления  к сделке, список пользователей пуст'
            );
        }

        $random = array_rand($users->all());

        // Создаем сделку
        $lead = (new LeadModel())
            ->setName('Сделка')
            ->setResponsibleUserId($users[$random]->getId());

        // Добавляем сделку
        $this->amoManager->apiClient->leads()->addOne($lead);

        // Вешаем сделку к контакту
        $this->amoManager->apiClient->contacts()->link($contact, (new LinksCollection())->add($lead));

        // Получаем список товаров
        try {
            $productsCatalog = $this->amoManager->apiClient->catalogs()->get(
                (new CatalogsFilter())->setType('products')
            );
            $products        = $this->amoManager->apiClient->catalogElements($productsCatalog->first()->getId())->get();
        } catch (Exception $e) {
            $session = new Session();
            $session->start();
            $session->getFlashBag()->add(
                'error',
                'Ошибка добавления товаров к сделке, возможно каталог товаров  отсутсвует или пуст'
            );

            return $this->redirect($request->headers->get('referer'));
        }

        // Привязываем товары к сделке
        $links = new LinksCollection();

        foreach ($products as $product) {
            $links->add($product);
        }

        $this->amoManager->apiClient->leads()->link($lead, $links);

        $session = new Session();
        $session->start();
        $session->getFlashBag()->add('success', 'Пользователь успешно добавлен, сделка привязана');

        return $this->redirect($this->generateUrl('index'));
    }
}
