<?php

namespace App\Services\Entities;


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

class Contact extends ContactModel
{

    public function __construct(string $name, string $lastname)
    {
        $this->setName($name . ' ' . $lastname);
    }

    public function setPhone(string $phone, string $enum = 'WORK'): Contact
    {
        $this->addCustomFieldValue(
            (new MultitextCustomFieldValuesModel())
                ->setFieldCode('PHONE')
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

    public function setEmail(string $email, string $enum = 'WORK'): Contact
    {
        $this->addCustomFieldValue(
            (new MultitextCustomFieldValuesModel())
                ->setFieldCode('EMAIL')
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

            return $this;
        }

        $customFieldsValues = new CustomFieldsValuesCollection();
        $customFieldsValues->add($model);
        $this->setCustomFieldsValues($customFieldsValues);

        return $this;
    }

}