<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ScheduleController extends Controller
{
    public function index(Request $request) {
        $data = $request->validate(['from' => 'required|date_format:Y-m-d', 'to' => 'required|date_format:Y-m-d|after_or_equal:from']);
        return response()->json(['employees' => DB::table('employees')->orderBy('id')->get(), 'shifts' => DB::table('shifts')->whereBetween('date', [$data['from'], $data['to']])->get(), 'templates' => DB::table('schedule_templates')->orderBy('id')->get()]);
    }
    private function rules(): array { return ['name' => 'required|string|max:80', 'start_time' => 'required|date_format:H:i', 'end_time' => 'required|date_format:H:i|after:start_time', 'color' => ['required', Rule::in(['cornflower', 'mint', 'lavender', 'brand'])]]; }
    public function createTemplate(Request $request) { $data = $request->validate($this->rules()); $id = DB::table('schedule_templates')->insertGetId($data); return response()->json(DB::table('schedule_templates')->find($id), 201); }
    public function updateTemplate(Request $request, int $id) { $this->find('schedule_templates', $id); DB::table('schedule_templates')->where('id', $id)->update($request->validate($this->rules())); return response()->json(DB::table('schedule_templates')->find($id)); }
    public function deleteTemplate(int $id) { $this->find('schedule_templates', $id); DB::table('schedule_templates')->where('id', $id)->delete(); return response()->noContent(); }
    private function find(string $table, int $id): object { return DB::table($table)->find($id) ?? abort(404); }
    public function saveShift(Request $request, ?int $id = null) {
        if ($id !== null) $this->find('shifts', $id);
        $data = $request->validate(array_merge($this->rules(), ['employee_id' => 'required|integer|exists:employees,id', 'date' => 'required|date_format:Y-m-d', 'template_id' => 'nullable|integer|exists:schedule_templates,id']));
        $duplicate = DB::table('shifts')->where('employee_id', $data['employee_id'])->where('date', $data['date'])->when($id, fn ($q) => $q->where('id', '!=', $id))->exists();
        if ($duplicate) return response()->json(['message' => 'На этот день уже назначена смена.'], 422);
        if ($id) DB::table('shifts')->where('id', $id)->update($data); else $id = DB::table('shifts')->insertGetId($data);
        return response()->json(DB::table('shifts')->find($id), $request->isMethod('post') ? 201 : 200);
    }
    public function deleteShift(int $id) { $this->find('shifts', $id); DB::table('shifts')->where('id', $id)->delete(); return response()->noContent(); }
    public function apply(Request $request) {
        $data = $request->validate(['cells' => 'required|array|min:1|max:500', 'cells.*' => 'required|array:employee_id,date', 'cells.*.employee_id' => 'required|integer|exists:employees,id', 'cells.*.date' => 'required|date_format:Y-m-d', 'template_id' => 'nullable|integer|exists:schedule_templates,id'] + $this->rules());
        DB::transaction(function () use ($data) {
            foreach ($data['cells'] as $cell) {
                if (DB::table('shifts')->where($cell)->exists()) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['cells' => 'Одна из выбранных ячеек уже занята. Обновите график.']);
                }
                DB::table('shifts')->insert($cell + ['template_id' => $data['template_id'] ?? null, 'name' => $data['name'], 'start_time' => $data['start_time'], 'end_time' => $data['end_time'], 'color' => $data['color']]);
            }
        });
        return response()->json(['message' => 'Смены сохранены.']);
    }
    public function clear(Request $request) {
        $data = $request->validate(['cells' => 'required|array|min:1|max:500', 'cells.*' => 'required|array:employee_id,date', 'cells.*.employee_id' => 'required|integer|exists:employees,id', 'cells.*.date' => 'required|date_format:Y-m-d']);
        DB::transaction(function () use ($data) {
            foreach ($data['cells'] as $cell) DB::table('shifts')->where('employee_id', $cell['employee_id'])->where('date', $cell['date'])->delete();
        });
        return response()->json(['message' => 'Смены удалены.']);
    }
}
