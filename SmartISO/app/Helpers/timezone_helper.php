<?php

if (!function_exists('get_system_timezone')) {
    /**
     * Get the system timezone from configuration
     */
    function get_system_timezone()
    {
        $db = \Config\Database::connect();
        try {
            $builder = $db->table('configurations');
            $config = $builder->where('config_key', 'system_timezone')->get()->getRow();
            return $config && isset($config->config_value) ? $config->config_value : 'Asia/Singapore';
        } catch (\Exception $e) {
            return 'Asia/Singapore'; // Default fallback
        }
    }
}

if (!function_exists('set_system_timezone')) {
    /**
     * Set the system timezone for the current request
     */
    function set_system_timezone()
    {
        $timezone = get_system_timezone();
        date_default_timezone_set($timezone);
        return $timezone;
    }
}

if (!function_exists('format_date')) {
    /**
     * Format date according to system timezone
     */
    function format_date($date, $format = 'Y-m-d H:i:s')
    {
        if (empty($date)) {
            return '';
        }
        
        $timezone = get_system_timezone();
        
        try {
            if (is_string($date)) {
                $dateTime = new \DateTime($date);
            } else {
                $dateTime = $date;
            }
            
            $dateTime->setTimezone(new \DateTimeZone($timezone));
            return $dateTime->format($format);
        } catch (\Exception $e) {
            return $date; // Return original if formatting fails
        }
    }
}

if (!function_exists('now_in_timezone')) {
    /**
     * Get current datetime in system timezone
     */
    function now_in_timezone($format = 'Y-m-d H:i:s')
    {
        $timezone = get_system_timezone();
        $now = new \DateTime('now', new \DateTimeZone($timezone));
        return $now->format($format);
    }
}

if (!function_exists('convert_to_user_timezone')) {
    /**
     * Convert UTC datetime to user timezone
     */
    function convert_to_user_timezone($utcDate, $format = 'Y-m-d H:i:s')
    {
        if (empty($utcDate)) {
            return '';
        }
        
        $timezone = get_system_timezone();
        
        try {
            $utcDateTime = new \DateTime($utcDate, new \DateTimeZone('UTC'));
            $utcDateTime->setTimezone(new \DateTimeZone($timezone));
            return $utcDateTime->format($format);
        } catch (\Exception $e) {
            return $utcDate;
        }
    }
}
