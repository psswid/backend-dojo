<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\Topic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Seeds coding tasks from database/seeders/content/tasks/*.json (one file per module).
 */
class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $files = File::glob(database_path('seeders/content/tasks/*.json'));

        foreach ($files as $file) {
            $data = json_decode(File::get($file), true);

            if (! is_array($data) || empty($data['topic_slug'])) {
                $this->command?->warn("Skipping invalid task file: {$file}");
                continue;
            }

            $topic = Topic::where('slug', $data['topic_slug'])->first();

            if (! $topic) {
                $this->command?->warn("Task file references unknown topic slug '{$data['topic_slug']}': {$file}");
                continue;
            }

            foreach ($data['tasks'] ?? [] as $t) {
                if (empty($t['title'])) {
                    continue;
                }

                Task::updateOrCreate(
                    ['topic_id' => $topic->id, 'title' => $t['title']],
                    [
                        'type' => $t['type'] ?? 'snippet',
                        'description' => $t['description'] ?? '',
                        'starter_code' => $t['starter_code'] ?? null,
                        'test_suite' => $t['test_suite'] ?? null,
                        'solution' => $t['solution'] ?? null,
                        'rubric' => $t['rubric'] ?? null,
                        'difficulty' => $t['difficulty'] ?? 'medium',
                        'tags' => $t['tags'] ?? null,
                    ]
                );
            }
        }

        $this->command?->info('Tasks seeded from '.count($files).' file(s).');
    }
}
