<?php
namespace App\Repository;

use App\Entity\InsurancePackage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<InsurancePackage>
 */
class InsurancePackageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InsurancePackage::class);
    }

    /**
     * Search, filter and sort active packages.
     */
    public function search(
        ?string $query,
        ?string $assetType,
        string $orderBy = 'p.name',
        string $dir = 'ASC'
    ): array {
        $allowedOrder = ['p.name', 'p.basePrice', 'p.durationMonths', 'p.riskMultiplier'];
        if (!in_array($orderBy, $allowedOrder, true)) {
            $orderBy = 'p.name';
        }
        $dir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';

        $qb = $this->createQueryBuilder('p')
            ->where('p.isActive = true')
            ->orderBy($orderBy, $dir);

        if ($query !== null && $query !== '') {
            $qb->andWhere('p.name LIKE :q OR p.description LIKE :q OR p.coverageDetails LIKE :q')
               ->setParameter('q', '%' . $query . '%');
        }

        if ($assetType !== null && $assetType !== '') {
            $qb->andWhere('p.assetType = :assetType')
               ->setParameter('assetType', $assetType);
        }

        return $qb->getQuery()->getResult();
    }
}
