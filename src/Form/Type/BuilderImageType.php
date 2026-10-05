<?php

declare(strict_types=1);

namespace Builder\PageBuilderBundle\Form\Type;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class BuilderImageType extends AbstractType
{
    public const JOLI_LIBRARY = 'JoliCode\MediaBundle\Library\LibraryContainer';

    public const JOLI_ADMIN_TYPE = 'JoliCode\MediaBundle\Bridge\EasyAdmin\Form\Type\MediaChoiceType';

    public const JOLI_ADMIN_BUNDLE = 'EasyCorp\Bundle\EasyAdminBundle\EasyAdminBundle';

    public function __construct(
        #[Autowire('%page_builder.joli_admin%')]
        private bool $joliAdmin,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!$this->joliAdmin) {
            return;
        }

        // MediaChoiceType transforme le chemin soumis en Media. Ce transformer
        // est ajouté devant le sien : au retour, le Media redevient un chemin.
        $builder->addModelTransformer(new CallbackTransformer(
            self::path(...),
            self::path(...),
        ));
    }

    public function getParent(): string
    {
        return $this->joliAdmin ? self::JOLI_ADMIN_TYPE : TextType::class;
    }

    public function getBlockPrefix(): string
    {
        // Le parent MediaChoiceType porte déjà ce préfixe. Le répéter casse le rendu.
        return $this->joliAdmin ? 'builder_joli_image' : 'builder_image';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required' => false,
        ]);
    }

    public static function path(mixed $data): ?string
    {
        if (null === $data || \is_string($data)) {
            return $data;
        }
        if (\is_object($data) && method_exists($data, 'getPath')) {
            $path = $data->getPath();

            return \is_string($path) ? $path : null;
        }

        return null;
    }
}
