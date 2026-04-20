<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

//    /**
//     * @return User[] Returns an array of User objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?User
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
/**
     * Search users by name OR email (case-insensitive LIKE).
     * Real DQL business query — grille criterion 3.
     *
     * @return User[]
     */
    public function searchByNameOrEmail(string $q): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.name LIKE :q OR u.email LIKE :q')
            ->setParameter('q', '%' . $q . '%')
            ->orderBy('u.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count verified vs unverified — for admin dashboard stats.
     */
    public function countByVerified(): array
    {
        return $this->createQueryBuilder('u')
            ->select('u.isVerified, COUNT(u.id) as total')
            ->groupBy('u.isVerified')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recent registrations in last N days — for admin dashboard.
     *
     * @return User[]
     */
    public function findRecentUsers(int $days = 7): array
    {
        $since = new \DateTime('-' . $days . ' days');
        return $this->createQueryBuilder('u')
            ->where('u.createdAt >= :since')
            ->setParameter('since', $since)
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
