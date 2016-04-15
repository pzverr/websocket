## Simple websocket server

Fork of [morozovsk/websocket](https://github.com/morozovsk/websocket).
Increased MAX_SOCKET_BUFFER_SIZE to 262144.
Add Custom options in Daemon Class.

### Installation
```json
{
    "require": {
        "pzverr/websocket": "dev-master",
    }
}
```

### Symfony2 Example
FooBundle\Console\Command\WebSocketServerCommand.php<br/>
```php
class WebSocketServerCommand extends ContainerAwareCommand
{
    ...
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();

        $servers = [
            'default' => [
                'class' => 'AppBundle\WebSocket\DefaultDaemonHandler',
                'pid' => '/tmp/websocket_default.pid',
                'websocket' => 'tcp://localhost:5001',
                'options' => [
                    'em' => $em,
                ]
            ]
        ];

        $action = $input->getArgument('action');

        $server = $input->getArgument('server');

        $WebSocketServer = new Server($servers[$server]);
        call_user_func(array($WebSocketServer, $action));
    }
}

```
AppBundle\Services\DefaultDaemonHandler.php<br/>
```php
use pzverr\websocket\Daemon;
...
class DefaultDaemonHandler extends Daemon
{
    protected function onOpen($connectionId, $info)
    {
        $entity = $this->em->getRepository('FooBundle:Entity')->find(1);
        //etc
    }
}
...
```