<?php

/** @copyright 2020 ng-voice GmbH */

declare(strict_types=1);

namespace NgVoice\AriClient\Tests\Client\WebSocket;

use GuzzleHttp\Client;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use React\EventLoop\LoopInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ResponseInterface;
use PHPUnit\Framework\MockObject\MockObject;
use React\EventLoop\Factory as EventLoopFactory;
use NgVoice\AriClient\Client\Rest\Resource\Applications;
use NgVoice\AriClient\Exception\AsteriskRestInterfaceException;
use NgVoice\AriClient\Client\WebSocket\{AbstractWebSocketClient,
    Settings as WebSocketClientSettings};
use NgVoice\AriClient\Model\Message\Event\ChannelUserevent;
use NgVoice\AriClient\StasisApplicationInterface;
use NgVoice\AriClient\Client\Rest\Settings as RestClientSettings;
use NgVoice\AriClient\Tests\Client\Rest\ResourceClient\ApplicationsTest;
use Throwable;

/**
 * Class AbstractWebSocketClientTest
 *
 * @package NgVoice\AriClient\Tests\WebSocket
 *
 * @author Lukas Stermann <lukas@ng-voice.com>
 */
class AbstractWebSocketClientTest extends TestCase
{
    private AbstractWebSocketClient $abstractWebSocketClient;

    private WebSocketClientSettings $webSocketClientSettings;

    private StasisApplicationInterface $stasisApplicationInterface;

    /**
     * @var MockObject|Applications
     */
    private $ariApplicationsClient;

    /**
     * @var MockObject|LoopInterface
     */
    private $loop;

    /**
     * @var MockObject|LoggerInterface
     */
    private $loggerInterface;

    /**
     * @var Client|MockObject
     */
    private $httpClient;

    public function setUp(): void
    {
        $this->httpClient = $this->createMock(Client::class);

        $this->ariApplicationsClient = new Applications(
            new RestClientSettings('asterisk', 'asterisk'),
            $this->httpClient
        );

        $this->webSocketClientSettings = new WebSocketClientSettings('asterisk', 'asterisk');
        $this->webSocketClientSettings->setAriApplicationsClient($this->ariApplicationsClient);
        $this->loggerInterface = $this->createMock(LoggerInterface::class);
        $this->webSocketClientSettings->setLoggerInterface($this->loggerInterface);
        $this->webSocketClientSettings->setIsInDebugMode(true);

        $this->stasisApplicationInterface =
            new class () implements StasisApplicationInterface {
                /**
                 * @param ChannelUserevent $channelUserevent
                 *
                 * @return void
                 */
                public function onAriEventChannelUserevent(
                    ChannelUserevent $channelUserevent
                ): void {
                    if (((bool) $channelUserevent->getUserevent()->ThrowException) === true) {
                        throw new InvalidArgumentException('jo');
                    }
                }
            };

        $this->loop = EventLoopFactory::create();

        $this->abstractWebSocketClient = new class (
            $this->webSocketClientSettings,
            $this->stasisApplicationInterface,
            $this->loop,
        ) extends AbstractWebSocketClient {
            public function __construct(
                WebSocketClientSettings $webSocketClientSettings,
                StasisApplicationInterface $myApp,
                LoopInterface $loop
            ) {
                parent::__construct($webSocketClientSettings, $myApp);

                $this->loop = $loop;
            }

            public function triggerCreateUri(): string
            {
                $webSocketClientSettings =
                    new WebSocketClientSettings('asterisk', 'asterisk');
                $webSocketClientSettings->setIsSubscribeAll(true);

                return $this->createUri(
                    $webSocketClientSettings,
                    $this->myApp,
                );
            }

            /**
             * @inheritDoc
             */
            public function start(): void
            {
            }

            /**
             * @inheritDoc
             */
            public function getLoop(): LoopInterface
            {
                return $this->loop;
            }

        };
    }

    public function testConstruct(): void
    {
        $this->assertInstanceOf(
            AbstractWebSocketClient::class,
            $this->abstractWebSocketClient
        );
    }

    public function testGetLoop(): void
    {
        $this->assertSame($this->loop, $this->abstractWebSocketClient->getLoop());
    }

    /**
     * @throws AsteriskRestInterfaceException Will never throw this!
     * It is just an annotation supposed to suppress warnings.
     */
    public function testOnConnectionHandlerLogic(): void
    {
        $streamInterface = $this->createMock(StreamInterface::class);
        $streamInterface
            ->method('__toString')
            ->willReturn(json_encode(ApplicationsTest::EXAMPLE));
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($streamInterface);
        $this->httpClient->method('request')->willReturn($response);

        $this->abstractWebSocketClient->onConnectionHandlerLogic();
        $this->assertTrue(true);
    }

    public function testOnMessageHandlerLogic(): void
    {
        $this->abstractWebSocketClient->onMessageHandlerLogic(
            json_encode(
                [
                    'type' => 'ChannelUserevent',
                    'application' => 'ExampleApplication',
                    'timestamp' => 'someTimestamp',
                    'eventname' => 'jo',
                    'userevent' => ['ThrowException' => false]
                ],
                JSON_THROW_ON_ERROR
            )
        );

        $this->assertTrue(true);
    }

    public function testOnMessageHandlerLogicHandlesLogicException(): void
    {
        $this->abstractWebSocketClient->onMessageHandlerLogic(
            json_encode(
                [
                    'type' => 'ChannelUserevent',
                    'application' => 'ExampleApplication',
                    'timestamp' => 'someTimestamp',
                    'eventname' => 'jo',
                    'userevent' => ['ThrowException' => true]
                ],
                JSON_THROW_ON_ERROR
            )
        );

        $this->assertTrue(true);
    }

    public function testCreateUri(): void
    {
        $this->assertStringContainsString(
            'ws://127.0.0.1:8088/ari/events?api_key=asterisk:asterisk&app=',
            $this->abstractWebSocketClient->triggerCreateUri()
        );
        $this->assertStringContainsString(
            '&subscribeAll=true',
            $this->abstractWebSocketClient->triggerCreateUri()
        );
    }

    public function testCreateWithUserErrorHandler(): void
    {
        $webSocketClientSettings = new WebSocketClientSettings('asterisk', 'asterisk');
        $webSocketClientSettings->setErrorHandler(
            static function (string $messageType, Throwable $throwable) {}
        );

        $this->abstractWebSocketClient = new class (
            $webSocketClientSettings,
            $this->createMock(StasisApplicationInterface::class)
        ) extends AbstractWebSocketClient{
            /**
             * @inheritDoc
             */
            public function start(): void
            {
            }

            /**
             * @inheritDoc
             */
            public function getLoop(): LoopInterface
            {
                return EventLoopFactory::create();
            }
        };

        $this->assertInstanceOf(
            AbstractWebSocketClient::class,
            $this->abstractWebSocketClient
        );
    }

    public function testCreateWithInvalidErrorHandler(): void
    {
        $webSocketClientSettings = new WebSocketClientSettings('asterisk', 'asterisk');
        $webSocketClientSettings->setErrorHandler(
            static function (string $e, Throwable $a) {}
        );

        $this->expectException(InvalidArgumentException::class);
        $this->abstractWebSocketClient = new class (
            $webSocketClientSettings,
            $this->createMock(StasisApplicationInterface::class)
        ) extends AbstractWebSocketClient{
            /**
             * @inheritDoc
             */
            public function start(): void
            {
            }

            /**
             * @inheritDoc
             */
            public function getLoop(): LoopInterface
            {
                return EventLoopFactory::create();
            }
        };
    }

    public function testOnMessageHandlerHandlesInvalidAriEvent(): void
    {
        $this->abstractWebSocketClient->onMessageHandlerLogic(
            json_encode(
                [
                    'type' => 'ChannelUserevent',
                ],
                JSON_THROW_ON_ERROR
            )
        );

        $this->assertTrue(true);
    }
}
