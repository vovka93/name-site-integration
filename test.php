<?php

  define('C_REST_WEB_HOOK_URL', '-->>___WEB HOOK___<<--');

  require_once('./b24.php');

  \NAME\Lead::new(
    'НАЗВАНИЕ ЛИДА',
    'ФАМИЛИЯ',
    'ИМЯ',
    'ОТЧЕСТВО',
    '380110000000',
    'email@email.com',
    array(
      [
        'NAME'     => 'НАЗВАНИЕ ТОВАРА 1',
        'PRICE'    => 1,
        'QUANTITY' => 2
      ],
      [
        'NAME'     => 'НАЗВАНИЕ ТОВАРА 2',
        'PRICE'    => 1,
        'QUANTITY' => 2
      ]
    )
  );

