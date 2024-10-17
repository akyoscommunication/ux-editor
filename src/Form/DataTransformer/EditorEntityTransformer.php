<?php

namespace Akyos\UXEditor\Form\DataTransformer;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\DataTransformerInterface;

class EditorEntityTransformer implements DataTransformerInterface
{
    public function __construct(
        #[Autowire(service: EntityRepository::class)]
        private $repository,
    ){}

    public function transform(mixed $value): mixed
    {
        if (null === $value) {
            return null;
        }

        return $this->repository->find($value);
    }

    public function reverseTransform(mixed $value): mixed
    {
        return $value?->getId();
    }
}
