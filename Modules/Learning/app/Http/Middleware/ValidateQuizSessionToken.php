<?php

declare(strict_types=1);

namespace Modules\Learning\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Learning\Enums\QuizSubmissionStatus;
use Modules\Learning\Models\QuizSubmission;
use Symfony\Component\HttpFoundation\Response;

class ValidateQuizSessionToken
{
    public function handle(Request $request, Closure $next): Response
    {
        if (\App\Support\LoadtestBypass::isEnabled($request)) {
            return $next($request);
        }

        $submission = $request->route('submission');

        if (! $submission instanceof QuizSubmission) {
            return $next($request);
        }

        if ($submission->status !== QuizSubmissionStatus::Draft) {
            return $next($request);
        }

        $user = $request->user();
        if ($user && ! $user->hasRole('Student')) {
            return $next($request);
        }

        if ($user && $submission->user_id !== $user->id) {
            return $next($request);
        }

        $token = $request->header('X-Session-Token');

        if (empty($token)) {
            return response()->json([
                'success' => false,
                'message' => __('messages.quiz_submissions.session_token_required'),
                'errors' => ['session_token' => [__('messages.quiz_submissions.session_token_required')]],
                'data' => null,
            ], 422);
        }

        if (! hash_equals((string) $submission->session_token, (string) $token)) {
            return response()->json([
                'success' => false,
                'message' => __('messages.quiz_submissions.session_taken_over'),
                'errors' => null,
                'data' => null,
            ], 409);
        }

        return $next($request);
    }
}
