<?php

namespace App\Http\Livewire\Events;

use App\Http\Livewire\AppComponent;
use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\Auth;


class Events extends AppComponent
{

    public $year = 0;
    public $month = 0;
    // public $calendar = [];
    public $pagination = [];
    public $current_month = "";
    public $groups = [];
    public $cal_service_days = [];
    public $cal_group_data = [];
    public $form_groupId = 0;
    public $cal_day_data = [
        'date' => 0,
        'dateFormat' => 0,
        'table' => [],
        'selects' => [
            'start' => [],
            'end' => [],
        ],
    ];
    public $cal_original_day_data = [];
    public $listeners = ['openModal'];
    public $cal_active_tab = '';

    public function mount(int $year = 0, int $month = 0) {
        if(isset($year)) {
            if(strlen($year) == 4) {
                if($year > 2020 && $year < 9999) {
                    $this->year = $year;            
                }
            }
        } 
        if($this->year == 0)
            $this->year = date('Y');

        if(isset($month)) {
            if($month > 0 && $month < 13) {
                $this->month = $month;            
            }

        } 
        if($this->month == 0) {
            $this->month = date('m');
        }
    }    

    function build_pagination() {
 
        $prevMonth = $this->month - 1;         
        if ($prevMonth == 0) {
            $prevMonth = 12;
        }         
        if ($prevMonth == 12){  
            $prevYear = $this->year - 1;
        } else {
            $prevYear = $this->year;
        }

        $nextMonth = $this->month + 1;
         
        if ($nextMonth == 13) {
            $nextMonth = 1;
        }
       
        if ($nextMonth == 1){  
            $nextYear = $this->year + 1;
        } else {
            $nextYear = $this->year;
        }
        
        $this->pagination['prev'] = [
            'year' => $prevYear,
            'month' => $prevMonth
        ];
        $this->pagination['next'] = [
            'year' => $nextYear,
            'month' => $nextMonth
        ];
    }

    public function changeGroup() {
        $group = Group::findOrFail($this->form_groupId)->whereId($this->form_groupId)->first()->toArray();
        if($group['id']) {
            session(['groupId' => $this->form_groupId]);
        }
        $this->emitTo('events.modal', 'setGroup', $this->form_groupId);
    }
    
    public function getGroupData() {
        $this->cal_service_days = [];
        $groupId = session('groupId');
        $group = Group::findOrFail($groupId);
        $days = $group->days()->get()->toArray();
        if(count($days)) {
            foreach($days as $day) {
                $this->cal_service_days[$day['day_number']] = [
                    'start_time' => $day['start_time'],
                    'end_time' => $day['end_time'],
                ];
            }
        }
        $this->cal_group_data = $group->whereId($groupId)->first()->toArray();
    }


    public function render()
    {
        // $this->calendar = [];
        // dd($this->day_data);
        
        $groups = User::findOrFail(Auth::id());
        $this->groups = $groups->userGroups()->get()->toArray();

        if(!session('groupId')) {
            $first = $groups->userGroups()->first()->toArray();
            session(['groupId' => $first['id']]);
        }
        $this->getGroupData();
        $this->build_pagination();

        $calendar = [];

        // What is the first day of the month in question?
        $firstDayOfMonth = mktime(0,0,0,$this->month,1, $this->year);
        $this->current_month = date('F', $firstDayOfMonth);

        // How many days does this month contain?
        $numberDays = date('t',$firstDayOfMonth);

        // Retrieve some information about the first day of the
        // month in question.
        $dayOfWeek = strftime("%u", $firstDayOfMonth) - 1;

        $weekDays = [
            1,2,3,4,5,6,0
        ];
        $row = 1;
        $currentDay = 1;

        if ($dayOfWeek > 0) { 
            $calendar[$row][] = [
                'colspan' => $dayOfWeek,
                'day' => '',
                'current' => '',
                'weekDay' => '',
                'fullDate' => '',
                'available' => false,
                'service_day' => false,
            ];
        }

        $month = str_pad($this->month, 2, "0", STR_PAD_LEFT);
        $today = strtotime('today');
        $max_day = strtotime('+'.$this->cal_group_data['max_extend_days'].' days');
        $this->cal_group_data['max_day'] = date("Y-m-d", $max_day);
        // dd(date('Y-m-d', $max_day), date('Y-m-d', $today));

        while ($currentDay <= $numberDays) {
            //start new row
            if ($dayOfWeek == 7) {

                $dayOfWeek = 0;
                $row++;
            }
            
            $currentDayRel = str_pad($currentDay, 2, "0", STR_PAD_LEFT);
            
            $date = "$this->year-$month-$currentDayRel";
            $timestamp = strtotime($date);
            $available = ($timestamp >= $today && $timestamp <= $max_day);

            $calendar[$row][] = [
                'colspan' => null,
                'weekDay' => $weekDays[$dayOfWeek],
                'day' => $currentDay,
                'current' => $date == date("Y-m-d") ? true : false,
                'fullDate' => $date,
                'available' => $available,
                'service_day' => (isset($this->cal_service_days[$weekDays[$dayOfWeek]])) ? true : false
            ];
            // Increment counters
            $currentDay++;
            $dayOfWeek++;
        }

        // Complete the row of the last week in month, if necessary

        if ($dayOfWeek != 7) { 

            $remainingDays = 7 - $dayOfWeek;

            $calendar[$row][] = [
                'colspan' => $remainingDays,
                'day' => '',
                'current' => '',
                'weekDay' => '',
                'fullDate' => '',
                'available' => false,
                'service_day' => false,
            ];
        }        
        
        // dd($calendar);
        return view('livewire.events.events', [
            'service_days' => $this->cal_service_days,
            'calendar' => $calendar
        ]);

        // return view('livewire.events.calendar');
    }
}
