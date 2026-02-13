<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /** @return Order[] */
    public function findExpiredPendingOrders(\DateTimeImmutable $now): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.status = :status')
            ->andWhere('o.reservedUntil <= :now')
            ->setParameter('status', Order::STATUS_PENDING)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }
}
