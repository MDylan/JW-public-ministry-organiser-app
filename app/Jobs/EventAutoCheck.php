<?php

namespace App\Jobs;

use App\Classes\GenerateSlots;
use App\Models\Event;
use App\Models\Group;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EventAutoCheck implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $start = null;
    private $end = null;
    private $event = null;
    private $groupId = null;
    private $date = null;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Event $event, $start, $end)
    {
        $this->event = $event;
        $this->groupId = $event->group_id;
        $this->date = $event->day;
        $this->start = $start;
        $this->end = $end;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        /**
         * IMPORTANT!!! THIS IS NOT FINISHED YET!!!
         */

        //if not need to approve event, skip the whole process
        if(!$this->event->groups->need_approval) return false;

        //if not affected by auto aproval or auto status restoration, skip too
        if (!$this->event->groups->auto_approval && !$this->event->groups->auto_back) return false;

        $groupId = $this->groupId;
        $date = $this->date;

        //get event's date info
        $group = Group::with(['current_date' => function ($q) use ($date) {
            $q->where('date', '=', $date);
        }])->find($groupId);

        if ($group) {
            $day_stat = array();

            $group_data = $group->toArray();

            if (($group_data['current_date']['date_status'] ?? 1) == 0) {
                //this day is disabled
                return false;
            }

            $start = strtotime($group_data['current_date']['date_start']);
            $max = strtotime($group_data['current_date']['date_end']);
            $date_data = [
                'min_publishers' => $group_data['current_date']['date_min_publishers'],
                'max_publishers' => $group_data['current_date']['date_max_publishers'],
                'min_time' => $group_data['current_date']['date_min_time'],
                'max_time' => $group_data['current_date']['date_max_time']
            ];


            if (count($date_data) === 0) return false;

            $step = $date_data['min_time'] * 60;

            $slots_array = GenerateSlots::generate($date, $start, $max, $step);
            foreach ($slots_array as $current) {
                $key = "'" . date('Hi', $current) . "'";
                $day_stat[$key] = [
                    'group_id' => $this->groupId,
                    'day' => $this->date,
                    'time_slot' => date('Y-m-d H:i', $current),
                    'all_events' => [],
                    'accepted_events' => [],
                    'pending_events' => []
                ];
            }
            //get accepted and pending events
            $events = $group->day_events($date)->get()->toArray();
            $current_event_slots = $events_slots = array();
            $current_event_id = $this->event->id;

            //generate slots
            foreach ($events as $event) {
                $steps = ($event['end'] - $event['start']) / $step;
                $key = "'" . date('Hi', $event['start']) . "'";
                $cell_start = $event['start'];
                for ($i = 0; $i < $steps; $i++) {
                    $slot_key = "'" . date("Hi", $cell_start) . "'";

                    $events_slots[$event->id][$slot_key] = $slot_key;
                    $day_stat[$slot_key]['all_events'][$event->id] = $event->id;
                    if($event->status == 1) 
                        $day_stat[$slot_key]['accepted_events'][$event->id] = $event->id;
                    else
                        $day_stat[$slot_key]['pending_events'][$event->id] = $event->id;

                    if($event->id == $current_event_id) {
                        $current_event_slots[$slot_key] = $slot_key;
                    }                   

                    $cell_start += $step;
                }
            }

            //ok, we get data for all slots of the day. Now we check what can be affected by the current event

            $other_events = Event::where('group_id', $this->event->group_id)
                ->where('status', '<>', 2)
                ->where('start', '=<', date("Y-m-d H:i:s", $this->end))
                ->where('end', '>=', date("Y-m-d H:i:s", $this->start))
                ->orderBy('created_at', 'ASC')
                ->get();

            foreach($current_event_slots as $slot) {

            }



        } else {
            return false;
        }


        $min_publishers = $this->event->groups->min_publishers;

        if($this->event->groups->auto_approval) {
            //automatikus elfogadás ha megvan a minimum létszám
            //megkeressük azokat az eseményeket, amelyeknek a kezdési ideje 

            Log::info('Esemény: '.$this->event->id);

            $other_events = Event::where('group_id', $this->event->group_id)
                            ->where('status', '<>', 2)
                            ->where('start', '=', date("Y-m-d H:i:s", $this->start))
                            ->where('end', '=', date("Y-m-d H:i:s", $this->end))
                            //->where('id', '<>', $this->event->id)
                            ->orderBy('created_at', 'ASC')
                            ->get();
            //count the events
            $counter = 1;
            $accepts = [];
            //$accepts[] = $this->event->id;
            foreach($other_events as $other_event) {
                if($counter >= $min_publishers) continue;
                Log::info('Egyéb esemény találat: '.$other_event->id);
                $accepts[] = $other_event->id;
                $counter++;
            }
            if($counter >= $min_publishers) {
                Log::info('Találatok: Min pub:'.$min_publishers.', counter: '.$counter);
                foreach($accepts as $accept) {
                    Log::info('Elfogadom: '.$accept);
                    $event = Event::findOrFail($accept);
                    $event->update(['status' => 1]);
                }
            } else {
                Log::info('Nincs meg a létszám: Min pub:' . $min_publishers . ', counter: ' . $counter);
            }

        }

        // if ($this->event->groups->auto_back) {
        //     Log::info('Auto back vizsgálat. Event ID: ' . $this->event->id);

        //     $other_events = Event::where('group_id', $this->event->group_id)
        //         ->where('status', '<>', 2)
        //         ->where('start', '=', date("Y-m-d H:i:s", $this->start))
        //         ->where('end', '=', date("Y-m-d H:i:s", $this->end))
        //         ->orderBy('created_at', 'ASC')
        //         ->get();
        //     //count the events
        //     $counter = 1;
        //     $accepts = [];
        //     $accepts[] = $this->event->id;
        //     foreach ($other_events as $other_event) {
        //         if ($counter >= $min_publishers) continue;
        //         Log::info('Egyéb esemény találat: ' . $other_event->id);
        //         $accepts[] = $other_event->id;
        //         $counter++;
        //     }
        //     if ($counter >= $min_publishers) {
        //         Log::info('Találatok: Min pub:' . $min_publishers . ', counter: ' . $counter);
        //         foreach ($accepts as $accept) {
        //             Log::info('Elfogadom: ' . $accept);
        //             $event = Event::findOrFail($accept);
        //             $event->update(['status' => 1]);
        //         }
        //     } else {
        //         Log::info('Nincs meg a létszám: Min pub:' . $min_publishers . ', counter: ' . $counter);
        //     }

        // }

    }
}
