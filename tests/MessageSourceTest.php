<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Message\Db\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Sqlite\Connection;
use Yiisoft\Di\Container;
use Yiisoft\Factory\Definitions\Reference;
use Yiisoft\Translator\Message;
use Yiisoft\Translator\Message\Db\MessageSource;
use Yiisoft\Yii\Console\Application;
use Yiisoft\Yii\Db\Migration\Command\DownCommand;
use Yiisoft\Yii\Db\Migration\Command\UpdateCommand;
use Yiisoft\Yii\Db\Migration\Helper\ConsoleHelper;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;

final class MessageSourceTest extends TestCase
{
    private ContainerInterface $container;
    private Application $application;
    private Aliases $aliases;
    private ?ConnectionInterface $db = null;
    private ?CacheInterface $cache = null;
    private ConsoleHelper $consoleHelper;
    private Migration $migration;
    private MigrationService $migrationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configContainer();

        $this->migrationService->updateNamespace(['Yiisoft\\Translator\\Message\\Db\\migrations']);
        $this->consoleHelper->output()->setVerbosity(OutputInterface::VERBOSITY_QUIET);
        $this->migrationService->compact(true);

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

        unset($this->aliases, $this->application, $this->container, $this->migrationService);
    }

    public function generateTranslationsData(): array
    {
        return [
            [
                'app',
                'de',
                [
                    'test.id1' => new Message('app: Test 1 on the (de)', ['comment' => 'Translate wisely!']),
                    'test.id2' => new Message('app: Test 2 on the (de)'),
                    'test.id3' => new Message('app: Test 3 on the (de)'),
                ],
            ],
            [
                'app',
                'de-DE',
                [
                    'test.id1' => new Message('app: Test 1 on the (de-DE)'),
                    'test.id2' => new Message('app: Test 2 on the (de-DE)'),
                ],
            ],
        ];
    }

    /**
     * @dataProvider generateTranslationsData
     */
    public function testWrite(string $category, string $locale, array $data): void
    {
        $messageSource = new MessageSource($this->db);
        $messageSource->write($category, $locale, $data);

        foreach ($data as $messageId => $messageData) {
            $this->assertEquals($messageData->translation(), $messageSource->getMessage($messageId, $category, $locale));
        }
    }

    public function testMultiWrite(): void
    {
        $allData = $this->generateTranslationsData();

        $messageSource = new MessageSource($this->db);

        foreach ($allData as $fileData) {
            [$category, $locale, $data] = $fileData;
            $messageSource->write($category, $locale, $data);
        }

        foreach ($allData as $fileData) {
            [$category, $locale, $data] = $fileData;
            foreach ($data as $messageId => $messageData) {
                $this->assertEquals($messageData->translation(), $messageSource->getMessage($messageId, $category, $locale));
            }
        }
    }

    public function testMultiWriteWithCache(): void
    {
        $allData = $this->generateTranslationsData();

        $messageSource = new MessageSource($this->db, $this->cache);

        foreach ($allData as $fileData) {
            [$category, $locale, $data] = $fileData;
            $messageSource->write($category, $locale, $data);
        }

        foreach ($allData as $fileData) {
            [$category, $locale, $data] = $fileData;
            foreach ($data as $messageId => $messageData) {
                $this->assertEquals($messageData->translation(), $messageSource->getMessage($messageId, $category, $locale));
            }
        }
    }

    protected function configContainer(): void
    {
        $this->container = new Container($this->config());

        $this->application = $this->container->get(Application::class);
        $this->aliases = $this->container->get(Aliases::class);
        $this->consoleHelper = $this->container->get(ConsoleHelper::class);
        $this->db = $this->container->get(ConnectionInterface::class);
        $this->cache = $this->container->get(CacheInterface::class);
        $this->migration = $this->container->get(Migration::class);
        $this->migrationService = $this->container->get(MigrationService::class);

        $loader = new ContainerCommandLoader(
            $this->container,
            [
                'migrate/down' => DownCommand::class,
                'migrate/up' => UpdateCommand::class,
            ]
        );

        $this->application->setCommandLoader($loader);
    }

    private function config(): array
    {
        return [
            Aliases::class => [
                '@root' => dirname(__DIR__, 1),
                '@yiisoft/yii/db/migration' => dirname(__DIR__, 1),
            ],

            CacheInterface::class => [
                '__class' => Cache::class,
                '__construct()' => [Reference::to(ArrayCache::class)],
            ],

            LoggerInterface::class => NullLogger::class,

            ConnectionInterface::class => [
                '__class' => Connection::class,
                '__construct()' => [
                    'dsn' => 'sqlite:' . __DIR__ . '/Data/yiitest.sq3',
                ],
            ],
        ];
    }
}
