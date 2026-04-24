<?php
namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    public static function log(string $action, string $message, string $level = 'INFO', string $module = null, array $data = []): void
    {
        try {
            ActivityLog::create([
                'user_id'    => Auth::id(),
                'action'     => $action,
                'level'      => $level,
                'message'    => $message,
                'module'     => $module,
                'data'       => $data ?: null,
                'ip_address' => Request::ip(),
            ]);
        } catch (\Exception $e) {
            \Log::error('ActivityLogger failed: ' . $e->getMessage());
        }
    }

    public static function success(string $action, string $message, string $module = null, array $data = []): void
    {
        self::log($action, $message, 'SUCCESS', $module, $data);
    }

    public static function info(string $action, string $message, string $module = null, array $data = []): void
    {
        self::log($action, $message, 'INFO', $module, $data);
    }

    public static function warning(string $action, string $message, string $module = null, array $data = []): void
    {
        self::log($action, $message, 'WARN', $module, $data);
    }

    public static function error(string $action, string $message, string $module = null, array $data = []): void
    {
        self::log($action, $message, 'ERROR', $module, $data);
    }
}