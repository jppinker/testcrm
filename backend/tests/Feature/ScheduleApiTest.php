<?php
namespace Tests\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
class ScheduleApiTest extends TestCase {
 use RefreshDatabase;
 public function test_shift_lifecycle_and_validation(): void {
  $employee = DB::table('employees')->insertGetId(['name'=>'Анна', 'role'=>'агент']);
  $data = ['employee_id'=>$employee,'date'=>'2026-11-11','name'=>'Утро','start_time'=>'09:00','end_time'=>'17:00','color'=>'mint'];
  $id = $this->postJson('/api/shifts',$data)->assertCreated()->json('id');
  $this->postJson('/api/shifts',$data)->assertStatus(422);
  $this->putJson('/api/shifts/'.$id, array_merge($data,['end_time'=>'18:00']))->assertOk()->assertJsonPath('end_time','18:00');
  $this->putJson('/api/shifts/'.$id, array_merge($data,['end_time'=>'08:00']))->assertUnprocessable();
  $this->getJson('/api/schedule?from=2026-11-01&to=2026-11-30')->assertOk()->assertJsonPath('shifts.0.end_time','18:00');
  $this->deleteJson('/api/shifts/'.$id)->assertNoContent();
  $this->assertDatabaseMissing('shifts',['id'=>$id]);
 }
 public function test_templates_bulk_apply_and_publication(): void {
  $employee = DB::table('employees')->insertGetId(['name'=>'Анна', 'role'=>'агент']);
  $template = ['name'=>'День','start_time'=>'10:00','end_time'=>'18:00','color'=>'lavender'];
  $id = $this->postJson('/api/templates',$template)->assertCreated()->json('id');
  $this->putJson('/api/templates/'.$id,array_merge($template,['name'=>'Работа']))->assertOk();
  $cells = [['employee_id'=>$employee,'date'=>'2026-11-11'],['employee_id'=>$employee,'date'=>'2026-11-12']];
  $this->postJson('/api/shifts/apply',array_merge($template,['template_id'=>$id,'cells'=>$cells]))->assertOk()->assertJsonCount(2, 'shifts')->assertJsonPath('shifts.0.name', 'День');
  $this->assertDatabaseCount('shifts',2);
  $this->postJson('/api/schedule/publish',['from'=>'2026-11-01','to'=>'2026-11-30'])->assertCreated()->assertJsonPath('count',2);
  $this->deleteJson('/api/templates/'.$id)->assertNoContent();
  $this->assertDatabaseHas('shifts',['employee_id'=>$employee,'template_id'=>null]);
 }
 public function test_seeder_only_populates_a_new_database(): void {
  $this->seed();
  $this->assertDatabaseCount('employees', 9);
  $this->assertDatabaseCount('shifts', 27);
  DB::table('schedule_templates')->where('name', 'Утро')->update(['start_time' => '08:00']);
  DB::table('shifts')->where('id', DB::table('shifts')->min('id'))->delete();
  $this->seed();
  $this->assertDatabaseCount('shifts', 26);
  $this->assertDatabaseHas('schedule_templates', ['name' => 'Утро', 'start_time' => '08:00']);
 }
 public function test_bulk_apply_does_not_overwrite_existing_shifts(): void {
  $employee = DB::table('employees')->insertGetId(['name'=>'Анна', 'role'=>'агент']);
  $data = ['name'=>'Утро','start_time'=>'09:00','end_time'=>'17:00','color'=>'mint'];
  $cell = ['employee_id'=>$employee,'date'=>'2026-11-11'];
  $this->postJson('/api/shifts', $cell + $data)->assertCreated();
  $this->postJson('/api/shifts/apply', $data + ['cells'=>[['employee_id'=>$employee,'date'=>'2026-11-12'], $cell]])->assertUnprocessable();
  $this->assertDatabaseCount('shifts', 1);
 }
 public function test_clear_selected_shifts_only(): void {
  $employee = DB::table('employees')->insertGetId(['name'=>'Анна', 'role'=>'агент']);
  $data = ['name'=>'Утро','start_time'=>'09:00','end_time'=>'17:00','color'=>'mint'];
  $a = ['employee_id'=>$employee,'date'=>'2026-11-11'];
  $b = ['employee_id'=>$employee,'date'=>'2026-11-12'];
  $this->postJson('/api/shifts', $a + $data)->assertCreated();
  $this->postJson('/api/shifts', $b + $data)->assertCreated();
  $this->postJson('/api/shifts/clear', ['cells'=>[$a]])->assertOk();
  $this->assertDatabaseMissing('shifts', $a);
  $this->assertDatabaseHas('shifts', $b);
  $this->postJson('/api/shifts/clear', ['cells'=>[['employee_id'=>$employee,'date'=>'invalid']]])->assertUnprocessable();
 }
}
