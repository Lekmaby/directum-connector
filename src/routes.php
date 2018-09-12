<?php

Route::group(['prefix' => 'dircon'], function () {
    Route::get('test', function () {
        echo 'Directum Connector available!';
    });
});
