<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\Resource;
use App\Models\Topic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Seeds the curriculum from versioned JSON files in database/seeders/content/*.json.
 *
 * Content is data, not code — each file is self-contained (topic + questions + resources)
 * and can be regenerated or replaced without touching the seeder.
 */
class CurriculumSeeder extends Seeder
{
    public function run(): void
    {
        $files = File::glob(database_path('seeders/content/*.json'));

        foreach ($files as $file) {
            $data = json_decode(File::get($file), true);

            if (! is_array($data) || empty($data['topic']['slug'])) {
                $this->command?->warn("Skipping invalid curriculum file: {$file}");
                continue;
            }

            $this->seedFile($data);
        }

        $this->command?->info('Curriculum seeded: '.count($files).' topic file(s).');
    }

    private function seedFile(array $data): void
    {
        $topic = $this->upsertTopic($data['topic']);

        foreach ($data['resources'] ?? [] as $resource) {
            $this->upsertResource($resource, $topic->id, null);
        }

        $sort = 0;
        foreach ($data['questions'] ?? [] as $question) {
            $sort++;
            $q = Question::updateOrCreate(
                ['topic_id' => $topic->id, 'prompt' => $question['prompt']],
                [
                    'type' => $question['type'] ?? 'single',
                    'options' => empty($question['options']) ? null : array_values($question['options']),
                    'correct' => empty($question['correct']) ? null : array_map('intval', $question['correct']),
                    'explanation' => $question['explanation'] ?? '',
                    'difficulty' => $question['difficulty'] ?? 'medium',
                    'tags' => $question['tags'] ?? null,
                    'source' => $question['source'] ?? null,
                    'sort_order' => $sort,
                ]
            );

            foreach ($question['resources'] ?? [] as $resource) {
                $this->upsertResource($resource, $topic->id, $q->id);
            }
        }
    }

    private function upsertTopic(array $data): Topic
    {
        return Topic::updateOrCreate(
            ['slug' => $data['slug']],
            [
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'color' => $data['color'] ?? '#6366f1',
                'icon' => $data['icon'] ?? '📘',
                'sort_order' => $data['sort_order'] ?? 0,
            ]
        );
    }

    private function upsertResource(array $data, int $topicId, ?int $questionId): void
    {
        if (empty($data['url'])) {
            return;
        }

        Resource::updateOrCreate(
            [
                'topic_id' => $questionId === null ? $topicId : null,
                'question_id' => $questionId,
                'url' => $data['url'],
            ],
            [
                'title' => $data['title'] ?? $data['url'],
                'type' => $data['type'] ?? 'docs',
                'summary' => $data['summary'] ?? null,
                'sort_order' => 0,
            ]
        );
    }
}
