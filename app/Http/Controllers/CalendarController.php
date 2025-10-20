<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CalendarController extends Controller
{
    public function ics(Request $request)
    {
        // Parámetros esperados (con defaults razonables)
        $title = trim($request->string('title')->toString() ?: 'Recordatorio');
        $description = trim($request->string('description')->toString() ?: '');
        $tz = $request->string('tz')->toString() ?: config('app.timezone', 'UTC');

        // Fecha/hora de inicio y fin
        // dtstart: ISO 8601 o 'now' si no viene
        $start = $request->string('dtstart')->toString();
        $start = $start ? Carbon::parse($start, $tz) : now($tz)->addMinutes(5);
        $duration = (int) ($request->integer('duration') ?: 15);
        $end = (clone $start)->addMinutes($duration);

        // RRULE opcional (FREQ=DAILY|WEEKLY|MONTHLY...;INTERVAL=1;COUNT=... etc.)
        $rrule = trim($request->string('rrule')->toString() ?: '');

        // Formatos iCal
        $fmt = 'Ymd\THis';
        $uid = Str::uuid() . '@' . parse_url(config('app.url', request()->getSchemeAndHttpHost()), PHP_URL_HOST);
        $dtstamp = now('UTC')->format($fmt) . 'Z';

        // Si querés forzar TZ explícita en DTSTART/DTEND:
        $dtstart = "DTSTART;TZID={$tz}:" . $start->format($fmt);
        $dtend = "DTEND;TZID={$tz}:" . $end->format($fmt);

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//' . config('app.name', 'App') . '//NONSGML v1.0//ES',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            "UID:{$uid}",
            "DTSTAMP:{$dtstamp}",
            $dtstart,
            $dtend,
            'SUMMARY:' . self::escape($title),
            'DESCRIPTION:' . self::escape($description),
        ];

        if ($rrule !== '') {
            $lines[] = 'RRULE:' . $rrule;
        }

        $lines[] = 'END:VEVENT';
        $lines[] = 'END:VCALENDAR';

        $ics = implode("\r\n", $lines) . "\r\n";

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="recordatorio.ics"',
        ]);
    }

    private static function escape(string $text): string
    {
        // Escapado básico iCal
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace("\n", '\\n', $text);
        $text = str_replace(',', '\,', $text);
        $text = str_replace(';', '\;', $text);
        return $text;
    }
}
