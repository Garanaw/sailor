<?php

declare(strict_types=1);

namespace Garanaw\Sailor\Console;

use Garanaw\Sailor\DockerComposeBuilder;
use Laravel\Sail\Console\InstallCommand as SailInstallCommand;

use function Laravel\Prompts\multiselect;

class InstallCommand extends SailInstallCommand
{
    protected $signature = 'sailor:install
                {--with= : The services that should be included in the installation}
                {--devcontainer : Create a .devcontainer configuration directory}';

    protected $description = 'Install Laravel Sailor\'s default Docker Compose file';

    /**
     * The available services that may be installed.
     *
     * @var array<string>
     */
    protected $services = [
        'php-fpm',
        'nginx',
        'mysql',
        'pgsql',
        'mariadb',
        'redis',
        'memcached',
        'meilisearch',
        'typesense',
        'minio',
        'mailpit',
        'selenium',
        'soketi',
    ];

    /**
     * The default services used when the user chooses non-interactive mode.
     *
     * @var string[]
     */
    protected $defaultServices = [
        'php-fpm',
        'nginx',
        'mysql',
        'redis',
        'selenium',
        'mailpit',
    ];

    public function handle(): int {
        $this->info('Installing Laravel Sailor...');

        /** @var DockerComposeBuilder $builder */
        $builder = $this->laravel->make(DockerComposeBuilder::class);

        if ($this->option('with')) {
            $services = $this->option('with') == 'none' ? [] : explode(',', $this->option('with'));
        } elseif ($this->option('no-interaction')) {
            $services = $this->defaultServices;
        } else {
            $services = $builder->gatherServicesInteractively($this->services, $this->defaultServices);
        }

        // If the user is not using Sailor, we shall let the original Laravel Sail handle the installation.
        if ($builder->isUsingSailor($services) === false) {
            return parent::handle();
        }

        if ($invalidServices = array_diff($services, $this->services)) {
            $this->components->error('Invalid services ['.implode(',', $invalidServices).'].');

            return 1;
        }

        $builder->buildDockerCompose($services);
        $this->replaceEnvVariables($services);
        $builder->replaceEnvVariables($services);
        $this->configurePhpUnit();

        if ($this->option('devcontainer')) {
            $this->installDevContainer();
        }

        $this->prepareInstallation($services);

        $this->output->writeln('');
        $this->components->info('Sail and Sailor scaffolding installed successfully. You may run your Docker containers using Sail\'s "up" command.');

        $this->output->writeln('<fg=gray>➜</> <options=bold>./vendor/bin/sail up</>');

        if (in_array('mysql', $services) ||
            in_array('mariadb', $services) ||
            in_array('pgsql', $services)) {
            $this->components->warn('A database service was installed. Run "artisan migrate" to prepare your database:');

            $this->output->writeln('<fg=gray>➜</> <options=bold>./vendor/bin/sail artisan migrate</>');
        }

        return 0;
    }

    protected function gatherServicesInteractively(): array
    {
        return multiselect(
            label: 'Which services would you like to include in your installation?',
            options: $this->defaultServices,
            default: $this->defaultServices,
        );
    }
}
