<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class NetLearnSeeder extends Seeder
{
    public function run(): void
    {
        $student = User::firstOrCreate(
            ['email' => 'student@test.com'],
            [
                'name' => 'student1',
                'password' => Hash::make('password123'),
                'role' => 'student',
            ]
        );

        $lecturer = User::firstOrCreate(
            ['email' => 'lecturer@test.com'],
            [
                'name' => 'lecturer1',
                'password' => Hash::make('password123'),
                'role' => 'lecturer',
            ]
        );

        $osiTopic = Topic::firstOrCreate(
            ['title' => 'OSI Model'],
            [
                'description' => 'Introduction to the seven layers of the OSI model.',
                'content' => "The OSI Model is a conceptual framework used to understand how data moves through a network.\n\nIt contains seven layers:\n\n1. Physical Layer\n2. Data Link Layer\n3. Network Layer\n4. Transport Layer\n5. Session Layer\n6. Presentation Layer\n7. Application Layer\n\nEach layer has a specific responsibility in the communication process.",
                'created_by' => $lecturer->user_id,
                'topic_order' => 1,
            ]
        );

        $tcpTopic = Topic::firstOrCreate(
            ['title' => 'TCP/IP Model'],
            [
                'description' => 'Introduction to the TCP/IP networking model.',
                'content' => "The TCP/IP Model explains how devices communicate over the internet.\n\nIt usually contains four layers:\n\n1. Network Access Layer\n2. Internet Layer\n3. Transport Layer\n4. Application Layer\n\nTCP/IP is widely used in real-world networking and internet communication.",
                'created_by' => $lecturer->user_id,
                'topic_order' => 2,
            ]
        );

        $osiQuiz = Quiz::firstOrCreate(
            ['title' => 'OSI Model Quiz'],
            [
                'topic_id' => $osiTopic->topic_id,
                'passing_mark' => 60,
            ]
        );

        $tcpQuiz = Quiz::firstOrCreate(
            ['title' => 'TCP/IP Model Quiz'],
            [
                'topic_id' => $tcpTopic->topic_id,
                'passing_mark' => 60,
            ]
        );

        Question::firstOrCreate(
            [
                'quiz_id' => $osiQuiz->quiz_id,
                'question_text' => 'How many layers are in the OSI Model?',
            ],
            [
                'option_a' => '4',
                'option_b' => '5',
                'option_c' => '7',
                'option_d' => '9',
                'correct_answer' => 'C',
            ]
        );

        Question::firstOrCreate(
            [
                'quiz_id' => $osiQuiz->quiz_id,
                'question_text' => 'Which OSI layer is responsible for routing?',
            ],
            [
                'option_a' => 'Physical Layer',
                'option_b' => 'Network Layer',
                'option_c' => 'Session Layer',
                'option_d' => 'Presentation Layer',
                'correct_answer' => 'B',
            ]
        );

        Question::firstOrCreate(
            [
                'quiz_id' => $osiQuiz->quiz_id,
                'question_text' => 'Which OSI layer is closest to the user?',
            ],
            [
                'option_a' => 'Application Layer',
                'option_b' => 'Transport Layer',
                'option_c' => 'Data Link Layer',
                'option_d' => 'Physical Layer',
                'correct_answer' => 'A',
            ]
        );

        Question::firstOrCreate(
            [
                'quiz_id' => $tcpQuiz->quiz_id,
                'question_text' => 'How many layers are commonly used in the TCP/IP Model?',
            ],
            [
                'option_a' => '2',
                'option_b' => '4',
                'option_c' => '7',
                'option_d' => '8',
                'correct_answer' => 'B',
            ]
        );

        Question::firstOrCreate(
            [
                'quiz_id' => $tcpQuiz->quiz_id,
                'question_text' => 'Which TCP/IP layer handles end-to-end communication?',
            ],
            [
                'option_a' => 'Application Layer',
                'option_b' => 'Internet Layer',
                'option_c' => 'Transport Layer',
                'option_d' => 'Network Access Layer',
                'correct_answer' => 'C',
            ]
        );

        Question::firstOrCreate(
            [
                'quiz_id' => $tcpQuiz->quiz_id,
                'question_text' => 'Which protocol is commonly used for reliable communication?',
            ],
            [
                'option_a' => 'TCP',
                'option_b' => 'IP',
                'option_c' => 'ARP',
                'option_d' => 'ICMP',
                'correct_answer' => 'A',
            ]
        );
    }
}