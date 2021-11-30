<?php

use Cake\Event\EventManager;
use Rest\Error\RestErrorHandleMiddleware;

/**
 * Add the custom Rest Error Handler Middleware
 */
EventManager::instance()->on(
    'Server.buildMiddleware',
    [],
    function ($event, $middlewareStack) {
        $middlewareStack->add(new RestErrorHandleMiddleware());
    }
);
