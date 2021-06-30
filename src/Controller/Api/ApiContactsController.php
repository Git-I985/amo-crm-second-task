<?php

namespace App\Controller\Api;

use AmoCRM\Collections\LinksCollection;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Exceptions\AmoCRMMissedTokenException;
use AmoCRM\Exceptions\AmoCRMoAuthApiException;
use AmoCRM\Exceptions\InvalidArgumentException;
use AmoCRM\Filters\CatalogsFilter;
use AmoCRM\Filters\ContactsFilter;
use AmoCRM\Filters\LeadsFilter;
use AmoCRM\Models\LeadModel;
use App\Entities\Contact;
use App\Services\AmoManager;
use Exception;
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
    /**
     * @Route("/contacts", name="contacts", methods={"POST"})
     *
     * @param  Request  $request
     * @param  AmoManager  $amoManager
     *
     * @return Response
     * @throws AmoCRMApiException
     * @throws AmoCRMMissedTokenException
     * @throws AmoCRMoAuthApiException
     * @throws InvalidArgumentException
     */
    public function create(Request $request, AmoManager $amoManager): Response
    {
        $contactFields = $request->request->all();
        $validation    = Contact::validate($contactFields);

        $session = new Session();
        $session->start();

        // валидируем входящие данные
        if ($validation->errors()->count()) {
            foreach ($validation->errors()->all() as $error) {
                $session->getFlashBag()->add('error', $error);
            }

            return $this->redirect($request->headers->get('referer'));
        }

        try {
            $contactsFilter = new ContactsFilter();
            $contactsFilter->setQuery($contactFields['tel']);
            $contacts = $amoManager->apiClient->contacts()->get();
        } catch (AmoCRMApiException $e) {
        }

        // Если такой контакт уже есть, смотрим его сделки
        if (isset($contacts)) {

            $msg = 'Пользователь с такими номером уже существует';

            try {
                $filterByContact = (new LeadsFilter())
                    ->setQuery($contactFields['tel']);

                $leads = $amoManager
                    ->apiClient
                    ->leads()
                    ->get($filterByContact);

            } catch (AmoCRMApiException $e) {
            }

            if (isset($leads) && $leads->first()->getStatusId() === LeadModel::WON_STATUS_ID) {
                $msg = 'Пользователь с такими номером уже существует, сделка в успешном статусе';
            }

            $session->getFlashBag()->add('error', $msg);

            return $this->redirect($this->generateUrl('index'));
        }

        // создаем контакт с всеми полями
        $contact = new Contact($contactFields);

        // добавляем контакт
        $amoManager->apiClient->contacts()->addOne($contact);

        // Создаем сделку
        $lead = (new LeadModel())->setName('Сделка');

        // Добавляем сделку
        $amoManager->apiClient->leads()->addOne($lead);

        // Вешаем сделку к контакту
        $amoManager->apiClient->contacts()->link($contact, (new LinksCollection())->add($lead));

        // Получаем список товаров
        try {
            $productsCatalog = $amoManager->apiClient->catalogs()->get(
                (new CatalogsFilter())->setType('products')
            );
            $products        = $amoManager->apiClient->catalogElements($productsCatalog->first()->getId())->get();
        } catch (Exception $e) {
            $session->getFlashBag()->add(
                'error',
                'Ошибка добавления товаров к сделке, возможно каталог товаров пуст или отсутсвует'
            );

            return $this->redirect($request->headers->get('referer'));
        }

        // Привязываем товары к сделке
        $links = new LinksCollection();

        foreach ($products as $product) {
            $links->add($product);
        }

        $amoManager->apiClient->leads()->link($lead, $links);

        $session->getFlashBag()->add('success', 'Пользователь успешно добавлен, сделка привязана');

        return $this->redirect($this->generateUrl('index'));
    }
}
