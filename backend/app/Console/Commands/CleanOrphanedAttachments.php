<?php

namespace App\Console\Commands;

use App\Models\MessageAttachment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanOrphanedAttachments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'connectly:clean-orphaned-attachments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean physical files for deleted message attachments older than retention threshold';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Scanning for orphaned or deleted message attachments...');

        $deletedAttachments = MessageAttachment::whereHas('message', function ($query) {
            $query->onlyTrashed()->where('deleted_at', '<', now()->subDays(30));
        })->get();

        $count = 0;
        foreach ($deletedAttachments as $attachment) {
            $disk = Storage::disk($attachment->disk);
            if ($disk->exists($attachment->path)) {
                $disk->delete($attachment->path);
                $count++;
            }
            $attachment->delete();
        }

        $this->info("Cleaned {$count} expired attachment files.");
        return Command::SUCCESS;
    }
}
