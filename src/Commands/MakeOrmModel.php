<?php

namespace Bayfront\BonesService\Orm\Commands;

use Bayfront\Bones\Application\Kernel\Console\ConsoleUtilities;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Exceptions\ConsoleException;
use Bayfront\Bones\Exceptions\FileAlreadyExistsException;
use Bayfront\Bones\Exceptions\UnableToCopyException;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MakeOrmModel extends Command
{

    /**
     * The container will resolve any dependencies.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return void
     */

    protected function configure(): void
    {

        $this->setName('make:ormmodel')
            ->setDescription('Create a new ORM model')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of model')
            ->addOption('type', null, InputOption::VALUE_REQUIRED);

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $name = ucfirst($input->getArgument('name'));

        $util_name = 'ORM model (' . $name . ')';

        ConsoleUtilities::msgInstalling($util_name, $output);

        try {

            $type = strtolower((string)$input->getOption('type'));

            $base_path = dirname(__FILE__, 3) . '/resources';

            if ($type == 'resource') {
                $src_file = $base_path . '/cli/templates/model-resource.php';
            } else {
                $src_file = $base_path . '/cli/templates/model-orm.php';
            }

            $dest_file = App::basePath('/' . strtolower(rtrim(App::getConfig('app.namespace'), '\\')) . '/Models/' . $name . '.php');

            ConsoleUtilities::copyFile($src_file, $dest_file);

            ConsoleUtilities::replaceFileContents($dest_file, [
                '_namespace_' => rtrim(App::getConfig('app.namespace'), '\\'),
                '_model_name_' => $name,
                '_bones_version_' => App::getBonesVersion()
            ]);

            ConsoleUtilities::msgInstalled($util_name, $output);

            $output->writeln('<info>For more info, see: https://github.com/bayfrontmedia/bones-service-orm/blob/master/docs/README.md</info>');

            return Command::SUCCESS;

        } catch (FileAlreadyExistsException) {
            ConsoleUtilities::msgFileExists($util_name, $output);
            return Command::FAILURE;
        } catch (UnableToCopyException) {
            ConsoleUtilities::msgUnableToCopy($util_name, $output);
            return Command::FAILURE;
        } catch (ConsoleException) {
            ConsoleUtilities::msgFailedToWrite($util_name, $output);
            return Command::FAILURE;
        }

    }

}