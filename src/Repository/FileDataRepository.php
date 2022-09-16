<?php

namespace App\Repository;

use App\Entity\FileData;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method FileData|null find($id, $lockMode = null, $lockVersion = null)
 * @method FileData|null findOneBy(array $criteria, array $orderBy = null)
 * @method FileData[]    findAll()
 * @method FileData[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FileDataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FileData::class);
    }
    
    public function findUserByInvoiceId($id){
        $data = $this->createQueryBuilder('q')
        ->where('q.invoiceId = ?1')
        ->setParameter('1', $id)
        ->getQuery()
        ->getOneOrNullResult();
        return $data;

    }
    
}
