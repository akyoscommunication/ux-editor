<?php

declare(strict_types=1);

namespace Builder\PageBuilderBundle\Form\Type;

use Builder\PageBuilderBundle\Service\BlockHydrator;
use Builder\PageBuilderBundle\Service\BlockRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ContentType extends AbstractType
{
    public function __construct(
        private BlockRegistry $registry,
        private BlockHydrator $hydrator,
        private FormFactoryInterface $forms,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new CallbackTransformer(
            static function (mixed $data): string {
                $data = \is_array($data) && isset($data['blocks']) ? $data : ['blocks' => []];

                return json_encode($data, \JSON_THROW_ON_ERROR);
            },
            static function (mixed $data): array {
                if (!\is_string($data) || '' === $data) {
                    return ['blocks' => []];
                }
                try {
                    $decoded = json_decode($data, true, 512, \JSON_THROW_ON_ERROR);
                } catch (\JsonException) {
                    throw new TransformationFailedException('Le contenu n\'est pas un JSON lisible.');
                }
                if (!\is_array($decoded)) {
                    throw new TransformationFailedException('Le contenu n\'est pas un JSON lisible.');
                }

                return $decoded;
            },
        ));
        $invalidJson = false;
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use (&$invalidJson): void {
            $submitted = $event->getData();
            if (!\is_string($submitted) || '' === $submitted) {
                return;
            }
            try {
                $decoded = json_decode($submitted, true, 512, \JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                $decoded = null;
            }
            if (\is_array($decoded)) {
                return;
            }
            $invalidJson = true;
            $current = $event->getForm()->getData();
            $event->setData(json_encode(\is_array($current) ? $current : ['blocks' => []], \JSON_THROW_ON_ERROR));
        });
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use (&$invalidJson): void {
            $form = $event->getForm();
            if ($invalidJson) {
                $form->addError(new FormError('Le contenu n\'est pas un JSON lisible.'));

                return;
            }
            if (!$form->isSynchronized()) {
                return;
            }
            $data = $form->getData();
            if (!\is_array($data)) {
                return;
            }
            foreach ($this->errors(\is_array($data['blocks'] ?? null) ? $data['blocks'] : []) as $message) {
                $form->addError(new FormError($message));
            }
        });
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $data = $form->getData();
        $view->vars['document'] = \is_array($data) && isset($data['blocks']) ? $data : ['blocks' => []];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['error_bubbling' => false]);
    }

    public function getParent(): string
    {
        return HiddenType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'builder_content';
    }

    /**
     * @param list<mixed> $blocks
     *
     * @return list<string>
     */
    private function errors(array $blocks): array
    {
        $messages = [];
        foreach ($blocks as $block) {
            if (!\is_array($block)) {
                continue;
            }
            $type = (string) ($block['type'] ?? '');
            if (!$this->registry->has($type)) {
                $messages[] = sprintf('Bloc « %s » introuvable.', $type);
            } else {
                $definition = $this->registry->get($type);
                $props = \is_array($block['props'] ?? null) ? $block['props'] : [];
                $child = $this->forms->create($definition->class, $this->hydrator->hydrate($definition->class, $props), [
                    'csrf_protection' => false,
                ]);
                $child->submit($props);
                foreach ($child->getErrors(true) as $error) {
                    $messages[] = $definition->label.' : '.$error->getMessage();
                }
            }
            foreach ($block['slots'] ?? [] as $children) {
                if (\is_array($children)) {
                    array_push($messages, ...$this->errors($children));
                }
            }
        }

        return $messages;
    }
}
