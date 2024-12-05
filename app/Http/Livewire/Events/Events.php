<?php

namespace App\Http\Livewire\Events;

use App\Helpers\GroupDateHelper;
use App\Http\Controllers\User\Profile;
use App\Http\Livewire\AppComponent;
use App\Models\DayStat;
use App\Models\Event;
use App\Models\Group;
use App\Models\GroupDate;
use App\Models\GroupDayDisabledSlots;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class Events extends AppComponent
{

    public $year = 0;
    public $month = 0;
    public $pagination = [];
    private $current_month = "";
    private $groups = [];
    private $cal_service_days = [];
    private $cal_group_data = [];
    public $form_groupId = 0;
    public $listeners = [
        'openModal', 
        'refresh' => 'render',
        'pollingOn',
        'pollingOff',
        'openEventsModal'
    ];
    private $first_day = null;
    private $last_day = null;
    private $day_stat = [];
    private $userEvents = [];
    public $polling = true;

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

    function build_pagination($created_at) {
 
        $prevMonth = $this->month - 1;         
        if ($prevMonth == 0) {
            $prevMonth = 12;
        }         
        if ($prevMonth == 12){  
            $prevYear = $this->year - 1;
        } else {
            $prevYear = $this->year;
        }
        $created = strtotime(date("Y-m-01", strtotime($created_at)));
        $prev = strtotime($prevYear."-".$prevMonth."-01");
        if($prev < $created) {
            $prevYear = false;
            $prevMonth = false;
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
        $this->getGroups();
        $key = array_search($this->form_groupId, array_column($this->groups, 'id'));
        // $group = Auth()->user()->groupsAccepted()->wherePivot('group_id', $this->form_groupId)->firstOrFail()->toArray();
        // if($group['pivot']['group_id']) {
        if($key !== false) {
            // dd('change', $this->form_groupId);
            session(['groupId' => $this->form_groupId]);
            $this->emitTo('events.modal', 'setGroup', $this->form_groupId);
            $this->emitTo('groups.special-date-modal', 'setGroup', $this->form_groupId);
        }         
    }
    
    public function getGroupData() {
        $this->cal_service_days = [];
        $groupId = session('groupId');
        try {
            //mybe logout the current session group
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
        } catch(ModelNotFoundException $e)
        {
            $first = Auth()->user()->groupsAccepted()->first()->toArray();
            if(isset($first['id'])) {
                session(['groupId' => $first['id']]);
                $this->reset();
            } else {
                abort('404');
            }
        }        
    }

    // public function getStat() {
    //     $groupId = session('groupId');

    //     $stats = DayStat::where('group_id', $groupId)
    //                         ->whereBetween('day', [$this->first_day, $this->last_day])
    //                         ->orderBy('time_slot')
    //                         ->get()
    //                         ->toArray();
    //     $colors = $dayOfWeeks = [];
    //     // dd($stats);
    //     foreach($stats as $stat) {
    //         if(!isset($dayOfWeeks[$stat['day']])) {
    //             $d = new DateTime( $stat['day'] );
    //             $dayOfWeek = $d->format("w");
    //             $dayOfWeeks[$stat['day']] = $dayOfWeek;
    //         } else {
    //             $dayOfWeek = $dayOfWeeks[$stat['day']];
    //         }

    //         $min_publishers = $specialDates[$stat['day']]['date_min_publishers'];
    //         $max_publishers = $specialDates[$stat['day']]['date_max_publishers'];
    //         $color = $this->cal_group_data['colors']['color_empty']; //green
    //         if($stat['events'] > 0 && $stat['events'] < $min_publishers) {
    //             $color = $this->cal_group_data['colors']['color_someone']; //blue
    //         }
    //         if($stat['events'] >= $min_publishers) {
    //             $color =  $this->cal_group_data['colors']['color_minimum']; //yellow
    //         } 
    //         if($stat['events'] == $max_publishers) {
    //             $color = $this->cal_group_data['colors']['color_maximum']; //red
    //         }
    //         $slot_key = Carbon::parse($stat['time_slot'])->format("H:i");
    //         if(($disabled_slots[$dayOfWeek][$slot_key] ?? false)) {
    //             $color = $this->cal_group_data['colors']['color_default'];
    //         }
    //         $colors[$stat['day']][] = $color;
    //     }
    //     if(count($colors)) {
    //         $total_percent = [];
    //         foreach($colors as $day => $values) {
    //             $total = count($values);
    //             $percent = round(100 / $total, 3);
    //             $total_percent[$day] = 0;
    //             $pos = 0;
    //             $this->day_stat[$day] = "linear-gradient(to right";
    //             foreach($values as $k => $color) {
    //                 // $this->day_stat[$day] .= ", ".$color." ".$percent."% ".$pos."%";
    //                 $this->day_stat[$day] .= ", ".$color." ".$pos."% ".($pos + $percent)."%";
    //                 $pos+=$percent;
    //                 $total_percent[$day]+=$percent;
    //             }
    //             $this->day_stat[$day] .= ");";
    //         }
    //     }
    //     // dd($this->day_stat, $total_percent);
    // }

    public function getGroups() {
        $this->groups = Auth()->user()->groupsAcceptedFiltered()->orderByPivot('list_order')
                                ->with(['dates' => function($q) {
                                    $q->whereBetween('date', [$this->first_day, $this->last_day]);
                                }])
                                ->get()->toArray();
    }

    public function pollingOn() {
        $this->polling = true;
    }

    public function pollingOff() {
        $this->polling = false;
    }

    public function openEventsModal($date) {
        // dd($date);
        $this->emitTo('events.modal', 'openModal', $date, $this->form_groupId);
        $this->polling = false;
    }

    public function render()
    {
        // $this->calendar = [];
        // dd($this->day_data);
        $this->day_stat = [];
        $this->cal_service_days = [];
        $this->cal_group_data = [];
        // What is the first day of the month in question?
        $firstDayOfMonth = mktime(0,0,0,$this->month,1, $this->year);
        $this->current_month = date('F', $firstDayOfMonth);
        $this->first_day = date("Y-m-d", $firstDayOfMonth);
        $this->last_day = date("Y-m-t", $firstDayOfMonth);
        
        // $groups =  User::findOrFail(Auth::id());
        $this->getGroups();
        // dd($this->groups);
        if(count($this->groups) == 0) {
            return view('livewire.default', [
                'error' => __('group.notInGroup')
            ]);
        }

        if(!session('groupId')) {
            // $first = $groups->groupsAccepted()->first()->toArray();
            session(['groupId' => $this->groups[0]['id']]);
        }
        $groupId = session('groupId');
        $this->form_groupId = $groupId;

        $key = array_search($groupId, array_column($this->groups, 'id'));
        if($key === false/* && count($this->groups) == 0*/) {
            if(count($this->groups) == 0) {
                abort('404');
            } else {
                session(['groupId' => $this->groups[0]['id']]);
                $groupId = session('groupId');
                $this->form_groupId = $groupId;
            }
        }

        // $helper = new GroupDateHelper($this->form_groupId);
        // $helper->generateDate("2022-06-03");

        $this->cal_group_data = Auth::user()->groupsAccepted()
        ->where('groups.id', '=', $this->form_groupId)
        ->with([
            'stats' => function($q) {
                $q->whereBetween('day', [$this->first_day, $this->last_day]);
                $q->orderBy('time_slot');
            },
            'justEvents' => function($q) {
                $q->where('user_id', '=', Auth::id());
                $q->whereIn('status', [0,1]);
                $q->whereBetween('day', [$this->first_day, $this->last_day]);
            },
            'days',
            'disabled_slots',
            'dates' => function($q) {
                $q->whereBetween('date', [$this->first_day, $this->last_day]);
            },
            'weather'
        ])->first()->toArray();
        // dd($this->cal_group_data);
        $dates = [];
        foreach($this->cal_group_data['dates'] as $date) {
            $dates[$date['date']] = $date;
        }
        // dd($dates);
        if($this->cal_group_data['weather_enabled']) {
            if($this->cal_group_data['weather']['current_weather'] !== null) {
                $current_weather = json_decode($this->cal_group_data['weather']['current_weather'], true);
                $this->cal_group_data['weather']['current_weather'] = $current_weather;

                $forecast_weather = json_decode($this->cal_group_data['weather']['forecast_weather'], true);
                $forecast_list = array();
                if(count($forecast_weather['list'])) {
                    foreach($forecast_weather['list'] as $key => $forecast) {
                        $weather_time = Carbon::parse($forecast['dt_txt'], "UTC");
                        $day = $weather_time->format("Y-m-d");
                        if(!isset($dates[$day])) {
                            //forecast only for sevice days
                            continue;
                        } else {
                            $start_time = Carbon::parse($dates[$day]['date_start']);
                            $end_time = Carbon::parse($dates[$day]['date_end']);

                            if($weather_time->lt($start_time) || $weather_time->gt($end_time)) {
                                //forecast only for sevice days
                                continue;
                            }

                            if (!isset($forecast_list[$day]['min_temp'])) {
                                $forecast_list[$day]['min_temp'] = $forecast['main']['temp'];
                            }
                            if (!isset($forecast_list[$day]['max_temp'])) {
                                $forecast_list[$day]['max_temp'] = $forecast['main']['temp'];
                            }
                            $forecast_list[$day]['min_temp'] = min($forecast['main']['temp'], $forecast_list[$day]['min_temp']);
                            $forecast_list[$day]['max_temp'] = max($forecast['main']['temp'], $forecast_list[$day]['max_temp']);
                            $forecast_list[$day]['description'] = $forecast['weather'][0]['description'];
                            $forecast_list[$day]['icon'] = $forecast['weather'][0]['icon'];
                            $forecast_list[$day]['day_num'] = $weather_time->format("w");
                            $forecast_list[$day]['day'] = $weather_time->format("m.d");
                        }                        
                    }
                }
                // dd($forecast_list);
                $this->cal_group_data['weather']['forecasts'] = $forecast_list;
            }
        }
        // $service_days = [];
        // foreach($this->cal_group_data['days'] as $day) {
        //     $service_days[$day['day_number']] =  [
        //         'start_time' => $day['start_time'],
        //         'end_time' => $day['end_time'],
        //     ];
        // }

        // $future_service_days = $future_disabled_slots = [];
        // $change_date = Carbon::parse('2070-01-01'); //default value for compare

        // if(isset($this->cal_group_data['future_changes'])) {
        //     $change_date = Carbon::parse($this->cal_group_data['future_changes']['change_date']);
        //     $last_day = Carbon::parse($this->last_day);
        //     if($last_day->gte($change_date)) {
        //         foreach($this->cal_group_data['future_changes']['days'] as $day) {
        //             $future_service_days[$day['day_number']] =  [
        //                 'start_time' => $day['start_time'],
        //                 'end_time' => $day['end_time'],
        //             ];
        //         }
        //         foreach($this->cal_group_data['future_changes']['disabled_slots'] as $slot) {
        //             $future_disabled_slots[$slot['day_number']][$slot['slot']] = true;
        //         }
        //     }
        // }

        // $disabled_slots = [];
        // foreach($this->cal_group_data['disabled_slots'] as $slot) {
        //     $disabled_slots[$slot['day_number']][$slot['slot']] = true;
        // }
        $stats = $this->cal_group_data['stats'];
        $specialDatesList = [];

        $this->build_pagination($this->cal_group_data['created_at']);
        $calendar = [];

        // Retrieve some information about the first day of the
        // month in question.

        $dayOfWeek = date("w", $firstDayOfMonth);

        $firstDay = auth()->user()->firstDay;
        if($firstDay === null) {
            $first_day_name = date('l', strtotime("this week"));
            $firstDay = ($first_day_name == "Monday") ? 1 : 0;
        } 

        $row = 1;
        $lineBreak = 7;

        if($firstDay == 1) {
            //monday is the first day of week
            $weekDays = [
                1,2,3,4,5,6,0
            ];
            $isoDay = date("N", $firstDayOfMonth);
            if ($isoDay > 0) { 
                $calendar[$row][] = [
                    'colspan' => $isoDay - 1,
                    'day' => '',
                    'current' => '',
                    'weekDay' => '',
                    'fullDate' => '',
                    'available' => false,
                    'service_day' => false,
                ];                
            }
        } else {
            //sunday is the first day of week
            $weekDays = [
                0,1,2,3,4,5,6
            ];
            if ($dayOfWeek > $firstDay) { 
                $calendar[$row][] = [
                    'colspan' => $dayOfWeek,
                    'day' => '',
                    'current' => '',
                    'weekDay' => '',
                    'fullDate' => '',
                    'available' => false,
                    'service_day' => false
                ];
            }
        }

        $today = strtotime('today');
        $max_day = strtotime('+'.$this->cal_group_data['max_extend_days'].' days');
        $this->cal_group_data['max_day'] = date("Y-m-d", $max_day);

        $calendar_days = Carbon::parse($this->first_day)->daysUntil($this->last_day);
        // dd($dates);
        // dd($this->cal_group_data);

        $helper = new GroupDateHelper($this->form_groupId);

        foreach($calendar_days as $currentDay) {
            //start new row
            $date = $currentDay->format("Y-m-d");
            $timestamp = $currentDay->getTimestamp();
            $weekDay = $currentDay->dayOfWeek;
            if ($weekDay == $firstDay) {
                $dayOfWeek = 0;
                $row++;
            }           
            
            $available = false;
            $service_day = false;
            // $dates[$date]['color'] = $this->cal_group_data['colors']['color_default'].";"; 
            $create = [];
            if($timestamp >= $today && !isset($dates[$date]['date_status'])) {
                $generate = $helper->generateDate($date);
                if(is_array($generate)) {
                    $dates[$date] = $generate;
                    if(!isset($dates[$date]['color']))
                        $dates[$date]['color'] = $this->cal_group_data['colors']['color_empty'].";";
                }
                // $end_date = $date;
                // if($currentDay->gte($change_date)) {
                //     if(isset($future_service_days[$weekDay])) {
                //         $create_day = $future_service_days[$weekDay];
                //         $create = [
                //             'group_id' => $groupId,
                //             'date' => $date,
                //             'date_start' => $date." ".$create_day['start_time'].":00",
                //             'date_end' => $end_date." ".$create_day['end_time'].":00",
                //             'date_status' => 1,
                //             'date_min_publishers' => $this->cal_group_data['future_changes']['group']['min_publishers'],
                //             'date_max_publishers' => $this->cal_group_data['future_changes']['group']['max_publishers'],
                //             'date_min_time' => $this->cal_group_data['future_changes']['group']['min_time'],
                //             'date_max_time' => $this->cal_group_data['future_changes']['group']['max_time'],
                //             'disabled_slots' => $future_disabled_slots[$dayOfWeek] ?? null
                //         ];
                //     }
                // } else {
                //     if(isset($service_days[$weekDay])) {
                //         $create_day = $service_days[$weekDay];
                //         if(strtotime($create_day['end_time']) == strtotime("00:00")) {
                //             $end_date = Carbon::parse($date)->addDay()->format("Y-m-d");
                //         }                        
                //         $create = [
                //             'group_id' => $groupId,
                //             'date' => $date,
                //             'date_start' => $date." ".$create_day['start_time'].":00",
                //             'date_end' => $end_date." ".$create_day['end_time'].":00",
                //             'date_status' => 1,
                //             'date_min_publishers' => $this->cal_group_data['min_publishers'],
                //             'date_max_publishers' => $this->cal_group_data['max_publishers'],
                //             'date_min_time' => $this->cal_group_data['min_time'],
                //             'date_max_time' => $this->cal_group_data['max_time'],
                //             'disabled_slots' => $disabled_slots[$dayOfWeek] ?? null
                //         ];
                //     }
                // }
            }
            // if(count($create) > 0) {
            //     //create day data if it's not exists
            //     // dump("create: ". $date);
            //     $dates[$date] = $create;
            //     // GroupDate::create($create);
            //     if(!isset($dates[$date]['color']))
            //         $dates[$date]['color'] = $this->cal_group_data['colors']['color_empty'].";";
            // }
            if(isset($service_days[$weekDay]) && !isset($dates[$date]['date_status'])) {
                $dates[$date]['color'] = $this->cal_group_data['colors']['color_empty'].";";
            }

            if(($dates[$date]['date_status'] ?? null) === 0) {
                $available = false;
                $service_day = false;
                if(!isset($dates[$date]['color']))
                    $dates[$date]['color'] = $this->cal_group_data['colors']['color_default'].";";
            } elseif(in_array(($dates[$date]['date_status'] ?? false), [1,2])) {
                $available = true;
                $service_day = true;
                if(!isset($dates[$date]['color']))
                    $dates[$date]['color'] = $this->cal_group_data['colors']['color_empty'].";";
            }
            if(in_array(($dates[$date]['date_status'] ?? []), [0,2])) {
                // dd($dates[$date]);
                $specialDatesList[$date] = $dates[$date];
                // dump($dates[$date]);
            }

            // if(isset($specialDates[$date])) {
            //     if($specialDates[$date]['date_status'] == 0) {
            //         $available = false;
            //         $service_day = false;
            //         $dates[$date]['color'] = $this->cal_group_data['colors']['color_default'].";";
            //     } else {
            //         $available = true;
            //         $service_day = true;
            //         $dates[$date]['color'] = $this->cal_group_data['colors']['color_empty'].";";
            //     }
            // } elseif($timestamp >= $today && isset($this->cal_service_days[$weekDay])) {
            //     //create day data if it's not exists
            //     $end_date = $date;
            //     if(strtotime($this->cal_service_days[$weekDay]['end_time']) == strtotime("00:00")) {
            //         $end_date = Carbon::parse($date)->addDay()->format("Y-m-d");
            //     }
            //     GroupDate::create([
            //         'group_id' => $groupId,
            //         'date' => $date,
            //         'date_start' => $date." ".$this->cal_service_days[$weekDay]['start_time'].":00",
            //         'date_end' => $end_date." ".$this->cal_service_days[$weekDay]['end_time'].":00",
            //         'date_status' => 1,
            //         'date_min_publishers' => $this->cal_group_data['min_publishers'],
            //         'date_max_publishers' => $this->cal_group_data['max_publishers'],
            //         'date_min_time' => $this->cal_group_data['min_time'],
            //         'date_max_time' => $this->cal_group_data['max_time'],
            //         'disabled_slots' => $disabled_slots[$dayOfWeek]
            //     ]);
                
            //     $available = true;
            //     $service_day = true;
            //     $dates[$date]['color'] = $this->cal_group_data['colors']['color_empty'].";";
            // }
            // if(isset($this->cal_service_days[$weekDay])) {
            //     $specialDates[$date] = [
            //         'date_min_publishers' => $this->cal_group_data['min_publishers'],
            //         'date_max_publishers' => $this->cal_group_data['max_publishers'],
            //         'date_min_time' => $this->cal_group_data['min_time'],
            //         'date_max_time' => $this->cal_group_data['max_time'],
            //         'disabled_slots' => $this->cal_group_data['disabled_slots']   
            //     ];
            // }

            if($timestamp > $max_day) {
                $available = false;
                $service_day = false;
            }

            $calendar[$row][] = [
                'colspan' => null,
                'weekDay' => $weekDay,
                'day' => $currentDay->format("j"),
                'current' => $date == date("Y-m-d") ? true : false,
                'fullDate' => $date,
                'available' => $available,
                'service_day' => $service_day
            ];
            
            $dayOfWeek++;
        }

        // Complete the row of the last week in month, if necessary

        if ($dayOfWeek != $lineBreak) { 
            $remainingDays = $lineBreak - $dayOfWeek;
            $calendar[$row][] = [
                'colspan' => $remainingDays,
                'day' => '',
                'current' => '',
                'weekDay' => '',
                'fullDate' => '',
                'available' => false,
                'service_day' => false
            ];
        }
        ksort($dates);

        $colors = $dayOfWeeks = [];
        foreach($stats as $stat) {
            if(!isset($dates[$stat['day']])) continue;

            if(!isset($dayOfWeeks[$stat['day']])) {
                $d = new DateTime( $stat['day'] );
                $dayOfWeek = $d->format("w");
                $dayOfWeeks[$stat['day']] = $dayOfWeek;
            } else {
                $dayOfWeek = $dayOfWeeks[$stat['day']];
            }
            $min_publishers = $dates[$stat['day']]['date_min_publishers'];
            $max_publishers = $dates[$stat['day']]['date_max_publishers'];
            $color = $this->cal_group_data['colors']['color_empty']; //green
            if($stat['events'] > 0 && $stat['events'] < $min_publishers) {
                $color = $this->cal_group_data['colors']['color_someone']; //blue
            }
            if($stat['events'] >= $min_publishers) {
                $color =  $this->cal_group_data['colors']['color_minimum']; //yellow
            } 
            if($stat['events'] == $max_publishers) {
                $color = $this->cal_group_data['colors']['color_maximum']; //red
            }
            $slot_key = Carbon::parse($stat['time_slot'])->format("H:i");
            if(($dates[$stat['day']]['disabled_slots'][$slot_key] ?? false)) {
                $color = $this->cal_group_data['colors']['color_default'];
            }
            $colors[$stat['day']][] = $color;
        }
        if(count($colors)) {
            $total_percent = [];
            foreach($colors as $day => $values) {
                $total = count($values);
                $percent = round(100 / $total, 3);
                $total_percent[$day] = 0;
                $pos = 0;
                $dates[$day]['color'] = "linear-gradient(to right";
                foreach($values as $k => $color) {
                    $dates[$day]['color'] .= ", ".$color." ".$pos."% ".($pos + $percent)."%";
                    $pos+=$percent;
                    $total_percent[$day]+=$percent;
                }
                $dates[$day]['color'] .= ");";
            }
        }

        $userEvents = $this->cal_group_data['just_events'];
        foreach($userEvents as $ev) {
            $dates[$ev['day']]['user_event'] = true;
        }

        $notAcceptedEvents = [];
        if(in_array($this->cal_group_data['pivot']['group_role'], ['admin', 'roler', 'helper'])) {
            $notAcceptedEvents = DB::table('events')
                                    ->groupBy('day')
                                    ->whereNull('deleted_at')
                                    ->where('group_id', '=', $this->cal_group_data['id'])
                                    ->where('status', '=', 0)
                                    ->whereBetween('day', [$this->first_day, $this->last_day])
                                    ->pluck('day', 'day')
                                    ->toArray();
        }
        return view('livewire.events.events', [
            'service_days' => $this->cal_service_days,
            'calendar' => $calendar,
            'dates' => $dates,
            'specialDatesList' => $specialDatesList,
            'notAcceptedEvents' => $notAcceptedEvents,
            'group_days' => $weekDays, // is_array(trans('group.days')) ? trans('group.days') : range(0,6,1),
            'current_month' => $this->current_month,
            'cal_group_data' => $this->cal_group_data,
            'day_stat' => $this->day_stat,
            'userEvents' => $this->userEvents,
            'groups' => $this->groups,
            'group_editor' => in_array($this->cal_group_data['pivot']['group_role'], ['admin', 'roler']) ? true : false
        ]);
    }
}
