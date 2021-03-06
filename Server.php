<?php

namespace pzverr\websocket;

class Server implements ServerInterface
{
    public function __construct($config)
    {
        $this->config = $config;
    }

    public function start()
    {
        $pid = @file_get_contents($this->config['pid']);
        if ($pid) {
            if (posix_getpgid($pid)) {
                die("already started\r\n");
            } else {
                unlink($this->config['pid']);
            }
        }

        if (empty($this->config['websocket']) && empty($this->config['localsocket']) && empty($this->config['master'])) {
            die("error: config: !websocket && !localsocket && !master\r\n");
        }

        $server = $service = $master = null;

        if (!empty($this->config['websocket'])) {
            $server = stream_socket_server($this->config['websocket'], $errorNumber, $errorString);
            stream_set_blocking($server, 0);

            if (!$server) {
                die("error: stream_socket_server: $errorString ($errorNumber)\r\n");
            }
        }

        if (!empty($this->config['localsocket'])) {
            $service = stream_socket_server($this->config['localsocket'], $errorNumber, $errorString);
            stream_set_blocking($service, 0);

            if (!$service) {
                die("error: stream_socket_server: $errorString ($errorNumber)\r\n");
            }
        }

        if (!empty($this->config['master'])) {
            $master = stream_socket_client($this->config['master'], $errorNumber, $errorString);
            stream_set_blocking($master, 0);

            if (!$master) {
                die("error: stream_socket_client: $errorString ($errorNumber)\r\n");
            }
        }

        if (!empty($this->config['eventDriver']) && $this->config['eventDriver'] == 'libevent') {
            class_alias('pzverr\websocket\Drivers\GenericLibevent', 'pzverr\websocket\Drivers\Generic');
        } elseif (!empty($this->config['eventDriver']) && $this->config['eventDriver'] == 'event') {
            class_alias('pzverr\websocket\Drivers\GenericEvent', 'pzverr\websocket\Drivers\Generic');
        } else {
            class_alias('pzverr\websocket\Drivers\GenericSelect', 'pzverr\websocket\Drivers\Generic');
        }

        file_put_contents($this->config['pid'], posix_getpid());

        $options = [];

        if (isset($this->config['options'])) {
            $options = $this->config['options'];
        }

        $workerClass = $this->config['class'];

        $worker = new $workerClass($server, $service, $master, $options);

        if (!empty($this->config['timer'])) {
            $worker->timer = $this->config['timer'];
        }
        $worker->start();
    }

    public function stop()
    {
        $pid = @file_get_contents($this->config['pid']);
        if ($pid) {
            posix_kill($pid, SIGTERM);
            for ($i=0;$i=10;$i++) {
                sleep(1);

                if (!posix_getpgid($pid)) {
                    unlink($this->config['pid']);
                    return;
                }
            }

            die("don't stopped\r\n");
        } else {
            die("already stopped\r\n");
        }
    }

    public function restart()
    {
        $pid = @file_get_contents($this->config['pid']);
        if ($pid) {
            $this->stop();
        }

        $this->start();
    }
}
