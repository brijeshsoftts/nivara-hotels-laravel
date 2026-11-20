@props([
    "name" => null,
    "value" => null,
])

<div
    data-slot="calendar"
    x-data="{
        currentDate: new Date(),
        selectedDate: @js($value ? date('Y-m-d', strtotime($value)) : null),
        get currentMonth() {
            return this.currentDate.getMonth();
        },
        get currentYear() {
            return this.currentDate.getFullYear();
        },
        get monthName() {
            return this.currentDate.toLocaleString('default', { month: 'long' });
        },
        prevMonth() {
            this.currentDate = new Date(this.currentYear, this.currentMonth - 1, 1);
        },
        nextMonth() {
            this.currentDate = new Date(this.currentYear, this.currentMonth + 1, 1);
        },
        selectDate(day) {
            const d = new Date(this.currentYear, this.currentMonth, day);
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const dateStr = String(d.getDate()).padStart(2, '0');
            this.selectedDate = `${y}-${m}-${dateStr}`;
            this.$dispatch('date-selected', this.selectedDate);
        },
        get daysInMonth() {
            return new Date(this.currentYear, this.currentMonth + 1, 0).getDate();
        },
        get firstDayOfWeek() {
            return new Date(this.currentYear, this.currentMonth, 1).getDay();
        },
        isSelected(day) {
            const d = new Date(this.currentYear, this.currentMonth, day);
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const dateStr = String(d.getDate()).padStart(2, '0');
            return this.selectedDate === `${y}-${m}-${dateStr}`;
        },
        isToday(day) {
            const today = new Date();
            return today.getDate() === day &&
                today.getMonth() === this.currentMonth &&
                today.getFullYear() === this.currentYear;
        }
    }"
    {{ $attributes->class([
        "group/calendar bg-background p-3 rounded-lg border w-fit [--cell-radius:var(--radius-md)] [--cell-size:--spacing(7)]"
    ]) }}
>
    @if($name)
        <input type="hidden" name="{{ $name }}" :value="selectedDate" />
    @endif

    <!-- Header Navigation -->
    <div class="relative flex items-center justify-between pt-1 pb-2">
        <button
            type="button"
            @click="prevMonth"
            class="inline-flex size-7 items-center justify-center rounded-md border border-input bg-transparent text-muted-foreground hover:bg-muted hover:text-foreground"
        >
            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m15 18-6-6 6-6"/>
            </svg>
        </button>
        <div class="text-sm font-medium" x-text="`${monthName} ${currentYear}`"></div>
        <button
            type="button"
            @click="nextMonth"
            class="inline-flex size-7 items-center justify-center rounded-md border border-input bg-transparent text-muted-foreground hover:bg-muted hover:text-foreground"
        >
            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m9 18 6-6-6-6"/>
            </svg>
        </button>
    </div>

    <!-- Weekdays Header -->
    <div class="grid grid-cols-7 gap-1 text-center text-xs text-muted-foreground py-1">
        <div>Su</div>
        <div>Mo</div>
        <div>Tu</div>
        <div>We</div>
        <div>Th</div>
        <div>Fr</div>
        <div>Sa</div>
    </div>

    <!-- Calendar Grid -->
    <div class="grid grid-cols-7 gap-1 mt-1">
        <template x-for="blank in firstDayOfWeek">
            <div class="size-7"></div>
        </template>
        <template x-for="day in daysInMonth">
            <button
                type="button"
                @click="selectDate(day)"
                :class="{
                    'bg-primary text-primary-foreground font-semibold': isSelected(day),
                    'bg-muted text-foreground': isToday(day) && !isSelected(day),
                    'hover:bg-muted text-foreground': !isSelected(day) && !isToday(day),
                }"
                class="size-7 text-xs rounded-md flex items-center justify-center transition-colors outline-none focus-visible:ring-2 focus-visible:ring-ring"
                x-text="day"
            ></button>
        </template>
    </div>
</div>
