<?php

namespace AppBundle\Repository;

use AppBundle\Entity\User;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * UserRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserRepository extends \Doctrine\ORM\EntityRepository
{
 //   const MAX_RESULTS = 10;

    /** @param User $user */
    public function activateUser($user)
    {
        $user->setConfirmationToken(null)->setEnabled(true);

        $this->_em->persist($user);
        $this->_em->flush();
    }

    /** @param User $user */
    public function resetPassword($user, $newPass)
    {
        $user->setPassword($newPass);
        $this->_em->persist($user);
        $this->_em->flush();
    }

//    public function paginate($dql, $page = 1, $limit = UserRepository::MAX_RESULTS)
//    {
//        $paginator = new Paginator($dql);
//
//        $paginator->getQuery()
//            ->setFirstResult(($page - 1) * $limit)
//            ->setMaxResults($limit);
//
//        return $paginator;
//    }

//    public function findBest($orderParams, $currentPage = 1, $limit)
//    {
//        $orderBy = (isset($orderParams['order'])) ? $orderParams['order'] : 'correct';
//        $direction = (isset($orderParams['direction'])) ? $orderParams['direction'] : 'DESC';
//
//        if($orderBy == 'percentage') {
//            $query = $this->findByPercentage();
//        }
//        else{
//            $query = $this->createQueryBuilder('u')
//                ->select('u')
//                ->orderBy('u.'.$orderBy, $direction)
//                ->getQuery();
//        }
//
//        $paginator = $this->paginate($query, $currentPage, $limit);
//
//        return $paginator;
//    }

    public function findByPercentage()
    {
        return $this->_em->createQuery('SELECT u, u.correct / (u.correct + u.incorrect) AS HIDDEN percentage
        FROM AppBundle:User u 
        ORDER BY percentage DESC');
    }

    public function userCount()
    {
        return $this->_em->createQuery('SELECT COUNT(u)
        FROM AppBundle:User u');
    }

    public function publicUserCount()
    {
        return $this->_em->createQueryBuilder()
            ->select('count(u)')
            ->from('AppBundle:User', 'u')
            ->where('u.publicProfile = true')
            ->getQuery()->getSingleScalarResult();
    }

    public function loadUserByUsername($username)
    {
        return $this->createQueryBuilder('u')
            ->select('u')
            ->where('u.username = :username')
            ->setParameter('username', $username)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param ParameterBag $params
     * @return array
     */
    public function getLeaderBoard($params)
    {
        $start      = $params->get('start');
        $length     = $params->get('length');
        $column     = $params->get('order')[0]['column'];
        $sortBy     = $params->get('columns')[$column]['data'];
        $sortDir    = $params->get('order')[0]['dir'];

        $qb = $this->createQueryBuilder('u')
            ->select('u, u.correct / (u.correct + u.incorrect) as percentage ')
            ->setFirstResult($start)
            ->setMaxResults($length)
            ->where('u.publicProfile = true');

        if ($sortBy == 'percentage') {
            $qb = $qb->orderBy($sortBy, $sortDir);
        } else {
            $qb = $qb->orderBy('u.'.$sortBy, $sortDir);
        }
        $results = $qb->getQuery()->getResult();

        $draw               = $params->get('draw');
        $totalRecords       = $this->publicUserCount();
        $data               = [];


        foreach ($results as $user) {
            $data[] = [
                'avatar'        => $user[0]->getAvatar(),
                'name'          => $user[0]->getName(),
                'correct'       => $user[0]->getCorrect(),
                'incorrect'     => $user[0]->getIncorrect(),
                'testsTaken'    => $user[0]->getTestsTaken(),
                'percentage'    => number_format($user['percentage'] * 100, 2).'%',
                'timeSpent'     => $user[0]->getFormatedTimeSpent()
            ];
        }

        return [
            'data'              => $data,
            'recordsFiltered'   => $totalRecords,
            'recordsTotal'      => $totalRecords,
            'draw'              => $draw
        ];
    }

    public function findByRole($role)
    {
        return $this->createQueryBuilder('u')
            ->select('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%'.$role.'%')
            ->getQuery()
            ->getResult();
    }
}
