# 小テスト専用マイグレーション

CMS設置先で、配置済みの23ファイルだけを対象に実行します。

```sh
php artisan migrate \
  --path=database/migrations/2026_07_19_000001_create_quizzes_table.php \
  --path=database/migrations/2026_07_19_000002_create_quiz_frames_table.php \
  --path=database/migrations/2026_07_19_000003_create_quiz_page_groups_table.php \
  --path=database/migrations/2026_07_19_000004_create_quiz_pages_table.php \
  --path=database/migrations/2026_07_19_000005_create_quiz_questions_table.php \
  --path=database/migrations/2026_07_19_000006_create_quiz_question_revisions_table.php \
  --path=database/migrations/2026_07_19_000007_add_current_revision_id_to_quiz_questions_table.php \
  --path=database/migrations/2026_07_19_000008_create_quiz_choice_revisions_table.php \
  --path=database/migrations/2026_07_19_000009_create_quiz_correct_answer_revisions_table.php \
  --path=database/migrations/2026_07_19_000010_create_quiz_attempts_table.php \
  --path=database/migrations/2026_07_19_000011_create_quiz_attempt_pages_table.php \
  --path=database/migrations/2026_07_19_000012_create_quiz_attempt_questions_table.php \
  --path=database/migrations/2026_07_19_000013_create_quiz_attempt_question_choices_table.php \
  --path=database/migrations/2026_07_19_000014_create_quiz_answers_table.php \
  --path=database/migrations/2026_07_19_000015_create_quiz_answer_grades_table.php \
  --path=database/migrations/2026_08_04_000001_add_question_display_settings_to_quizzes_table.php \
  --path=database/migrations/2026_08_08_000001_add_show_user_answer_to_quizzes_table.php \
  --path=database/migrations/2026_08_08_000002_add_result_statistics_display_to_quizzes_table.php \
  --path=database/migrations/2026_08_08_000003_add_score_distribution_display_to_quizzes_table.php \
  --path=database/migrations/2026_08_15_000001_add_use_category_scoring_to_quizzes_table.php \
  --path=database/migrations/2026_08_15_000002_create_quiz_category_tables.php \
  --path=database/migrations/2026_08_15_000003_create_quiz_attempt_category_snapshot_tables.php \
  --path=database/migrations/2026_09_27_000001_add_handwriting_answers.php
```
