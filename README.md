## Simple websocket server

Fork of [morozovsk/websocket](https://github.com/morozovsk/websocket).

### Installation
```json
{
    "require": {
        "pzverr/websocket": "dev-master",
    }
}
```

### Symfony Integration Example
services.yml<br/>
```yml
...
services:
    default_daemon_handler:
        class: AppBundle\Service\DefaultDaemonHandler
        arguments: [doctrine.orm.entity_manager]
...
```
AppBundle\Services\DefaultDaemonHandler.php<br/>
```php
use pzverr\websocket\Daemon;
...
class DefaultDaemonHandler extends Daemon
{
    protected $em

    /**
    * @param \Doctrine\ORM\EntityManager $em
    */
    public function __construct($em)
    {
        parent::__construct();
        $this->em = $em;
    }
}
...
```
AppBundle\Console\Command\WebSocketServerCommand.php<br/>
```php
class WebSocketServerCommand extends ContainerAwareCommand
{
    ...
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $servers = [
            'default' => [
                'class' => $this->getContainer()->get("default_daemon_handler"),
                'pid' => '/tmp/websocket_airdump.pid',
                'websocket' => 'tcp://localhost:5001',
                'timer' => 2
            ]
        ];

        $action = $input->getArgument('action');

        $server = $input->getArgument('server');

        $WebSocketServer = new Server($servers[$server]);
        call_user_func(array($WebSocketServer, $action));
    }
}
```
