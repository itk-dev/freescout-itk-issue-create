<?php

Route::group([
  'middleware' => 'web',
  'prefix' => \Helper::getSubdirectory(),
  'namespace' => 'Modules\ItkIssueCreate\Http\Controllers'
], function () {
    Route::get('/', 'ItkIssueCreateController@index');
});
