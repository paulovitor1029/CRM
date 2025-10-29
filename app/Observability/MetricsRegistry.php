<?php

namespace App\Observability;

use Illuminate\Support\Facades\Storage;

class MetricsRegistry
{
    private static array $counters = [];
    private static array $histograms = [];
    private static array $histogramBuckets = [];
    private static bool $loaded = false;

    private static function load(): void
    {
        if (self::$loaded) return;
        // Try APCu
        if (function_exists('apcu_fetch')) {
            self::$counters = apcu_fetch('metrics:counters') ?: [];
            self::$histograms = apcu_fetch('metrics:histograms') ?: [];
            self::$histogramBuckets = apcu_fetch('metrics:histogramBuckets') ?: [];
        } else {
            $path = storage_path('app/metrics.json');
            if (is_file($path)) {
                $data = json_decode((string) file_get_contents($path), true) ?: [];
                self::$counters = $data['counters'] ?? [];
                self::$histograms = $data['histograms'] ?? [];
                self::$histogramBuckets = $data['buckets'] ?? [];
            }
        }
        self::$loaded = true;
    }

    private static function persist(): void
    {
        if (function_exists('apcu_store')) {
            apcu_store('metrics:counters', self::$counters);
            apcu_store('metrics:histograms', self::$histograms);
            apcu_store('metrics:histogramBuckets', self::$histogramBuckets);
        } else {
            $path = storage_path('app/metrics.json');
            @file_put_contents($path, json_encode([
                'counters' => self::$counters,
                'histograms' => self::$histograms,
                'buckets' => self::$histogramBuckets,
            ]));
        }
    }

    public static function incCounter(string $name, array $labels = [], float $value = 1.0): void
    {
        self::load();
        $key = self::seriesKey($name, $labels);
        if (!isset(self::$counters[$name])) self::$counters[$name] = [];
        self::$counters[$name][$key] = (self::$counters[$name][$key] ?? 0) + $value;
        self::persist();
    }

    public static function observeHistogram(string $name, array $labels, float $value, array $buckets): void
    {
        self::load();
        if (!isset(self::$histogramBuckets[$name])) self::$histogramBuckets[$name] = $buckets;
        if (!isset(self::$histograms[$name])) self::$histograms[$name] = [];
        $key = self::seriesKey($name, $labels);
        if (!isset(self::$histograms[$name][$key])) {
            self::$histograms[$name][$key] = array_fill(0, count($buckets), 0);
        }
        foreach ($buckets as $i => $le) {
            if ($value <= $le) {
                self::$histograms[$name][$key][$i]++;
            }
        }
        self::persist();
    }

    public static function renderPrometheus(): string
    {
        self::load();
        $out = [];
        foreach (self::$counters as $name => $series) {
            $out[] = '# TYPE '.$name.' counter';
            foreach ($series as $key => $value) {
                [$labels, $labelStr] = self::parseSeriesKey($key);
                $out[] = sprintf('%s%s %s', $name, $labelStr, self::formatFloat($value));
            }
        }
        foreach (self::$histograms as $name => $series) {
            $out[] = '# TYPE '.$name.' histogram';
            $buckets = self::$histogramBuckets[$name] ?? [];
            foreach ($series as $key => $counts) {
                [$labels, $labelStr] = self::parseSeriesKey($key);
                $cum = 0;
                foreach ($counts as $i => $count) {
                    $cum += $count;
                    $labelsOut = self::labelsToString(array_merge($labels, ['le' => (string) $buckets[$i]]));
                    $out[] = sprintf('%s_bucket%s %d', $name, $labelsOut, $cum);
                }
                $out[] = sprintf('%s_count%s %d', $name, $labelStr, array_sum($counts));
            }
        }
        return implode("\n", $out)."\n";
    }

    private static function seriesKey(string $name, array $labels): string
    {
        ksort($labels);
        return $name.'|'.http_build_query($labels);
    }

    private static function parseSeriesKey(string $key): array
    {
        [$name, $qs] = explode('|', $key, 2);
        parse_str($qs, $labels);
        return [$labels, self::labelsToString($labels)];
    }

    private static function labelsToString(array $labels): string
    {
        if (empty($labels)) return '';
        $parts = [];
        foreach ($labels as $k => $v) {
            $parts[] = $k.'="'.str_replace('"','\"',(string)$v).'"';
        }
        return '{'.implode(',', $parts).'}';
    }

    private static function formatFloat(float $v): string
    {
        return sprintf('%.6f', $v);
    }
}

