<?php

/** @copyright 2019 ng-voice GmbH */

declare(strict_types=1);

namespace NgVoice\AriClient\WebSocketClient;

use Exception;
use Monolog\Logger;
use Nekland\Woketo\Message\MessageHandlerInterface;
use NgVoice\AriClient\{AsteriskStasisApplication,
    Helper,
    RestClient\Applications,
    RestClient\AriRestClientSettings};
use React\EventLoop\LoopInterface;

/**
 * Class WebSocketClient
 *
 * Within this library you should only either subscribe to one or all running StasisApps
 * on Asterisk. It is a good idea - regarding to the concept of 'separation of concerns'
 * - to have a worker process for each of your applications, so your code doesn't get
 * messy.
 *
 * @package NgVoice\ArClient\WeSocketClient
 *
 * @author Lukas Stermann <lukas@ng-voice.com>
 */
final class WebSocketClient
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ModifiedWoketoWebSocketClient
     */
    private $modifiedWoketoWebSocketClient;

    /**
     * @var MessageHandlerInterface
     */
    private $messageHandler;

    /**
     * WebSocketClient constructor.
     *
     * @param WebSocketClientSettings $webSocketSettings The settings for the asterisk web
     *     socket.
     * @param AsteriskStasisApplication $stasisApplication Application to subscribe to.
     * @param MessageHandlerInterface|null $messageHandler Handles incoming Message from
     *     Asterisk. Look at AriMessageHandler and RemoteAppMessageHandler for examples.
     * @param bool $subscribeAll Subscribe to all Asterisk events.
     * If provided, the applications listed will be subscribed to all events,
     * effectively disabling the application specific subscriptions. Default is 'false'.
     * @param ModifiedWoketoWebSocketClient|null $woketoWebSocketClient Optional
     * WebSocketClient to make this class testable
     */
    public function __construct(
        WebSocketClientSettings $webSocketSettings,
        AsteriskStasisApplication $stasisApplication,
        MessageHandlerInterface $messageHandler = null,
        bool $subscribeAll = false,
        ModifiedWoketoWebSocketClient $woketoWebSocketClient = null
    ) {
        $this->logger = Helper::initLogger(self::class);
        $appName = Helper::getShortClassName($stasisApplication);

        /*
         * Within this library you should only either subscribe to one or all running
         * StasisApps on Asterisk.
         * It is a good idea - regarding separation of concerns - to have a worker
         * process for each application you are running, so your code doesn't get messy.
         */

        // Initialize the WebSocket
        $wsType = $webSocketSettings->isWssEnabled() ? 'wss' : 'ws';

        $wsUrl =
            "{$wsType}://"
            . "{$webSocketSettings->getHost()}:"
            . "{$webSocketSettings->getPort()}{$webSocketSettings->getRootUri()}";

        $subscribeAllParameter =
            $subscribeAll ? '&subscribeAll=true' : '&subscribeAll=false';

        $uri =
            "{$wsUrl}/events?api_key="
            . "{$webSocketSettings->getUser()}:{$webSocketSettings->getPassword()}"
            . "&app={$appName}{$subscribeAllParameter}";

        $this->logger->debug("URI to asterisk: '{$uri}'");

        if ($messageHandler === null) {
            $messageHandler = new AriFilteredMessageHandler(
                $stasisApplication,
                new Applications(
                    new AriRestClientSettings(
                        $webSocketSettings->getUser(),
                        $webSocketSettings->getPassword()
                    )
                )
            );
        }

        $this->messageHandler = $messageHandler;

        if ($woketoWebSocketClient === null) {
            $woketoWebSocketClient = new ModifiedWoketoWebSocketClient($uri);
        }

        $this->modifiedWoketoWebSocketClient = $woketoWebSocketClient;
    }

    /**
     * Subscribe to the WebSocket of your Asterisk instance and handle Events in a
     * message handler.
     */
    public function start(): void
    {
        try {
            $this->modifiedWoketoWebSocketClient->start($this->messageHandler);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), [__FUNCTION__]);
        }
    }

    /**
     * Get the ReactPHP event loop resource associated with this WebSocketClient.
     *
     * @return LoopInterface
     */
    public function getLoop(): LoopInterface
    {
        return $this->modifiedWoketoWebSocketClient->getLoop();
    }
}
