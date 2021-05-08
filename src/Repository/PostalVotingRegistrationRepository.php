<?php


namespace App\Repository;

use App\Entity\PostalVotingRegistration;
use Doctrine\ORM\AbstractQuery;
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

    public function findByMailOrStudentNumber(string $email_or_student_number): ?PostalVotingRegistration
    {
        $qb = $this->createQueryBuilder('r')
            ->select('r')
            ->Where('r.student_number = :student_number')
            ->orWhere('r.email = :email')
            ->setParameters([
                'student_number' => $email_or_student_number,
                'email' => $email_or_student_number
            ]);

        return $qb->getQuery()->getOneOrNullResult();
    }
}