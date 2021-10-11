<?php

namespace App\Repository;

use App\Entity\Conversation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Conversation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Conversation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Conversation[]    findAll()
 * @method Conversation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    /**
     * Get all conversations of a user being involved in as recipient or author.
     *
     * @param UserInterface $user : instance of the user.
     *
     * @return array
     */
    public function getAllByRecipient($user)
    {
        $qb = $this->createQueryBuilder('c')
            ->join('c.recipients', 'r')
            ->join('c.lastMessage', 'lm')
            ->addSelect('lm')
            ->where('r.id = :user')
            ->setParameter('user', $user)
            ->orderBy('c.created', 'DESC');

        return $qb->getQuery()->getResult();
    }

    public function getAllUnreadByRecipient($user)
    {
        $qb = $this->createQueryBuilder('c')
            ->join('c.recipients', 'r')
            ->join('c.firstMessage', 'lm')
            ->leftJoin('c.conversationMessageReads', 'cmr',  'WITH', 'cmr.user = :user')
            ->addSelect('lm')
            ->addSelect('cmr')
            ->where('r.id = :user')
            ->setParameter('user', $user)
            ->orderBy('c.created', 'DESC');

        return $qb->getQuery()->getResult();
    }

    public function getOneById($cid)
    {
        $qb = $this->createQueryBuilder('c')
            ->join('c.recipients', 'r')
            ->join('c.messages', 'm')
            ->join('m.author', 'a')
            ->addSelect('r')
            ->addSelect('m')
            ->addSelect('a')
            ->where('c.id = :cid')
            ->setParameter('cid', $cid)
            ->orderBy('m.created', 'ASC');

        return $qb->getQuery()->getOneOrNullResult();
    }

    // /**
    //  * @return Conversation[] Returns an array of Conversation objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Conversation
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
