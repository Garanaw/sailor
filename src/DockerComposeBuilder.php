<?php

declare(strict_types=1);

namespace Garanaw\Sailor;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Yaml\Yaml;

use function Laravel\Prompts\{multiselect, warning};

class DockerComposeBuilder
{
    protected array $ownServices = [
        'php-fpm',
        'nginx',
    ];

    protected array $sailServices = [
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

    protected array $keyMapping = [
        'php-fpm' => 'laravel.test',
    ];

    protected array $stubPaths = [
        'own' => '/vendor/garanaw/sailor/stubs',
        'sail' => '/vendor/laravel/sail/stubs',
    ];

    public function gatherServicesInteractively(array $options, array $default = []): array
    {
        return multiselect(
            label: 'Which services would you like to include in your installation?',
            options: $options,
            default: $default,
        );
    }

    public function isUsingSailor(array $selectedServices): bool
    {
        return count(array_intersect($selectedServices, $this->ownServices)) > 0;
    }

    public function buildDockerCompose($services): void
    {
        $compose = $this->composeFile();

        // As php-fpm is already loaded in the docker-compose.yml, we can remove it from the list...
        unset($services[array_search('php-fpm', $services)]);

        // Adds the new services as dependencies of the laravel.test service...
        if (! array_key_exists('laravel.test', $compose['services'])) {
            warning('Couldn\'t find the laravel.test service. Make sure you add ['.implode(',', $services).'] to the depends_on config.');
        } else {
            $compose['services']['laravel.test']['depends_on'] = collect($compose['services']['laravel.test']['depends_on'] ?? [])
                ->merge($services)
                ->unique()
                ->values()
                ->all();
        }

        // Update the dependencies if the MariaDB service is used...
        if (in_array('mariadb', $services)) {
            $compose['services']['laravel.test']['depends_on'] = array_map(function ($dependedItem) {
                return $dependedItem;
            }, $compose['services']['laravel.test']['depends_on']);
        }

        // Add the services to the docker-compose.yml...
        collect($services)
            ->filter(function ($service) use ($compose) {
                return ! array_key_exists($service, $compose['services'] ?? []);
            })->each(function ($service) use (&$compose) {
                $compose['services'][$service] = $this->stubContentFor($service);
            });

        // Merge volumes...
        collect($services)
            ->filter(function ($service) {
                return in_array($service, ['mysql', 'pgsql', 'mariadb', 'redis', 'meilisearch', 'typesense', 'minio']);
            })->filter(function ($service) use ($compose) {
                return ! array_key_exists($service, $compose['volumes'] ?? []);
            })->each(function ($service) use (&$compose) {
                $compose['volumes']["sail-{$service}"] = ['driver' => 'local'];
            });

        // If the list of volumes is empty, we can remove it...
        if (empty($compose['volumes'])) {
            unset($compose['volumes']);
        }

        // Replace Selenium with ARM base container on Apple Silicon...
        if (in_array('selenium', $services) && in_array(php_uname('m'), ['arm64', 'aarch64'])) {
            $compose['services']['selenium']['image'] = 'seleniarm/standalone-chromium';
        }

        file_put_contents(base_path('docker-compose.yml'), Yaml::dump($compose, Yaml::DUMP_OBJECT_AS_MAP));
    }

    protected function composeFile(?string $path = null): mixed
    {
        // If the file exists, we parse it and return the content...
        $composePath = base_path($path ?? 'docker-compose.yml');
        if (file_exists($composePath)) {
            return Yaml::parseFile($composePath);
        }

        // If no file exists, we'll try to return our own default docker-compose.yml...
        $ownPath = base_path('vendor/garanaw/sailor/stubs/docker-compose.yml');
        if (file_exists($ownPath)) {
            return Yaml::parseFile($ownPath);
        }

        // If no file exists, we'll try to return Sail's default docker-compose.yml as per version 1.31...
        $sailPath = base_path('vendor/laravel/sail/stubs/docker-compose.stub');
        return file_exists($sailPath)
            ? Yaml::parseFile($sailPath)
            : [];
    }

    protected function mapServiceKey(string $key): string
    {
        return $this->keyMapping[$key] ?? $key;
    }

    protected function pathFor(string $service): string
    {
        return in_array($service, $this->ownServices)
            ? $this->stubPaths['own']
            : $this->stubPaths['sail'];
    }

    protected function stubPathFor(string $service): string
    {
        return base_path($this->pathFor($service) . "/{$service}.stub");
    }

    protected function stubContentFor(string $service): array
    {
        $stubContent = Yaml::parseFile($this->stubPathFor($service));

        return $stubContent[$this->mapServiceKey($service)];
    }

    public function replaceEnvVariables(array $services): void
    {
        $environment = file_get_contents(base_path('.env'));

        if (in_array('php-fpm', $services)) {
            $vars = [
                sprintf('WWWUSER=%s', Process::run('$(id -u)')->output()),
                sprintf('WWWGROUP=%s', Process::run('$(id -g)')->output()),
            ];

            foreach ($vars as $var) {
                $environment = preg_replace(
                    "/^{$var}=.*/m",
                    $var,
                    $environment,
                );
            }
        }
    }
}
