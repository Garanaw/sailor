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

    protected array $stubPaths = [
        'own' => __DIR__ . '/../../../stubs',
        'sail' => __DIR__ . '/../../../vendor/laravel/sail/stubs',
    ];

    public function __construct(private Container $laravel)
    {
    }

    public function gatherServicesInteractively(array $options, array $default = []): array
    {
        return multiselect(
            label: 'Which services would you like to include in your installation?',
            options: $options,
            default: $default,
        );
    }

    public function buildDockerCompose($services): void
    {
        $compose = $this->composeFile();

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

        file_put_contents($this->laravel->basePath('docker-compose.yml'), Yaml::dump($compose, Yaml::DUMP_OBJECT_AS_MAP));
    }

    protected function composeFile(?string $path = null): mixed
    {
        // If the file exists, we parse it and return the content...
        $composePath = base_path($path ?? 'docker-compose.yml');
        if (file_exists($composePath)) {
            return Yaml::parseFile($composePath);
        }

        // If no file exists, we'll try to return Sail's default docker-compose.yml as per version 1.31...
        $sailPath = __DIR__ . '/../../../vendor/laravel/sail/stubs/docker-compose.stub';
        if (file_exists($sailPath)) {
            return Yaml::parseFile($sailPath);
        }

        // If no file exists, we'll try to return our own default docker-compose.yml...
        $ownPath = __DIR__ . '/../../../stubs/docker-compose.stub';
        return file_exists($ownPath)
            ? Yaml::parseFile($ownPath)
            : [];
    }

    protected function pathFor(string $service): string
    {
        return in_array($service, $this->ownServices)
            ? $this->stubPaths['own']
            : $this->stubPaths['sail'];
    }

    protected function stubPathFor(string $service): string
    {
        return $this->pathFor($service) . "/{$service}.stub";
    }

    protected function stubContentFor(string $service): array
    {
        return Yaml::parseFile($this->stubPathFor($service))[$service];
    }

    public function replaceEnvVariables(array $services): void
    {
        $environment = file_get_contents($this->laravel->basePath('.env'));

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

    public function configurePhpUnit(): void
    {
        // Implement configurePhpUnit() method.
    }
}
