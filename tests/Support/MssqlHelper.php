<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Message\Db\Tests\Support;

use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;
use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Mssql\Connection;
use Yiisoft\Db\Mssql\Driver;
use Yiisoft\Db\Mssql\Dsn;
use Yiisoft\Definitions\Exception\CircularReferenceException;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Exception\NotInstantiableException;
use Yiisoft\Definitions\Reference;
use Yiisoft\Di\BuildingException;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;
use Yiisoft\Yii\Console\Application;
use Yiisoft\Yii\Db\Migration\Command\DownCommand;
use Yiisoft\Yii\Db\Migration\Command\UpdateCommand;
use Yiisoft\Yii\Db\Migration\Informer\MigrationInformerInterface;
use Yiisoft\Yii\Db\Migration\Informer\NullMigrationInformer;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;

trait MssqlHelper
{
    protected Application $application;
    protected CacheInterface $cache;
    protected ConnectionInterface $db;
    protected MigrationService $migrationService;

    /**
     * @throws BuildingException
     * @throws CircularReferenceException
     * @throws InvalidConfigException
     * @throws NotFoundExceptionInterface
     * @throws NotInstantiableException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $config = ContainerConfig::create()->withDefinitions($this->getDefinitions());
        $container = new Container($config);

        $aliases = $container->get(Aliases::class);
        $aliases->set('@root', dirname(__DIR__, 2));
        $aliases->set('@yiisoft/yii/db/migration', dirname(__DIR__, 2));

        $this->application = $container->get(Application::class);
        $this->cache = $container->get(CacheInterface::class);
        $this->db = $container->get(ConnectionInterface::class);
        $this->migrationService = $container->get(MigrationService::class);

        $loader = new ContainerCommandLoader(
            $container,
            [
                'migrate/down' => DownCommand::class,
                'migrate/up' => UpdateCommand::class,
            ]
        );

        $this->application->setCommandLoader($loader);
        $this->migrationService->updateNamespaces(['Yiisoft\\Translator\\Message\\Db\\Migrations']);

        $create = $this->application->find('migrate/up');
        $commandUp = new CommandTester($create);
        $commandUp->execute([]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $remove = $this->application->find('migrate/down');
        $commandDown = new CommandTester($remove);
        $commandDown->execute([]);

        unset($this->application, $this->migrationService);
    }

    private function getDsn(): string
    {
        return (new Dsn('sqlsrv', 'localhost', 'yiitest'))->asString();
    }

    /**
     * @throws InvalidConfigException
     */
    private function getDefinitions(): array
    {
        return [
            Aliases::class => [
                'class' => Aliases::class,
            ],

            \Psr\SimpleCache\CacheInterface::class => Reference::to(ArrayCache::class),

            CacheInterface::class => [
                'class' => Cache::class,
                '__construct()' => [Reference::to(ArrayCache::class)],
            ],

            ConnectionInterface::class => [
                'class' => Connection::class,
                '__construct()' => [
                    new Driver($this->getDsn(), 'SA', 'YourStrong!Passw0rd'),
                ],
            ],

            MigrationInformerInterface::class => NullMigrationInformer::class,
        ];
    }
}
