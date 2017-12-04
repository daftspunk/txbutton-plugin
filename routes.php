<?php

Route::post('cgi-bin/webscr', function() {
    // Raise a sale based on the info

    // Sale hash
    $hash = 'asdasd21312321231';

    return Redirect::to('web/'.$hash);
});

// Route::group(['prefix' => 'api'], function() {
// 
// });
