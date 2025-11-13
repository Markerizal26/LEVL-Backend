<?php

namespace Modules\Assessments\Observers;

use Modules\Assessments\Events\AttemptCompleted;
use Modules\Assessments\Models\Attempt;

class AttemptObserver
{
    public function updated(Attempt $attempt): void
    {
        if ($attempt->isDirty('status')
            && $attempt->status === 'completed'
            && $attempt->getOriginal('status') !== 'completed'
        ) {
            AttemptCompleted::dispatch($attempt->fresh());
        }
    }
}

