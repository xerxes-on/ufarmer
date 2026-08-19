<?php

declare(strict_types=1);

namespace Modules\Uzgidromet\Observers;

use Modules\Uzgidromet\Models\UzgidrometFile;
use Modules\Uzgidromet\Notifications\TelegramUploadNotifier;

final class UzgidrometFileObserver
{
    public function __construct(private readonly TelegramUploadNotifier $notifier) {}

    public function created(UzgidrometFile $file): void
    {
        $this->notifier->notifyUploaded($file);
    }
}
