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
use Yiisoft\Definitions\Reference;
use Yiisoft\Profiler\Profiler;
use Yiisoft\Profiler\ProfilerInterface;
use Yiisoft\Translator\Message\Db\MessageSource;
use Yiisoft\Yii\Console\Application;
use Yiisoft\Yii\Db\Migration\Command\DownCommand;
use Yiisoft\Yii\Db\Migration\Command\UpdateCommand;
use Yiisoft\Yii\Db\Migration\Helper\ConsoleHelper;
use Yiisoft\Yii\Db\Migration\Informer\MigrationInformerInterface;
use Yiisoft\Yii\Db\Migration\Informer\NullMigrationInformer;
use Yiisoft\Yii\Db\Migration\Migrator;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;
use InvalidArgumentException;

final class MessageSourceTest extends TestCase
{
    private ContainerInterface $container;
    private Application $application;
    private Aliases $aliases;
    private ?ConnectionInterface $db = null;
    private ?CacheInterface $cache = null;
    private ConsoleHelper $consoleHelper;
    private Migrator $migrator;
    private MigrationService $migrationService;
    private ProfilerInterface $profiler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configContainer();

        $this->migrationService->updateNamespaces(['Yiisoft\\Translator\\Message\\Db\\migrations']);
        $this->consoleHelper->output()->setVerbosity(OutputInterface::VERBOSITY_QUIET);

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
                    'test.id1' => [
                        'comment' => 'Translate wisely!',
                        'message' => 'app: Test 1 on the (de)',
                    ],
                    'test.id2' => [
                        'message' => 'app: Test 2 on the (de)',
                    ],
                    'test.id3' => [
                        'message' => 'app: Test 3 on the (de)',
                    ],
                ],
            ],
            [
                'app',
                'de-DE',
                [
                    'test.id1' => [
                        'message' => 'app: Test 1 on the (de-DE)',
                    ],
                    'test.id2' => [
                        'message' => 'app: Test 2 on the (de-DE)',
                    ],
                ],
            ],
        ];
    }

    public function generateFailTranslationsData(): array
    {
        return [
            [
                'app',
                'de',
                [
                    'test.id1' => [
                    ],
                ],
            ],
            [
                'app',
                'de-DE',
                [
                    'test.id1' => [
                        'message' => 1,
                    ],
                ],
            ],
            [
                'app',
                'de-DE',
                [
                    'test.id1' => [
                        'message' => '',
                        'comment' => 1,
                    ],
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
            $this->assertEquals($messageData['message'], $messageSource->getMessage($messageId, $category, $locale));
        }
    }

    /**
     * @dataProvider generateFailTranslationsData
     */
    public function testWriteWithFailData(string $category, string $locale, array $data): void
    {
        $this->expectException(InvalidArgumentException::class);
        $messageSource = new MessageSource($this->db);
        $messageSource->write($category, $locale, $data);
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
                $this->assertEquals($messageData['message'], $messageSource->getMessage($messageId, $category, $locale));
            }
        }
    }

    public function testUpdate(): void
    {
        $updatedData = [
            'app',
            'de',
            [
                'test.id1' => [
                    'message' => 'Need to update',
                ],
                'test.id2' => [
                    'message' => 'app: Test 2 on the (de-DE)',
                ],
            ],
        ];

        $allData = [
            [
                'app',
                'de',
                [
                    'test.id1' => [
                        'message' => 'app: Test 1 on the (de)',
                        'comment' => 'Translate wisely!',
                    ],
                    'test.id2' => [
                        'message' => 'app: Test 2 on the (de)',
                    ],
                    'test.id3' => [
                        'message' => 'app: Test 3 on the (de)',
                    ],
                ],
            ],
            $updatedData,
        ];

        $messageSource = new MessageSource($this->db);

        foreach ($allData as $fileData) {
            [$category, $locale, $data] = $fileData;
            $messageSource->write($category, $locale, $data);
        }

        [$category, $locale, $data] = $updatedData;
        foreach ($data as $messageId => $messageData) {
            $this->assertEquals($messageData['message'], $messageSource->getMessage($messageId, $category, $locale));
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
                $this->assertEquals($messageData['message'], $messageSource->getMessage($messageId, $category, $locale));
            }
        }
    }

    protected function configContainer(): void
    {
        $this->container = new Container($this->config());

        $this->application = $this->container->get(Application::class);
        $this->aliases = $this->container->get(Aliases::class);
        $this->aliases->set('@root', dirname(__DIR__, 1));
        $this->aliases->set('@yiisoft/yii/db/migration', dirname(__DIR__, 1));

        $this->consoleHelper = $this->container->get(ConsoleHelper::class);
        $this->db = $this->container->get(ConnectionInterface::class);
        $this->cache = $this->container->get(CacheInterface::class);
        $this->profiler = $this->container->get(ProfilerInterface::class);
        $this->migrator = $this->container->get(Migrator::class);
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
                'class' => Aliases::class,
            ],

            CacheInterface::class => [
                'class' => Cache::class,
                '__construct()' => [Reference::to(ArrayCache::class)],
            ],

            LoggerInterface::class => NullLogger::class,
            ProfilerInterface::class => Profiler::class,

            ConnectionInterface::class => [
                'class' => Connection::class,
                '__construct()' => [
                    'dsn' => 'sqlite:' . __DIR__ . '/Data/yiitest.sq3',
                ],
            ],
            MigrationInformerInterface::class => NullMigrationInformer::class,
        ];
    }

    /**
     * @dataProvider generateTranslationsData
     */
    public function testReadMessages(string $category, string $locale, array $data): void
    {
        $messageSource = new MessageSource($this->db);
        $messageSource->write($category, $locale, $data);

        $messages = $messageSource->getMessages($category, $locale);
        $this->assertEquals($messages, $data);
    }
}
