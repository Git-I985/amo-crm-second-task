<?php


namespace App\Entities;


use AmoCRM\Models\LeadModel;

class Lead extends LeadModel
{
    public function __construct(string $name)
    {
        $this->setName($name);
    }

}