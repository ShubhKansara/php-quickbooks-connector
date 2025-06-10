<?php
return [
  'username'    => env('QBWC_USERNAME', 'quickbooks'),
  'password'    => env('QBWC_PASSWORD', 'secret'),
  'url'         => env('QBWC_URL', env('APP_URL').'/qbwc'),
  'owner_id'    => '{90A44FB7-33D6-4815-AC85-AC86A7E7123B}',
  'file_id'     => '{57F3B9B6-86F6-4FCC-B1FF-967DE1813123}',
  'mode' => env('QB_MODE','desktop'),
];
