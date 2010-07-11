<?php

namespace Application\S2bBundle\Entities;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;

/**
 * BundleRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class BundleRepository extends EntityRepository
{
    public function search($query)
    {
        $pattern = '%'.str_replace(' ', '%', $query).'%';

        $qb = $this->createQueryBuilder('b')->orderBy('b.score', 'DESC');
        $qb->where($qb->expr()->orx(
            $qb->expr()->like('b.username', ':username'),
            $qb->expr()->like('b.name', ':name'),
            $qb->expr()->like('b.description', ':description')
        ));
        $qb->setParameters(array('username' => $pattern, 'name' => $pattern, 'description' => $pattern));
        return $qb->getQuery()->execute();
    }

    public function findAllSortedBy($field, $nb = null)
    {
        $qb = $this->createQueryBuilder('b');
        $qb->orderBy('b.'.$field, 'name' === $field ? 'asc' : 'desc');
        $query = $qb->getQuery();
        if(null !== $nb) {
            $query->setMaxResults($nb);
        }

        return $query->execute();
    }

    public function count()
    {
        return $this->_em->createQuery('SELECT COUNT(b.id) FROM Application\S2bBundle\Entities\Bundle b')->getSingleScalarResult();
    }

    public function getLastCommits($nb)
    {
        $bundles = $this->findByLastCommitAt($nb);
        $commits = array();
        foreach($bundles as $bundle) {
            $commits = array_merge($commits, $bundle->getLastCommits());
        }
        usort($commits, function($a, $b)
        {
            return strtotime($a['committed_date']) < strtotime($b['committed_date']);
        });
        $commits = array_slice($commits, 0, 5);

        return $commits;
    }

    public function findByLastCommitAt($nb)
    {
        return $this->createQueryBuilder('b')->orderBy('b.lastCommitAt', 'DESC')->getQuery()->setMaxResults($nb)->execute();
    }

    public function findOneByUsernameAndName($username, $name)
    {
        try {
            return $this->createQueryBuilder('b')
                ->where('b.username = :username')
                ->andWhere('b.name = :name')
                ->setParameter('username', $username)
                ->setParameter('name', $name)
                ->getQuery()
                ->getSingleResult();
        }
        catch(NoResultException $e) {
            return null;
        }
    }
}