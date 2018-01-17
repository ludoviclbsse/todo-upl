<?php

namespace LL\PlatformBundle\Controller;

use LL\PlatformBundle\Entity\Article;
use LL\PlatformBundle\Entity\Comment;
use LL\PlatformBundle\Form\ArticleType;
use LL\PlatformBundle\Form\ArticleEditType;
use LL\PlatformBundle\Form\CommentType;
use LL\PlatformBundle\LLPlatformBundle;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Ivory\CKEditorBundle\Form\Type\CKEditorType;


class ArticleController extends Controller
{
    public function indexAction($page)
    {
        if ($page < 1) {throw new NotFoundHttpException('Page "'.$page.'" inexistante.');}

        $nbPerPage = 10;

        $listArticles = $this->getDoctrine()
            ->getManager()
            ->getRepository('LLPlatformBundle:Article')
            ->getArticles($page, $nbPerPage)
        ;

        $nbPages = ceil(count($listArticles) / $nbPerPage);

        if ($page > $nbPages) {
            throw $this->createNotFoundException("La page ".$page." n'existe pas.");
        }

        return $this->render('LLPlatformBundle:Article:index.html.twig', array(
            'listArticles' => $listArticles,
            'nbPages'     => $nbPages,
            'page'        => $page,
        ));
    }

    public function viewAction(Article $article, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $comment = new Comment();
        $form   = $this->get('form.factory')->create(CommentType::class, $comment);

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {

            $em = $this->getDoctrine()->getManager();
            $user = $this->getUser();
            $comment->setArticle($article)->setAuthor($user);
            $em->persist($comment);
            $em->flush();
            $request->getSession()->getFlashBag()->add('notice', 'Commentaire bien enregistré.');

            return $this->redirectToRoute('ll_platform_view', array('id' => $article->getId()));
        }
        $listComments = $em
            ->getRepository('LLPlatformBundle:Comment')
            ->findBy(array('article' => $article))
        ;

        return $this->render('LLPlatformBundle:Article:view.html.twig', array(
            'article'           => $article,
            'listComments' => $listComments,
            'form' => $form->createView()
        ));
    }


    /**
     * @Security("has_role('ROLE_AUTEUR')")
     */
    public function addAction(Request $request)
    {
        $article = new Article();
        $form   = $this->get('form.factory')->create(ArticleType::class, $article);

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {

            $em = $this->getDoctrine()->getManager();
            $user = $this->getUser();
            $article->setAuthor($user);
            $em->persist($article);
            $em->flush();

            $request->getSession()->getFlashBag()->add('notice', 'Article bien enregistré.');

            return $this->redirectToRoute('ll_platform_view', array('id' => $article->getId()));
        }

        return $this->render('LLPlatformBundle:Article:add.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @Security("has_role('ROLE_AUTEUR')")
     */
    public function editAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $article = $em->getRepository('LLPlatformBundle:Article')->find($id);

        if (null === $article) {
            throw new NotFoundHttpException("L'article d'id ".$id." n'existe pas.");
        }

        $form = $this->get('form.factory')->create(ArticleEditType::class, $article);

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $em->flush();

            $request->getSession()->getFlashBag()->add('notice', 'Annonce bien modifiée.');

            return $this->redirectToRoute('ll_platform_view', array('id' => $article->getId()));
        }

        return $this->render('LLPlatformBundle:Article:edit.html.twig', array(
            'article' => $article,
            'form'    => $form->createView(),
        ));
    }

    public function editImageAction($articleId)
    {
        $em = $this->getDoctrine()->getManager();

        // On récupère l'annonce
        $article = $em->getRepository('LLPlatformBundle:Article')->find($articleId);

        // On modifie l'URL de l'image par exemple
        $article->getImage()->setUrl('test.png');

        // On n'a pas besoin de persister l'annonce ni l'image.
        // Rappelez-vous, ces entités sont automatiquement persistées car
        // on les a récupérées depuis Doctrine lui-même

        // On déclenche la modification
        $em->flush();

        return new Response('OK');
    }

    public function deleteAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $article = $em->getRepository('LLPlatformBundle:Article')->find($id);


        if (null === $article) {
            throw new NotFoundHttpException("L'article d'id ".$id." n'existe pas.");
        }

        // On crée un formulaire vide, qui ne contiendra que le champ CSRF
        // Cela permet de protéger la suppression d'annonce contre cette faille
        $form = $this->get('form.factory')->create();

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $em->remove($article);
            $em->flush();

            $request->getSession()->getFlashBag()->add('info', "L'article a bien été supprimé.");

            return $this->redirectToRoute('ll_platform_home');
        }

        return $this->render('LLPlatformBundle:Article:delete.html.twig', array(
            'article' => $article,
            'form'   => $form->createView(),
        ));
    }

    /*public function addCommentAction($id, Request $request)
    {
        $comment = new Comment();
        $form   = $this->get('form.factory')->create(CommentType::class, $comment);

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {

            $em = $this->getDoctrine()->getManager();
            $article = $em->getRepository('LLPlatformBundle:Article')->find($id);
            $user = $this->getUser();
            $comment->setArticle($article)->setAuthor($user);
            $em->persist($comment);
            $em->flush();
            $request->getSession()->getFlashBag()->add('notice', 'Commentaire bien enregistré.');

            return $this->redirectToRoute('ll_platform_view', array('id' => $article->getId()));
        }

        return $this->render('LLPlatformBundle:Article:add.html.twig', array(
            'form' => $form->createView(),
        ));
    }*/

    public function editCommentAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $comment = $em->getRepository('LLPlatformBundle:Comment')->find($id);
        $article = $comment->getArticle();
        $form = $this->get('form.factory')->create(CommentType::class, $comment);

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $em->flush();

            $request->getSession()->getFlashBag()->add('notice', 'Commentaire bien modifié.');

            return $this->redirectToRoute('ll_platform_view', array('id' => $article->getId()));
        }

        return $this->render('LLPlatformBundle:Article:edit.html.twig', array(
            'article' => $article,
            'form'    => $form->createView(),
        ));
    }

    public function deleteCommentAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $comment = $em->getRepository('LLPlatformBundle:Comment')->find($id);
        $article = $comment->getArticle();
        $form = $this->get('form.factory')->create();

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $em->remove($comment);
            $em->flush();

            $request->getSession()->getFlashBag()->add('info', "Le commentaire a bien été supprimé.");

            return $this->redirectToRoute('ll_platform_view', array('id' =>$article->getId()));
        }

        return $this->render('LLPlatformBundle:Article:deletecomment.html.twig', array(
            'comment' => $comment,
            'article' => $article,
            'form'   => $form->createView(),
        ));
    }

    public function menuAction($limit)
    {
        $em = $this->getDoctrine()->getManager();

        $listArticles = $em->getRepository('LLPlatformBundle:Article')->findBy(
            array(),
            array('date' => 'desc'),
            $limit,
            0
        );

        return $this->render('LLPlatformBundle:Article:menu.html.twig', array(
            'listArticles' => $listArticles
        ));
    }
}