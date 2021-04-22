<?php


namespace App\Repository;

use App\Entity\PostalVotingRegistration;
use Doctrine\ORM\EntityRepository;

class PostalVotingRegistrationRepository extends EntityRepository
{

    public function getConfirmedAndNotVerified(): iterable
    {
        $qb = $this->createQueryBuilder('r')
            ->select('r')
            ->andWhere('r.confirmation_date IS NOT NULL')
            ->andWhere('r.verified = false')
            ->andWhere('r.unwarranted = false');

        return $qb->getQuery()->toIterable();
    }

    public function findByMail(string $email): ?PostalVotingRegistration
    {
        return $this->findOneBy([
            'email' => $email
        ]);
    }
}