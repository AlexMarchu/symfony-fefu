<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\House;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<House>
 */
class HouseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, House::class);
    }

    /**
     * @return array<House>
     */
    public function findAllAvailableHouses(): array
    {
        return $this->createQueryBuilder('h')
            ->leftJoin(Booking::class, 'b', 'WITH', 'b.house = h.id')
            ->where('b.id IS NULL OR b.status != :activeStatus')
            ->setParameter('activeStatus', 'active')
            ->getQuery()
            ->getResult()
        ;
    }

    public function isHouseAvailable(int $houseId): bool
    {
        $booking = $this->getEntityManager()
            ->getRepository(Booking::class)
            ->createQueryBuilder('b')
            ->where('b.house = :houseId')
            ->andWhere('b.status = :activeStatus')
            ->setParameter('houseId', $houseId)
            ->setParameter('activeStatus', 'active')
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return null === $booking;
    }

    public function save(House $house, bool $flush = true)
    {
        $this->getEntityManager()->persist($house);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(House $house, bool $flush = true): void
    {
        $this->getEntityManager()->remove($house);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
