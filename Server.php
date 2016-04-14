<?php

namespace pzverr\websocket;

class Server
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
            class_alias('morozovsk\websocket\GenericLibevent', 'morozovsk\websocket\Generic');
        } elseif (!empty($this->config['eventDriver']) && $this->config['eventDriver'] == 'event') {
            class_alias('morozovsk\websocket\GenericEvent', 'morozovsk\websocket\Generic');
        } else {
            class_alias('morozovsk\websocket\GenericSelect', 'morozovsk\websocket\Generic');
        }

        file_put_contents($this->config['pid'], posix_getpid());

        $worker = $this->config['class'];

        $worker->setServer($server);
        $worker->setService($service);
        $worker->setMaster($master);

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
