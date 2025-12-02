<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $endpoint = '/api/crm/phonecalls/batch';
    // Tworzymy paczkę połączeń telefonicznych na podstawie różnych formatów dat/godzin

    $rawSamples = [
        ['date' => '2025-12-01', 'time' => '13:15'],
        ['date' => '01-12-2025', 'time' => '1:45 PM'],
        ['date' => 'December 01, 2025', 'time' => '08:30'],
        ['date' => '2025/12/01', 'time' => '07:05 AM'],
    ];

    $dateFormats = ['Y-m-d', 'd-m-Y', 'd/m/Y', 'm/d/Y', 'F d, Y', 'M d, Y', 'Y/m/d'];
    $timeFormats = ['H:i', 'H:i:s', 'g:i A', 'g:iA', 'h:i A'];

    $normalizeDate = static function (string $value) use ($dateFormats): ?string {
        foreach ($dateFormats as $format) {
            $dt = DateTime::createFromFormat($format, $value);
            if ($dt instanceof DateTime) {
                return $dt->format('Y-m-d');
            }
        }

        $dt = date_create($value);
        return $dt ? $dt->format('Y-m-d') : null;
    };

    $normalizeTime = static function (string $value) use ($timeFormats): ?string {
        foreach ($timeFormats as $format) {
            $dt = DateTime::createFromFormat($format, $value);
            if ($dt instanceof DateTime) {
                return $dt->format('H:i');
            }
        }

        $dt = date_create($value);
        return $dt ? $dt->format('H:i') : null;
    };

    $resources = [];
    $subjectPrefix = 'PhoneCall batch ' . date('YmdHis');

    foreach ($rawSamples as $index => $sample) {
        $normalizedDate = $normalizeDate($sample['date']);
        $normalizedTime = $normalizeTime($sample['time']);

        if (!$normalizedDate || !$normalizedTime) {
            echo 'Pomijam niepoprawny wpis: ' . json_encode($sample) . PHP_EOL;
            continue;
        }

        $resources[] = [
            'subject' => $subjectPrefix . ' #' . ($index + 1),
            'description' => 'Źródłowe wartości: ' . $sample['date'] . ' / ' . $sample['time'],
            'date' => $normalizedDate,
            'time' => $normalizedTime,
            'priority' => ($index % 3) + 1,
            'status' => 1,
            'contact_company_switch' => 3,
            'other_customer_name' => 'Batch klient #' . ($index + 1),
        ];
    }

    if (empty($resources)) {
        throw new RuntimeException('Nie udało się przygotować żadnego połączenia.');
    }

    $payload = [
        'resources' => $resources,
    ];

    $response = $api->post($endpoint, $payload);

    $body = json_decode($response->getBody()->getContents(), true);
    echo json_encode($body, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
