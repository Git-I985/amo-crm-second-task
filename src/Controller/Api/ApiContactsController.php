<?php

// TODO: инкапсуляция линки контакта с сделкой
// TODO: повесить задачу на сделку
// TODO: линка товара с сделкой

namespace App\Controller\Api;

use AmoCRM\Collections\LinksCollection;
use AmoCRM\Exceptions\AmoCRMApiNoContentException;
use AmoCRM\Filters\ContactsFilter;
use AmoCRM\Filters\LeadsFilter;
use App\Services\AmoManager;
use App\Services\Entities\Contact;
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
            $contacts = $this->amoManager->apiClient->contacts()->get($contactsFilter);
        } catch (AmoCRMApiNoContentException $e) {
        }

        // Если такой контакт уже есть, смотрим его сделки
        if (isset($contacts)) {
            $contact = $contacts->all()[0];

            $lead = $this
                ->amoManager
                ->apiClient
                ->leads()
                ->get((new LeadsFilter())->setQuery($params['tel']))->all()[0];

            $session = new Session();
            $session->start();


            if ($lead->getStatusId() === 142) {
                $session->getFlashBag()->add('error', 'Пользователь с такими номером уже существует, сделка в успешном статусе');
            } else {
                $session->getFlashBag()->add('error', 'Пользователь с такими номером уже существует');
            }

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

        // вешаем сделку
        $lead = $this->amoManager->apiClient->leads()->getOne(1817633);
        $this->amoManager->apiClient->contacts()->link($contact, (new LinksCollection())->add($lead));

        // Делаем ответственным случайного ююзера
        $users  = $this->amoManager->apiClient->users()->get()->all();
        $random = array_rand($users);
        $user   = $users[$random];
        $lead->setResponsibleUserId($user->id);

        $session = new Session();
        $session->start();

        $session->getFlashBag()->add('success', 'Пользователь успешно добавлен, сделка привязана');

        return $this->redirect($this->generateUrl('index'));
    }
}
