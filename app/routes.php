<?php

Route::get('/converter', [
    'as'    => 'converter',
    'uses'  => 'ConverterController@Init'
]);