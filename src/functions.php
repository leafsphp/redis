<?php

/**
 * Return redis object
 *
 * @return \Leaf\Redis
 */
function redis()
{
    if (!(\Leaf\Config::getStatic('redis'))) {
        \Leaf\Config::singleton('redis', function () {
            return new \Leaf\Redis();
        });
    }

    return \Leaf\Config::get('redis');
}
