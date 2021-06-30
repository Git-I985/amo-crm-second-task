<?php

namespace App\Entities;

use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Models\ContactModel;
use AmoCRM\Models\CustomFieldsValues\BaseCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\MultitextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\NumericCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\RadiobuttonCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\MultitextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\NumericCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\RadiobuttonCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\MultitextCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\NumericCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\RadiobuttonCustomFieldValueModel;
use Rakit\Validation\Validation;
use Rakit\Validation\Validator;

class Contact extends ContactModel
{

    public const FIELD_CODE_PHONE = 'PHONE';
    public const ENUM_WORK = 'WORK';
    public const FIELD_CODE_EMAIL = 'EMAIL';

    public function __construct(array $fields)
    {
        $this->setName($fields['name'] . ' ' . $fields['lastname'])
             ->setPhone($fields['tel'])
             ->setEmail($fields['email'])
             ->setGender($fields['gender'])
             ->setAge($fields['age']);
    }

    public function setPhone(string $phone, string $enum = self::ENUM_WORK): Contact
    {
        $this->addCustomFieldValue(
            (new MultitextCustomFieldValuesModel())
                ->setFieldCode(self::FIELD_CODE_PHONE)
                ->setValues(
                    (new MultitextCustomFieldValueCollection())
                        ->add(
                            (new MultitextCustomFieldValueModel())
                                ->setEnum($enum)
                                ->setValue($phone)
                        )
                )
        );

        return $this;
    }

    public function setEmail(string $email, string $enum = self::ENUM_WORK): Contact
    {
        $this->addCustomFieldValue(
            (new MultitextCustomFieldValuesModel())
                ->setFieldCode(self::FIELD_CODE_EMAIL)
                ->setValues(
                    (new MultitextCustomFieldValueCollection())
                        ->add(
                            (new MultitextCustomFieldValueModel())
                                ->setEnum($enum)
                                ->setValue($email)
                        )
                )
        );

        return $this;
    }

    public function setGender(string $gender): Contact
    {
        $this->addCustomFieldValue(
            (new RadiobuttonCustomFieldValuesModel())
                ->setFieldId(460321)
                ->setValues(
                    (new RadiobuttonCustomFieldValueCollection())
                        ->add(
                            (new RadiobuttonCustomFieldValueModel())
                                ->setValue($gender)
                        )
                )
        );

        return $this;
    }

    public function setAge(int $age): Contact
    {
        $this->addCustomFieldValue(
            (new NumericCustomFieldValuesModel())
                ->setFieldId(460269)
                ->setValues(
                    (new NumericCustomFieldValueCollection())
                        ->add(
                            (new NumericCustomFieldValueModel())
                                ->setValue($age)
                        )
                )
        );

        return $this;
    }

    public function addCustomFieldValue(BaseCustomFieldValuesModel $model): Contact
    {
        $existingCustomFieldsValues = $this->getCustomFieldsValues();

        if ( ! is_null($existingCustomFieldsValues)) {
            $existingCustomFieldsValues->add($model);
        } else {
            $customFieldsValues = new CustomFieldsValuesCollection();
            $customFieldsValues->add($model);
            $this->setCustomFieldsValues($customFieldsValues);
        }

        return $this;
    }


    public static function validate(array $contactFields): Validation
    {
        $validator = new Validator();

        $validator->setMessages(
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
            $contactFields,
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
}