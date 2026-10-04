<?php

namespace Database\Seeders;

use App\Models\Content;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['admin', 'reader', 'author', 'student', 'instructor', 'freelancer', 'employer', 'vendor', 'moderator'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $author = User::firstOrCreate(
            ['email' => 'hello@openshelf.test'],
            [
                'name' => 'OpenShelf Editorial',
                'headline' => 'Curious minds, learning together',
                'password' => Hash::make('openshelf123'),
            ],
        );
        $author->assignRole(['admin', 'author', 'instructor', 'vendor']);

        $samples = [
            [
                'type' => 'novel',
                'title' => 'The Cartographer of Small Things',
                'summary' => 'A quiet, hopeful story about maps, memory, and the places we carry with us.',
                'topic' => 'Fiction',
                'tags' => ['literary', 'community'],
                'body' => "Chapter One: The Unmarked Street\n\nEvery morning, Mara unfolded her grandfather's map across the kitchen table. Its paper was soft at the creases, its blue ink faded into the color of rain. The maps were not of countries. They charted smaller things: the bench where strangers became friends, the bakery that gave yesterday's bread away, the alley where a violinist practiced at dawn.\n\nMara had always thought the map was unfinished. On the first day of spring, she realized it was waiting for her.",
            ],
            [
                'type' => 'research',
                'title' => 'How retrieval practice changes what we remember',
                'summary' => 'A student-friendly review of low-stakes quizzes, spaced learning, and durable memory.',
                'topic' => 'Learning science',
                'tags' => ['research', 'study-skills'],
                'body' => "Overview\n\nRetrieval practice means trying to recall an idea before looking at the answer. Across many classroom studies, short, low-stakes recall sessions improve long-term retention more consistently than rereading alone.\n\nTry it: after a short reading, close the book and write three things you remember. Check your notes, correct gaps, and return to the topic tomorrow. This resource is an educational summary, not a substitute for reading the cited primary research.",
            ],
            [
                'type' => 'resource',
                'title' => 'A practical guide to focused study sessions',
                'summary' => 'A reusable study plan with short focus blocks, active recall, and a gentle review loop.',
                'topic' => 'Study skills',
                'tags' => ['guide', 'productivity'],
                'body' => "1. Choose one small, specific outcome for this session.\n\n2. Gather what you need, silence notifications, and set a 25-minute focus block.\n\n3. Work from memory first: solve a problem, explain a concept, or draft a summary before checking notes.\n\n4. Take a five-minute break. After two or three blocks, write down what still feels unclear.\n\n5. Schedule a brief review for tomorrow. Consistency beats marathon sessions.",
            ],
            [
                'type' => 'post',
                'title' => 'What are you learning this week?',
                'summary' => 'A little accountability goes a long way. Share a goal and find a study partner.',
                'topic' => 'Community',
                'tags' => ['discussion', 'weekly-goals'],
                'body' => "I am setting aside three short sessions this week to learn something new. My plan is to choose one question, gather a few trustworthy sources, and write down what I discover in my own words.\n\nWhat are you working on? Share your goal, what has been challenging, or one useful resource you found. Let's help each other keep going.",
            ],
        ];

        foreach ($samples as $sample) {
            Content::firstOrCreate(
                ['user_id' => $author->id, 'title' => $sample['title']],
                [...$sample, 'user_id' => $author->id, 'status' => 'published', 'published_at' => now()],
            );
        }
    }
}
