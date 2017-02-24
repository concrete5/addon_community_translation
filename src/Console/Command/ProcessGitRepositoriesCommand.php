<?php
namespace CommunityTranslation\Console\Command;

use CommunityTranslation\Git\Importer;
use CommunityTranslation\Repository\GitRepository as GitRepositoryRepository;
use Concrete\Core\Console\Command;
use Concrete\Core\Support\Facade\Application;
use Doctrine\ORM\EntityManager;
use Exception;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class ProcessGitRepositoriesCommand extends Command
{
    protected function configure()
    {
        $errExitCode = static::RETURN_CODE_ON_FAILURE;
        $this
            ->setName('ct:git-repository')
            ->addOption('repository', 'r', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Limit the process to one or more repositories')
            ->setDescription('Extract the translatable strings from the git repositories')
            ->setHelp(<<<EOT
Returns codes:
  0 operation completed successfully
  $errExitCode errors occurred
  2 errors occurred but some repository has been processed
EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = Application::getFacadeApplication();
        $em = $app->make(EntityManager::class);
        /* @var EntityManager $em */
        $gitRepositories = $this->getGitRepositories($input->getOption('repository'));
        $importer = $app->make(Importer::class);
        /* @var Importer $importer */
        $logger = new ConsoleLogger($output, [LogLevel::INFO => OutputInterface::VERBOSITY_NORMAL]);
        $importer->setLogger($logger);
        $someError = false;
        $someSuccess = true;
        foreach ($gitRepositories as $gitRepository) {
            $output->writeln(sprintf('Processing repository %s', $gitRepository->getName()));
            $error = null;
            try {
                $importer->import($gitRepository);
            } catch (Exception $x) {
                $error = $x;
            } catch (Throwable $x) {
                $error = $x;
            }
            if ($error === null) {
                $someSuccess = true;
            } else {
                $someError = true;
                $this->writeError($output, $error);
            }
        }
        if ($someError && $someSuccess) {
            $rc = 2;
        } elseif ($someError) {
            $rc = 1;
        } else {
            $rc = 0;
        }

        return $rc;
    }

    /**
     * @param EntityManager $em
     * @param string[] $filter
     *
     * @throws Exception
     *
     * @return \CommunityTranslation\Entity\GitRepository[]
     */
    private function getGitRepositories(array $filter)
    {
        $app = Application::getFacadeApplication();
        $allGitRepositories = $app->make(GitRepositoryRepository::class)->findAll();
        if (empty($allGitRepositories)) {
            throw new Exception('No git repository defined');
        }
        if (count($filter) === 0) {
            $result = $allGitRepositories;
        } else {
            $result = [];
            foreach ($filter as $f) {
                $found = null;
                foreach ($allGitRepositories as $r) {
                    if (strcasecmp($r->getName(), $f) === 0) {
                        if ($found !== null) {
                            throw new Exception("Duplicated repository specified: $f");
                        } else {
                            $found = $r;
                        }
                    }
                }
                if ($found === null) {
                    throw new Exception("Unable to find a repository with name $f");
                }
                $result[] = $found;
            }
        }

        return $result;
    }
}
