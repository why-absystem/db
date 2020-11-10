# GFCM

Google Firebase Cloud Messaging with PHP

#### INSTALLATION :fire:

`composer require absystem/gfcm`

#### REQUIREMENTS :warning: 

:black_small_square: PHP >=5.6.
<br/>
:black_small_square: <a href="https://getcomposer.org/">Composer</a>.
<br/>
:black_small_square: <a href="https://github.com/guzzle/guzzle">Guzzle v6.x</a> sudah include di package.

#### SETTING CONFIG :globe_with_meridians:

```php

$config = [
	'server_key' => '', // kunci server dari google console
	'base_url'   => 'https://fcm.googleapis.com/fcm/send'
];

//array token device
$tokendevice = [];
```

#### CONTOH PENGGUNAAN :computer:
```php
require_once __DIR__ . '/vendor/autoload.php';
use ABSystem\Google\FCM;

$fcm = new FCM($config);
$fcm->setTokenDevice($tokendevice);
$fcm->setDataPayload([
	'koordinat' => [
		'lat' => '',
		'lng' => '',
	],
	'link'	=> [
		'page'=> '/map.html'
	],
]);
$fcm->setPesan('Judul Notifikasi', 'Isi pesan yang tampil.');
$fcm->kirim();

```