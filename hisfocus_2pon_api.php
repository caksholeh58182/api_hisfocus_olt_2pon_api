<?php

/**
 * Hisfocus 2 PON OLT API client.
 *
 * Library sederhana untuk membaca daftar ONU, mencari ONU, rename ONU,
 * dan reboot ONU melalui web interface OLT Hisfocus 2 PON.
 */
class Hisfocus2PonApi
{
    private $host;
    private $username;
    private $password;
    private $timeout;
    private $pons;
    private $oltName;
    private $scheme;

    /**
     * @param string $host IP/domain OLT, contoh: 192.168.0.88
     * @param string $username Username login OLT
     * @param string $password Password login OLT
     * @param array $options Opsi tambahan: timeout, pons, olt_name, scheme
     */
    public function __construct($host, $username, $password, array $options = [])
    {
        $this->host = trim($host);
        $this->username = $username;
        $this->password = $password;
        $this->timeout = isset($options['timeout']) ? (int) $options['timeout'] : 20;
        $this->pons = isset($options['pons']) ? $options['pons'] : ['0/1/1', '0/1/2'];
        $this->oltName = isset($options['olt_name']) ? $options['olt_name'] : 'OLTKU';
        $this->scheme = isset($options['scheme']) ? rtrim($options['scheme'], ':/') : 'http';

        if ($this->host === '') {
            throw new InvalidArgumentException('Host OLT wajib diisi.');
        }

        if (!function_exists('curl_init')) {
            throw new RuntimeException('Ekstensi PHP cURL belum aktif.');
        }
    }

    /**
     * Ambil semua ONU dari seluruh PON yang dikonfigurasi.
     *
     * @return array
     */
    public function getOnuList()
    {
        $allOnu = [];

        foreach ($this->pons as $pon) {
            $result = $this->get('/onuConfigOnuList.asp', [
                'oltponno' => $pon,
            ]);

            if (!empty($result['error'])) {
                continue;
            }

            $rows = $this->parseOnuListResponse($result['response']);

            foreach ($rows as $row) {
                $row['olt_name'] = $this->oltName;
                $row['olt_ip'] = $this->host;
                $row['pon'] = $pon;

                $allOnu[] = $row;
            }
        }

        return $allOnu;
    }

    /**
     * Cari ONU berdasarkan ONU ID.
     *
     * @param string $onuId
     * @return array|null
     */
    public function getOnuById($onuId)
    {
        foreach ($this->getOnuList() as $onu) {
            if ((string) ($onu['onu_id'] ?? '') === (string) $onuId) {
                return $onu;
            }
        }

        return null;
    }

    /**
     * Cari ONU berdasarkan nama, ONU ID, atau MAC address.
     *
     * @param string $keyword
     * @return array
     */
    public function searchOnu($keyword)
    {
        $keyword = strtolower(trim($keyword));
        $result = [];

        if ($keyword === '') {
            return $result;
        }

        foreach ($this->getOnuList() as $onu) {
            $name = strtolower($onu['name'] ?? '');
            $onuId = strtolower($onu['onu_id'] ?? '');
            $mac = strtolower($onu['mac'] ?? '');

            if (
                strpos($name, $keyword) !== false ||
                strpos($onuId, $keyword) !== false ||
                strpos($mac, $keyword) !== false
            ) {
                $result[] = $onu;
            }
        }

        return $result;
    }

    /**
     * Rename ONU berdasarkan ONU ID.
     *
     * @param string $onuId
     * @param string $onuName
     * @return array
     */
    public function renameOnu($onuId, $onuName)
    {
        return $this->setOnu($onuId, $onuName, 'nonOp');
    }

    /**
     * Reboot ONU berdasarkan ONU ID dan nama ONU.
     *
     * @param string $onuId
     * @param string $onuName
     * @return array
     */
    public function rebootOnu($onuId, $onuName)
    {
        return $this->setOnu($onuId, $onuName, 'rebootOp');
    }

    /**
     * Cari ONU dengan keyword, lalu reboot jika hasilnya tepat satu.
     *
     * @param string $keyword
     * @return array
     */
    public function rebootOnuByKeyword($keyword)
    {
        $matches = $this->searchOnu($keyword);

        if (count($matches) === 0) {
            return [
                'ok' => false,
                'message' => 'ONU tidak ditemukan.',
            ];
        }

        if (count($matches) > 1) {
            return [
                'ok' => false,
                'message' => 'ONU lebih dari satu, gunakan keyword yang lebih spesifik.',
                'count' => count($matches),
                'data' => $matches,
            ];
        }

        return $this->rebootOnu($matches[0]['onu_id'], $matches[0]['name']);
    }

    /**
     * Kirim operasi setOnu ke OLT.
     *
     * @param string $onuId
     * @param string $onuName
     * @param string $operation
     * @return array
     */
    public function setOnu($onuId, $onuName, $operation)
    {
        $result = $this->post('/goform/setOnu', [
            'onuId' => $onuId,
            'onuName' => $onuName,
            'onuOperation' => $operation,
        ]);

        return [
            'ok' => ((int) $result['http_code'] === 302),
            'message' => ((int) $result['http_code'] === 302) ? 'Operasi berhasil.' : 'Operasi gagal.',
            'onu_id' => $onuId,
            'onu_name' => $onuName,
            'operation' => $operation,
            'http_code' => $result['http_code'],
            'error' => $result['error'],
            'response' => $result['response'],
        ];
    }

    /**
     * Request GET ke OLT.
     *
     * @param string $path
     * @param array $query
     * @return array
     */
    public function get($path, array $query = [])
    {
        $url = $this->buildUrl($path, $query);
        return $this->request($url);
    }

    /**
     * Request POST ke OLT.
     *
     * @param string $path
     * @param array $data
     * @return array
     */
    public function post($path, array $data = [])
    {
        $url = $this->buildUrl($path);
        return $this->request($url, $data);
    }

    private function request($url, array $postData = null)
    {
        $ch = curl_init($url);

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => $this->username . ':' . $this->password,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_TIMEOUT => $this->timeout,
        ];

        if ($postData !== null) {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = http_build_query($postData);
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return [
            'response' => $response,
            'error' => $error,
            'http_code' => $httpCode,
        ];
    }

    private function buildUrl($path, array $query = [])
    {
        $path = '/' . ltrim($path, '/');
        $url = $this->scheme . '://' . $this->host . $path;

        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        return $url;
    }

    private function parseOnuListResponse($response)
    {
        if (!preg_match(
            "/var\s+ponOnuTable\s*=\s*new\s+Array\s*\((.*?)\);/s",
            (string) $response,
            $match
        )) {
            return [];
        }

        preg_match_all("/'([^']*)'/", $match[1], $values);
        $values = $values[1];
        $onus = [];

        for ($i = 0; $i < count($values); $i += 12) {
            if (!isset($values[$i + 11])) {
                break;
            }

            $onus[] = [
                'onu_id' => $values[$i],
                'name' => $values[$i + 1],
                'mac' => strtoupper($values[$i + 2]),
                'status' => $values[$i + 3],
                'vendor' => $values[$i + 4],
                'model' => $values[$i + 5],
                'ports' => $values[$i + 6],
                'distance' => $values[$i + 7],
                'tx_power' => $values[$i + 10],
                'rx_power' => $values[$i + 11],
            ];
        }

        return $onus;
    }
}
