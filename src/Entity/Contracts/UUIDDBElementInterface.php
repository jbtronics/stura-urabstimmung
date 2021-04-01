<?php


namespace App\Entity\Contracts;


use Symfony\Component\Uid\Uuid;

interface UUIDDBElementInterface
{
    public function getId(): ?Uuid;
}