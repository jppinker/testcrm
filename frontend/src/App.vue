<script setup lang="ts">
import { computed, onMounted, onBeforeUnmount, ref, watch } from 'vue'
import searchIcon from './assets/figma/cf751.svg'
import homeIcon from './assets/figma/11e94.svg'
import shiftsIcon from './assets/figma/b0a81.svg'
import collapseIcon from './assets/figma/affb1.svg'
import backIcon from './assets/figma/81bcd.svg'
import applyIcon from './assets/figma/a5cbb.svg'
import clearIcon from './assets/figma/7c37b.svg'
import deselectIcon from './assets/figma/c3465.svg'
import plusIcon from './assets/figma/ebcf4.svg'
import bellIcon from './assets/figma/0e262.svg'
import avatar1 from './assets/figma/1a590.png'
import avatar2 from './assets/figma/354f3.png'
import avatar3 from './assets/figma/336ac.png'
import avatar4 from './assets/figma/0ac26.png'
import avatar5 from './assets/figma/a8a34.png'
import avatar6 from './assets/figma/cd47d.png'
import avatar7 from './assets/figma/ed234.png'
import avatar8 from './assets/figma/120ec.png'
const avatars: Record<string, string> = Object.fromEntries(['Кузнецова Алина','Волков Тимур','Морозова Виктория','Соколов Артём','Лебедева Полина','Новиков Кирилл','Ерохина Дарья','Гаврилов Егор'].map((name, i) => [name, [avatar1, avatar2, avatar3, avatar4, avatar5, avatar6, avatar7, avatar8][i]!]))

type Color = 'cornflower' | 'mint' | 'lavender' | 'brand'
interface Employee { id: number; name: string; role: string }
interface ShiftData { name: string; start_time: string; end_time: string; color: Color }
interface Template extends ShiftData { id: number }
interface Shift extends ShiftData { id: number; employee_id: number; date: string; template_id: number | null }
interface Cell { employee_id: number; date: string }
interface Schedule { employees: Employee[]; shifts: Shift[]; templates: Template[] }
const employees = ref<Employee[]>([]), shifts = ref<Shift[]>([]), templates = ref<Template[]>([])
const loading = ref(true), busy = ref(false), error = ref(''), notice = ref(''), query = ref('')
const view = ref<'week' | 'month'>('week'), anchor = ref(new Date()), selected = ref<Cell[]>([])
const customStart = ref('09:00'), customEnd = ref('17:00')
type DragItem = { kind: 'template'; value: Template } | { kind: 'shift'; value: Shift }
const dragItem = ref<DragItem | null>(null), dragActive = ref(false), dragTarget = ref<Cell | null>(null)
const dragGhost = ref<HTMLElement | null>(null), gridScroll = ref<HTMLElement | null>(null)
let pointerId = -1, dragStartX = 0, dragStartY = 0, lastX = 0, lastY = 0, dragFrame = 0, suppressClickUntil = 0
function startDrag(event: PointerEvent, item: DragItem) {
  if (busy.value || loading.value || selected.value.length || !event.isPrimary || (event.pointerType === 'mouse' && event.button !== 0)) return
  pointerId = event.pointerId; dragStartX = lastX = event.clientX; dragStartY = lastY = event.clientY
  dragItem.value = item; dragActive.value = false; dragTarget.value = null
  window.addEventListener('pointermove', moveDrag, { passive: false })
  window.addEventListener('pointerup', endDrag); window.addEventListener('pointercancel', cancelDrag); window.addEventListener('keydown', cancelDragOnEscape)
}
function dragTick() {
  dragFrame = 0
  if (!dragActive.value) return
  if (dragGhost.value) dragGhost.value.style.transform = `translate3d(${lastX + 14}px, ${lastY + 14}px, 0)`
  const grid = gridScroll.value
  if (grid) {
    const bounds = grid.getBoundingClientRect(), edge = 48
    if (lastY >= bounds.top && lastY <= bounds.bottom) {
      if (lastX > bounds.right-edge && lastX < bounds.right+10) grid.scrollLeft += Math.min(16, (lastX-(bounds.right-edge))/3)
      else if (lastX < bounds.left+edge && lastX > bounds.left-10) grid.scrollLeft -= Math.min(16, ((bounds.left+edge)-lastX)/3)
    }
  }
  const cell = document.elementFromPoint(lastX, lastY)?.closest<HTMLElement>('[data-drag-cell]')
  const employee_id = Number(cell?.dataset.employeeId), date = cell?.dataset.date
  if (employee_id && date) {
    if (dragTarget.value?.employee_id !== employee_id || dragTarget.value.date !== date) dragTarget.value = { employee_id, date }
  } else if (dragTarget.value) dragTarget.value = null
  dragFrame = requestAnimationFrame(dragTick)
}
function moveDrag(event: PointerEvent) {
  if (event.pointerId !== pointerId || !dragItem.value) return
  lastX = event.clientX; lastY = event.clientY
  if (!dragActive.value && Math.hypot(lastX-dragStartX, lastY-dragStartY) >= 7) {
    dragActive.value = true; document.body.classList.add('dragging-shift')
    dragFrame = requestAnimationFrame(dragTick)
  }
  if (dragActive.value) event.preventDefault()
}
function cleanupDrag() {
  window.removeEventListener('pointermove', moveDrag); window.removeEventListener('pointerup', endDrag)
  window.removeEventListener('pointercancel', cancelDrag); window.removeEventListener('keydown', cancelDragOnEscape)
  cancelAnimationFrame(dragFrame); dragFrame = 0; pointerId = -1; dragItem.value = null; dragTarget.value = null; dragActive.value = false
  document.body.classList.remove('dragging-shift')
}
function cancelDrag() { if (dragActive.value) suppressClickUntil = Date.now()+500; cleanupDrag() }
function cancelDragOnEscape(event: KeyboardEvent) { if (event.key === 'Escape') cancelDrag() }
function endDrag(event: PointerEvent) {
  if (event.pointerId !== pointerId) return
  const item = dragItem.value, active = dragActive.value
  const cell = document.elementFromPoint(event.clientX, event.clientY)?.closest<HTMLElement>('[data-drag-cell]')
  const employee_id = Number(cell?.dataset.employeeId), date = cell?.dataset.date
  if (active) suppressClickUntil = Date.now()+500
  cleanupDrag()
  if (!active || !item || !employee_id || !date) return
  if (item.kind === 'shift' && item.value.employee_id === employee_id && item.value.date === date) return
  if (shiftAt(employee_id,date)) { error.value = 'На этот день уже назначена смена.'; return }
  void action(async () => {
    const source = item.value
    if (item.kind === 'template') {
      const created = await api<Shift>('/shifts', {method:'POST', body:JSON.stringify({...source, employee_id, date, template_id:source.id})})
      shifts.value.push(created)
    } else {
      const moved = await api<Shift>(`/shifts/${source.id}`, {method:'PUT', body:JSON.stringify({...source, employee_id, date})})
      shifts.value = shifts.value.map(s => s.id === moved.id ? moved : s)
    }
  }, item.kind === 'template' ? 'Смена назначена' : 'Смена перемещена')
}
function templateClick(template: Template) { if (Date.now() >= suppressClickUntil) apply(template, template.id) }
onBeforeUnmount(cleanupDrag)

const selectionMode = ref(false)
const selectionMenu = ref(false), quickDate = ref(''), quickEmployee = ref<number | ''>(''), quickPosition = ref({left: 0, top: 0})
const selectedEmployees = computed(() => new Set(selected.value.map(c => c.employee_id)).size)
const selectedDays = computed(() => new Set(selected.value.map(c => c.date)).size)
function showQuick(date: string, event: MouseEvent) { const rect=(event.currentTarget as HTMLElement).getBoundingClientRect(); quickPosition.value={left:Math.max(8,Math.min(rect.left, window.innerWidth-290)),top:Math.max(8,Math.min(rect.bottom+4,window.innerHeight-290))}; quickDate.value=date; quickEmployee.value=''; error.value=''; selectionMenu.value=false }
function quickAssign(t: Template) { if(!quickEmployee.value) { error.value='Выберите сотрудника'; return } const employee_id=quickEmployee.value; void action(async()=>{ const shift = await api<Shift>('/shifts',{method:'POST',body:JSON.stringify({...t,employee_id,date:quickDate.value,template_id:t.id})}); shifts.value.push(shift); quickDate.value='' },'Смена сохранена') }
function clearSelected() { void action(async()=>{ const cells = [...selected.value]; await api('/shifts/clear',{method:'POST',body:JSON.stringify({cells})}); shifts.value = shifts.value.filter(s => !cells.some(c => c.employee_id === s.employee_id && c.date === s.date)); selected.value=[]; selectionMode.value=false; selectionMenu.value=false },'Выбранные смены удалены') }

const editing = ref<Shift | null>(null), templateEditing = ref<Template | null>(null), templateModal = ref(false)
const form = ref<ShiftData>({name:'Своя смена', start_time:'09:00', end_time:'17:00', color:'brand'})
function iso(d: Date) { const y=d.getFullYear(), m=String(d.getMonth()+1).padStart(2,'0'), day=String(d.getDate()).padStart(2,'0'); return `${y}-${m}-${day}` }
const days = computed(() => { const d = new Date(anchor.value.getFullYear(),anchor.value.getMonth(),anchor.value.getDate()); if(view.value==='week') d.setDate(d.getDate()-(d.getDay()+6)%7); else d.setDate(1); const count = view.value==='week' ? 7 : new Date(d.getFullYear(),d.getMonth()+1,0).getDate(); return Array.from({length: count}, (_,i) => { const x=new Date(d); x.setDate(d.getDate()+i); return x }) })
const visibleEmployees = computed(() => employees.value.filter(e => e.name.toLocaleLowerCase('ru').includes(query.value.trim().toLocaleLowerCase('ru'))))
const period = computed(() => new Intl.DateTimeFormat('ru', { month:'long', year:'numeric' }).format(anchor.value))
function move(n:number) { const d=new Date(anchor.value); if(view.value==='week') d.setDate(d.getDate()+n*7); else { d.setDate(1); d.setMonth(d.getMonth()+n) } anchor.value=d }
async function api<T>(url:string, options:RequestInit = {}):Promise<T> { const response=await fetch(`/api${url}`, { ...options, headers: {Accept:'application/json', 'Content-Type':'application/json', ...options.headers} }); if(!response.ok) { const data=await response.json().catch(()=>({})); throw new Error(data.message || 'Не удалось выполнить запрос') } return response.status===204 ? undefined as T : response.json() }
let loadVersion = 0
async function load(showLoading = true) { const version=++loadVersion; if(showLoading) loading.value=true; error.value=''; try { const data=await api<Schedule>(`/schedule?from=${iso(days.value[0]!)}&to=${iso(days.value[days.value.length-1]!)}`); if(version!==loadVersion) return false; employees.value=data.employees; shifts.value=data.shifts; templates.value=data.templates; return true } catch(e) { if(version===loadVersion) error.value=(e as Error).message; return false } finally { if(version===loadVersion) loading.value=false } }
watch([anchor,view], () => { selected.value=[]; selectionMode.value=false; void load() }); onMounted(load)
function shiftAt(employee_id:number,date:string) { return shifts.value.find(s=>s.employee_id===employee_id && s.date===date) }
function isSelected(employee_id:number,date:string) { return selected.value.some(c=>c.employee_id===employee_id && c.date===date) }
function cellClick(employee_id:number,date:string,event:MouseEvent) { if (Date.now() < suppressClickUntil) return; const shift=shiftAt(employee_id,date); if(shift && !event.shiftKey && !selectionMode.value && !selected.value.length) { error.value=''; editing.value=shift; form.value={name:shift.name,start_time:shift.start_time,end_time:shift.end_time,color:shift.color}; return } const i=selected.value.findIndex(c=>c.employee_id===employee_id && c.date===date); if(i<0) selected.value.push({employee_id,date}); else selected.value.splice(i,1) }
async function action(fn:()=>Promise<void>, message:string) { busy.value=true; error.value=''; notice.value=''; try { await fn(); notice.value=message } catch(e) { error.value=(e as Error).message } finally { busy.value=false } }
function apply(data:ShiftData, template_id:number|null=null) { if(!selected.value.length) { notice.value='Выберите ячейки в графике'; return } void action(async()=>{ const result = await api<{shifts: Shift[]}>('/shifts/apply',{method:'POST',body:JSON.stringify({...data,template_id,cells:selected.value})}); shifts.value.push(...result.shifts); selected.value=[]; selectionMode.value=false; selectionMenu.value=false },'Смены сохранены') }
async function save() { busy.value=true; notice.value=''; try { if (await load(false)) notice.value='График сохранён' } finally { busy.value=false } }
function publish() { void action(async()=>{ await api('/schedule/publish',{method:'POST',body:JSON.stringify({from:iso(days.value[0]!),to:iso(days.value[days.value.length-1]!)})}) },'График за выбранный период опубликован') }
function editSave() { if(!editing.value) return; const s=editing.value; void action(async()=>{ const updated = await api<Shift>(`/shifts/${s.id}`,{method:'PUT',body:JSON.stringify({...s,...form.value})}); shifts.value = shifts.value.map(item => item.id === updated.id ? updated : item); editing.value=null },'Смена сохранена') }
function removeShift() { if(!editing.value) return; const id=editing.value.id; void action(async()=>{ await api(`/shifts/${id}`,{method:'DELETE'}); shifts.value = shifts.value.filter(item => item.id !== id); editing.value=null },'Смена удалена') }
function openTemplate(t?:Template) { error.value=''; templateEditing.value=t || null; form.value=t ? {name:t.name,start_time:t.start_time,end_time:t.end_time,color:t.color} : {name:'',start_time:'09:00',end_time:'17:00',color:'cornflower'}; templateModal.value=true }
function saveTemplate() { const id=templateEditing.value?.id; void action(async()=>{ const saved = await api<Template>(id ? `/templates/${id}`:'/templates',{method:id?'PUT':'POST',body:JSON.stringify(form.value)}); templates.value = id ? templates.value.map(t => t.id === id ? saved : t) : [...templates.value, saved]; templateModal.value=false },'Шаблон сохранён') }
function removeTemplate() { const id=templateEditing.value?.id; if(!id) return; void action(async()=>{ await api(`/templates/${id}`,{method:'DELETE'}); templates.value = templates.value.filter(t => t.id !== id); shifts.value = shifts.value.map(s => s.template_id === id ? {...s, template_id:null} : s); templateModal.value=false },'Шаблон удалён') }
function duration(start:string,end:string) { const [a,b]=start.split(':').map(Number), [c,d]=end.split(':').map(Number); return ((c! * 60 + d! - a! * 60 - b!)/60).toLocaleString('ru') }
function shiftTime(time:string) { return time.endsWith(':00') ? time.slice(0,2) : time }
function shiftDuration(start:string,end:string) { const [sh,sm]=start.split(':').map(Number), [eh,em]=end.split(':').map(Number); const minutes=(eh!*60+em!)-(sh!*60+sm!); const hours=Math.floor(minutes/60), remainder=minutes%60; return [hours ? `${hours} ч` : '', remainder ? `${remainder} мин` : ''].filter(Boolean).join(' ') }
function weekdayLabel(d:Date) { return new Intl.DateTimeFormat('ru',{weekday:'short'}).format(d) }
function dateLabel(d:Date) { return `${weekdayLabel(d)} ${d.getDate()}` }
</script>

<template>
  <div class="app-shell">
    <aside class="sidebar"><div><div class="logo">CRM <img class="bell" :src="bellIcon" alt=""></div><div class="nav-item"><img :src="homeIcon" alt="">Главная</div><div class="nav-item active"><img :src="shiftsIcon" alt="">Смены</div></div><div class="nav-item bottom"><img :src="collapseIcon" alt="">Свернуть меню</div></aside>
    <main class="main"><div class="breadcrumb"><span class="back"><img :src="backIcon" alt=""></span>Графики смен</div>
      <div class="heading"><h1>График</h1><div class="heading-actions"><button class="secondary" @click="publish" :disabled="busy">Опубликовать</button><button class="primary" :disabled="busy" @click="save">Сохранить</button></div></div>
      <div v-if="error" class="alert" role="alert">{{ error }} <button @click="load()">Повторить</button></div><div v-if="notice" class="notice" role="status">{{ notice }} <button @click="notice=''" aria-label="Закрыть">×</button></div>
      <div class="workspace"><section class="schedule-panel" aria-label="График сотрудников"><div v-if="selected.length" class="selection-bar" role="toolbar" aria-label="Выбранные ячейки"><div class="selection-info"><span>Выделено:</span><span class="selection-count">{{ selected.length }} ячеек</span><span>{{ selectedEmployees }} сотрудника(-ов) · {{ selectedDays }} дн.</span></div><div class="selection-actions"><div class="selection-dropdown"><button :disabled="busy" @click="selectionMenu=!selectionMenu"><img :src="applyIcon" alt="">Применить шаблон</button><div v-if="selectionMenu" class="template-menu"><button v-for="t in templates" :key="t.id" :disabled="busy" @click="apply(t,t.id)">{{ t.name }} · {{ t.start_time }}–{{ t.end_time }}</button><span v-if="!templates.length">Нет шаблонов</span></div></div><button :disabled="busy" @click="clearSelected"><img :src="clearIcon" alt="">Очистить</button><button :disabled="busy" @click="selected=[]; selectionMenu=false; selectionMode=false"><img :src="deselectIcon" alt="">Снять</button></div></div><div v-else class="toolbar"><label class="search"><img :src="searchIcon" alt=""><input v-model="query" placeholder="Поиск по ФИО" aria-label="Поиск по ФИО"></label><div class="tabs"><button :disabled="busy" :class="{chosen:view==='month'}" @click="view='month'">Месяц</button><button :disabled="busy" :class="{chosen:view==='week'}" @click="view='week'">Неделя</button></div></div>
        <div class="period"><button :disabled="busy" aria-label="Предыдущий период" @click="move(-1)">‹</button><strong>{{ period }}</strong><button :disabled="busy" aria-label="Следующий период" @click="move(1)">›</button><button :disabled="busy" class="today" @click="anchor=new Date()">Сегодня</button><button :disabled="busy" v-if="!selected.length" class="select-mode" :aria-pressed="selectionMode" @click="selectionMode=!selectionMode">{{ selectionMode ? 'Завершить выделение' : 'Выбрать ячейки' }}</button></div>
        <div v-if="loading" class="state">Загрузка графика…</div><div v-else-if="!visibleEmployees.length" class="state">Сотрудники не найдены</div><div v-else ref="gridScroll" class="grid-scroll"><div class="grid" :class="{'grid-month':view==='month'}" :style="{'--columns': days.length, '--cell-width':view==='week'?'110px':'64px'}"><div class="grid-row grid-header"><div class="employee-label">Сотрудник</div><div v-for="d in days" :key="iso(d)" class="day-label"><button :disabled="busy" :aria-label="`Быстрое назначение: ${dateLabel(d)}`" @click="showQuick(iso(d), $event)"><template v-if="view==='month'"><span class="month-weekday">{{ weekdayLabel(d) }}</span><span class="month-day">{{ d.getDate() }}</span></template><template v-else>{{ dateLabel(d) }}</template></button></div></div><div v-for="employee in visibleEmployees" :key="employee.id" class="grid-row employee-row"><div class="employee"><span class="avatar"><img v-if="avatars[employee.name]" :src="avatars[employee.name]" alt=""><template v-else>{{ employee.name.split(' ').map(x=>x[0]).join('') }}</template></span><span><b>{{ employee.name }}</b><small>{{ employee.role }}</small></span></div><button v-for="d in days" :key="iso(d)" :disabled="busy" class="cell" data-drag-cell :data-employee-id="employee.id" :data-date="iso(d)" :class="{selected:isSelected(employee.id,iso(d)), 'drop-target':dragActive && dragTarget?.employee_id===employee.id && dragTarget?.date===iso(d), 'drop-occupied':!!shiftAt(employee.id,iso(d))}" :aria-label="`${employee.name}, ${dateLabel(d)}${shiftAt(employee.id,iso(d)) ? `, ${shiftAt(employee.id,iso(d))!.name}, ${shiftAt(employee.id,iso(d))!.start_time}–${shiftAt(employee.id,iso(d))!.end_time}` : ''}`" :title="shiftAt(employee.id,iso(d)) ? `${shiftAt(employee.id,iso(d))!.name}: ${shiftAt(employee.id,iso(d))!.start_time}–${shiftAt(employee.id,iso(d))!.end_time}` : undefined" @click="cellClick(employee.id,iso(d),$event)" :aria-pressed="isSelected(employee.id,iso(d))"><span v-if="isSelected(employee.id,iso(d))" class="cell-selected"><img :src="plusIcon" alt=""><small v-if="shiftAt(employee.id,iso(d))">{{ shiftAt(employee.id,iso(d))?.name }}</small></span><span v-else-if="shiftAt(employee.id,iso(d))" class="shift" :class="[shiftAt(employee.id,iso(d))?.color, {'drag-source':dragActive && dragItem?.kind === 'shift' && dragItem.value.id === shiftAt(employee.id,iso(d))?.id}]" @pointerdown="startDrag($event, {kind:'shift', value:shiftAt(employee.id,iso(d))!})"><span v-if="view!=='month'" class="shift-time" :class="{'shift-time-precise': !shiftAt(employee.id,iso(d))!.start_time.endsWith(':00') || !shiftAt(employee.id,iso(d))!.end_time.endsWith(':00')}">{{ shiftTime(shiftAt(employee.id,iso(d))!.start_time) }}–{{ shiftTime(shiftAt(employee.id,iso(d))!.end_time) }}</span><span class="shift-duration" :class="{'shift-duration-precise': shiftDuration(shiftAt(employee.id,iso(d))!.start_time, shiftAt(employee.id,iso(d))!.end_time).includes('мин')}">{{ shiftDuration(shiftAt(employee.id,iso(d))!.start_time, shiftAt(employee.id,iso(d))!.end_time) }}</span></span><span v-else class="cell-empty"></span></button></div><div class="grid-row coverage"><div>Покрытие</div><div v-for="d in days" :key="iso(d)" :class="{covered:shifts.some(s=>s.date===iso(d))}">{{ shifts.filter(s=>s.date===iso(d)).length }}</div></div></div></div>
      </section><aside class="templates-panel"><div class="panel-title">Шаблоны смен <button :disabled="busy" @click="openTemplate()" aria-label="Добавить шаблон">＋</button></div><div v-if="!templates.length" class="muted">Нет шаблонов</div><div v-for="(t,i) in templates" :key="t.id" class="template" :class="t.color"><button class="template-apply" :class="{'drag-source':dragActive && dragItem?.kind === 'template' && dragItem.value.id === t.id}" :disabled="busy" @pointerdown="startDrag($event, {kind:'template', value:t})" @click="templateClick(t)"><b>{{ t.name }}</b><span>{{ t.start_time }} - {{ t.end_time }} · {{ duration(t.start_time,t.end_time) }} ч</span></button><button :disabled="busy" class="template-edit" :aria-label="`Изменить шаблон ${t.name}`" @click="openTemplate(t)">{{ i+1 }}</button></div><div class="custom"><p>Своя смена</p><div class="time-fields"><label>Начало<input v-model="customStart" type="time"></label><label>Конец<input v-model="customEnd" type="time"></label></div><button class="apply" :disabled="busy || !selected.length" @click="apply({name:'Своя смена',start_time:customStart,end_time:customEnd,color:'brand'})">＋ Поставить в выделенные</button><small>Выберите ячейки в графике</small></div></aside></div>
    </main>
    <Teleport to="body"><div v-if="dragActive && dragItem" ref="dragGhost" class="drag-ghost" :class="dragItem.value.color" aria-hidden="true"><span>{{ shiftTime(dragItem.value.start_time) }}–{{ shiftTime(dragItem.value.end_time) }}</span><small>{{ shiftDuration(dragItem.value.start_time,dragItem.value.end_time) }}</small></div></Teleport>
    <div v-if="quickDate" class="quick-backdrop" @click.self="quickDate='' "><div class="quick-menu" :style="{left: quickPosition.left+'px', top: quickPosition.top+'px'}" role="dialog" aria-label="Быстрое назначение"><div class="quick-title">Быстрое назначение <button @click="quickDate=''" aria-label="Закрыть">×</button></div><label>Сотрудник<select v-model="quickEmployee"><option value="" disabled>Выберите сотрудника</option><option v-for="e in employees" :key="e.id" :value="e.id" :disabled="!!shiftAt(e.id,quickDate)">{{ e.name }}{{ shiftAt(e.id,quickDate) ? ' · уже назначена' : '' }}</option></select></label><p v-if="error" class="quick-error" role="alert">{{ error }}</p><p>Выберите шаблон</p><button v-for="t in templates" :key="t.id" class="quick-template" :disabled="busy" @click="quickAssign(t)"><b>{{ t.name }}</b><span>{{ t.start_time }}–{{ t.end_time }}</span></button><span v-if="!templates.length">Нет шаблонов</span></div></div>
    <div v-if="editing || templateModal" class="overlay" @click.self="editing=null; templateModal=false"><div class="dialog" role="dialog" aria-modal="true" :aria-label="editing ? 'Редактирование смены' : 'Шаблон смены'"><div class="dialog-title"><h2>{{ editing ? 'Редактировать смену' : templateEditing ? 'Редактировать шаблон' : 'Новый шаблон' }}</h2><button @click="editing=null; templateModal=false" aria-label="Закрыть">×</button></div><p v-if="error" class="dialog-error" role="alert">{{ error }}</p><form @submit.prevent="editing ? editSave() : saveTemplate()"><label>Название<input v-model="form.name" required maxlength="80"></label><div class="time-fields"><label>Начало<input v-model="form.start_time" type="time" required></label><label>Конец<input v-model="form.end_time" type="time" required></label></div><label>Цвет<select v-model="form.color"><option value="cornflower">Голубой</option><option value="lavender">Лавандовый</option><option value="mint">Мятный</option><option value="brand">Синий</option></select></label><p class="muted">Время окончания должно быть позже времени начала.</p><div class="dialog-actions"><button v-if="editing || templateEditing" type="button" class="danger" @click="editing ? removeShift() : removeTemplate()">Удалить</button><button class="primary" :disabled="busy">Сохранить</button></div></form></div></div>
  </div>
</template>
