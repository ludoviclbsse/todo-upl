<?php

namespace LL\PlatformBundle\Form;

use LL\PlatformBundle\Repository\CategoryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ivory\CKEditorBundle\Form\Type\CKEditorType;

class ArticleType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $pattern = 'C%';

        $builder
            ->add('date',      DateType::class)
            ->add('title',     TextType::class)
            ->add('content',   CkeditorType::class)
            //->add('image',     ImageType::class)
            ->add('categories', EntityType::class, array(
                'class'         => 'LLPlatformBundle:Category',
                'choice_label'  => 'name',
                'multiple'      => true,
                'query_builder' => function(CategoryRepository $repository) use($pattern) {
                    return $repository->getLikeQueryBuilder($pattern);
                }
            ))
            ->add('save',      SubmitType::class);

        // On ajoute une fonction qui va écouter un évènement
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,    // 1er argument : L'évènement qui nous intéresse : ici, PRE_SET_DATA
            function(FormEvent $event) { // 2e argument : La fonction à exécuter lorsque l'évènement est déclenché
                // On récupère notre objet Article sous-jacent
                $article = $event->getData();

                // Cette condition est importante, on en reparle plus loin
                if (null === $article) {
                    return; // On sort de la fonction sans rien faire lorsque $article vaut null
                }

                // Si l'annonce n'est pas publiée, ou si elle n'existe pas encore en base (id est null)
                if (!$article->getPublished() || null === $article->getId()) {
                    // Alors on ajoute le champ published
                    $event->getForm()->add('published', CheckboxType::class, array('required' => false));
                } else {
                    // Sinon, on le supprime
                    $event->getForm()->remove('published');
                }
            }
        );
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'LL\PlatformBundle\Entity\Article'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'll_platformbundle_article';
    }


}
