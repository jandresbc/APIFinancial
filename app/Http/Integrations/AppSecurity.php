<?php
namespace App\Http\Integrations;

use Psy\Util\Json;

class AppSecurity
{
    protected $platform = "Nuovo";
    protected $urlBase = 'https://app.nuovopay.com/dm/api/';

    /**
     * @throws \JsonException
     */
    public function requestSecurity ($url, $method, $data = null) {
        $full_url = $this->urlBase . $url;
        $options = [];
        if ($method === 'PATCH' || $method === 'POST') {
            $options = array(
                "http" => array(
                    "ignore_errors" => true,
                    "header" => "Content-Type: application/json\r\n" .
                        "Accept: application/json\r\n" .
                        "Authorization: Token 6efca0d204724b9da472eb0faa07ef3d",
                    "method" => $method,
                    "content" => $data,
                ),
            );
        } else if ($method === 'GET') {
            $options = array(
                "http" => array(
                    "ignore_errors" => true,
                    "header" => "Content-Type: application/json\r\n" .
                        "Accept: application/json\r\n" .
                        "Authorization: Token 6efca0d204724b9da472eb0faa07ef3d",
                    "method" => $method,
                ),
            );
        }

        $context = stream_context_create($options);
        return json_decode(file_get_contents($full_url, false, $context), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws \JsonException
     */
    public function unlock ($device, $balance, $platform = null, $lockDate = null) {
        $this->platform = $platform;
        $device = (int)$device;
        switch ($this->platform) {
            case 'Nuovo':
                $data = null;
                try {
                    if (isset($lockDate)) {
                        $data = json_encode(array(
                            "device_ids" => [$device],
//                            "auto_lock_date" => $lockDate
                        ), JSON_THROW_ON_ERROR);
                    } else {
                        $data = json_encode(array(
                            "device_ids" => [$device]
                        ), JSON_THROW_ON_ERROR);
                    }
                    $info_device = $this->infoDevice($device);
                    $result = $this->requestSecurity('v2/devices/unlock.json', 'PATCH', $data);
                    $balance = number_format($balance, 2, ',', '.');
                    $message = '';
                    if (isset($info_device['device_info']['locked'], $result['unlocked_devices']) && count($info_device) > 0 && $info_device['device_info']['locked'] && $result && $balance > 0) {
                        $message = '¡Gracias! por su pago su dispositivo a sido <b>DESBLOQUEADO</b>. En estos momentos tienes un saldo pendiente de <b>$' . $balance . '</b>. En caso de tener bloqueado aún tu dispositivo, ingresa este código para desbloquearlo: <b>' . $result['unlocked_devices'][0]['unlock_code'] . '</b>. Lo invitamos a realizar el pago del saldo pendiente en los próximos 3 días para evitar ser bloqueado nuevamente. Paga en Efecty al convenio <b>112459</b> o paga en línea en el siguiente link: https://n9.cl/key1l';
                        $this->sendMessage($device, $message);
                    }
                    if ($result === false) {
                        return [
                            'message' => 'EROR AL REALIZAR LA PETICIÓN'
                        ];
                    }
                    return [
                        'message' => 'TELEFONO DESBLOQUEADO',
                        'response' => $result
                    ];
                } catch (\Exception $e) {
                    print_r([
                        'code' => $e->getCode(),
                        'exceptionMessage' => $e->getMessage(),
                        'line_error' => $e->getLine(),
                        'file_error' => $e->getFile()
                    ]);
                }
                break;
        }
    }

    /**
     * @throws \JsonException
     */
    public function lock ($device): array
    {
        $data = json_encode([
            'device_ids[]' => $device
        ], JSON_THROW_ON_ERROR);

        $result = $this->requestSecurity('v1/devices/lock.json', 'PATCH', $data);

        return [
            'message' => 'Bloqueo realizado',
            'result' => $result
        ];
    }

    /**
     * @throws \JsonException
     */
    public function sendMessage ($device, $message): void
    {
        $data = json_encode(array(
            "message_text" => $message,
            "device_ids" => [$device],
            "device_groups_ids" => [],
            "blocked_devices" => true,
            "un_blocked_devices" => true
        ), JSON_THROW_ON_ERROR);
        $result = $this->requestSecurity('v1/payment_reminders/send_message.json', 'POST', $data);

        print_r($result);
    }

    /**
     * @throws \JsonException
     */
    public function infoDevice ($device) {
        return $this->requestSecurity('v1/devices/{id}.json?device_id=' . $device, 'GET');
    }
}
