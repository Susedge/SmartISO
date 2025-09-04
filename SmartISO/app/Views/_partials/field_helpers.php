<?php
// Shared helpers for rendering submission field values consistently across views
if (!function_exists('render_submission_value')) {
    function render_submission_value($raw) {
        if ($raw === null || $raw === '') return '-';
        if (is_array($raw)) return esc(implode(', ', $raw));
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) return esc(implode(', ', $decoded));
        return esc($raw);
    }
}

if (!function_exists('render_field_display')) {
    function render_field_display($field, $submission_data) {
        $name = $field['field_name'] ?? '';
        $raw = $submission_data[$name] ?? null;

        // gather options (array of labels or objects)
        $opts = [];
        if (!empty($field['options']) && is_array($field['options'])) {
            $opts = $field['options'];
        } elseif (!empty($field['default_value'])) {
            $decoded = json_decode($field['default_value'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $opts = $decoded;
            } else {
                $lines = array_filter(array_map('trim', explode("\n", $field['default_value'])));
                if (!empty($lines)) $opts = $lines;
            }
        }

        // normalize selected values into array
        $selected = [];
        if (is_array($raw)) {
            $selected = $raw;
        } else {
            $dec = @json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($dec)) {
                $selected = $dec;
            } elseif ($raw === null || $raw === '') {
                $selected = [];
            } else {
                $selected = preg_split('/\s*[,;]\s*/', (string)$raw);
            }
        }

        if (empty($opts)) {
            return render_submission_value($raw);
        }

        $labels = [];
        foreach ($opts as $opt) {
            if (is_array($opt)) {
                $optLabel = $opt['label'] ?? ($opt['sub_field'] ?? '');
                $optValue = $opt['sub_field'] ?? ($opt['label'] ?? '');
            } else {
                $optLabel = $opt;
                $optValue = $opt;
            }
            foreach ($selected as $s) {
                if (strcasecmp((string)$s, (string)$optValue) === 0 || strcasecmp((string)$s, (string)$optLabel) === 0) {
                    $labels[] = $optLabel;
                    break;
                }
            }
        }

        if (empty($labels)) return render_submission_value($raw);
        return esc(implode(', ', $labels));
    }
}

?>
