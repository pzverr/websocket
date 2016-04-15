<?php

namespace pzverr\websocket;

interface ServerInterface
{
    public function start();

    public function stop();

    public function restart();
}