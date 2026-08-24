<?php

namespace App\Plugins\User\Quizzes\Services;

use App\Models\User\Quizzes\QuizzesAttempts;

/**
 * 小問別反応表・SP表・カテゴリー別結果CSV出力
 */
class QuizAnalysisCsvService
{
    /**
     * 小問別反応表CSVを返します。
     */
    public function itemResponse(int $quiz_id)
    {
        $matrix = $this->buildMatrix($quiz_id);
        $questions = collect($matrix['questions']);
        $participants = collect($matrix['participants']);

        return $this->download(
            'quiz_item_response_' . $quiz_id . '.csv',
            function ($handle) use ($questions, $participants) {
                $header = ['受験者', '合計点'];
                foreach ($questions as $question) {
                    $header[] = '問' . $question['display_no'] . '：' . $question['text'];
                }
                fputcsv($handle, $header);

                foreach ($participants as $participant) {
                    $row = [
                        $participant['name'],
                        $this->number($participant['total_score']),
                    ];

                    foreach ($questions as $question) {
                        $correctness = $participant['answers'][$question['id']]['correctness']
                            ?? 'unanswered';
                        $row[] = $this->correctnessLabel($correctness);
                    }

                    fputcsv($handle, $row);
                }

                $correct_counts = ['正答者数', ''];
                $partial_counts = ['部分正答者数', ''];
                $incorrect_counts = ['誤答者数', ''];
                $unanswered_counts = ['未回答者数', ''];
                $correct_rates = ['正答率', ''];
                $participant_count = $participants->count();

                foreach ($questions as $question) {
                    $reactions = $participants->map(function ($participant) use ($question) {
                        return $participant['answers'][$question['id']]['correctness']
                            ?? 'unanswered';
                    });
                    $correct_count = $reactions->filter(function ($reaction) {
                        return $reaction === 'correct';
                    })->count();

                    $correct_counts[] = $correct_count;
                    $partial_counts[] = $reactions->filter(function ($reaction) {
                        return $reaction === 'partial';
                    })->count();
                    $incorrect_counts[] = $reactions->filter(function ($reaction) {
                        return $reaction === 'incorrect';
                    })->count();
                    $unanswered_counts[] = $reactions->filter(function ($reaction) {
                        return $reaction === 'unanswered';
                    })->count();
                    $correct_rates[] = $participant_count > 0
                        ? number_format($correct_count / $participant_count * 100, 2, '.', '') . '%'
                        : '0.00%';
                }

                fputcsv($handle, $correct_counts);
                fputcsv($handle, $partial_counts);
                fputcsv($handle, $incorrect_counts);
                fputcsv($handle, $unanswered_counts);
                fputcsv($handle, $correct_rates);
            }
        );
    }

    /**
     * SP表CSVを返します。
     */
    public function spTable(int $quiz_id)
    {
        $matrix = $this->buildMatrix($quiz_id);
        $participant_count = count($matrix['participants']);

        $questions = collect($matrix['questions'])
            ->map(function ($question) use ($matrix, $participant_count) {
                $correct_count = collect($matrix['participants'])
                    ->filter(function ($participant) use ($question) {
                        return ($participant['answers'][$question['id']]['correctness'] ?? null)
                            === 'correct';
                    })
                    ->count();
                $question['correct_count'] = $correct_count;
                $question['correct_rate'] = $participant_count > 0
                    ? $correct_count / $participant_count
                    : 0;
                return $question;
            })
            ->sort(function ($left, $right) {
                if ($left['correct_rate'] !== $right['correct_rate']) {
                    return $left['correct_rate'] < $right['correct_rate'] ? 1 : -1;
                }
                return $left['order'] <=> $right['order'];
            })
            ->values();

        $participants = collect($matrix['participants'])
            ->sort(function ($left, $right) {
                if ($left['total_score'] !== $right['total_score']) {
                    return $left['total_score'] < $right['total_score'] ? 1 : -1;
                }
                $left_time = $left['submitted_at'] ?: '';
                $right_time = $right['submitted_at'] ?: '';
                if ($left_time !== $right_time) {
                    return strcmp($left_time, $right_time);
                }
                return $left['attempt_id'] <=> $right['attempt_id'];
            })
            ->values();

        return $this->download(
            'quiz_sp_table_' . $quiz_id . '.csv',
            function ($handle) use ($questions, $participants) {
                $header = ['受験者', '合計点'];
                foreach ($questions as $index => $question) {
                    $header[] = '問' . $question['display_no'] . '：' . $question['text'];
                }
                $header[] = 'S境界（正答数）';
                fputcsv($handle, $header);

                foreach ($participants as $participant) {
                    $row = [$participant['name'], $this->number($participant['total_score'])];
                    $correct_count = 0;
                    foreach ($questions as $question) {
                        $is_correct = ($participant['answers'][$question['id']]['correctness'] ?? null)
                            === 'correct';
                        $row[] = $is_correct ? 1 : 0;
                        $correct_count += $is_correct ? 1 : 0;
                    }
                    $row[] = $correct_count;
                    fputcsv($handle, $row);
                }

                $p_boundary = ['P境界（正答者数）', ''];
                $correct_rates = ['正答率', ''];
                foreach ($questions as $question) {
                    $p_boundary[] = $question['correct_count'];
                    $correct_rates[] = number_format(
                        $question['correct_rate'] * 100,
                        2,
                        '.',
                        ''
                    ) . '%';
                }
                $p_boundary[] = '';
                $correct_rates[] = '';
                fputcsv($handle, $p_boundary);
                fputcsv($handle, $correct_rates);
            }
        );
    }

    /**
     * カテゴリー別結果CSVを返します。
     */
    public function categoryResults(int $quiz_id)
    {
        $admin_results = app(QuizAdminResultService::class)->forQuiz($quiz_id);
        $category_service = app(QuizCategoryResultService::class);
        $category_groups = $category_service->averagesForQuiz($quiz_id);
        $attempt_results = $category_service->forAttempts(
            $admin_results->attempts->pluck('id')->all()
        );

        $columns = collect($category_groups)->flatMap(function ($group) {
            return collect($group['categories'])->map(function ($category) use ($group) {
                return [
                    'group_name' => $group['name'],
                    'category_id' => (int) $category['id'],
                    'category_name' => $category['name'],
                ];
            });
        })->values();

        return $this->download(
            'quiz_category_results_' . $quiz_id . '.csv',
            function ($handle) use ($admin_results, $attempt_results, $columns) {
                $header = ['受験者', '受験ID', '受験回数', '合計点'];
                foreach ($columns as $column) {
                    $prefix = $column['group_name'] . '／' . $column['category_name'];
                    $header[] = $prefix . '／獲得点';
                    $header[] = $prefix . '／配点';
                    $header[] = $prefix . '／得点率';
                    $header[] = $prefix . '／採点状態';
                }
                fputcsv($handle, $header);

                foreach ($admin_results->attempts as $attempt) {
                    $category_map = [];
                    foreach ($attempt_results[(int) $attempt->id] ?? [] as $group) {
                        foreach ($group['categories'] as $category) {
                            if ($category['source_id'] !== null) {
                                $category_map[(int) $category['source_id']] = $category;
                            }
                        }
                    }

                    $row = [
                        optional($attempt->user)->name
                            ?: 'ユーザーID ' . $attempt->user_id,
                        (int) $attempt->id,
                        (int) $attempt->attempt_no,
                        $this->number((float) $attempt->total_score),
                    ];

                    foreach ($columns as $column) {
                        $category = $category_map[$column['category_id']] ?? null;
                        if (!$category) {
                            array_push($row, '', '', '', '対象なし');
                            continue;
                        }

                        $row[] = $category['status'] === 'pending'
                            ? ''
                            : $this->number((float) $category['earned_score']);
                        $row[] = $this->number((float) $category['max_score']);
                        $row[] = $category['score_rate'] === null
                            ? ''
                            : number_format((float) $category['score_rate'], 1, '.', '') . '%';
                        $row[] = $this->categoryStatusLabel($category['status']);
                    }

                    fputcsv($handle, $row);
                }
            }
        );
    }

    /**
     * 管理者結果と同じ「各受験者の最新採点済み・非プレビュー」から反応行列を作ります。
     */
    private function buildMatrix(int $quiz_id): array
    {
        $admin_results = app(QuizAdminResultService::class)->forQuiz($quiz_id);
        $attempt_ids = $admin_results->attempts->pluck('id');

        if ($attempt_ids->isEmpty()) {
            return ['questions' => [], 'participants' => []];
        }

        $attempts = QuizzesAttempts::query()
            ->with([
                'user',
                'attempt_pages.attempt_questions.question_revision',
                'attempt_pages.attempt_questions.quiz_question.quiz_page',
                'attempt_pages.attempt_questions.answer',
            ])
            ->whereIn('id', $attempt_ids)
            ->get()
            ->keyBy('id');

        $question_map = [];
        foreach ($attempts as $attempt) {
            foreach ($attempt->attempt_pages as $attempt_page) {
                foreach ($attempt_page->attempt_questions as $attempt_question) {
                    $question_id = (int) $attempt_question->quiz_question_id;
                    if (isset($question_map[$question_id])) {
                        continue;
                    }
                    $question = $attempt_question->quiz_question;
                    $revision = $attempt_question->question_revision;
                    $question_map[$question_id] = [
                        'id' => $question_id,
                        'text' => $this->plainText(optional($revision)->question_text),
                        'points' => (float) $attempt_question->points,
                        'page_sequence' => $question && $question->quiz_page
                            ? (int) $question->quiz_page->sequence
                            : PHP_INT_MAX,
                        'question_sequence' => $question
                            ? (int) $question->sequence
                            : PHP_INT_MAX,
                    ];
                }
            }
        }

        $questions = collect(array_values($question_map))
            ->sort(function ($left, $right) {
                return [
                    $left['page_sequence'],
                    $left['question_sequence'],
                    $left['id'],
                ] <=> [
                    $right['page_sequence'],
                    $right['question_sequence'],
                    $right['id'],
                ];
            })
            ->values()
            ->map(function ($question, $index) {
                $question['order'] = $index;
                $question['display_no'] = $index + 1;
                return $question;
            })
            ->all();

        $participants = [];
        foreach ($admin_results->attempts as $result_attempt) {
            $attempt = $attempts->get($result_attempt->id);
            if (!$attempt) {
                continue;
            }

            $answers = [];
            foreach ($attempt->attempt_pages as $attempt_page) {
                foreach ($attempt_page->attempt_questions as $attempt_question) {
                    $answer = $attempt_question->answer;
                    $answers[(int) $attempt_question->quiz_question_id] = [
                        'correctness' => $answer ? $answer->correctness : 'unanswered',
                        'score' => $answer ? (float) $answer->current_score : 0,
                    ];
                }
            }

            $participants[] = [
                'attempt_id' => (int) $attempt->id,
                'name' => optional($attempt->user)->name
                    ?: 'ユーザーID ' . $attempt->user_id,
                'total_score' => (float) $attempt->total_score,
                'submitted_at' => $attempt->submitted_at
                    ? $attempt->submitted_at->format('Y-m-d H:i:s')
                    : null,
                'answers' => $answers,
            ];
        }

        return [
            'questions' => $questions,
            'participants' => $participants,
        ];
    }

    /**
     * UTF-8 BOM付きCSVをレスポンスとして返します。
     *
     * Connect-CMSのダウンロード経路ではStreamedResponseが文字列化されるため、
     * 既存プラグインのCSV出力と同じくCSV本文を生成してresponse()->make()で返します。
     */
    private function download(string $filename, callable $writer)
    {
        $handle = fopen('php://temp', 'w+');
        fwrite($handle, "\xEF\xBB\xBF");
        $writer($handle);
        rewind($handle);
        $csv_data = stream_get_contents($handle);
        fclose($handle);

        return response()->make($csv_data, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * 小問別反応表に表示する判定名を返します。
     */
    private function correctnessLabel(string $correctness): string
    {
        return [
            'correct' => '正答',
            'partial' => '部分正答',
            'incorrect' => '誤答',
            'unanswered' => '未回答',
        ][$correctness] ?? '未回答';
    }

    private function categoryStatusLabel(string $status): string
    {
        return [
            'graded' => '採点済み',
            'pending' => '採点待ち',
            'not_applicable' => '対象なし',
        ][$status] ?? $status;
    }

    private function plainText($html): string
    {
        $text = html_entity_decode(
            strip_tags((string) $html),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );
        $text = preg_replace('/[\r\n\t ]+/u', ' ', $text);

        return trim((string) $text);
    }

    private function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
