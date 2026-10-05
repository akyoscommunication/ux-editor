<?php

declare(strict_types=1);

namespace Builder\PageBuilderBundle\Command;

use Builder\PageBuilderBundle\Service\BlockFromComponentGenerator;
use Builder\PageBuilderBundle\Service\BlockRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'builder:make-block',
    description: 'Crée un bloc du builder à partir d\'un composant Twig anonyme déjà importé',
)]
final class MakeBlockCommand extends Command
{
    public function __construct(
        private BlockFromComponentGenerator $generator,
        private BlockRegistry $registry,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'Nom du composant, par exemple Badge ou Breadcrumb:List');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $component = str_replace('/', ':', trim((string) $input->getArgument('name')));
        $sourcePath = $this->projectDir.'/templates/components/'.str_replace(':', '/', $component).'.html.twig';
        if (!is_file($sourcePath)) {
            $io->error(sprintf('Composant "%s" introuvable (%s).', $component, $sourcePath));

            return Command::FAILURE;
        }

        $generated = $this->generator->generate($component, (string) file_get_contents($sourcePath), $this->appNamespace());
        if (null === $generated) {
            $io->error(sprintf('"%s" n\'a aucune prop documentée (ligne ##) ni contenu à reprendre.', $component));

            return Command::FAILURE;
        }
        if ($this->registry->has($generated->blockName)) {
            $io->error(sprintf('Le bloc "%s" existe déjà.', $generated->blockName));

            return Command::FAILURE;
        }

        $phpPath = $this->projectDir.'/src/Block/'.$generated->className.'.php';
        $twigPath = $this->projectDir.'/templates/blocks/'.$generated->blockName.'.html.twig';
        if (is_file($phpPath) || is_file($twigPath)) {
            $io->error(sprintf('Un fichier existe déjà pour "%s".', $generated->blockName));

            return Command::FAILURE;
        }

        if (!is_dir(\dirname($phpPath)) && !mkdir(\dirname($phpPath), 0777, true) && !is_dir(\dirname($phpPath))) {
            $io->error(sprintf('Impossible de créer %s.', \dirname($phpPath)));

            return Command::FAILURE;
        }
        if (!is_dir(\dirname($twigPath)) && !mkdir(\dirname($twigPath), 0777, true) && !is_dir(\dirname($twigPath))) {
            $io->error(sprintf('Impossible de créer %s.', \dirname($twigPath)));

            return Command::FAILURE;
        }

        file_put_contents($phpPath, $generated->php);
        file_put_contents($twigPath, $generated->twig);
        $io->success(sprintf('Bloc "%s" créé.', $generated->blockName));
        $io->listing([$phpPath, $twigPath]);
        $io->note('Videz le cache (php bin/console cache:clear) pour le voir dans le builder.');

        return Command::SUCCESS;
    }

    private function appNamespace(): string
    {
        $composer = json_decode((string) file_get_contents($this->projectDir.'/composer.json'), true, 512, JSON_THROW_ON_ERROR);
        foreach ($composer['autoload']['psr-4'] ?? [] as $namespace => $path) {
            if ('src' !== rtrim((string) $path, '/') || str_starts_with((string) $namespace, 'Builder\\PageBuilderBundle\\')) {
                continue;
            }

            return rtrim((string) $namespace, '\\');
        }

        throw new \RuntimeException('Aucun namespace psr-4 vers src/ dans composer.json.');
    }
}
