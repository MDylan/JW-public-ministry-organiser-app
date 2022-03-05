<?php

namespace App\Http\Livewire\Groups;

// use App\Classes\GenerateStat;
use App\Classes\GroupUserMoves;
use App\Http\Livewire\AppComponent;
// use App\Jobs\UserLogoutFromGroupProcess;
use App\Models\Group;
use App\Models\GroupUser;
// use App\Models\GroupDate;
use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Models\User;
use App\Notifications\FinishRegistration;
use App\Notifications\GroupParentGroupAttachedNotification;
use App\Notifications\GroupParentGroupDetachedNotification;
use App\Notifications\GroupUserAddedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

class ListUsers extends AppComponent
{
    public $groupId;
    public $state;
    public $selected_user;
    public $searchTerm = null;
    //for search with an url string
    protected $queryString = ['searchTerm' => ['except' => '']];
    private $group = null;
    private $role = null;

    static $group_roles = [
        'member', 
        'helper',
        'roler',
        'admin',
    ];

    public $userIdBeeingRemoved = null;
    public $detachId = null;
    public $new_users = "";
    public $email_language = "";

    public $new_parent_group_id = 0;

    protected $listeners = [         
        'openModal', 
        'createUser',
        'editUser',         
        'deleteUser',
        'detachChildGroup',
        'detachParentGroup',
    ];

    public function mount($group) {
        $this->groupId = $group;
        $this->email_language = config('settings_default_language');
    }

    public function openModal($modalId = 'UserModal') {
        // parent::openModal($modalId);
        $this->dispatchBrowserEvent('show-modal', ['id' => $modalId]);
    }

    /**
     * Elmenti a felhasználó adatait
     */
    public function createUser() {   

        $this->getGroupInfo();
        if($this->isNotHelper()) {
            abort(403);
        }

        //we cannot add new user, if this is a child group
        if($this->group->parent_group_id) {
            return;
        }

        // $email_array = preg_split('/\r\n|[\r\n]/', trim($this->new_users));
        $email_array = preg_split("/\R/", trim($this->new_users)); 
        // dd($email_array);
        $email = [];
        if(count($email_array)) {
            foreach($email_array as $mail) {
                $tmail = trim($mail);
                if(strlen($tmail) == 0) continue;
                $email['email'][] = $tmail;
            }
        }
        // dd($email);

        $v = Validator::make($email, [
            'email.*' => 'required|email:filter',
        ]); //->validate();

        $v->after(function ($validator) {            
            $langs = config('available_languages');
            if(!isset($langs[$this->email_language])) {
                $validator->errors()->add(
                    'email_language', __('group.user.add.email_language_error')
                );
            }
        });
        
        $validatedData = $v->validate();

        // dd($validatedData);
        if(count($validatedData['email'])) {
            $current_users = $this->group->groupUsers()->pluck('users.id')->toArray();

            foreach($validatedData['email'] as $mail) {
                $us = User::where('email',  $mail)->firstOr(function () use ($mail) {
                    $password = Str::random(10);
                    $u = User::create([
                        'email' => $mail,
                        'password' => bcrypt($password),
                        'language' => $this->email_language
                    ]);
                    // dd($u);
                    $url = URL::temporarySignedRoute(
                        'finish_registration', now()->addMinutes(7 * 24 * 60 * 60), [
                            'id' => $u->id
                        ]
                    );
                    $u->notify(
                        new FinishRegistration([
                            'groupAdmin' => auth()->user()->name, 
                            'userMail' => $mail,
                            'url' => $url
                        ])
                    );
                    return $u;
                });
                //skip user, if he is already in the group
                if(in_array($us->id, $current_users)) continue;

                $add = new GroupUserMoves($this->group->id, $us->id);
                $add->attach();
            }

            $this->dispatchBrowserEvent('hide-modal', [
                'id' => 'UserAddModal',
                'message' => __('group.user.add.success', ['number' => count($validatedData['email'])]),
                'savedMessage' => __('app.saved')
            ]);
        }
        $this->new_users = "";       
        

    }

    /**
     * Load user data into edit modal
     */
    public function editUser($UserId) {
        // dd($UserId);
        $this->getGroupInfo();
        if($this->isNotHelper()) {
            abort(403);
        }

        $user = $this->group->groupUsers()->where('user_id', '=', $UserId)->first();
        // dd($user);
        $this->selected_user = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
        $this->state = [
            'group_role' => $user->pivot->group_role,
            'note' => $user->pivot->note,
            'hidden' => $user->pivot->hidden
        ];

        $this->dispatchBrowserEvent('show-modal', ['id' => 'UserModal']);
    }

    public function updateUser() {

        $this->getGroupInfo();
        if($this->isNotHelper()) {
            abort(403);
        }       
        $admins = $this->group->groupAdmins()->get()->count();
        $current_user = $this->group->groupUsers()->where('user_id', Auth::id())->first();
        $selected_user = $this->group->groupUsers()->where('user_id', $this->selected_user['id'])->first();
        // dd($selected_user->toArray());

        if(!in_array($this->state['group_role'], $this->maxRoles())) {
            //if user have lower right, he dont change privilage
            $this->state['group_role'] = $selected_user->pivot->group_role;
        }
        // dd($this->state, $this->maxRoles(), $this->role, $selected_user->pivot->group_role);
        // $roles = implode(",", $this->maxRoles());
        // dd($roles, $this->maxRoles());

        $v = Validator::make($this->state, [
            'hidden' => 'required|boolean',
            'note' => 'nullable|string|max:50', 
            'group_role' =>  [
                Rule::In(self::$group_roles),    //csak a megadott jogosultság adható ki
            ],
        ]);
        $v->after(function ($validator) use ($admins, $current_user, $selected_user) {
            //if modified user rule
            if($selected_user->pivot->group_role != $this->state['group_role']) {
                if ($admins <= 1 && $selected_user->pivot->group_role == 'admin') {
                    $validator->errors()->add(
                        'users', __('group.error_no_admin_user')
                    );
                }
                if($current_user->pivot->group_role != 'admin' &&  $selected_user->pivot->group_role == 'admin') {
                    $validator->errors()->add(
                        'users', __('group.error_no_right_to_remove_admin')
                    );
                }
                if($current_user->pivot->group_role != 'admin' && $this->state['group_role'] == 'admin') {
                    $validator->errors()->add(
                        'users', __('group.error_no_right')
                    );
                }
            }
        });
        
        $validatedData = $v->validate();
        $user_sync[$this->selected_user['id']] = $validatedData;

        $this->group->groupUsersAll()->syncWithoutDetaching($user_sync);

        $this->dispatchBrowserEvent('hide-modal', [
            'id' => 'UserModal',
            'message' => __('group.user.saved').' ('.$selected_user->name.')',
            'savedMessage' => __('app.saved')
        ]);
    }

    public function confirmUserRemoval($userId) {
        
        $this->getGroupInfo();
        if($this->isNotHelper()) {
            abort(403);
        }
        $this->userIdBeeingRemoved = $userId;

        $selected_user = $this->group->groupUsers()->where('user_id', $userId)->first();
        if(!$selected_user->id) abort(403);

        $this->dispatchBrowserEvent('show-deletion-confirmation', [
            'title' => __('group.user.confirmDelete.question', ['name' => $selected_user->name]),
            'text' => __('group.user.confirmDelete.message'),
            'emit' => 'deleteUser'
        ]);
    }

    public function deleteUser() {
        $this->getGroupInfo();
        if($this->isNotHelper()) {
            abort(403);
        }

        if($this->group->parent_group_id > 0) {
            $this->dispatchBrowserEvent('error', [
                'message' => __('group.user.confirmDelete.error_this_is_child')
            ]);
            return;
        }

        $detach = new GroupUserMoves($this->group->id, $this->userIdBeeingRemoved);
        $detach->detach();

        $this->dispatchBrowserEvent('success', [
            'message' => __('group.user.confirmDelete.success')
        ]);

        // if($group->groupUsersAll()->detach($this->userIdBeeingRemoved)) {
        //     $this->dispatchBrowserEvent('success', [
        //         'message' => __('group.user.confirmDelete.success')
        //     ]);
        // } else {
        //     $this->dispatchBrowserEvent('error', [
        //         'message' => __('group.user.confirmDelete.error')
        //     ]);
        // }
        $this->userIdBeeingRemoved = null;
    }

    public function updatedSearchTerm() {
        $this->resetPage();
    }

    public function clearSearch() {
        $this->searchTerm = null;
    }

    public function user_admin_groups() {
        $user_admin_groups = User::find(Auth::id())->userGroupsDeletable()->get(['groups.id', 'groups.name', 'groups.parent_group_id']);
        // unset($user_admin_groups[$this->groupId]);
        // dd($user_admin_groups);
        return $user_admin_groups;
    }

    public function linkToGroup() {
        $group = Group::findorFail($this->groupId);
        if($group->groupAdmins()->wherePivot('user_id', Auth::id())->count() == 0) {
            abort(403);
        }
        $this->resetErrorBag();
        $this->resetValidation();
        
        //don't choose any group...
        if($this->new_parent_group_id == 0) {
            $this->addError('parent_group_id', __('group.link.error_no_selection'));
        }

        //this is the same group...
        if($this->new_parent_group_id == $this->groupId) {
            $this->addError('parent_group_id', __('group.link.error_same_group'));
        }

        $groups = $this->user_admin_groups();
        //check if he is admin or not in that group
        if($groups->where('id', $this->new_parent_group_id)->count() == 0) {
            $this->addError('parent_group_id', __('group.link.error_not_in_group'));
        } 
        if($groups->where('id', $this->new_parent_group_id)->whereNull('parent_group_id')->count() == 0) {
            $this->addError('parent_group_id', __('group.link.error_this_is_child'));
        } 

        if($group->childGroups()->count() > 0) {
            $this->addError('parent_group_id', __('group.link.error_this_is_parent'));
        }

        $errors = count($this->getErrorBag()->all());
        if($errors === 0) {
            $up = $group->update(['parent_group_id' => $this->new_parent_group_id]);
            if($up) {
                $new_group = Group::findorFail($this->new_parent_group_id);

                //send notifications to admins
                $admins = $group->groupAdmins;
                $data = [
                    'groupName' => $new_group->name,
                    'childGroupName' => $group->name,
                    'userName' => auth()->user()->name
                ];
                Notification::send($admins, new GroupParentGroupAttachedNotification($data));
                
                $new_users = $new_group->groupUsers()->get()->toArray();
                $user_sync = [];
                foreach($new_users as $new_user) {
                    $user_sync[$new_user['id']] = [
                        // 'group_role' => $new_user['pivot']['group_role'],
                        'note' => strip_tags(trim($new_user['pivot']['note'])),
                        'hidden' => $new_user['pivot']['hidden'] == 1 ? 1 : 0,
                        'deleted_at' => null, //because maybe we try to reattach logged out user
                        //automatically accept invitation if user is already member of the parent group
                        'accepted_at' => $new_user['pivot']['accepted_at'] ? date("Y-m-d H:i:s") : null
                    ];
                }
                $res = $group->groupUsersAll()->sync($user_sync);
                $data = [
                    'groupAdmin' => auth()->user()->name, 
                    'groupName' => $new_group->name
                ];
                //az újakat értesítem, hogy hozzá lett adva a csoporthoz
                if(isset($res['attached'])) {
                    $attached_users = User::whereIn('id', $res['attached'])
                        ->whereNotNull('email_verified_at')
                        ->get();

                    Notification::send($attached_users, new GroupUserAddedNotification($data));
                    // foreach($res['attached'] as $user) {
                    //     $us = User::find($user);
                    //     if($us->email_verified_at) {
                    //         $us->notify(
                    //             new GroupUserAddedNotification($data)
                    //         );
                    //     }
                    // }                
                }
                if(isset($res['updated'])) {
                    $updated_users = User::whereIn('id', $res['updated'])
                        ->whereNotNull('email_verified_at')
                        ->get();
                    Notification::send($updated_users, new GroupUserAddedNotification($data));            
                }
                if(isset($res['detached'])) {
                    foreach($res['detached'] as $user) {
                        $m = new GroupUserMoves($group->id, $user);
                        $m->detach();
                    }                
                }
                // dd($res);
                $this->dispatchBrowserEvent('hide-modal', [
                    'id' => 'LinkToModal',
                    'message' => __('group.link.success'),
                    'savedMessage' => __('app.saved')
                ]);
            } else {
                $this->dispatchBrowserEvent('error', [
                    'message' => __('group.link.error')
                ]);
            }
            
        }
    }

    public function confirmChildDetach($child_group_id) {
        $group = Group::findorFail($this->groupId);
        if($group->groupAdmins()->wherePivot('user_id', Auth::id())->count() == 0) {
            abort(403);
        }

        $selected_group = $group->childGroups()->where('id', $child_group_id)->first();
        if(!$selected_group->id) abort(403);

        $this->detachId = $selected_group->id;

        $this->dispatchBrowserEvent('show-deletion-confirmation', [
            'title' => __('group.link.parent.detach.question', ['groupName' => $selected_group->name]),
            'text' => __('group.link.parent.detach.message'),
            'emit' => 'detachChildGroup'
        ]);
    }

    public function detachChildGroup() {
        $group = Group::findorFail($this->groupId);
        if($group->groupAdmins()->wherePivot('user_id', Auth::id())->count() == 0) {
            abort(403);
        }

        $selected_group = $group->childGroups()->where('id', $this->detachId)->first();
        if(!$selected_group->id) abort(403);

        $group->childGroups()->where('id', $this->detachId)->update(['parent_group_id' => null]);
        $this->detachId = null;
        $this->dispatchBrowserEvent('hide-modal', [
            'id' => 'ChildGroupsModal',
            'message' => __('group.link.parent.detach.success'),
            'savedMessage' => __('app.saved')
        ]);
    }

    public function confirmParentDetach($parent_group_id) {
        $group = Group::findorFail($this->groupId);
        if($group->groupAdmins()->wherePivot('user_id', Auth::id())->count() == 0) {
            abort(403);
        }
        if($group->parent_group_id != $parent_group_id)
            abort(403);

        $this->detachId = $group->parent_group_id;

        $this->dispatchBrowserEvent('show-deletion-confirmation', [
            'title' => __('group.link.child.detach.question'),
            'text' => __('group.link.child.detach.message'),
            'emit' => 'detachParentGroup'
        ]);
    }

    public function detachParentGroup() {
        $group = Group::findorFail($this->groupId);
        $parent_group = $group->parentGroup;
        $parent_group_name = ($parent_group !== null) ? $parent_group->name : null;
        $data = [
            'groupName' => $parent_group_name,
            'childGroupName' => $group->name,
            'userName' => auth()->user()->name
        ];

        if($group->groupAdmins()->wherePivot('user_id', Auth::id())->count() == 0) {
            abort(403);
        }

        if($group->parent_group_id != $this->detachId)
            abort(403);

        $res = $group->update(['parent_group_id' => null]);
        if($res) {
            $this->detachId = null;

            //send notifications to admins
            $admins = $group->groupAdmins;
            
            Notification::send($admins, new GroupParentGroupDetachedNotification($data));

            $this->dispatchBrowserEvent('hide-modal', [
                'id' => 'ParentGroupModal',
                'message' => __('group.link.child.detach.success'),
                'savedMessage' => __('app.saved')
            ]);
        } else {
            $this->dispatchBrowserEvent('error', [
                'message' => __('group.link.child.detach.error')
            ]);
        }
    }

    /**
     * Toggle user signs
     */
    public function toogleSign($user_id, $icon) {
        // $group = Group::findorFail($this->groupId);
        $this->getGroupInfo();
        if(Auth::id() !== $user_id && $this->isNotHelper()) {
            $this->dispatchBrowserEvent('error', [
                'message' => __('group.signs.error')
            ]);
            return false;
        }
        $userData = GroupUser::where('group_id', $this->groupId)
                    ->where('user_id', $user_id);

        $signs = $userData->first('signs')->toArray();
        // dd($signs);
        $signs = $signs['signs'];
        if(isset($signs[$icon])) {
            $signs[$icon] = !$signs[$icon];
        } else {
            $signs[$icon] = true;
        }
        $userData->update(['signs' => $signs]);
        $this->dispatchBrowserEvent('success', [
            'message' => __('group.signs.success')
        ]);
    }

    private function getGroupInfo() {
        $this->group = Group::findOrFail($this->groupId);
        $this->getRole();
    }

    private function isNotEditor() {
        // return $this->group->editors()->wherePivot('user_id', Auth::id())->count();
        return !in_array($this->role, ['admin', 'roler']);
    }

    private function isNotHelper() {
        return !in_array($this->role, ['admin', 'roler', 'helper']);
    }

    private function getRole() {
        $info = GroupUser::where('user_id', '=', Auth::id())
            ->where('group_id', '=', $this->groupId)
            ->select('group_role')
            ->first()->toArray();
        // dd($info);
        $this->role = $info['group_role'];
    }

    private function maxRoles() {
        $roles = self::$group_roles;
        $available = [];
        // dd($this->role);
        foreach($roles as $role) {
            $available[] = $role;
            if($role == $this->role) break;
        }
        return $available;
    }

    public function render()
    {
        $this->getGroupInfo();
        // $group = Group::findOrFail($this->groupId);
        $parent_group = $this->group->parentGroup;
        $parent_group_name = ($parent_group !== null) ? $parent_group->name : null;
        $editor = !$this->isNotHelper(); // $group->editors()->wherePivot('user_id', Auth::id())->count();

        $users = $this->group->groupUsers()
                    ->where(function($query) {
                        $query->where('users.name', 'LIKE', '%'.$this->searchTerm.'%');
                        $query->orWhere('users.email', 'LIKE', '%'.$this->searchTerm.'%');
                    })
                    ->where(function($query) use ($editor) {
                        if(!$editor) {
                            $query->whereNotNull('group_user.accepted_at');
                        }
                    })
                    ->paginate(10);        
        // dd($users->toArray());
        return view('livewire.groups.list-users', [
            'editor' => $editor,
            'admin' => $this->group->groupAdmins()->wherePivot('user_id', Auth::id())->count(),
            'users' => $users,
            'group_name' => $this->group->name,
            'group_id' => $this->group->id,
            'current_parent_group_id' => $this->group->parent_group_id,
            'parent_group_name' => $parent_group_name,
            'child_groups' => $this->group->childGroups()->get(['id', 'name'])->toArray(),
            'group_roles' => self::$group_roles, //available because highher roles are available for users, check on save
            'group_signs' => $this->group->signs,
            'user_admin_groups' => $this->user_admin_groups(),
            'user_role' => $this->role
        ]);
    }
}
