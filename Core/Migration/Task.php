<?php

namespace Cloudinary\Cloudinary\Core\Migration;

interface Task
{
    public function hasStarted();

    public function hasBeenStopped();

    public function stop();

    public function start();
}
