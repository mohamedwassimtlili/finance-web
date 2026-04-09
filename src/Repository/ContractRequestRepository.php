<?php
namespace App\Repository;

use App\Entity\ContractRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<ContractRequest>
 */
class ContractRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContractRequest::class);
    }

    /**
     * Search, filter and sort contract requests for a given user.
     */
    public function search(
        UserInterface $user,
        ?string $query,
        ?string $status,
        string $orderBy = 'r.createdAt',
        string $dir = 'DESC'
    ): array {
        $allowedOrder = ['r.createdAt', 'r.calculatedPremium', 'r.status'];
        if (!in_array($orderBy, $allowedOrder, true)) {
            $orderBy = 'r.createdAt';
        }
        $dir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';

        $qb = $this->createQueryBuilder('r')
            ->join('r.asset', 'a')
            ->join('r.package', 'p')
            ->where('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy($orderBy, $dir);

        if ($query !== null && $query !== '') {
            $qb->andWhere('a.reference LIKE :q OR p.name LIKE :q')
               ->setParameter('q', '%' . $query . '%');
        }

        if ($status !== null && $status !== '') {
            $qb->andWhere('r.status = :status')
               ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }
}
