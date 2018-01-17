<?php

namespace LL\PlatformBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;

class ArticleRepository extends EntityRepository
{
    public function getArticles($page, $nbPerPage)
    {
        $query = $this->createQueryBuilder('a')
            ->leftJoin('a.image', 'i')
            ->addSelect('i')
            ->leftJoin('a.categories', 'c')
            ->addSelect('c')
            ->orderBy('a.date', 'DESC')
            ->getQuery()
        ;

        $query
            // On définit l'article à partir duquel commencer la liste
            ->setFirstResult(($page-1) * $nbPerPage)
            // Ainsi que le nombre d'article à afficher sur une page
            ->setMaxResults($nbPerPage)
        ;

        // Enfin, on retourne l'objet Paginator correspondant à la requête construite
        return new Paginator($query, true);
    }

    public function getArticleWithComments()
    {
        $qb = $this
            ->createQueryBuilder('a')
            ->leftJoin('a.comments', 'app')
            ->addSelect('app')
        ;

        return $qb
            ->getQuery()
            ->getResult()
        ;
    }

    public function getArticleWithCategories(array $categoryNames)
    {
        $qb = $this->createQueryBuilder('a');
        // On fait une jointure avec l'entité Category avec pour alias « c »
        $qb
            ->innerJoin('a.categories', 'c')
            ->addSelect('c')
        ;
        // Puis on filtre sur le nom des catégories à l'aide d'un IN
        $qb->where($qb->expr()->in('c.name', $categoryNames));
        // La syntaxe du IN et d'autres expressions se trouve dans la documentation Doctrine
        // Enfin, on retourne le résultat
        return $qb
            ->getQuery()
            ->getResult()
            ;
    }
}
