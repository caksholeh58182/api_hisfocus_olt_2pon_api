Hisfocus 2 PON API PHP Library
==============================

Library PHP sederhana untuk mengakses OLT Hisfocus 2 PON melalui web interface.
Library ini dibuat agar kode OLT bisa dipakai ulang oleh banyak project, mirip
cara penggunaan file library seperti routeros_api.class.php.


Requirement
-----------

1. PHP 7.0 atau lebih baru.
2. Ekstensi PHP cURL aktif.
3. OLT bisa diakses dari server/aplikasi PHP.
4. Username dan password web login OLT tersedia.


File Library
------------

hisfocus_2pon_api.php

Class utama:

Hisfocus2PonApi


Cara Pakai
----------

Copy folder hisfocus_2pon_api ke project PHP Anda, lalu include file library:

<?php

require_once __DIR__ . '/hisfocus_2pon_api/hisfocus_2pon_api.php';

$olt = new Hisfocus2PonApi('192.168.0.88', 'admin', 'admin', [
    'olt_name' => 'OLTKU',
    'timeout' => 20,
    'pons' => ['0/1/1', '0/1/2'],
]);


Contoh Ambil Semua ONU
----------------------

<?php

require_once __DIR__ . '/hisfocus_2pon_api/hisfocus_2pon_api.php';

$olt = new Hisfocus2PonApi('192.168.0.88', 'admin', 'admin');

$data = $olt->getOnuList();

header('Content-Type: application/json');
echo json_encode([
    'ok' => true,
    'count' => count($data),
    'data' => $data,
]);


Contoh Cari ONU
---------------

<?php

require_once __DIR__ . '/hisfocus_2pon_api/hisfocus_2pon_api.php';

$olt = new Hisfocus2PonApi('192.168.0.88', 'admin', 'admin');

$hasil = $olt->searchOnu('jaya');

header('Content-Type: application/json');
echo json_encode([
    'ok' => true,
    'count' => count($hasil),
    'data' => $hasil,
]);


Contoh Cari ONU Berdasarkan ONU ID
----------------------------------

<?php

require_once __DIR__ . '/hisfocus_2pon_api/hisfocus_2pon_api.php';

$olt = new Hisfocus2PonApi('192.168.0.88', 'admin', 'admin');

$onu = $olt->getOnuById('1');

header('Content-Type: application/json');
echo json_encode([
    'ok' => $onu !== null,
    'data' => $onu,
]);


Contoh Rename ONU
-----------------

<?php

require_once __DIR__ . '/hisfocus_2pon_api/hisfocus_2pon_api.php';

$olt = new Hisfocus2PonApi('192.168.0.88', 'admin', 'admin');

$result = $olt->renameOnu('1', 'NAMA-ONU-BARU');

header('Content-Type: application/json');
echo json_encode($result);


Contoh Reboot ONU
-----------------

<?php

require_once __DIR__ . '/hisfocus_2pon_api/hisfocus_2pon_api.php';

$olt = new Hisfocus2PonApi('192.168.0.88', 'admin', 'admin');

$result = $olt->rebootOnu('1', 'NAMA-ONU');

header('Content-Type: application/json');
echo json_encode($result);


Contoh Reboot ONU Berdasarkan Nama/Keyword
------------------------------------------

<?php

require_once __DIR__ . '/hisfocus_2pon_api/hisfocus_2pon_api.php';

$olt = new Hisfocus2PonApi('192.168.0.88', 'admin', 'admin');

$result = $olt->rebootOnuByKeyword('ppp15');

header('Content-Type: application/json');
echo json_encode($result);


Daftar Method
-------------

getOnuList()
    Mengambil semua ONU dari PON yang dikonfigurasi.

getOnuById($onuId)
    Mengambil satu ONU berdasarkan ONU ID.

searchOnu($keyword)
    Mencari ONU berdasarkan nama, ONU ID, atau MAC address.

renameOnu($onuId, $onuName)
    Mengubah nama ONU.

rebootOnu($onuId, $onuName)
    Reboot ONU berdasarkan ONU ID dan nama ONU.

rebootOnuByKeyword($keyword)
    Mencari ONU berdasarkan keyword, lalu reboot jika hasil hanya satu.

get($path, array $query = [])
    Request GET manual ke OLT.

post($path, array $data = [])
    Request POST manual ke OLT.


Format Data ONU
---------------

Setiap ONU dikembalikan dalam bentuk array:

[
    'olt_name' => 'OLTKU',
    'olt_ip' => '192.168.0.88',
    'pon' => '0/1/1',
    'onu_id' => '1',
    'name' => 'NAMA-ONU',
    'mac' => 'AABBCCDDEEFF',
    'status' => 'online',
    'vendor' => 'VENDOR',
    'model' => 'MODEL',
    'ports' => '1',
    'distance' => '1000',
    'tx_power' => '-',
    'rx_power' => '-',
]


Opsi Constructor
----------------

$olt = new Hisfocus2PonApi($host, $username, $password, [
    'olt_name' => 'OLTKU',
    'timeout' => 20,
    'pons' => ['0/1/1', '0/1/2'],
    'scheme' => 'http',
]);

olt_name
    Nama OLT yang akan muncul pada data ONU.

timeout
    Batas waktu request dalam detik.

pons
    Daftar PON yang akan dibaca.

scheme
    Protocol akses OLT. Default: http.


Catatan
-------

1. Beberapa OLT mengembalikan HTTP code 302 saat operasi rename/reboot berhasil.
2. Jika struktur halaman web OLT berbeda, parsing daftar ONU mungkin perlu
   disesuaikan.
3. Simpan username dan password di file konfigurasi project Anda sendiri,
   jangan hardcode credential pada file library jika library dibagikan publik.


Tested Device
-------------

This library has been tested on:

Model:
    Hisfocus 2 PON OLT

Software Version:
    v7.68

Revision:
    Release20220825

Compatibility with other firmware versions may vary.
Please open an issue if you find differences in page structure or API behavior.
