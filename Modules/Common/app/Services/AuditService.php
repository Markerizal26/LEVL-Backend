<?php

namespace Modules\Common\Services;

use Modules\Common\Models\Audit;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    /**
     * Log an audit entry.
     *
     * @param string $action Action performed (create, update, delete, etc.)
     * @param mixed $target Target model or array with target_table and target_id
     * @param array $options Additional options (module, context, meta, properties)
     * @return Audit
     */
    public function log(
        string $action,
        $target = null,
        array $options = []
    ): Audit {
        $user = Auth::guard('api')->user();

        // Determine target information
        $targetTable = null;
        $targetType = null;
        $targetId = null;

        if ($target) {
            if (is_object($target) && method_exists($target, 'getTable')) {
                $targetTable = $target->getTable();
                $targetType = get_class($target);
                $targetId = $target->id;
            } elseif (is_array($target)) {
                $targetTable = $target['table'] ?? null;
                $targetType = $target['type'] ?? null;
                $targetId = $target['id'] ?? null;
            }
        }

        // Determine actor information
        $actorType = null;
        $actorId = null;
        if ($user) {
            $actorType = get_class($user);
            $actorId = $user->id;
        }

        // Get request metadata
        $request = request();
        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();

        return Audit::create([
            'action' => $action,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'user_id' => $user?->id,
            'target_table' => $targetTable,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'module' => $options['module'] ?? null,
            'context' => $options['context'] ?? 'application',
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'meta' => $options['meta'] ?? null,
            'properties' => $options['properties'] ?? null,
            'logged_at' => now(),
        ]);
    }

    /**
     * Log a system audit.
     */
    public function logSystem(
        string $action,
        $target = null,
        array $options = []
    ): Audit {
        $options['context'] = 'system';
        return $this->log($action, $target, $options);
    }

    /**
     * Log an application audit.
     */
    public function logApplication(
        string $action,
        $target = null,
        array $options = []
    ): Audit {
        $options['context'] = 'application';
        return $this->log($action, $target, $options);
    }
}

