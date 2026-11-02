<?php
require __DIR__ . '/vendor/autoload.php';
use Illuminate\Foundation\Application;
use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Schema\Blueprint;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

$root = sys_get_temp_dir() . '/quiz-reset-' . bin2hex(random_bytes(5));
mkdir($root . '/storage/framework', 0777, true);
$app = new class($root) extends Application {
 public function isDownForMaintenance() { return is_file($this->storagePath().'/framework/down'); }
};
$app->instance('config', new Repository([
    'app' => ['key' => 'test-key', 'env' => 'testing'],
    'database' => ['default' => 'sqlite', 'connections' => ['sqlite' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]]],
    'filesystems' => ['default' => 'local', 'disks' => ['local' => ['driver' => 'local', 'root' => $root . '/storage/app']]],
]));
Facade::setFacadeApplication($app);
$app->register(Illuminate\Events\EventServiceProvider::class);
$app->register(Illuminate\Database\DatabaseServiceProvider::class);
$app->register(Illuminate\Filesystem\FilesystemServiceProvider::class);
require __DIR__ . '/ResetQuizzesDemo.php';
Schema::create('yuyu_quizzes', function (Blueprint $t) { $t->id(); $t->string('title'); });
$relations = [
 'yuyu_quiz_attempts' => ['quiz_id', 'yuyu_quizzes'],
 'yuyu_quiz_attempt_pages' => ['quiz_attempt_id', 'yuyu_quiz_attempts'],
 'yuyu_quiz_attempt_questions' => ['quiz_attempt_page_id', 'yuyu_quiz_attempt_pages'],
 'yuyu_quiz_attempt_question_choices' => ['quiz_attempt_question_id', 'yuyu_quiz_attempt_questions'],
 'yuyu_quiz_attempt_category_groups' => ['quiz_attempt_id', 'yuyu_quiz_attempts'],
 'yuyu_quiz_attempt_categories' => ['quiz_attempt_category_group_id', 'yuyu_quiz_attempt_category_groups'],
 'yuyu_quiz_attempt_question_categories' => ['quiz_attempt_category_id', 'yuyu_quiz_attempt_categories'],
 'yuyu_quiz_answers' => ['quiz_attempt_id', 'yuyu_quiz_attempts'],
 'yuyu_quiz_answer_grades' => ['quiz_answer_id', 'yuyu_quiz_answers'],
 'yuyu_quiz_handwriting_images' => ['quiz_answer_id', 'yuyu_quiz_answers'],
];
foreach ($relations as $table => [$column, $parent]) {
 Schema::create($table, function (Blueprint $t) use ($column, $parent, $table) {
  $t->id(); $t->unsignedBigInteger($column);
  $t->foreign($column)->references('id')->on($parent)->onDelete('cascade');
  if ($table === 'yuyu_quiz_handwriting_images') { $t->string('path'); $t->integer('bytes'); }
 });
}
DB::table('yuyu_quizzes')->insert([['id'=>1,'title'=>'Test'],['id'=>2,'title'=>'Unchanged']]);
foreach ($relations as $table => [$column]) {
 foreach ([1,2] as $id) {
  $row=['id'=>$id,$column=>$id];
  if ($table === 'yuyu_quiz_handwriting_images') {
   $row['path']='yuyu-quizzes/handwriting/'.$id.'/'.str_repeat((string)$id,40).'.png';
   $row['bytes']=7;
   Storage::disk('local')->put($row['path'], 'image-'.$id);
  }
  DB::table($table)->insert($row);
 }
}
function runCommand(array $args): int {
 global $app;
 $cmd = new App\Console\Commands\ResetQuizzesDemo();
 $cmd->setLaravel($app);
 $output=new BufferedOutput();
 try { return $cmd->run(new ArrayInput($args),$output); }
 finally { echo $output->fetch(); }
}
function check($condition, $label) {
 if (!$condition) throw new RuntimeException($label);
 echo "PASS: $label\n";
}
check(runCommand(['quiz_id'=>'bad','--force'=>true])===1,'reject invalid ID');
check(runCommand(['quiz_id'=>1,'--force'=>true,'--create-baseline'=>true])===0,'create baseline');
check(runCommand(['quiz_id'=>1,'--force'=>true])===1,'reject restore outside maintenance');
file_put_contents($root.'/storage/framework/down', '{}');
if ($app->bound('Illuminate\\Contracts\\Foundation\\MaintenanceMode')) {
 // Laravel resolves the file-backed maintenance driver from app configuration.
}
Storage::disk('local')->delete('yuyu-quizzes/handwriting/1/'.str_repeat('1',40).'.png');
DB::table('yuyu_quiz_answer_grades')->where('id',1)->delete();
check(runCommand(['quiz_id'=>1,'--force'=>true])===0,'restore baseline including missing image');
check(DB::table('yuyu_quiz_answer_grades')->where('id',1)->exists(),'grades restored');
check(Storage::disk('local')->get('yuyu-quizzes/handwriting/1/'.str_repeat('1',40).'.png')==='image-1','image bytes restored');
foreach ($relations as $table => [$column]) check(DB::table($table)->where('id',2)->exists(),'other quiz retained: '.$table);
$path=$root.'/storage/app/quizzes-demo/baselines/quiz-1.json';
$good=file_get_contents($path); $bad=json_decode($good,true);$bad['quiz_id']=2;file_put_contents($path,json_encode($bad));
check(runCommand(['quiz_id'=>1,'--force'=>true])===1,'reject wrong quiz snapshot');
file_put_contents($path,$good);$bad=json_decode($good,true);$bad['checksum']='invalid';file_put_contents($path,json_encode($bad));
check(runCommand(['quiz_id'=>1,'--force'=>true])===1,'reject damaged checksum');
check(DB::table('yuyu_quiz_attempts')->count()===2,'rejections leave database intact');
echo "ALL RESET CHECKS PASSED\n";
