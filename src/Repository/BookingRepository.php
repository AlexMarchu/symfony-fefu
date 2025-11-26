<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\House;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Booking>
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    public function findByHouse(House $house)
    {
        return $this->createQueryBuilder('b')
            ->where('b.house = :house')
            ->setParameter('house', $house)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findActiveBookingByHouseId(int $houseId): ?Booking
    {
        return $this->createQueryBuilder('b')
            ->where('b.house = :houseId')
            ->andWhere('b.status = :activeStatus')
            ->setParameter('houseId', $houseId)
            ->setParameter('activeStatus', 'active')
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function save(Booking $booking, bool $flush = true): void
    {
        $this->getEntityManager()->persist($booking);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Booking $booking, bool $flush = true): void
    {
        $this->getEntityManager()->remove($booking);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
