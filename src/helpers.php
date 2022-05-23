<?php

function version_asset($path) {
    return app('ResourceInclude')->url($path);
}