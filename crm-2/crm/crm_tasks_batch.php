<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../api.php';

$api = new Api("https://demo.contractors.es", "admin", "admin", "en");

try {
    $endpoint = '/api/crm/tasks/batch';
    // Tworzymy paczkę zadań demonstrując różne formaty dat i godzin deadlinów
    $rawSamples = [
        ['date' => '2025-12-05', 'time' => '15:45'],
        ['date' => '05/12/2025', 'time' => '3:15 PM'],
        ['date' => 'December 6, 2025', 'time' => '07:00'],
        ['date' => '2025.12.07', 'time' => '08:30 AM'],
    ];

    $dateFormats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'Y.m.d', 'd.m.Y', 'F j, Y', 'F d, Y'];
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
    $titlePrefix = 'Task batch ' . date('YmdHis');

    foreach ($rawSamples as $index => $sample) {
        $normalizedDate = $normalizeDate($sample['date']);
        $normalizedTime = $normalizeTime($sample['time']);

        if (!$normalizedDate || !$normalizedTime) {
            echo 'Pomijam niepoprawny przykład: ' . json_encode($sample) . PHP_EOL;
            continue;
        }

        $resources[] = [
            'title' => $titlePrefix . ' #' . ($index + 1),
            'description' => 'Źródłowe wartości: ' . $sample['date'] . ' / ' . $sample['time'],
            'deadline_date' => $normalizedDate,
            'deadline_time' => $normalizedTime,
            'priority' => ($index % 3) + 1,
            'status' => 1,
        ];
    }

    if (empty($resources)) {
        throw new RuntimeException('Brak poprawnych danych do wysłania w batchu.');
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
