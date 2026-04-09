<?php
namespace App\Repository;

use App\Entity\InsuredAsset;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<InsuredAsset>
 */
class InsuredAssetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InsuredAsset::class);
    }

    /**
     * Search, filter and sort assets for a given user.
     */
    public function search(
        UserInterface $user,
        ?string $query,
        ?string $type,
        string $orderBy = 'a.createdAt',
        string $dir = 'DESC'
    ): array {
        $allowedOrder = ['a.createdAt', 'a.reference', 'a.brand', 'a.declaredValue', 'a.manufactureDate'];
        if (!in_array($orderBy, $allowedOrder, true)) {
            $orderBy = 'a.createdAt';
        }
        $dir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';

        $qb = $this->createQueryBuilder('a')
            ->where('a.user = :user')
            ->setParameter('user', $user)
            ->orderBy($orderBy, $dir);

        if ($query !== null && $query !== '') {
            $qb->andWhere('a.reference LIKE :q OR a.brand LIKE :q OR a.description LIKE :q OR a.location LIKE :q')
               ->setParameter('q', '%' . $query . '%');
        }

        if ($type !== null && $type !== '') {
            $qb->andWhere('a.type = :type')
               ->setParameter('type', $type);
        }

        return $qb->getQuery()->getResult();
    }
}
